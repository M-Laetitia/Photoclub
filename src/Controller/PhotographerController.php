<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PhotographerController extends AbstractController
{
    #[Route('/photographer', name: 'app_photographer')]
    public function index(): Response
    {
        return $this->render('photographer/index.html.twig', [
            'controller_name' => 'PhotographerController',
        ]);
    }
}
