<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Video;
use App\Form\UserType;
use App\Repository\VideoRepository;
use App\Utils\CategoryTreeFrontPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontController extends AbstractController
{
    public function __construct(ManagerRegistry $doctrine,    private TokenStorageInterface $tokenStorage,
                                RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->session = $requestStack->getSession();
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

    #[Route('/video-details/{video}', name: 'video_details')]
    public function videoDetails(VideoRepository $repository, $video): Response
    {
        return $this->render('front/video_details.html.twig',[
            'video'=>$repository->videoDetails($video)
        ]);
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


    #[Route('/new-comment/{video}', name: 'new_comment', methods: 'POST')]
    public function newComment(Video $video, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if(!empty(trim($request->request->get('comment')))){
            $comment = new Comment();
            $comment->setContent($request->request->get('comment'));
            $comment->setUser($this->getUser());
            $comment->setVideo($video);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();
        }


        return $this->redirectToRoute('video_details', ['video'=>$video->getId()]);
    }


    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $userF = $form->getData();
            // not so safe solution
            // couldn't solve with getting data from $request->request->get()[] method
            // used getting data via $form->getData()
            $password = $passwordHasher->hashPassword($user, $userF->getPassword());
            $user->setPassword($password);
            $user->setRoles(["ROLE_USER"]);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->loginUserAutomatically($user);

            return $this->redirectToRoute('admin_main_page');
        }

        return $this->render('front/register.html.twig',[
            'form' => $form->createView()
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $helper): Response //executes in background by Symfony
    {
        return $this->render('front/login.html.twig',[
            'error' => $helper->getLastAuthenticationError()
        ]);
    }

    private function loginUserAutomatically($user){
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->tokenStorage->setToken($token);
        $this->session->set('_security_main', serialize($token));
    }


    #[Route('/logout', name: 'logout')]
    public function logout(): void //executes in background by Symfony
    {
        throw new \Exception('This should never be reached!');
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
