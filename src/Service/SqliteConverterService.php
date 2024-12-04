<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Album;
use App\Entity\Image;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappingAttribute;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\Column;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;
use function Symfony\Component\String\u;
use Doctrine\DBAL\Types\Types;

class SqliteConverterService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/src/Entity')] private string $entityDir,
        #[Autowire('%kernel.project_dir%/src/Repository')] private string $repoDir,
        private ?InflectorInterface $englishInflector=null,
    )
    {
        $this->englishInflector  = new EnglishInflector();
//        dd(
//            $this->englishInflector->singularize('Images'),
//            $this->englishInflector->singularize('Searches')
//        );
    }


    public function parseSql($filename) {

        // if the property matches the key, create a ManyToOne relation
        $relationsMap =  [
            'albumRoot' => Album::class,
            'imageid' => Image::class
        ];

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
                $options=$this->englishInflector->singularize($tableName);
                $entityClassShortname = end($options);

                $entityClassShortname = match ($entityClassShortname) {
                    'Searche' => 'Search',
                    default => $entityClassShortname
                };

                // the Entity
                $namespace = new PhpNamespace('App\Entity');
                $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
                $namespace->addUse($repoNs = "App\\Repository\\{$entityClassShortname}Repository");
                $namespace->addUse(Types::class);

                $class = new ClassType($entityClassShortname);

                $class->addAttribute('Doctrine\ORM\Mapping\Entity', [$repoNs]);
                $class->addAttribute('Doctrine\ORM\Mapping\Table', [
                    'name' => $tableName,
//                    'constraints' => [
//                        Literal::new('ORM\UniqueConstraint', ['name' => 'ean', 'columns' => ['ean']]),
//                    ],
                ]);

//                $method = $class->addMethod('count')
//                    ->addAttribute('Foo\Cached', ['mode' => true]);
//
//                $method->addParameter('items')
//                    ->addAttribute('Bar');

                $props = explode(",\n", (string) $m[2]);
                $p = [];
                // hack -- the dump always puts the PK first, if it exists
                $hasId = false;
                foreach ($props as $prop) {
                    $prop = trim($prop);
                    if (preg_match('/^(UNIQUE)/', $prop)) {
                        // handle unique
//                        dump($prop);
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

                        $property = $class->addProperty($name);
                        if ($relatedEntityClass = $relationsMap[$name] ?? false) {
                            if ($relatedEntityClass === Image::class) {
                                $property->addAttribute(OneToOne::class, [
                                    'targetEntity' => $relatedEntityClass,
                                ]);
                                $property
//                                    ->setType($relatedEntityClass)
                                    ->addAttribute(Column::class, [
                                        'name' => $name
                                    ]);
//                                dd($property, $p, $relatedEntityClass, $statement, $entityClassShortname);
                            } else {
                                $property
//                                    ->setType($relatedEntityClass)
                                    ->addAttribute(Column::class, [
                                        'name' => u($name)->camel()->toString(),
                                        'type' => $relatedEntityClass
                                    ]);

                            }


//                            #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'albumRoot')]
//                            private Collection $albums;
//                            dd($prop, $property);
                        } else {
                            $property
                                ->setType($phpMap[$type])
                                ->addAttribute(Column::class, [
                                    'name' => u($name)->camel()->toString(),
                                    'type' => $doctrineMap[$type] ?? null
                                ]);
                        }
                        if ($isId) {
                            $property->addAttribute(Id::class);
                            $hasId = true;
                        }
                    }
                }

                // skip tables with no PK, they are one-to-many tables, like ImageProperties
                if (!$hasId) {
//                    dd($entityClassShortname, $props);
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

                file_put_contents($fn = $this->entityDir . "/$entityClassShortname.php", "<?php\n\n" . $namespace);
                // the Repository
                $this->createRepositoryClass($entityClassShortname);


            } else {
                // dump($statement);
            }

        }
        return $tables;

    }

    /**
     * @param string $entityClassShortname
     * @return array
     */
    public function createRepositoryClass(string $entityClassShortname): array
    {
        $namespace = new PhpNamespace('App\Repository');
        $namespace->addUse(ServiceEntityRepository::class);
        $namespace->addUse(ManagerRegistry::class);
        $namespace->addUse($entityClassname = 'App\\Entity\\' . $entityClassShortname);
        // write the repo class
        $class = new ClassType($className = $entityClassShortname . 'Repository');
        $class
            ->setFinal()
            ->setExtends(ServiceEntityRepository::class)
//                    ->addImplement(Countable::class)
            ->addComment("Class description.\nSecond line\n");
        $namespace->add($class);
        $method = $class->addMethod('__construct');
        $method->addPromotedParameter('registry')->setType(ManagerRegistry::class); // ->setPrivate();
        $method->setBody(sprintf('parent::__construct($registry, %s::class);', $entityClassShortname));


//                public function __construct(ManagerRegistry $registry)
//                {
//                    parent::__construct($registry, Test::class);
//                }

        file_put_contents($fn = $this->repoDir . "/{$entityClassShortname}Repository.php", "<?php\n\n" . $namespace);
        return array($namespace, $class, $method, $fn);
    }

}
