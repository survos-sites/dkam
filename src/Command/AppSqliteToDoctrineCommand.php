<?php

namespace App\Command;

use App\Service\SqliteConverterService;
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
//        string $filename = '/home/tac/Pictures/digikam4.db',
        string $filename = 'schema.sql',

        #[Option(description: 'overwrite existing src/Entities classes')]
        bool $force = true,
    ): void {
        $this->sqliteConverterService->parseSql($filename);
        $io->success('app:sqlite-to-doctrine success.');
    }


}
