<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\AlbumRoot;
use App\Repository\AlbumRepository;
use App\Service\SqliteConverterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
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
        /** @var ServiceEntityRepositoryInterface|AlbumRepository $repo */
//        foreach ($repos as $repo) {
        foreach ($repos as $repo) {
            if (!in_array($repo->getClassName(), [Album::class, AlbumRoot::class])) {
//                continue;
            }
            try {
                $entity = $repo->findBy([], limit: 1)[0]??null;
                $data[$repo->getClassName()] = $entity;
            } catch (\Exception $e) {
                dd($e, $repo->getClassName());
            }
        }
        dd($data);

        return $this->render('app/index.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/albums', name: 'app_albums')]
    #[Template("dk/albumRoots.html.twig")]
    public function albumRoots(): array
    {
        return [
            'albumRoots' => $this->entityManager->getRepository(AlbumRoot::class)->findBy([], limit: 30)
        ];
    }


    #[Route('/rebuild', name: 'app_rebuild')]
    public function rebuild(): Response
    {

        $filename = $this->projectDir . '/schema.sql';
        assert(file_exists($filename), "$filename not found");
        $this->sqliteConverterService->parseSql($filename);
        return $this->redirectToRoute('app_homepage');

    }


}
