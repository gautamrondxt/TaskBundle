<?php

namespace Acme\PlansBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AcmePlansBundle:Default:index.html.twig');
    }
	
	public function plansAction()
    {
        return $this->render('AcmePlansBundle:Default:plans.html.twig');
    }
	
	public function dashboardAction()
    {
        return $this->render('AcmePlansBundle:Default:dashboard.html.twig');
    }
	
	public function lnloginAction()
    {
       //$test= new \test();
	   //echo $test->sayHello();
	    
		$linkedin = new \linkedin();
		$linkedin->init();
		 echo '<pre>'; 
		print_r($linkedin);
		echo '</pre>'; 
		return new RedirectResponse( $linkedin->linkedin_redirect_url );
		//return $this->render('AcmePlansBundle:Default:plans.html.twig');
    }
	public function accessTokenAction()
    {
       //$test= new \test();
	   //echo $test->sayHello();
	    
		$linkedin = new \linkedin();
		$session = $this->get('session');
			echo '<pre> session:'; 
		print_r($session);
		echo '</pre>';  
		$linkedin->init();
		$linkedin->get_access_token($session );
		
		$profile=$linkedin->get_logged_in_users_profile();
	
	 exit;
		/* echo '<pre>'; 
		print_r($linkedin);
		echo '</pre>';   */
		//return new RedirectResponse( $linkedin->linkedin_redirect_url );
		return $this->render('AcmePlansBundle:Default:plans.html.twig');
    }
	
}
