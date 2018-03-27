<?php

class mBox
{
	function __construct($dbObj)
	{
		$this->_db = $dbObj;
	}
	
	function send()
	{
		// Check inserted data
		if( strlen($_REQUEST['message_subject']) < 3 )
		{
			$_SESSION['error']['subject'] = __ERROR_MBOX_SUBJECT__;
		}
		if( strlen($_REQUEST['message_content']) < 10 )
		{
			$_SESSION['error']['content'] = __ERROR_MBOX_CONTENT__;
		}
		if( $_SESSION['error'] )
		{
			$_SESSION['error_data'] = $_REQUEST;
			$this->add();
		}
		include_once __DIR_CLASSES__ . 'class.edioperation.php';
		try
		{
			$message_op = new ediOperation();
		
			$message_check = $message_op->sendEdiMessage($_SESSION['user_data']['user_id'],
														  $_REQUEST['message_to'],
														  $_REQUEST['message_content'],
														  $_REQUEST['message_translate']);
														  
			// Display message
			$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'addMessage.html');
		}
		catch( AS2Exception $exc )
		{
			$_SESSION['error']['send'] = $exc->getMessage();
			$_SESSION['error_data'] = $_REQUEST;
			$this->add();
		}
	}
	
	function add()
	{
		// Check if partner exists
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'addMessage.html');
		// Get list partners from DB
		$query = 'SELECT * FROM partner_identifying'
		.		 " WHERE user_id='" . (int)$_SESSION['user_data']['user_id'] . "'"
		.		 " AND partner_org_name!='" . mysql_real_escape_string($_SESSION['user_data']['org_name']) . "'"
		.		 " ORDER BY partner_org_name"
		;
		if( !$partner_list = $this->_db->getAll($query) )
		{
			$page_out->parse('main.jsAlert');
			$page_out->parse('main');
			$page_out->out('main');
			exit;
		}
		$page_out->assign( 'USER_DATA', $_SESSION['user_data'] );
		foreach ( $partner_list as $key => $single_partner )
		{
			if( $_SESSION['error_data']['message_to'] == $single_partner['partner_id'] )
			{
				$single_partner['selected'] = 'selected';
			}
			$page_out->assign( 'PARTNER', $single_partner );
			$page_out->parse( 'main.singlePartner' );
		}
		if( $_SESSION['error'] )
		{
			$page_out->assign( 'MESSAGE_DETAIL', $_SESSION['error_data'] );
			foreach( $_SESSION['error'] as $key => $single_error )
			{
				$page_out->assign( 'ERROR_' . strtoupper($key), $single_error );
				$page_out->parse( 'main.error' . ucfirst($key) );
			}
			unset($_SESSION['error']);
		}
		$page_out->parse('main');
		$page_out->out('main');
		exit;
		break;
	}
	
	function download()
	{
		$query = 'SELECT * FROM user_messages'
		.		 " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
		.		 " AND message_id='" . mysql_real_escape_string($_REQUEST['id']) . "'"
		;
		if( !$message_detail = $this->_db->GetRow($query) )
		{
			Header( 'Location: index.php' );
			exit;
		}
		// Get file from filesystem
		$dir_path = __DIR_CUSTOMERS__ . $_SESSION['user_data']['user_id'] . '/' . $message_detail['message_path'];
		$list_files = scandir($dir_path);
		foreach( $list_files as $key => $single_file )
		{
			if($single_file != str_replace($message_detail['message_name'], '', $single_file) )
			{
				if ( $single_file != str_replace('payload', '', $single_file) || $single_file != str_replace('original', '', $single_file) )
				{
					$message_out = '---- START MESSAGE PAYLOAD ----' . "\n\n";
					$message_out .= file_get_contents( $dir_path . '/' . $single_file );
					$message_out .= "\n\n" . '---- END MESSAGE PAYLOAD ----' . "\n\n";
				}
				$file_name = str_replace( '-', '_', $message_detail['message_date'] );
				$file_name = str_replace( ' ', '_', $message_detail['message_date'] );
				$file_name = str_replace( ':', '_', $message_detail['message_date'] );
			}
		}
		if( $message_out )
		{
			header('Content-type: text/plain');
			header('Content-Length: ' . strlen($message_out));
			header('Content-disposition: attachment; filename="edi_message_' . $file_name . '.txt "');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			echo $message_out;
		}
		exit;
	}

	function delete()
	{
		$list_elements = explode( '|', $_REQUEST['string'] );
		if( sizeof($list_elements) > 0 )
		{
			foreach( $list_elements as $key => $single_element )
			{
				$query = 'SELECT * FROM user_messages'
				.		 " WHERE user_id='" . (int)$_SESSION['user_data']['user_id'] . "'"
				.		 " AND message_id='" . (int)$single_element . "'"
				;
				if( $message_detail = $this->_db->getRow($query) )
				{
					// Delete real file of message in filesystem
					// Get file from filesystem
					$dir_path = __DIR_CUSTOMERS__ . $_SESSION['user_data']['user_id'] . '/' . $message_detail['message_path'];
					$list_files = scandir($dir_path);
					foreach( $list_files as $key => $single_file )
					{
						if($single_file != str_replace($message_detail['message_name'], '', $single_file) )
						{
							@unlink( $dir_path . '/' . $single_file );
						}
					}
					$query = 'DELETE FROM user_messages'
					.		 " WHERE user_id='" . (int)$_SESSION['user_data']['user_id'] . "'"
					.		 " AND message_id='" . (int)$message_detail['message_id'] . "'"
					;
					$this->_db->Execute( $query );
				}
			}
		}
		$_REQUEST['path'] = $message_detail['message_path'];
		$this->partialDisplay();
	}

	function viewDetail()
	{
		// Check if partner exists
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'mainMbox.html');
		$query = 'SELECT * FROM user_messages'
		.		 " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
		.		 " AND message_id='" . mysql_real_escape_string($_REQUEST['id']) . "'"
		;
		if( !$message_detail = $this->_db->GetRow($query) )
		{
			// Partner does no exist. Parse page and display JS alert
			$page_out->parse('main.messages.messageDetail.jsAlert');
			$page_out->parse('main.messages.messageDetail');
			$page_out->out('main.messages');
			exit;
		}
		// Update message status if it's are set to 1
		if( $message_detail['message_status'] == 1 )
		{
			$query = "UPDATE user_messages SET message_new='0'"
			.		 " WHERE message_id='" . $message_detail['message_id'] . "'"
			;
			$this->_db->Execute($query);
		}
		// Get file from filesystem
		$dir_path = __DIR_CUSTOMERS__ . $_SESSION['user_data']['user_id'] . '/' . $message_detail['message_path'] . '/';
		print_r($message_detail['message_path']);
		
		$list_files = scandir($dir_path);
		foreach( $list_files as $key => $single_file )
		{
			if($single_file != str_replace($message_detail['message_name'], '', $single_file) )
			{
				if ( $single_file != str_replace('payload', '', $single_file) || $single_file != str_replace('original', '', $single_file) )
				{
					$message_detail['message_content'] = '---- CUT HERE - START MESSAGE PAYLOAD ----' . "\n\n";
					$message_detail['message_content'] .= file_get_contents( $dir_path . '/' . $single_file );
					$message_detail['message_content'] .= "\n\n" . '---- CUT HERE - END MESSAGE PAYLOAD ----' . "\n\n";
				}
			}
		}
		$page_out->assign('MESSAGE_DETAIL', $message_detail);
		$page_out->parse('main.messages.messageDetail');
		$page_out->out('main.messages.messageDetail');
		$page_out->out('main.messages');
		exit;
	}

	function partialDisplay()
	{
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'mainMbox.html');
		switch( $_REQUEST['path'] )
		{
			case 'Outbox':
				$message_path = 'Outbox';
				break;

			case 'Draft':
				$message_path = 'Draft';
				break;

			default:
				$message_path = 'Inbox';
				break;
		}
		// Get all user messages
		$query = 'SELECT * FROM user_messages'
		.		 " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
		.		 " AND message_path='" . $message_path . "'"
		.		 " ORDER BY message_date DESC"
		;
		if( $list_messages = $this->_db->GetAll($query) )
		{
			$x = 0;
			foreach( $list_messages as $key => $single_message )
			{
				$single_message['message_from'] = str_replace('"', '', $single_message['message_from']);
				$single_message['message_to'] = str_replace('"', '', $single_message['message_to']);
				$single_message['message_from'] = explode('#', $single_message['message_from']);
				$single_message['message_from'] = $single_message['message_from'][0];
				$single_message['message_to'] = explode('#', $single_message['message_to']);
				$single_message['message_to'] = $single_message['message_to'][0];
				$page_out->assign( 'MESSAGE', $single_message );
				if( $x == 0 )
				{
					if( $single_message['message_new'] == 1 )
					{
						$page_out->parse( 'main.messages.tableRow.rowWhite.new' );
					}
					else
					{
						$page_out->parse( 'main.messages.tableRow.rowWhite.old' );
					}
					$page_out->parse( 'main.messages.tableRow.rowWhite');
					$x++;
				}
				else
				{
					if( $single_message['message_new'] == 1 )
					{
						$page_out->parse( 'main.messages.tableRow.rowGrey.new' );
					}
					else
					{
						$page_out->parse( 'main.messages.tableRow.rowGrey.old' );
					}
					$page_out->parse( 'main.messages.tableRow.rowGrey');
					$x = 0;
				}
				$page_out->parse( 'main.messages.tableRow');
			}
		}
		$page_out->parse('main.messages.messageDetail');
		$page_out->parse('main.messages');
		$page_out->out('main.messages');
	}

	function mainDisplay()
	{
		$page_out = new Xtemplate(__DIR_TEMPLATES__ . 'mainMbox.html');
		switch( $_REQUEST['path'] )
		{
			case 'Outbox':
				$message_path = 'Outbox';
				break;

			case 'Draft':
				$message_path = 'Draft';
				break;

			default:
				$message_path = 'Inbox';
				break;
		}
		// Get all user messages
		$query = 'SELECT * FROM user_messages'
		.		 " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
		.		 " AND message_path='" . $message_path . "'"
		.		 " ORDER BY message_date DESC"
		;
		if( $list_messages = $this->_db->GetAll($query) )
		{
			$x = 0;
			foreach( $list_messages as $key => $single_message )
			{
				$single_message['message_from'] = str_replace('"', '', $single_message['message_from']);
				$single_message['message_to'] = str_replace('"', '', $single_message['message_to']);
				$single_message['message_from'] = explode('#', $single_message['message_from']);
				$single_message['message_from'] = $single_message['message_from'][0];
				$single_message['message_to'] = explode('#', $single_message['message_to']);
				$single_message['message_to'] = $single_message['message_to'][0];
				$page_out->assign( 'MESSAGE', $single_message );
				if( $x == 0 )
				{
					if( $single_message['message_new'] == 1 )
					{
						$page_out->parse( 'main.messages.tableRow.rowWhite.new' );
					}
					else
					{
						$page_out->parse( 'main.messages.tableRow.rowWhite.old' );
					}
					$page_out->parse( 'main.messages.tableRow.rowWhite');
					$x++;
				}
				else
				{
					if( $single_message['message_new'] == 1 )
					{
						$page_out->parse( 'main.messages.tableRow.rowGrey.new' );
					}
					else
					{
						$page_out->parse( 'main.messages.tableRow.rowGrey.old' );
					}
					$page_out->parse( 'main.messages.tableRow.rowGrey');
					$x = 0;
				}
				$page_out->parse( 'main.messages.tableRow');
			}
		}
		$page_out->parse('main.messages.messageDetail');
		$page_out->parse('main.messages');
		$page_out->parse('main');
		$page_out->out('main');
	}
}

$class_op = new Mbox($dbObj->_db);

switch ( $_REQUEST['ac'] )
{
	case 'send';
		$class_op->send();
	break;
	
	case 'add':
		$class_op->add();
	break;
	
	case 'delete';
		$class_op->delete();
		break;
		
	case 'download':
		$class_op->download();
		break;
		
	case 'viewDetail':
		$class_op->viewDetail();
		break;
	
	case 'viewPartial':
		$class_op->partialDisplay();
		break;

	default:
		$class_op->mainDisplay();
		break;
}