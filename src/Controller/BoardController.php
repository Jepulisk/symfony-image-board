<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Board;
use App\Form\NewBoardType;

class BoardController extends AbstractController
{
    /**
     * @Route("/board/{name}", name="board")
     */
    public function index($name)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["name" => $name]);
    
        if (!$board) 
        {
            throw $this->createNotFoundException();
        }
        else
        {
            $topics = $board->getTopics();
        }

        return $this->render("board/index.html.twig", [
            "board" => $board,
            "topics" => $topics
        ]);
    }

    /**
     * @Route("/board/new", name="board_new")
     */
    public function new(Request $request)
    {
        $board = new Board();

        $form = $this->createForm(NewBoardType::class, $board);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $board->setTsCreated(new \DateTime());
    
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($board);
            $manager->flush();
    
            return $this->redirectToRoute("board");
        }

        return $this->render("board/new.html.twig", [
            "board_new" => $form->createView()
        ]);
    }
}
