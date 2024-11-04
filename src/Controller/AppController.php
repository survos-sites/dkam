<?php

namespace App\Controller;

use App\Service\SqliteConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Doctrine\Persistence\Mapping\Driver\PHPDriver;

class AppController extends AbstractController
{

    public function __construct(
        private SqliteConverterService $sqliteConverterService,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {

        $filename = $this->projectDir . '/schema.sql';
        assert(file_exists($filename), "$filename not found");
        $this->sqliteConverterService->parseSql($filename);

        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
        ]);
    }


}
