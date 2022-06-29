<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Utils\CategoryTreeAdminList;
use App\Utils\CategoryTreeAdminOptionList;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
    }


    #[Route('/', name: 'admin_main_page')]
    public function index(): Response
    {
        return $this->render('admin/my_profile.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET', 'POST'])]
    public function categories(CategoryTreeAdminList $categories, Request $request): Response
    {
        $categories->getCategoryList($categories->buildTree());

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $is_invalid = null;
        $form->handleRequest($request);

        if($this->saveCategory($category, $form, $request)){
            return $this->redirectToRoute('categories');
        }
        elseif($request->isMethod('post')){
            $is_invalid = ' is-invalid';
        }

        return $this->render('admin/categories.html.twig', [
            'controller_name' => 'AdminController',
            'categories' => $categories->categoryList,
            'form' => $form->createView(),
            'is_invalid' => $is_invalid
        ]);
    }

    #[Route('/videos', name: 'videos')]
    public function videos(): Response
    {
        return $this->render('admin/videos.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }


    #[Route('/upload-video', name: 'upload_video')]
    public function uploadVideo(): Response
    {
        return $this->render('admin/upload_video.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/users', name: 'users')]
    public function users(): Response
    {
        return $this->render('admin/users.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/edit-category/{id}', name: 'edit_category', methods: ['GET', 'POST'])]
    public function editCategory(Category $category, Request $request): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $is_invalid = null;

        if($this->saveCategory($category, $form, $request))
        {
            return $this->redirectToRoute('categories');
        }
        elseif($request->isMethod('post'))
        {
            $is_invalid = ' is-invalid';
        }
        return $this->render('admin/edit_category.html.twig', [
            'controller_name' => 'AdminController',
            'category'=>$category,
            'form' => $form->createView(),
            'is_invalid' => $is_invalid
        ]);
    }

    #[Route('/delete-category/{id}', name: 'delete_category')]
    public function deleteCategory(Category $category): Response
    {
        $this->entityManager->remove($category);
        $this->entityManager->flush();
        return $this->redirectToRoute('categories');
    }

    public function getAllCategories(CategoryTreeAdminOptionList $categories, $editedCategory = null){
        $categories->getCategoryList($categories->buildTree());
        return $this->render('admin/_all_categories.html.twig',[
           'categories'=>$categories,
            'editedCategory'=>$editedCategory
        ]);
    }

    private function saveCategory($category, $form, $request)
    {
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $category->setName($request->request->get('category')['name']);

            $repository = $this->doctrine->getRepository(Category::class);
            $parent = $repository->find($request->request->get('category')['parent']);
            $category->setParent($parent);


            $this->entityManager->persist($category);
            $this->entityManager->flush();

            return true;
        }
        return false;
    }

}
