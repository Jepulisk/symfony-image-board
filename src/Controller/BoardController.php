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
     * @Route("/board/{abbreviation}", name="board")
     */
    public function board(Request $request, SluggerInterface $slugger, $abbreviation)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);
    
        if (!$board) throw $this->createNotFoundException();

        $threads = $board->getThreads();

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
        
            return $this->redirectToRoute("thread", [
                "abbreviation" => $abbreviation,
                "thread_id" => $thread->getId()
            ]);
        }
    
        return $this->render("board/index.html.twig", [
            "board" => $board,
            "threads" => $threads,
            "new_thread" => $form->createView()
        ]);
    }

    /**
     * @Route("/board/{abbreviation}/thread/{thread_id}", name="thread")
     */
    public function thread(Request $request, SluggerInterface $slugger, $abbreviation, $thread_id)
    {
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->findOneBy(["abbreviation" => $abbreviation]);
    
        if (!$board) throw $this->createNotFoundException();

        $thread = $this->getDoctrine()
            ->getRepository(Thread::class)
            ->find($thread_id);

        if (!$thread) throw $this->createNotFoundException();

        $replies = $thread->getReplies();

        $reply = new Reply();
        $reply->setThread($thread);

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

            $content = $reply->getContent();

            preg_match_all("/(^|\s)#(\d+)(\s|\n|\r|$)/m", $content, $reply_to_ids);

            foreach ($reply_to_ids[0] as $reply_to_id)
            {
                $reply_to_id = trim($reply_to_id);
                $reply_to_id = str_replace("#", "", $reply_to_id);

                $reply_to = $this->getDoctrine()
                    ->getRepository(Reply::class)
                    ->find($reply_to_id);
                
                if ($reply_to) $reply->addReplyTo($reply_to);
            }

            $user = $this->getUser();

            if ($user)
            {
                $reply->setUser($user);
            }

            $reply->setTsCreated(new \DateTime());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($reply);

            if (sizeOf($thread->getReplies()) == 15)
            {
                $thread->setLocked(true);
                $manager->persist($thread);
            }

            $manager->flush();
    
            return $this->redirectToRoute("thread", [
                "abbreviation" => $abbreviation,
                "thread_id" => $thread_id
            ]);
        }

        return $this->render("board/thread.html.twig", [
            "board" => $board,
            "thread" => $thread,
            "replies" => $replies,
            "new_reply" => $form->createView()
        ]);
    }

    /**
     * @Route("/new-board", name="new_board")
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(Request $request)
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
            "new_board" => $form->createView()
        ]);
    }

    /**
     * @Route("/reply/{reply_id}/delete", name="delete_reply")
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, $reply_id)
    {
        $reply = $this->getDoctrine()
            ->getRepository(Reply::class)
            ->find($reply_id);

        if (!$reply) throw $this->createNotFoundException();

        $manager = $this->getDoctrine()->getManager();

        $thread = $reply->getThread();

        if (sizeOf($thread->getReplies()) == 1)
        {
            $manager->remove($thread);
        }

        $manager->remove($reply);
        $manager->flush();

        return $this->redirect($request->server->get("HTTP_REFERER"));
    }
}
