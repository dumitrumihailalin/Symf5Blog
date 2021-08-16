<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use function PHPUnit\Framework\throwException;

class ArticlesController extends AbstractController
{
    private $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    #[Route('/', name: 'articles')]
    public function index(): Response
    {
        $articles = $this->articleRepository->allArticles();

        return $this->render('articles/index.html.twig', [
            'controller_name' => 'ArticlesController',
            'articles' => $articles
        ]);
    }

    #[Route('/article/{slug}', name: 'article')]
    public function article($slug): Response
    {
        if(!$slug) {
            throwException('not found');
        }
        $slug = strtolower($slug);
        $article = $this->articleRepository->slug($slug);
        return $this->render('articles/article.html.twig', [
            'controller_name' => 'ArticlesController',
            'article' => $article
        ]);
    }
    /**
     * This route has a greedy pattern and is defined first. small update
     *
     * @Route("/filter", name="filter")
     */
    public function filter(Request $request)
    {
        if( $request->query->get('title') != null){
            $title = trim($request->query->get('title'));
            $title = str_replace('+', ' ', $title);

            $articles = $this->articleRepository->findOneByTitle($title);

            return $this->render('articles/index.html.twig', [
                'controller_name' => 'ArticlesController',
                'articles' => $articles,
                'title' => $title
            ]);
        }
    }


    #[Route('/add', name: 'add')]
    public function addArticle(Request $request, ArticleRepository $articleRepository): Response
    {
        $article  = new Article();
        $form = $this->createFormBuilder($article)
            ->add('title', TextType::class, ['label'=>'Title'])
            ->add('content', TextareaType::class, ['label'=>'Content'])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            $data->setRelation($this->getUser());

            $data->setDateTime(new \DateTime());
            $data->setSlug(str_replace(' ', '-', $data->getTitle()));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($article);
            $entityManager->flush();
            return $this->redirectToRoute('articles');
        }
        return $this->render('articles/add_article.html.twig', ['form'=>$form->createView()]);
    }
}
