<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Board;

use App\Form\BoardType;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function home(Request $request)
    {
        $boards = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findAll();

        $board = new Board();

        $form = $this->createForm(BoardType::class, $board);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid())
        {
            $board->setTsCreated(new \DateTime());
        
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($board);
            $manager->flush();
        
            return $this->redirectToRoute("board", [
                "abbreviation" => $board->getAbbreviation()
            ]);
        }
        
        return $this->render("home/index.html.twig", [
            "boards" => $boards,
            "new_board" => $form->createView()
        ]);
    }
}
