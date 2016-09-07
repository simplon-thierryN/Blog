<?php
/**
 * Created by PhpStorm.
 * User: nguyenthierry
 * Date: 28/08/2016
 * Time: 14:21
 */

namespace BlogBundle\Controller;

use BlogBundle\BlogBundle;
use BlogBundle\Entity\Article;
use BlogBundle\Form\ArticleType;
use BlogBundle\Entity\album_picture;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class ArticleController extends Controller
{
    /**
     * @return Response
     * @Route("/", name="home")
     */
    public function indexArticleAction()
    {
        $user = $this->getUser();
        $userId = $user->getId();
        $repoArticle = $this->getDoctrine()->getRepository('BlogBundle:Article');
        $listArticle=$repoArticle->findAll();

        $content= $this->get('templating')->render('BlogBundle:Default:blog.html.twig',array(
            'userId'=>$userId,
            'listArticle'=>$listArticle
        ));

        return new Response($content);
    }

    /**
     * @param Request $request
     * @param $userId
     * @return Response
     * @Route("/addArticle/{userId}", name="addArticle")
     */
    public function addArticleAction(Request $request,$userId)
    {
        $repo = $this->getDoctrine()->getRepository('BlogBundle:User');
        $user = $repo->find($userId);

        $article = new Article();
        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if ($formArticle->isSubmitted() && $formArticle->isValid()){
            $img = $article->getImage();
            $filename = $img->getClientOriginalName();
            $img->move(
                $this->getParameter('image_directory'),
                $filename
            );
            $article->setImage($filename);

            $picture = new album_picture();
            $picture->setUser($user);
            $article->setUser($user);

            $alt = explode(".", $filename);
            $picture->setCategory("imgArticle");
            $picture->setUrl($filename);
            $picture->setAlt($alt[0]);

            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->persist($picture);
            $em->flush();

            return $this->redirectToRoute('home');
        }

        $content = $this->get('templating')->render('BlogBundle:Default:addArticle.html.twig',array(
            'formArticle'=>$formArticle->createView()
        ));

        return new Response($content);
    }

    /**
     * @param $idArticle
     * @return Response
     * @Route("/articleView/{idArticle}", name="articleView")
     */
    public function articleViewAction($idArticle)
    {
        $article = $this->getDoctrine()
            ->getRepository('BlogBundle:Article')
            ->find($idArticle);

        $userId = $this->getUser()->getId();

        $albumImg = $this->getDoctrine()
            ->getRepository('BlogBundle:album_picture')
            ->listPicture($userId,'imgArticle');

        $content = $this->get('templating')->render('BlogBundle:Default:article.html.twig',array(
            'article'=>$article,
            'albumImg'=>$albumImg,
            'id'=>$article->getId($idArticle),
            'title'=>$article->getTitle($idArticle),
            'content'=>$article->getContent($idArticle),
            'date'=>$article->getDate($idArticle)

        ));

        return new Response($content);
    }

    /**
     * @param $idArticle
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("/updateArticle/{idArticle}", name="updateArticle")
     */
    public function updateArticleAction($idArticle, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $article =$em->getRepository('BlogBundle:Article')->find($idArticle);

        $updateForm = $this->createForm(ArticleType::class, $article);

        $updateForm->handleRequest($request);

        if($updateForm->isValid()){
            $em->flush();
            return $this->redirectToRoute('articleView',array(
                'idArticle'=>$idArticle
            ));
        }

        $content= $this->get('templating')->render('BlogBundle:Default:updateArticle.html.twig',array(
            'updateForm'=>$updateForm->createView()
        ));

        return new Response($content);
    }

    /**
     * @param $userId
     * @return JsonResponse
     * @Route("/listImg/{userId}", name="listImg")
     */
    public function albumImgAction($userId)
    {
        $albumImg = $this->getDoctrine()
            ->getRepository('BlogBundle:album_picture')
            ->listPicture($userId, 'imgArticle');

        return new JsonResponse($albumImg);


    }


}

