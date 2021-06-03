<?php

namespace App\Controller;

use App\Classe\Search;
use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="home")
     */
    public function articles(Request $request): Response
    {
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articles = $this->entityManager->getRepository(Article::class)->findWithSearch($search); // filtre
        } else {
            $articles = $this->entityManager->getRepository(Article::class)->findAll(); // Sinon affiche les articles
        }

        return $this->render('home/index.html.twig', [
            'articles' => $articles,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{slug}", name="show")
     *
     * @param mixed $slug
     */
    public function show(Article $article, Request $request, $slug): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime())->setArticles($article);
            $user = $this->getUser();
            $comment->setAuthor($user);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('show', ['slug' => $article->getSlug()]);
        }

        $article = $this->getDoctrine()->getRepository(Article::class)->findOneBySlug($slug);
        $comment = $this->getDoctrine()->getRepository(Comment::class)->findAll();

        if (!$article) {
            return $this->redirectToRoute('home');
        }

        return $this->render('home/show.html.twig', [
            'article' => $article,
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }
}
