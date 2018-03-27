<?php
require_once __DIR_CLASSES__ . 'class.authentication.php';

switch( $_REQUEST['ac'] )
{	
	case 'logout':
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . '/mainLogout.html');
		$page_out->parse( 'main' );
		$page_out->out( 'main' );
		exit;
		break;
	
	case 'clogout':
		$auth = new Authentication();
		$auth->logout();
		echo 1;
		break;

	default:
		$auth = new Authentication();
		if( !$user = $auth->login($_REQUEST['username'], $_REQUEST['password']) )
		{
			echo 0;
			exit;
		}
		$_SESSION['auth']		= 1;
		$_SESSION['user_data']	= $user;
		echo 1;
		exit;
		break;
}
