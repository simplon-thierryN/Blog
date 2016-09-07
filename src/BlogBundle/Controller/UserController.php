<?php
/**
 * Created by PhpStorm.
 * User: nguyenthierry
 * Date: 25/08/2016
 * Time: 11:01
 */

namespace BlogBundle\Controller;

use BlogBundle\Entity\album_picture;
use BlogBundle\Entity\User;
use BlogBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller{

    /**
     * @Route("/registration", name="registration")
     */
    public function registerAction(Request $request){
        $user = new User();
        $formUser = $this->createForm(UserType::class, $user);
        $formUser->handleRequest($request);

        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $avatar = $user->getAvatar();
            $filename = $avatar->getClientOriginalName();
            $avatar->move(
                $this->getParameter('image_directory'),
                $filename
            );
            $user->setAvatar($filename);

            $picture = new album_picture();
            $picture->setUser($user);
            $picture->setCategory('imgPortrait');
            $alt = explode(".", $filename);
            $picture->setUrl($filename);
            $picture->setAlt($alt[0]);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->persist($picture);
            $em->flush();

            return $this->redirectToRoute('login');
        }

        $content= $this->get('templating')->render('BlogBundle:Default:registration.html.twig',array(
            'form'=>$formUser->createView()
        ));
        return new Response($content);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request){

        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('BlogBundle:Default:login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error
        ));
    }
}