<?php

namespace App\Controller;

use App\Entity\AlbumRoots;
use App\Entity\Albums;
use App\Repository\AlbumRootsRepository;
use App\Service\SqliteConverterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Doctrine\Persistence\Mapping\Driver\PHPDriver;

class AppController extends AbstractController
{

    public function __construct(
        private SqliteConverterService $sqliteConverterService,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    #[Route('/', name: 'app_homepage')]
    public function index(
        #[AutowireIterator('doctrine.repository_service')] $repos
    ): Response
    {
        $data = [];
        /** @var ServiceEntityRepositoryInterface $repo */
        foreach ($repos as $repo) {
//            $repo = $this->entityManager->getRepository($class = );
            $entity = $repo->findBy([], limit: 1)[0]??null;
            $data[$repo->getClassName()] = $entity;
        }

        return $this->render('app/index.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/rebuild', name: 'app_rebuild')]
    public function rebuild(): Response
    {

        $filename = $this->projectDir . '/schema.sql';
        assert(file_exists($filename), "$filename not found");
        $this->sqliteConverterService->parseSql($filename);

        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
        ]);
    }


}
