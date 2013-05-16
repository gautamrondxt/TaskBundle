<?php

namespace Acme\StoreBundle\Controller;

use Acme\StoreBundle\Model\Product;
use Acme\StoreBundle\Model\ProductQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

    public function indexAction($name) {
        return $this->render('StoreBundle:Default:index.html.twig', array('name' => $name));
    }

    public function createAction() {
        $product = new Product();
        $product->setName('A Foo Bar');
        $product->setPrice(19.99);
        $product->setDescription('Lorem ipsum dolor');

        $product->save();

        return new Response('Created product id ' . $product->getId());
    }

    public function showAction($id) {
        $product = ProductQuery::create()
                ->findPk($id);

        if (!$product) {
            throw $this->createNotFoundException(
                    'No product found for id ' . $id
            );
        }

        // ... do something, like pass the $product object into a template
    }

    public function updateAction($id) {
        $product = ProductQuery::create()
                ->findPk($id);

        if (!$product) {
            throw $this->createNotFoundException(
                    'No product found for id ' . $id
            );
        }

        $product->setName('New product name!');
        $product->save();

        // return $this->redirect($this->generateUrl('homepage'));
    }

    public function deleteAction($id) {
        $product = ProductQuery::create()
                ->findPk($id);

        if (!$product) {
            throw $this->createNotFoundException(
                    'No product found for id ' . $id
            );
        }
        $product->delete();
    }

}
