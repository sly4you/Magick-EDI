<?php

switch( $_REQUEST['ac'] )
{
	case 'save':
		
		break;
		
	case 'changeConnector':
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'addPartner.html');
		switch($_REQUEST['connector'])
		{
			case 'SMTP':
				$page_out->parse('main.partnerConnector.SMTP');
				$page_out->out('main.partnerConnector.SMTP');
				break;
			case 'HTTP':
				$page_out->parse('main.partnerConnector.HTTP');
				$page_out->out('main.partnerConnector.HTTP');
				break;
				
			default:
				$page_out->parse('main.partnerConnector.AS2');
				$page_out->out('main.partnerConnector.AS2');
				break;
		}
		exit;
		break;
		
	case 'add':
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'addPartner.html');
		$page_out->parse('main.partnerDetail');
		// Default: as2 connector are displayed
		$page_out->parse('main.partnerConnector.AS2');
		$page_out->parse('main.partnerConnector');
		$page_out->out('main.partnerDetail');
		$page_out->out('main.partnerConnector');
		exit;		
		break;
	
	case 'viewDetail':
		// Check if partner exists
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'mainPartners.html');
		$query = 'SELECT * FROM partner_identifying'
		.		 " WHERE partner_id='" . mysql_real_escape_string($_REQUEST['id']) . "'"
		.		 " AND user_id='" . $_SESSION['user_data']['user_id'] . "'"
		;
		if( $partner_detail = $dbObj->_db->GetRow($query) )
		{
			// Get partner connector
			$query = 'SELECT * FROM partner_connector_' . strtolower($partner_detail['partner_connector'])
			.		 " WHERE partner_id='" . $partner_detail['partner_id'] . "'"
			;
			$partner_connector = $dbObj->_db->GetRow($query);
			$page_out->assign('PARTNER_DETAIL', $partner_detail );
			$page_out->assign('PARTNER_CONNECTOR', $partner_connector);
			$page_out->parse('main.partnerDetail');
			$page_out->parse('main.partnerConnector' . strtoupper($partner_detail['partner_connector']));
			$page_out->out('main.partnerDetail');
			$page_out->out('main.partnerConnector' . strtoupper($partner_detail['partner_connector']));			
			exit;
		}
		// Partner does no exist. Parse page and display JS alert
		$page_out->parse('main.partnerDetail.jsAlert');
		$page_out->parse('main.partnerDetail');
		$page_out->out('main.partnerDetail');
		exit;
		break;
		
	default:
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'mainPartners.html');
		// Search all partners registered on system for customer
		$query = 'SELECT * FROM partner_identifying'
		.		 " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
		.		 " AND default_profile!='1'"
		;
		if( $list_partners = $dbObj->_db->GetAll($query) )
		{
			$x = 0;
			foreach( $list_partners as $key => $single_partner )
			{
				$page_out->assign( 'PARTNER', $single_partner );
				if( $x == 0 )
				{
					$page_out->parse( 'main.tableRow.rowWhite');
					$x = 1;
				}
				else
				{
					$page_out->parse( 'main.tableRow.rowGrey');
					$x = 0;
				}
			}
			$page_out->parse( 'main.tableRow');
		}
		$page_out->parse('main.partnerDetail');
		$page_out->parse( 'main' );
		$page_out->out( 'main' );
		exit;
		break;
}