<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Board;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index()
    {
        $boards = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findAll();
        
        return $this->render("home/index.html.twig", [
            "boards" => $boards
        ]);
    }
}
