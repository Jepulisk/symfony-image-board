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
use App\Entity\Thread;
use App\Entity\Reply;

use App\Form\BoardType;
use App\Form\ReplyType;

class BoardController extends AbstractController
{
    /**
     * @Route("/board/{abbreviation}", name="get_board")
     */
    public function getBoard($abbreviation)
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
            $threads = $board->getThreads();
        }

        return $this->render("board/index.html.twig", [
            "board" => $board,
            "threads" => $threads
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/thread/{thread_id}", name="get_thread")
     */
    public function getThread($abbreviation, $thread_id)
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
            $thread = $this->getDoctrine()
                ->getRepository(Thread::class)
                ->find($thread_id);

            if (!$thread) 
            {
                throw $this->createNotFoundException();
            }
            else
            {
                $replies = $thread->getReplies();
            }
        }

        return $this->render("board/thread.html.twig", [
            "board" => $board,
            "thread" => $thread,
            "replies" => $replies
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/new-thread", name="new_thread")
     */
    public function newThread(Request $request, SluggerInterface $slugger, $abbreviation)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);

        $reply = new Reply();

        $form = $this->createForm(ReplyType::class, $reply);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $thread = new Thread();
            $thread->setBoard($board);
            $thread->setTsCreated(new \DateTime());
    
            $reply->setThread($thread);
            
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

            $user = $this->getUser();

            if ($user)
            {
                $thread->setUser($user);
                $reply->setUser($user);
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($thread);
            $manager->persist($reply);
            $manager->flush();
    
            return $this->redirectToRoute("get_thread", [
                "abbreviation" => $abbreviation,
                "thread_id" => $thread->getId()
            ]);
        }

        return $this->render("board/new_thread.html.twig", [
            "board" => $board,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/thread/{thread_id}/new-reply/{reply_id}", name="new_reply")
     */
    public function newReply(Request $request, SluggerInterface $slugger, $abbreviation, $thread_id, $reply_id = null)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);

        $thread = $this->getDoctrine()
            ->getRepository(Thread::class)
            ->find($thread_id);

        $reply = new Reply();
        $reply->setThread($thread);

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

            $user = $this->getUser();

            if ($user)
            {
                $reply->setUser($user);
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($reply);
            $manager->flush();
    
            return $this->redirectToRoute("get_thread", [
                "abbreviation" => $abbreviation,
                "thread_id" => $thread_id
            ]);
        }

        return $this->render("board/new_reply.html.twig", [
            "board" => $board,
            "thread" => $thread,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/new-board", name="new_board")
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
    
            return $this->redirectToRoute("get_board", [
                "abbreviation" => $board->getAbbreviation()
            ]);
        }

        return $this->render("board/new_board.html.twig", [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/thread/{thread_id}/delete-reply/{reply_id}", name="delete_reply")
     */
    public function deleteReply(Request $request, $abbreviation, $thread_id, $reply_id)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);

        if (!$board) throw $this->createNotFoundException();

        $thread = $this->getDoctrine()
            ->getRepository(Thread::class)
            ->find($thread_id);

        if (!$thread) throw $this->createNotFoundException();

        $reply = $this->getDoctrine()
            ->getRepository(Reply::class)
            ->find($reply_id);

        if (!$reply) throw $this->createNotFoundException();

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($reply);
        $manager->flush();

        if (sizeOf($thread->getReplies()) == 0)
        {
            $manager->remove($thread);
            $manager->flush();
        }

        return $this->redirectToRoute("get_thread", [
            "abbreviation" => $abbreviation,
            "thread_id" => $thread_id
        ]);
    }
}
