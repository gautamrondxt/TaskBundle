<?php

namespace Acme\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('APIBundle:Default:index.html.twig', array('name' => $name));
    }
}
