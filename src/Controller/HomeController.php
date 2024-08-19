<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Form\PhotographerStatusType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    //public function index(): Response
    //{
    //    return $this->render('home/index.html.twig', [
    //        'controller_name' => 'HomeController',
    //    ]);
    //}

    public function index(UserRepository $userRepository, Security $security, EntityManagerInterface $entityManager, Request $request): Response
    {

        $user = $security->getUser();
        $photographers = $userRepository->findPhotographerUsers();


        return $this->render('home/home.html.twig', [
            'photographers' => $photographers,
       
        ]);
    }

    // ^ test style
    #[Route('/home/style', name: 'test_style')]
    public function index_style(): Response
    {

        return $this->render('home/testStyle.html.twig', [
            
        ]);
    }

    // ^ contact
    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {

        return $this->render('home/contact.html.twig', [
        ]);
    }

    // ^ Privacy policy
    #[Route('/privacy-policy', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {

        return $this->render('home/privacyPolicy.html.twig', [
        ]);
    }

    // ^ Terms of Use
    #[Route('/terms-of-use', name: 'app_terms_of_use')]
    public function termsOfUse(): Response
    {

        return $this->render('home/termsOfUse.html.twig', [
        ]);
    }

    // ^ Sitemap
    #[Route('/sitemap', name: 'app_sitemap')]
    public function sitemap(): Response
    {

        // Render the contents of 'home/sitemap.xml.twig' into a string
        $content = $this->renderView('home/sitemap.xml.twig');

        // Create a new Response object with the rendered content
        $response = new Response($content);
        // Set the Content-Type header of the response to 'application/xml'
        $response->headers->set('Content-Type', 'application/xml');

        // Return the response with the rendered XML content
        return $response;
    }
}
