<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappingAttribute;
use Doctrine\ORM\Mapping\Table;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\Column;
use function Symfony\Component\String\u;
use Doctrine\DBAL\Types\Types;

class SqliteConverterService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/src/Entity')] private string $entityDir,
        #[Autowire('%kernel.project_dir%/src/Repository')] private string $repoDir
    )
    {
    }


    public function parseSql($filename) {
        $sql = file_get_contents($filename);
        //  \((.*)\)
        $statements = explode(";\n", $sql);
        $tables = [];

//        #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
//    private ?\DateTimeInterface $birthday = null;

        $doctrineMap = [
            'DATE' => Types::DATE_IMMUTABLE,
            'DATETIME' => Types::DATETIME_IMMUTABLE,
            ];
        $phpMap = [
            'INTEGER' => 'int',
            'DATE' => '?\DateTimeInterface',
            'DATETIME' => '?\DateTimeInterface',
            'REAL' => 'float',
            'TEXT' => 'string',
            '' => ''
        ];

        foreach ($statements as $statement) {
            if (preg_match('/CREATE TABLE (\S*)\n\s*\((.*)\)/ms', $statement, $m)) {
                $tableName = $m[1];

                // the Entity
                $namespace = new PhpNamespace('App\Entity');
                $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
                $namespace->addUse($repoNs = "App\\Repository\\{$tableName}Repository");
                $namespace->addUse(Types::class);

                $class = new ClassType($tableName);

                $class->addAttribute('Doctrine\ORM\Mapping\Entity', [$repoNs]);
                $class->addAttribute('Doctrine\ORM\Mapping\Table', [
                    'name' => $tableName,
//                    'constraints' => [
//                        Literal::new('ORM\UniqueConstraint', ['name' => 'ean', 'columns' => ['ean']]),
//                    ],
                ]);

                $method = $class->addMethod('count')
                    ->addAttribute('Foo\Cached', ['mode' => true]);

                $method->addParameter('items')
                    ->addAttribute('Bar');

                $props = explode(",\n", (string) $m[2]);
                $p = [];
                // hack -- the dump always puts the PK first, if it exists
                $hasId = false;
                foreach ($props as $prop) {
                    $prop = trim($prop);
                    if (preg_match('/^(UNIQUE)/', $prop)) {
                        // handle unique
                        dump($prop);
                    } else {
                        // properties
                        [$name, $type] = explode(' ', $prop);
                        $name = trim($name);
                        $isId = preg_match('/PRIMARY KEY/', $prop);
                        $p[] = [
                            'name' => $name,
                            'type' => $type,
                            'id' => $isId
                        ];
                        $property = $class->addProperty($name)
                            ->setType($phpMap[$type])
                            ->addAttribute(Column::class, [
                                'name' => u($name)->camel()->toString(),
                                'type' => $doctrineMap[$type]??null
                            ]);
                        if ($isId) {
                            $property->addAttribute(Id::class);
                            $hasId = true;
                        }
                    }
                }

                // skip tables with no PK, they are one-to-many tables, like ImageProperties
                if (!$hasId) {
                    continue;
                }
                $tableData = [
                    'name' => $tableName,
                    'sql' => $statement,
                    'props' => $p
                ];
                $tables[$tableName] = $tableData;


                $class
                    ->setFinal()
//                    ->setExtends(ParentClass::class)
//                    ->addImplement(Countable::class)
                    ->addComment("Class description.\nSecond line\n")
                    ->addComment('@property-read Nette\Forms\Form $form');

                $namespace->add($class);
                file_put_contents($fn = $this->entityDir . "/$tableName.php", "<?php\n\n" . $namespace);
//                dd($fn, (string) $namespace);
                // the Repository
                $this->createRepositoryClass($tableName);


            } else {
                // dump($statement);
            }

        }
        return $tables;

    }

    /**
     * @param string $tableName
     * @return array
     */
    public function createRepositoryClass(string $tableName): array
    {
        $namespace = new PhpNamespace('App\Repository');
        $namespace->addUse(ServiceEntityRepository::class);
        $namespace->addUse(ManagerRegistry::class);
        $namespace->addUse($entityClassname = 'App\\Entity\\' . $tableName);
        // write the repo class
        $class = new ClassType($className = $tableName . 'Repository');
        $class
            ->setFinal()
            ->setExtends(ServiceEntityRepository::class)
//                    ->addImplement(Countable::class)
            ->addComment("Class description.\nSecond line\n");
        $namespace->add($class);
        $method = $class->addMethod('__construct');
        $method->addPromotedParameter('registry')->setType(ManagerRegistry::class); // ->setPrivate();
        $method->setBody(sprintf('parent::__construct($registry, %s::class);', $tableName));


//                public function __construct(ManagerRegistry $registry)
//                {
//                    parent::__construct($registry, Test::class);
//                }

        file_put_contents($fn = $this->repoDir . "/{$tableName}Repository.php", "<?php\n\n" . $namespace);
        dump($fn, (string)$namespace);
        return array($namespace, $class, $method, $fn);
    }

}
