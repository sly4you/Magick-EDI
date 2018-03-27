<?php
switch( $_REQUEST['ac'] )
{
	case 'login':
		require_once( 'login/login.php' );
		break;
	
	default:
		if( $_SESSION['authentication'] != 'ok' )
		{
			die('Unauthenticate');
		}
		break;
}
?>