<?php

//session_start();
require_once("oauth.php");
require_once("config.php");
/********* END SET GOTO URL *********/
$str_attach_redr=0;
//define('CALLBACK_URL', 'http://'.DOMAIN.'/social/linkedin?_api=accesstoken'.$str_attach_redr);
define('CALLBACK_URL', 'http://54.241.180.4/app_dev.php/accesstoken/?service=linkedin&_api=accesstoken'.$str_attach_redr);
define('BASE_API_URL', 'https://api.linkedin.com');

define('REQUEST_PATH', '/uas/oauth/requestToken');
define('AUTH_PATH', '/uas/oauth/authorize');
define('ACC_PATH', '/uas/oauth/accessToken');


use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;

global $pv_linkedin_api_key;
global $pv_linkedin_api_secret_key;

define('CUSTOMER_KEY', '6r50xgftn9mz');
define('CUSTOMER_SECRET', 'KOc2mVrpcYSdGEvE');

$profileFields = array(
	'id', 
	'first-name', 
	'last-name', 
	'picture-url',
	'public-profile-url',
	'headline', 
	'current-status', 
	'location', 
	'distance', 
	'summary',
	'industry', 
	'specialties',
	'positions',
	'educations'
);

class linkedin {
	protected $signatureMethod;
	protected $consumer;

	protected $_id;
	protected $_firstname;
	protected $_lastname;
	protected $_pictureURL;
	protected $_publicURL;
	protected $_headline;
	protected $_currentStatus;
	protected $_locationName;
	protected $_locationCountryCode;
	protected $_distance;
	protected $_summary;
	protected $_industry;

	protected $_specialties = array();
	protected $_positions = array();
	protected $_eductions = array();
	protected $_connections = array();

	protected $_public = true;
	
	public $obj_user_info = "";
	public $linkedin_token_key;
	public $linkedin_secret_key;
	public $linkedin_redirect_url='';
	
	function __construct() { }

