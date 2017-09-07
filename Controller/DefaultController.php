<?php

namespace Kit\GeneratorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('KitGeneratorBundle:Default:index.html.twig');
    }
}
