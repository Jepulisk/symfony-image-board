<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TopicController extends AbstractController
{
    /**
     * @Route("/new-topic", name="topic_new")
     */
    public function new()
    {
        return $this->render("topic/new.html.twig", [

        ]);
    }
}
