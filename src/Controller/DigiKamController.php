<?php

namespace App\Controller;

use App\Entity\AlbumRoots;
use App\Entity\Albums;
use App\Entity\Image;

use App\Entity\Tags;
use App\Repository\AlbumsRepository;
use App\Repository\ImageRepository;
use App\Service\ImageService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DigiKamController extends AbstractController
{
    /**
     * DigiKamController constructor.
     */
    public function __construct(
                                private readonly RouterInterface $router,
                                private readonly UrlGeneratorInterface $urlGenerator,
                                private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    private function getConnection(): Connection
    {
        /** @var Connection $connection */
        return $this->managerRegistry->getConnection('thumb');
    }

    #[Route(path: '/thumb/{id}', name: 'dk_thumb')]
    public function thumb(Connection $connection, $id)
    {
        $path = null;
        $connection = $this->getConnection();
        $r = $connection->executeQuery("select * from Thumbnails where id = $id")->fetch();
        dump($r);
        die();
        if ($r) {
            $response = new Response();
            // $response->headers->set('Content-type', mime_content_type($path));
            // $response->headers->set('Content-length', filesize($path));
            $response->sendHeaders();
            $response->setContent($r['data']);
            return $response;
        } else {
            throw new NotFoundHttpException("No path for $path");
        }
        return $this->render("dk/albumRoots.html.twig", [
            'albumRoots' =>
            $this->managerRegistry->getRepository(AlbumRoots::class)->findAll()
        ]);
    }


    #[Route(path: '/roots', name: 'digi_kam')]
    public function index(Connection $connection)
    {
        return $this->render("dk/albumRoots.html.twig", ['albumRoots' =>
            $this->managerRegistry->getRepository(AlbumRoots::class)->findAll()
        ]);
    }

    private function setBreadcrumbs(): Breadcrumbs {
        $breadcrumbs = $this->breadcrumbs;
        // Simple example
        $breadcrumbs->addItem("Home", $this->urlGenerator->generate("home"));

        return $breadcrumbs;

    }

    #[Route(path: '/dashboard', name: 'admin_dashboard')]
    public function dashboardAction()
    {
        $em = $this->managerRegistry->getManager();
        return $this->render('easy_admin/dashboard.html.twig', [
            'tags' => $this->managerRegistry->getRepository(Tags::class)->findAll()
        ]);
    }


//    #[Route(path: '/home', name: 'home')]
//    #[Route(path: '/home', name: 'app_homepage')]
//    #[Route(path: '/search', name: 'app_search')]
//    public function home(AlbumsRepository $albumsRepository, ImageRepository $imageRepository)
//    {
//        $breadcrumbs = $this->setBreadcrumbs();
//        $breadcrumbs->addItem("Dashboard", '/');
//        // Example with parameter injected into translation "user.profile"
//        // $breadcrumbs->addItem($txt, $url, ["%user%" => $user->getName()]);
//        return $this->render("dashboard.html.twig", [
//            'tags' => $this->managerRegistry->getRepository(Tags::class)->findAll(),
//            'albumCount' => $albumsRepository->count([]),
//            'imageCount' => $imageRepository->count([]),
//            'albumRoots' =>
//            $this->managerRegistry->getRepository(AlbumRoots::class)->findAll()
//        ]);
//    }
//
    #[Route(path: '/root/{albumRootId}', name: 'dk_album_root')]
    public function albumRoot(Request $request, $albumRootId)
    {
        $albumRoot = $this->managerRegistry->getRepository(AlbumRoots::class)->find($albumRootId);
        $this->setBreadcrumbs()
            ->addRouteItem($albumRoot->__toString(), 'dk_album_root', $albumRoot->getRP() );
        return $this->render("dk/albums.html.twig", [
            'albumRoot' => $albumRoot,
            'albums' => $albumRoot->getAlbums(),
        ]);
    }

    #[Route(path: '/album/{albumId}', name: 'dk_album')]
    public function showAlbum(Request $request, $albumId, ImageRepository $imageRepository)
    {
        $album = $this->managerRegistry->getRepository(Albums::class)->find($albumId);
        $albumRoot = $album->getAlbumroot();
        $breadcrumbs = $this->setBreadcrumbs()
            ->addRouteItem($albumRoot, 'dk_album_root', $albumRoot->getRp())
            ->addRouteItem($album, 'dk_album', $album->getRp());
        $imagesQuery = $imageRepository->createQueryBuilder('i')
            ->join('i.meta', 'meta')
            ->andWhere('i.album = :album')
            ->setParameter('album', $album);
        return $this->render("dk/album.html.twig", [
            'album' => $album,
            'images' => $imagesQuery->getQuery()->getResult()
            ]);
    }

    #[Route(path: '/image/{id}', name: 'dk_image')]
    public function showImage(Image $image, ImageService $service)
    {
        $history  = $service->getHistory($image);
        $album = $image->getAlbum();
        $albumRoot = $album->getAlbumroot();
        $this->setBreadcrumbs()
            ->addRouteItem($albumRoot, 'dk_album_root', $albumRoot->getRp())
            ->addRouteItem($album, 'dk_album', $album->getRp())
            ->addItem($image);
        return $this->render("dk/image.html.twig", [
            'image' => $image,
            'history' => $history
        ]);
    }

    #[Route(path: '/jpg/{id}.jpg', name: 'dk_jpg')]
    public function showJpeg(Image $image)
    {
        $root = $this->getParameter('image_root');
        $path = $image->getFilePath();
        if (file_exists($path)) {
            $response = new Response();
            $response->headers->set('Content-type', mime_content_type($path));
            $response->headers->set('Content-length', filesize($path));
            $response->sendHeaders();
            $response->setContent(readfile($path));
            return $response;
        } else {
            throw new NotFoundHttpException("No path for $path");
        }
    }

}
