<?php

namespace App\Controller\Admin\Post;

use App\Entity\Post;
use App\Form\PostFormType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    #[Route('/admin/post/list', name: 'admin.post.index')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy([], ['updatedAt' => 'DESC']);

        return $this->render('pages/admin/post/index.html.twig', [
            "posts" => $posts
        ]);
    }

    #[Route('/admin/post/create', name: 'admin.post.create', methods:['GET', 'POST'])]
    public function create(Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository
        ) : Response
    {

        if (count($categoryRepository->findAll()) == 0) 
        {
            $this->addFlash("warning", "Acune catégorie. Vous devrez créer un afin du créer un article.");
            return $this->redirectToRoute('admin.category.index');
        }

        $tags = $tagRepository->findAll();

        $post = new Post();

        $form = $this->createForm(PostFormType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $post->setUser($this->getUser());

            $em->persist($post);
            $em->flush();

            $this->addFlash("success", "L'article a été bien crée et sauvgardé");

            return $this->redirectToRoute("admin.post.index");
        }

        return $this->render('pages/admin/post/create.html.twig', [
            "form" => $form->createView(),
            "tags" => $tags
        ]);
    }

    #[Route('/admin/post/{id}/publish', name: 'admin.post.publish', methods:['PUT'])]
    public function publish(Post $post, EntityManagerInterface $em, Request $request, CategoryRepository $categoryRepository) : Response
    {

        if (count($categoryRepository->findAll()) == 0) 
        {
            $this->addFlash("warning", "Acune catégorie. Vous devrez créer un afin du créer un article.");
            return $this->redirectToRoute('admin.category.index');
        }

        if ( $this->isCsrfTokenValid('post_publish_' . $post->getId(), $request->request->get('csrf_token'))) 
        {
            if ($post->isIsPublished()) 
            {
                $post->setIsPublished(false);
             
                $post->setPublishedAt(null);
             
    
                $this->addFlash("success", "Cet article vient d'êtere retiré de la liste de publication");
            }
            else
            {
                $post->setIsPublished(true);
             
                $post->setPublishedAt(new DateTimeImmutable('now'));
             
                $this->addFlash("success", "Cet article article a été publié.");
            }

            $em->persist($post);
            $em->flush();
        }

        return $this->redirectToRoute("admin.post.index");
    }

    #[Route('/admin/post/{id}/show', name: 'admin.post.show', methods:['GET'])]
    public function show(Post $post, CategoryRepository $categoryRepository) : Response
    {
        if (count($categoryRepository->findAll()) == 0) 
        {
            $this->addFlash("warning", "Acune catégorie. Vous devrez créer un afin du créer un article.");
            return $this->redirectToRoute('admin.category.index');
        }

        return $this->render("pages/admin/post/show.html.twig", compact('post'));
    }

    #[Route('/admin/post/{id}/edit', name: 'admin.post.edit', methods:['GET', 'PUT'])]
    public function edit(Post $post, Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository) : Response
    {
        if (count($categoryRepository->findAll()) == 0) 
        {
            $this->addFlash("warning", "Acune catégorie. Vous devrez créer un afin du créer un article.");
            return $this->redirectToRoute('admin.category.index');
        }

        $form = $this->createForm(PostFormType::class, $post, [
            "method" => "PUT"
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $post->setUser($this->getUser());

            $em->persist($post);
            $em->flush();

            $this->addFlash("success", "L'article a été modifié et sauvgardé");

            return $this->redirectToRoute('admin.post.index');
        }

        return $this->render("pages/admin/post/edit.html.twig", [
            "form"  => $form->createView(),
            "post"  => $post
        ]);
    }

    #[Route('/admin/post/{id}/delete', name: 'admin.post.delete', methods: ['DELETE'])]
    public function delete(Post $post, Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository) :Response
    {

        if (count($categoryRepository->findAll()) == 0) 
        {
            $this->addFlash("warning", "Acune catégorie. Vous devrez créer un afin du créer un article.");
            return $this->redirectToRoute('admin.category.index');
        }

        if ( $this->isCsrfTokenValid('post_delete_' . $post->getId(), $request->request->get('csrf_token'))) 
        {
            $em->remove($post);
            $em->flush();
            
            $this->addFlash("success", "L'article a été supprimmée.");
        }

        return $this->redirectToRoute('admin.post.index');
    }

    #[Route('/admin/post/multiple-posts-delete', name: 'admin.post.multiple_delete', methods: ['DELETE'])]
    public function multipleDelete(Request $request, PostRepository $postRepository, EntityManagerInterface $em) : Response
    {
        $csrfTokenValue = $request->request->get('csrf_token');
        
        if ( ! $this->isCsrfTokenValid("multiple_delete_post_token_key", $csrfTokenValue) )
        {
            return $this->json(
                ['status' => false, "message" => "Un probème est survenu, veillez réessayer."],
                Response::HTTP_BAD_REQUEST
            );    
        }
        
        $ids = $request->request->get('ids');
        
        $ids = explode(",", $ids);
        
        foreach ($ids as $id) 
        {
            $post = $postRepository->findOneBy(["id" => $id]);
            
            $em->remove($post);
            $em->flush();
        }
        
        return  $this->json(['status' => true, "message" => "La suppression multiple a été effectuée avec succès."]);
        // return new JasonResponse();
    }
}
