<?php
switch( $_REQUEST['chapter'] )
{
	case 'login':
	case 'logout':
	case 'clogout':
		require_once( 'login/login.php' );
		break;
	
	default:
		if( $_SESSION['auth'] !== 1 )
		{
			die('Unauthenticate');
		}
		switch( $_REQUEST['chapter'] )
		{
			case 'mbox':
				require_once( 'mbox/mbox.php' );
				break;
			
			case 'identyfing':
				require_once( 'partners/partners.php' );
				break;
				
			default:
				require_once( 'partners/partners.php' );
				break;				
		}
		break;
}
?>