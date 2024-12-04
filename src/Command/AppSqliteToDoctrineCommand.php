<?php

namespace App\Command;

use App\Service\SqliteConverterService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Attribute\AsCommand;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('app:sqlite-to-doctrine', 'convert sqlite database to doctrine entities (reverse engineer)')]
final class AppSqliteToDoctrineCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(
        private SqliteConverterService $sqliteConverterService
    ) {
        parent::__construct();
    }

    public function __invoke(
        IO $io,
        #[Argument(description: 'filename of the sqlite schema')]
        string $filename = 'schema.sql',// someday  '/home/tac/Pictures/digikam4.db',
        #[Option('ods')] string $odsfilename = 'DBSCHEMA.ODS',
        #[Option(description: 'overwrite existing src/Entities classes')] bool $force = true,
    ): void {


        if (!file_exists($filename)) {
            $io->error("sqlite3 digikam4.db .schema > schema.sql");
//            return self::FAILURE;
        }

        if (!file_exists($odsfilename)) {
            $io->error("download from https://invent.kde.org/graphics/digikam/-/tree/master/project/documents?ref_type=heads");
//            return self::FAILURE;
        }

        $this->importSpreadsheet($odsfilename);
        // DBSCHEMA.ODS is located at https://invent.kde.org/graphics/digikam/-/tree/master/project/documents?ref_type=heads

        $this->sqliteConverterService->parseSql($filename);
        $io->success($this->getName() . '  success.');

//        return self::SUCCESS;

    }

    private function importSpreadsheet(string $odsFile): void
    {

        /** @var IReader $reader */
        $reader = IOFactory::createReader('Ods');
        $reader->setReadDataOnly(TRUE);

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = $reader->load($odsFile);
        $tables = [];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {

            $this->io()->writeln("Reading $odsFile: $sheetName");
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $tables += $this->processSheet($sheet);
        }
        file_put_contents('tables.json', json_encode($tables, JSON_PRETTY_PRINT));

    }

    private function processSheet(Worksheet $worksheet): array
    {
        $inTable = null;
        $property = [];
        $tables = [];
        $propertyName = null;
        // Get the highest row and column numbers referenced in the worksheet
        $tableName = '';
        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $headers = [
            'name',
            'type',
            'description',
            'read_from',
            'write_to',
            'changed_by'
        ];

        for ($row = 1; $row <= $highestRow; ++$row) {
            $firstValue = trim((string) $worksheet->getCell([1, $row])->getValue());

            $inRowHeader = ($firstValue == 'NAME');
            if ($inRowHeader) {
                $inTable = true;
                continue;
            }

            if (preg_match('/Table « (.*?) »/', $firstValue, $m)) {
                $tableName = $m[1];
                continue;
            }

            if ($firstValue === '') {
                $inTable = false;
            }

            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $value = trim((string) $worksheet->getCell([$col, $row])->getValue());
                if ($inTable) {
                    if ($col == 1) {
                        $propertyName = $value;
                    }
                    if (($headers[$col-1]??null) && ($value<>'')) {
                        $property[$headers[$col-1]] = $value;
                    }
                }
                if ($value === '') {
                    continue;
                }
//                printf("%s %d.%d %s\n", $tableName, $row, $col, $value);
            }
            if ($inTable) {
                if ($property) {
                    $tables[$tableName][$propertyName] = $property;
                }
            }
        }
        return $tables;
    }


}
