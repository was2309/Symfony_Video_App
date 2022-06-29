<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Video;
use App\Utils\CategoryTreeFrontPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class FrontController extends AbstractController
{
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
    }

    #[Route('/', name: 'main_page')]
    public function index(): Response
    {
        return $this->render('front/index.html.twig');
    }

    #[Route('/video-list/category/{categoryname},{id}/{page}', name: 'video_list', defaults: ['page'=>'1'])]
    public function videoList($id, $page, CategoryTreeFrontPage $categories, Request $request): Response
    {
        $ids = $categories->getChildIds($id);
        array_push($ids, $id);

        $videos = $this->doctrine->getRepository(Video::class)
            ->findByChildIds($ids,$page, $request->get('sortby'));
        $categories->getCategoryListAndParent($id);
        return $this->render('front/video_list.html.twig',[
            'subcategories' => $categories,
            'videos' => $videos
        ]);
    }

    #[Route('/video-details', name: 'video_details')]
    public function videoDetails(): Response
    {
        return $this->render('front/video_details.html.twig');
    }

    #[Route('/search-results/{page}', name: 'search_results', defaults: ['page'=>'1'], methods: 'GET')]
    public function searchResults($page, Request $request): Response
    {
        $videos = null;
        $query = null;
        if($query = $request->get('query')){
            $videos = $this->doctrine
                ->getRepository(Video::class)
                ->findByTitle($query, $page, $request->get('sortby'));

            if(!$videos->getItems()){
                $videos = null;
            }
        }

        return $this->render('front/search_results.html.twig',[
            'videos' => $videos,
            'query' => $query
        ]);
    }


    #[Route('/pricing', name: 'pricing')]
    public function pricing(): Response
    {
        return $this->render('front/pricing.html.twig');
    }


    #[Route('/register', name: 'register')]
    public function register(): Response
    {
        return $this->render('front/register.html.twig');
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('front/login.html.twig');
    }

    #[Route('/payment', name: 'payment')]
    public function payment(): Response
    {
        return $this->render('front/payment.html.twig');
    }

    public function mainCategories(){
        $categories = $this->doctrine->getRepository(Category::class)->findBy(['parent' => null], ['name'=>'ASC']);
        return $this->render('front/_main_categories.html.twig',[
            'categories'=>$categories
        ]);
    }

}
