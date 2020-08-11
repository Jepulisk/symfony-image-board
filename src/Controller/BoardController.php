<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Board;
use App\Entity\Topic;
use App\Entity\Reply;

use App\Form\ReplyType;

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
     * @Route("/board/{name}/topic/{id}", name="topic")
     */
    public function topic($name, $id)
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
            $topic = $this->getDoctrine()
                ->getRepository(Topic::class)
                ->find($id);

            if (!$topic) 
            {
                throw $this->createNotFoundException();
            }
            else
            {
                $replies = $topic->getReplies();
            }
        }

        return $this->render("board/topic.html.twig", [
            "board" => $board,
            "topic" => $topic,
            "replies" => $replies
        ]);
    }

    /**
     * @Route("/board/{name}/new-topic", name="new_topic")
     */
    public function newTopic(Request $request, $name)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["name" => $name]);

        $reply = new Reply();

        $form = $this->createForm(ReplyType::class, $reply);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $topic = new Topic();
            $topic->setBoard($board);
            $topic->setTsCreated(new \DateTime());
    
            $reply->setTopic($topic);
            $reply->setTsCreated(new \DateTime());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($topic);
            $manager->persist($reply);
            $manager->flush();
    
            return $this->redirectToRoute("topic", [
                "name" => $name,
                "id" => $topic->getId()
            ]);
        }

        return $this->render("board/new_topic.html.twig", [
            "board" => $board,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/board/{name}/topic/{topic_id}/new-reply/{reply_id}", name="new_reply")
     */
    public function newReply(Request $request, $name, $topic_id, $reply_id = null)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["name" => $name]);

        $topic = $this->getDoctrine()
            ->getRepository(Topic::class)
            ->find($topic_id);

        $reply = new Reply();
        $reply->setTopic($topic);

        if ($reply_id)
        {
            $reply_to = $this->getDoctrine()
                ->getRepository(Reply::class)
                ->find($reply_id);

            $reply->addReplyTo($reply_to);
        }

        $form = $this->createForm(ReplyType::class, $reply);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $reply->setTsCreated(new \DateTime());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($reply);
            $manager->flush();
    
            return $this->redirectToRoute("topic", [
                "name" => $name,
                "id" => $topic->getId()
            ]);
        }

        return $this->render("board/new_reply.html.twig", [
            "board" => $board,
            "topic" => $topic,
            "form" => $form->createView()
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
