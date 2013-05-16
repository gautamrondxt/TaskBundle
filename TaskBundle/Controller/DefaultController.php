<?php

namespace Acme\TaskBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Acme\TaskBundle\Entity\Task;
use Acme\TaskBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session; /* This is for the session */

class DefaultController extends Controller {

    public function indexAction($name) {
        return $this->render('TaskBundle:Default:index.html.twig', array('name' => $name));
    }

    public function newAction(Request $request) {
        // create a task and give it some dummy data for this example

        $session = new Session();
        $session->start();
        $session->set('name', 'Symfony Framework Understanding');
        $session_val=$session->get('name');

        $task = new Task();
        $task->setName('Write a blog post');
        $task->setDate(new \DateTime('2013-05-05'));

        $submission = new Submission();
        $submission->setName('Write a blog post');

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->persist($submission);
        $em->flush();

        $form = $this->createFormBuilder($task)
                ->add('task', 'text')
                ->add('dueDate', 'date')
                ->getForm();

        return $this->render('TaskBundle:Default:new.html.twig', array(
                    'form' => $form->createView(),
                    'session'=>    $session_val,
        ));

        if ($request->getMethod() == 'POST') {
            $data = $form->bindRequest($request);

            if ($form->isValid()) {
                // perform some action, such as saving the task to the database
                return $this->redirect($this->generateUrl('form3'));
            }
        }
    }

    public function showAction() {

        echo "HI in this";
        exit;

        //$things = $this->getDoctrine()->getEntityManager()->getRepository('TaskBundle:Task')->getThings(5,0);
        // return $this->render('TaskBundle:Default:index.html.twig');
        /*
          $product = $this->getDoctrine()->getRepository('TaskBundle:Task')->findAll();
          echo "<pre>";
          print_r($product);
          exit; */
    }

}
