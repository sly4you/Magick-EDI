<?php
ini_set('display_errors','off');
// Start a session
session_start();
// Required file inclusion
require_once 'constants/constants_file.php';
require_once __DIR_CONFIG__ . 'configuration.php';
require_once __DIR_CLASSES__ . 'class.dboperation.php';
require_once __DIR_CLASSES__ . 'class.xtemplate.php';

$dbObj = new dbOperation();
switch( $_REQUEST['mode'] )
{
	case 'ajax':
		include_once( 'modules/ajax/ajax.php' );
		break;
	
	default:
		switch( $_SESSION['auth'] )
		{
			case '1':
				$page_out = new Xtemplate(__DIR_TEMPLATES__ . '/mainLogged.html');
				$page_out->assign( 'CUSTOMER', $_SESSION['user_data'] );
				break;
			
			default:
				$page_out = new Xtemplate(__DIR_TEMPLATES__ . '/mainLogin.html');
				break;
		}
		$page_out->parse( 'main' );
		$page_out->out( 'main' );
		break;
}
?>