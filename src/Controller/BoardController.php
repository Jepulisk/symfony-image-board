<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\Board;
use App\Entity\Topic;
use App\Entity\Reply;

use App\Form\BoardType;
use App\Form\ReplyType;

class BoardController extends AbstractController
{
    /**
     * @Route("/board/{abbreviation}", name="board")
     */
    public function index($abbreviation)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);
    
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
     * @Route("/board/{abbreviation}/topic/{topic_id}", name="topic")
     */
    public function topic($abbreviation, $topic_id)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);
    
        if (!$board) 
        {
            throw $this->createNotFoundException();
        }
        else
        {
            $topic = $this->getDoctrine()
                ->getRepository(Topic::class)
                ->find($topic_id);

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
     * @Route("/board/{abbreviation}/new-topic", name="new_topic")
     */
    public function newTopic(Request $request, SluggerInterface $slugger, $abbreviation)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);

        $reply = new Reply();

        $form = $this->createForm(ReplyType::class, $reply);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $topic = new Topic();
            $topic->setBoard($board);
            $topic->setTsCreated(new \DateTime());
    
            $reply->setTopic($topic);
            
            $attachment = $form->get("attachment")->getData();

            if ($attachment) 
            {
                $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename."-".uniqid().".".$attachment->guessExtension();

                try 
                {
                    $attachment->move(
                        $this->getParameter("reply_attachments"),
                        $newFilename
                    );
                } 
                catch (FileException $e) 
                {
                    unset($e);
                }

                $reply->setAttachment($newFilename);
            }

            $reply->setTsCreated(new \DateTime());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($topic);
            $manager->persist($reply);
            $manager->flush();
    
            return $this->redirectToRoute("topic", [
                "abbreviation" => $abbreviation,
                "topic_id" => $topic->getId()
            ]);
        }

        return $this->render("board/new_topic.html.twig", [
            "board" => $board,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/topic/{topic_id}/new-reply/{reply_id}", name="new_reply")
     */
    public function newReply(Request $request, SluggerInterface $slugger, $abbreviation, $topic_id, $reply_id = null)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);

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
            $attachment = $form->get("attachment")->getData();

            if ($attachment) 
            {
                $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename."-".uniqid().".".$attachment->guessExtension();

                try 
                {
                    $attachment->move(
                        $this->getParameter("reply_attachments"),
                        $newFilename
                    );
                } 
                catch (FileException $e) 
                {
                    unset($e);
                }

                $reply->setAttachment($newFilename);
            }

            $reply->setTsCreated(new \DateTime());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($reply);
            $manager->flush();
    
            return $this->redirectToRoute("topic", [
                "abbreviation" => $abbreviation,
                "topic_id" => $topic->getId()
            ]);
        }

        return $this->render("board/new_reply.html.twig", [
            "board" => $board,
            "topic" => $topic,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/new", name="new_board")
     * @IsGranted("ROLE_ADMIN")
     */
    public function newBoard(Request $request)
    {
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

        return $this->render("board/new_board.html.twig", [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/topic/{topic_id}/delete-reply/{reply_id}", name="delete_reply")
     */
    public function deleteReply(Request $request, $abbreviation, $topic_id, $reply_id)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);

        if (!$board) throw $this->createNotFoundException();

        $topic = $this->getDoctrine()
            ->getRepository(Topic::class)
            ->find($topic_id);

        if (!$topic) throw $this->createNotFoundException();

        $reply = $this->getDoctrine()
            ->getRepository(Reply::class)
            ->find($reply_id);

        if (!$reply) throw $this->createNotFoundException();

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($reply);
        $manager->flush();

        return $this->redirectToRoute("topic", [
            "abbreviation" => $abbreviation,
            "topic_id" => $topic->getId()
        ]);
    }
}
