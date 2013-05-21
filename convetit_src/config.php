<?php



/********* END SET GOTO URL *********/

//define('CALLBACK_URL', 'http://'.DOMAIN.'/social/linkedin?_api=accesstoken'.$str_attach_redr);
define('CALLBACK_URL', 'http://54.241.180.4/app_dev.php/plans?service=linkedin&_api=accesstoken'.$str_attach_redr);
define('BASE_API_URL', 'https://api.linkedin.com');

define('REQUEST_PATH', '/uas/oauth/requestToken');
define('AUTH_PATH', '/uas/oauth/authorize');
define('ACC_PATH', '/uas/oauth/accessToken');



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

?>