	function get_curl_response($toHeader, $url, $post = true) {

	        $ch = curl_init();
	
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader));
	        curl_setopt($ch, CURLOPT_URL, $url);

		if($post) { 
		   curl_setopt($ch, CURLOPT_POSTFIELDS, '');
		   curl_setopt($ch, CURLOPT_POST, 1); 
		}

		$output = curl_exec($ch);		
  		curl_close($ch);
  		/*$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  		print $status."==="; 
  		printvar($output);
  		die;*/
		return $output;
  	}
	
	function fn_Test_Linkedin_Conn($str_ln_api_key="",$str_ln_api_secret_key="") {

		$this->_public = false;
		$this->signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
		
		if($str_ln_api_key && $str_ln_api_secret_key)
		{
			$this->consumer = new OAuthConsumer($str_ln_api_key, $str_ln_api_secret_key, NULL);
		}
		else
		{
			$this->consumer = new OAuthConsumer(CUSTOMER_KEY, CUSTOMER_SECRET, NULL);
		}		

		$reqObj = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "POST", BASE_API_URL.REQUEST_PATH);
		$reqObj->set_parameter("oauth_callback", CALLBACK_URL); # part of OAuth 1.0a - callback now in requestToken
		$reqObj->sign_request($this->signatureMethod, $this->consumer, NULL);
		$toHeader = $reqObj->to_header();

		//$output = $this->get_curl_response($toHeader, BASE_API_URL.REQUEST_PATH);
		
		/*********** LINKEDIN CURL TEST ***********/
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader));
		curl_setopt($ch, CURLOPT_URL, BASE_API_URL.REQUEST_PATH);
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, '');
		curl_setopt($ch, CURLOPT_POST, 1); 		

		$output = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);	
		//print "<pre>";print_r($output); print "--STATUS----".$status."------";exit;			
		curl_close($ch);			
		return $status;
		
		/*********** END FOR LINKEDIN CURL TEST ***********/			
	}

	function init($session='') {
		$this->_public = false;
		$this->signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer(CUSTOMER_KEY, CUSTOMER_SECRET, NULL);
	
		if(!isset($_GET['_api'])) {
			$this->get_request_token();
 	  	}
		else {
			$this->get_access_token();
 		}
   	}
    
    function init_user() {

		$this->_public = false;

		$this->signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer(CUSTOMER_KEY, CUSTOMER_SECRET, NULL);	
   	}
    
    function post_linkedin_update() {

	        $token = new OAuthConsumer($_REQUEST['oauth_token'], $_SESSION['oauth_token_secret'], 1);

	        $accObj = OAuthRequest::from_consumer_and_token($this->consumer, $token, "POST", BASE_API_URL.ACC_PATH);
	        $accObj->set_parameter("oauth_verifier", $_REQUEST['oauth_verifier']); # need the verifier too!
	        $accObj->sign_request($this->signatureMethod, $this->consumer, $token);
		$toHeader = $accObj->to_header();

		$output = $this->get_curl_response($toHeader, BASE_API_URL.ACC_PATH);
		parse_str($output, $oauth);

	        $_SESSION['oauth_token'] = $oauth['oauth_token'];
	        $_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];
	}   

	function get_request_token() {

	        $reqObj = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "POST", BASE_API_URL.REQUEST_PATH);
	        $reqObj->set_parameter("oauth_callback", CALLBACK_URL); # part of OAuth 1.0a - callback now in requestToken
	        $reqObj->sign_request($this->signatureMethod, $this->consumer, NULL);
			$toHeader = $reqObj->to_header();
	
		$output = $this->get_curl_response($toHeader, BASE_API_URL.REQUEST_PATH);
		parse_str($output, $oauth);
			
			$session = new Session();
			$session->start();
		

	        //$_SESSION['oauth_token'] = $oauth['oauth_token'];
	        //$_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];
			
			
			// store an attribute for reuse during a later user request
			$session->set('oauth_token', $oauth['oauth_token']);
			$session->set('oauth_token_secret', $oauth['oauth_token_secret']);
			$session->save();
		//header('Location: ' . BASE_API_URL . AUTH_PATH . '?oauth_token=' . $oauth['oauth_token']);
		//return BASE_API_URL . AUTH_PATH . '?oauth_token=' . $oauth['oauth_token'];
		/* echo '<pre>';
		print_r($reqObj);
		echo '</pre>'; */
		$this->linkedin_redirect_url=BASE_API_URL . AUTH_PATH . '?oauth_token=' . $oauth['oauth_token'];
	}

	function get_access_token() {

			//$this->consumer = new OAuthConsumer(CUSTOMER_KEY, CUSTOMER_SECRET, NULL);
		 	echo'<pre>session';
			print_r($session);
			echo'this:'; exit;
			/*print_r($this->consumer);
			echo'CUSTOMER_KEY:'.CUSTOMER_KEY;
			echo'<br/> CUSTOMER_SECRET:'.CUSTOMER_SECRET;
			 */
			
	       // $token = new OAuthConsumer($_REQUEST['oauth_token'], $_SESSION['oauth_token_secret'], 1);
	        $token = new OAuthConsumer($_REQUEST['oauth_token'], $session->get('oauth_token_secret'), 1);

	        $accObj = OAuthRequest::from_consumer_and_token($this->consumer, $token, "POST", BASE_API_URL.ACC_PATH);
	        $accObj->set_parameter("oauth_verifier", $_REQUEST['oauth_verifier']); # need the verifier too!
	        $accObj->sign_request($this->signatureMethod, $this->consumer, $token);
			$toHeader = $accObj->to_header();

			$output = $this->get_curl_response($toHeader, BASE_API_URL.ACC_PATH);
			parse_str($output, $oauth);

	       // $_SESSION['oauth_token'] = $oauth['oauth_token'];
	       // $_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];
			
			// store an attribute for reuse during a later user request
			$session->set('oauth_token', $oauth['oauth_token']);
			$session->set('oauth_token_secret', $oauth['oauth_token_secret']);
			
			
	}

	function get_profile($requestURL) {

		//Global $profileFields;
		
		$profileFields = array(
								'id', 
								'first-name', 
								'last-name', 
								'picture-url',
								'public-profile-url',
								'headline', 
								'current-status', 
								'location', 
								'distance', 
								'summary',
								'industry', 
								'specialties',
								'positions',
								'educations'
							);

		$endpoint = $requestURL.":(".join(',', $profileFields).")";
        
		$token = new OAuthConsumer($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], 1);
        
		$profileObj = OAuthRequest::from_consumer_and_token($this->consumer, $token, "GET", $endpoint, array());
	    $profileObj->sign_request($this->signatureMethod, $this->consumer, $token);
		$toHeader = $profileObj->to_header();

		$this->parse_xml($this->get_curl_response($toHeader, $endpoint, false));
	}

	function get_logged_in_users_profile() {
		$this->get_profile(BASE_API_URL . '/v1/people/~');
	}

	function get_public_profile_by_public_url($publicURL) {

		if($this->_public)
			$this->parse_public_profile($this->get_curl_response(null, $publicURL, false));
		else
			$this->get_profile(BASE_API_URL . '/v1/people/url=' . urlencode($publicURL));
	}

	function get_public_profile_by_member_token($memberToken) {
		$this->get_profile(BASE_API_URL . '/v1/people/id=' . $memberToken);
	}

	function parse_xml($data) {

		$profileXML = simplexml_load_string($data);
				
		if(isset($profileXML->{'id'}))
			$this->_id = $profileXML->{'id'};

		if(isset($profileXML->{'first-name'}))
			$this->_firstname = $profileXML->{'first-name'};

		if(isset($profileXML->{'last-name'}))
			$this->_lastname = $profileXML->{'last-name'};

		if(isset($profileXML->{'picture-url'}))
			$this->_pictureURL = $profileXML->{'picture-url'};

		if(isset($profileXML->{'public-profile-url'}))
			$this->_publicURL = $profileXML->{'public-profile-url'};

		if(isset($profileXML->headline))
			$this->_headline = $profileXML->headline;

		if(isset($profileXML->{'current-status'}))
			$this->_currentStatus = $profileXML->{'current-status'};
		
		if(isset($profileXML->location->name))
			$this->_locationName = $profileXML->location->name;

		if(isset($profileXML->location->country->code))
			$this->_locationCountryCode = $profileXML->location->country->code;

		if(isset($profileXML->distance))
			$this->_distance = $profileXML->distance;

		if(isset($profileXML->{'summary'}))
			$this->_summary = $profileXML->{'summary'};

		if(isset($profileXML->industry))
			$this->_industry = $profileXML->industry;

	}

	function parse_public_profile($data) {

		preg_match('/<span class=\"given-name\">(.*?)<\/span>/is', $data, $_firstname);
		if(isset($_firstname[1]))
			$this->_firstname = trim($_firstname[1]);

		preg_match('/<span class=\"family-name\">(.*?)<\/span>/is', $data, $_lastname);
		if(isset($_lastname[1]))
			$this->_lastname = trim($_lastname[1]);

		preg_match('/<div class=\"image\"><img src=\"(.*?)\"(.)*\/><\/div>/', $data, $_pictureURL);
		if(isset($_pictureURL[1]))
			$this->_pictureURL = trim($_pictureURL[1]);

		preg_match('/<p class=\"headline title\">(.*?)<\/p>/is', $data, $_headline);
		if(isset($_headline[1]))
			$this->_headline = trim($_headline[1]);

		preg_match('/<p class=\"locality\">(.*?)<\/p>/is', $data, $_locationCountryCode);
		if(isset($_locationCountryCode[1]))
			$this->_locationCountryCode = trim($_locationCountryCode[1]);

		preg_match('/<p class=\"summary\">(.*?)<\/p>/is', $data, $_summary);
		if(isset($_summary[1]))
			$this->_summary = trim($_summary[1]);

		preg_match('/<dt>Industry<\/dt>(.*?)<dd>(.*?)<\/dd>/is', $data, $_industry);
		if(isset($_industry[2]))
			$this->_industry = trim($_industry[2]);

	}

	function get_member_token() { return $this->_id; }

	function get_firstname() { return $this->_firstname; }

	function get_lastname() { return $this->_lastname; }

	function get_picture_url() { return $this->_pictureURL; }

	function get_public_profile_url() { return $this->_publicURL; }

	function get_headline() { return $this->_headline; }

	function get_current_status() { return $this->_currentStatus; }

	function get_location_name() { return $this->_locationName; }

	function get_location_country_code() { return $this->_locationCountryCode; }

	function get_distance() { return $this->_distance; }

	function get_summary() { return $this->_summary; }

	function get_industry() { return $this->_industry; }
    
    
    /******* PROFILE ACTIVITY FUNCTIONS *******/
    
    function fn_post_update_ln_profile($update_text)
    {
        global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key;          
       
        $host = "http://api.linkedin.com";
        
        /*<user authorized token received from OAuth>*/        
        $token = $user_data->linkedin_token_key;
        
        /*<user authorized secret received from OAuth>*/
        $secret = $user_data->linkedin_secret_key;
        
        /* share url - v1/people/~/current-status */
        $endpoint = "http://api.linkedin.com/v1/people/~/current-status";         
        $method = 'PUT';       
                   
        /* to set the status, $args is the XML with the message update.*/        
             
        $args = '<?xml version="1.0" encoding="UTF-8"?><current-status>'.$update_text.'</current-status>';
                              
        $test_consumer  = new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
        //$endpoint       = sprintf("%s/%s",$host,$uri); // need a + symbol here.
         
        $arg_seq    = array();
        $req_token  = new OAuthConsumer($token,$secret,1);
        
        $profile_req = OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint,$arg_seq);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token);
        
        /* PROCESS VIA CURL */        
       
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array ( $profile_req->to_header($host),'Content-Type: text/xml' ));
        curl_setopt( $ch, CURLOPT_URL, $endpoint );
         
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $args );
         
        $output = curl_exec($ch);
         
        curl_close($ch);
        //flush();
        return true;
    }
	
	/*
	
	http://api.linkedin.com/v1/groups/{group-id}/posts:(creation-timestamp,title,summary,creator:(first-name,last-name,picture-url,headline),likes,attachment:(image-url,content-domain,content-url,title,summary),relation-to-viewer)?category=discussion&order=recency&modified-since=1302727083000&count=5
	
	*/
	
	function fn_get_current_share()
	{
		/* http://api.linkedin.com/v1/people/~:(current-share) */
		
		global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key; 		
		
		$host = "http://api.linkedin.com";
        
        /*<user authorized token received from OAuth>*/        
        $token = $user_data->linkedin_token_key;
        
        /*<user authorized secret received from OAuth>*/
        $secret = $user_data->linkedin_secret_key;
		
		if($this->linkedin_token_key && $this->linkedin_secret_key) {
			$token = $this->linkedin_token_key;
			$secret = $this->linkedin_secret_key;
		}
        
        /* share url -  */				
		
		$endpoint = "http://api.linkedin.com/v1/people/~:(current-share)";
		$method = 'GET'; 
        $args = '';
                              
        $test_consumer  = new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
        //$endpoint       = sprintf("%s/%s",$host,$uri); // need a + symbol here.
         
        $arg_seq    = array();
        $req_token  = new OAuthConsumer($token,$secret,1);
        
        $profile_req = OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint,$arg_seq);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token);
		
		$toHeader = $profile_req->to_header($host);		
		
	  	$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader,'Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		$output = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  		curl_close($ch);

		//print_r($output); print $status."------";exit;		
		return $output; 
	}
	
	
	function fn_validate_linkedin_profile()
	{
		/* http://api.linkedin.com/v1/people/~:(current-share) */
		
		global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key; 		
		
		$host = "http://api.linkedin.com";
        
        /*<user authorized token received from OAuth>*/        
        $token = $user_data->linkedin_token_key;
        
        /*<user authorized secret received from OAuth>*/
        $secret = $user_data->linkedin_secret_key;
		
		if($this->linkedin_token_key && $this->linkedin_secret_key) {
			$token = $this->linkedin_token_key;
			$secret = $this->linkedin_secret_key;
		}
        
        /* share url -  */				
		
		$endpoint = "http://api.linkedin.com/v1/people/~:(current-share)";
		$method = 'GET'; 
        $args = '';
                              
        $test_consumer  = new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
        //$endpoint       = sprintf("%s/%s",$host,$uri); // need a + symbol here.
         
        $arg_seq    = array();
        $req_token  = new OAuthConsumer($token,$secret,1);
        
        $profile_req = OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint,$arg_seq);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token);
		
		$toHeader = $profile_req->to_header($host);		
		
	  	$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader,'Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		$output = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  		curl_close($ch);

		//print_r($output); print $status."------";exit;		
		return $status; 
	}
	
	function fn_get_group_posts($int_group_id)
    {
		global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key; 		
		
		$host = "http://api.linkedin.com";
        
        /*<user authorized token received from OAuth>*/        
        $token = $user_data->linkedin_token_key;
        
        /*<user authorized secret received from OAuth>*/
        $secret = $user_data->linkedin_secret_key;
        
        /* share url -  */
		
		/*		
		http://api.linkedin.com/v1/people/~/group-memberships:(group:(id,name,counts-by-category))?membership-state=member				
		$endpoint = "http://api.linkedin.com/v1/people/~/group-memberships:(group:(id,name,counts-by-category,website-url,site-group-url,small-logo-url))?count=200&start=0";         
        */
				
		$endpoint = "http://api.linkedin.com/v1/groups/".$int_group_id."/posts:(creation-timestamp,title,summary,creator:(first-name,last-name,picture-url,headline),likes,attachment:(image-url,content-domain,content-url,title,summary),relation-to-viewer)?category=discussion&order=recency&modified-since=1302727083000&count=5";			
		$endpoint = "http://api.linkedin.com/v1/groups/".$int_group_id."/posts:(creation-timestamp,title,summary,creator:(first-name,last-name,picture-url,headline),likes,attachment:(image-url,content-domain,content-url,title,summary),relation-to-viewer,site-group-post-url)?category=discussion&order=recency&count=25";			
		$method = 'GET'; 
        $args = '';
                              
        $test_consumer  = new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
        //$endpoint       = sprintf("%s/%s",$host,$uri); // need a + symbol here.
         
        $arg_seq    = array();
        $req_token  = new OAuthConsumer($token,$secret,1);
        
        $profile_req = OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint,$arg_seq);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token);
		
		$toHeader = $profile_req->to_header($host);		
		
	  	$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader,'Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		$output = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  		curl_close($ch);

		//print_r($output); print $status."------";exit;		
		return $output;       
	}
	
	function fn_post_to_member_groups($group_id,$post_title="New post",$post_summary="",$content_title="",$content_description="",$content_url="",$content_image="")
    {		
        global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key;   

        $host = "http://api.linkedin.com";
		
		if($this->obj_user_info){
			$token = $this->obj_user_info->token_key;
        	$secret = $this->obj_user_info->secret_key;
		}else{
        	$token = $user_data->linkedin_token_key;
        	$secret = $user_data->linkedin_secret_key;
		}  
		        
        $endpoint="http://api.linkedin.com/v1/groups/".$group_id."/posts";                
        $method='POST';      
                   
        $test_consumer=new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method=new OAuthSignatureMethod_HMAC_SHA1();
         
        $arg_seq=array();
        $req_token=new OAuthConsumer($token,$secret,1);
        $profile_req=OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token,$arg_seq);
        
        /* PROCESS VIA CURL */
		
		$arr_json=Array();
		
		$arr_json['title']=trim($post_title);
		$arr_json['summary']=trim($post_summary?$post_summary:$content_description);
		if($content_title) $arr_json['content']['title']=$content_title;
		if($content_description) $arr_json['content']['description']=$content_description;
		if($content_url) $arr_json['content']['submitted-url']=$content_url;
		if($content_image){
			$arr_json['content']['submitted-image-url']=$content_image;
			if(!$arr_json['content']['submitted-url'])  $arr_json['content']['submitted-url']=$content_image;
		}

		$args=json_encode($arr_json);
         
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array($profile_req->to_header($host),'Content-Type: application/json'));
        curl_setopt($ch,CURLOPT_URL,$endpoint);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,$method);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$args);
        $output=curl_exec($ch);
		$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		print_r($output);
				
		/****** GET GROUP POST URL *****/		
		$arr_response["post_status"] = $status;		
		if($status == 201 || $status == 202)
		{
			$str_post_url = $this->fn_Get_Group_Post_Url($group_id,$arr_json['title'],$arr_json['summary']);
			$arr_response["post_url"] = $str_post_url;
		}
		else
		{
			$arr_response["post_output"] = $output;
		}
		return $arr_response;
		/****** END GET GROUP POST URL *****/
		
		//return $status;
	}
	
	function fn_Get_Linkedin_Post_Url()
	{
		$arrXmlResult = $this->fn_get_current_share();			
		$arrXmlResult = simplexml_load_string($arrXmlResult);
		$str_post_url = "";	
		
		if(!empty($arrXmlResult))
		{			
			foreach($arrXmlResult as $strkey => $arrXml)
			{			
				$str_post_url = (string)$arrXml->content->{"eyebrow-url"};		
			}		
		}		
		
		return $str_post_url; 
	}
	
	function fn_Get_Group_Post_Url($int_group_id,$post_title,$post_summary)
	{
		$arrXmlResult = $this->fn_get_group_posts($int_group_id);			
		$arrXmlResult = simplexml_load_string($arrXmlResult);
		$str_post_url = "";	
		
		if(!empty($arrXmlResult))
		{
			$int_ln_cnt = 0;
			$arr_ln_group = array();
			foreach($arrXmlResult as $strkey=>$arrXml){		
				$str_creation_timestamp = (string)$arrXml->{"creation-timestamp"};
				$str_post_title = (string)$arrXml->{"title"};
				$str_post_summary = (string)$arrXml->{"summary"};
				$str_group_post_url = (string)$arrXml->{"site-group-post-url"};
				
				$str_creator_fname = (string)$arrXml->creator->{"first-name"};
				$str_creator_lname = (string)$arrXml->creator->{"last-name"};
				
				if(trim($str_post_summary) == trim($str_post_summary)){
					$str_post_url = $str_group_post_url;
					break;	
				}			
			}		
		}

		#Remove tracking var
		if($str_post_url)
		{
			/*
			$arrparse = parse_url($str_post_url);
			parse_str($arrparse["query"],$arrstr);
			unset($arrstr["trk"]);
			$str_bulid_query = http_build_query($arrstr);
			$str_post_url = $arrparse["scheme"]."://".$arrparse["host"].$arrparse["path"]."?".$str_bulid_query;
			*/
			$str_post_url=preg_replace("/&trk=([a-z0-9-\*]+)/i","",$str_post_url);
		}

		return $str_post_url; 
	}
	
	function fn_get_member_groups()
    {
		global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key; 		
		
		$host = "http://api.linkedin.com";
        
        /*<user authorized token received from OAuth>*/
		if($this->linkedin_token_key)
			$token = $this->linkedin_token_key;
		else
			$token = $user_data->linkedin_token_key;
        
        /*<user authorized secret received from OAuth>*/
		if($this->linkedin_secret_key)
			$secret = $this->linkedin_secret_key;
		else
			$secret = $user_data->linkedin_secret_key;
        
        /* share url -  */
		
		/*		
		http://api.linkedin.com/v1/people/~/group-memberships:(group:(id,name,counts-by-category))?membership-state=member				
		$endpoint = "http://api.linkedin.com/v1/people/~/group-memberships:(group:(id,name,counts-by-category,website-url,site-group-url,small-logo-url))?count=200&start=0";         
        */
		
		//$endpoint = "http://api.linkedin.com/v1/people/~/group-memberships:(group:(id,name,counts-by-category,website-url,site-group-url,small-logo-url))?membership-state=owner";		
		$endpoint = "http://api.linkedin.com/v1/people/~/group-memberships:(group:(id,name,counts-by-category,website-url,site-group-url,small-logo-url))?count=300&start=0";
		$method = 'GET'; 
        $args = '';
                              
        $test_consumer  = new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
        //$endpoint       = sprintf("%s/%s",$host,$uri); // need a + symbol here.
         
        $arg_seq    = array();
        $req_token  = new OAuthConsumer($token,$secret,1);
        
        $profile_req = OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint,$arg_seq);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token);
		
		$toHeader = $profile_req->to_header($host);		
		
	  	$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader,'Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		$output = curl_exec($ch);
  		curl_close($ch);		
		return $output;		
	}
		
    function fn_post_share_ln_profile($comment,$title,$url,$imageUrl,$str_desc)
    {
        /* http://developer.linkedin.com/message/2244 */ 
              
        global $user_data;
        global $pv_linkedin_api_key;
        global $pv_linkedin_api_secret_key;    
       
        $host = "http://api.linkedin.com";		
		
		if($this->obj_user_info)
		{
			 /*<user authorized token received from OAuth>*/        
			$token = $this->obj_user_info->token_key;
        
        	/*<user authorized secret received from OAuth>*/
        	$secret = $this->obj_user_info->secret_key;
		}
		else
		{
			  /*<user authorized token received from OAuth>*/        
        	$token = $user_data->linkedin_token_key;
        
        	/*<user authorized secret received from OAuth>*/
        	$secret = $user_data->linkedin_secret_key;
		}  
        
        /* share url - v1/people/~/shares */        
        $endpoint = "http://api.linkedin.com/v1/people/~/shares";                
        $method = 'POST';      
                   
        /* to set the status, $args is the XML with the message update.*/               
                 
        $test_consumer  = new OAuthConsumer($pv_linkedin_api_key,$pv_linkedin_api_secret_key,NULL);
        $sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
        //$endpoint       = sprintf("%s/%s",$host,$uri); // need a + symbol here.
         
        $arg_seq    = array();
        $req_token  = new OAuthConsumer($token,$secret,1);
        
        $profile_req = OAuthRequest::from_consumer_and_token($test_consumer,$req_token,$method,$endpoint,$arg_seq);
        $profile_req->sign_request($sig_method,$test_consumer,$req_token);
        
        /* PROCESS VIA CURL */        
       
	   /*if($url)
	   {
		$url = urlencode($url);
	   }*/	   
	   
	   if(preg_match( "/twitter\.com\//", $url))	
	   {
		 $url = str_replace("/#!/","/",$url);
	   }
	   
	   $url = trim(htmlspecialchars($url));	   
	   $comment = trim(htmlspecialchars($comment));
	   $title = trim(htmlspecialchars($title));
	   $imageUrl = trim(htmlspecialchars($imageUrl));
	   $str_desc = trim(htmlspecialchars($str_desc));

		$content='
			'.($title?'<title>'.$title.'</title>':'').'
			'.($url?'<submitted-url>'.$url.'</submitted-url>':'').'
			'.($imageUrl?'<submitted-image-url>'.$imageUrl.'</submitted-image-url>':'').'
			'.($str_desc?'<description>'.$str_desc.'</description>':'');
		$content=trim($content);
	   
		$args='<?xml version="1.0" encoding="UTF-8"?><share><comment>'.$comment.'</comment>'.($content?'<content>'.$content.'</content>':'').'<visibility><code>anyone</code></visibility></share>';
         
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array ( $profile_req->to_header($host),'Content-Type: text/xml' ));        
        curl_setopt( $ch, CURLOPT_URL, $endpoint );
         
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $args );
         
        $output = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);		
        curl_close($ch);
				
		/****** GET POST URL *****/		
		$arr_response["post_status"] = $status;		
		if($status == 201 || $status == 202)
		{
			$str_post_url = $this->fn_Get_Linkedin_Post_Url();
			$arr_response["post_url"] = $str_post_url;
		}
		else
		{
			$arr_response["post_output"] = $output;
		}
		return $arr_response;
		/****** END GET POST URL *****/
		      
        //return $status;
    }
    
    /******* END FOR PROFILE ACTIVITY FUNCTIONS *******/

}

?>
