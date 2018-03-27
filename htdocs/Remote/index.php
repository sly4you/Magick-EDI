<?php
ini_set('display_errors','Off');
//error_reporting(E_ALL | E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
session_start();
set_time_limit(0);
include_once $_SERVER['DOCUMENT_ROOT'] . '/constants/constants_file.php';
include_once __DIR_CLASSES__ . 'As2/AS2Constants.php';
include_once __DIR_CONFIG__ . 'configuration.php';
include_once __DIR_CLASSES__ . 'class.dboperation.php';
include_once __DIR_CLASSES__ . 'class.authentication.php';
include_once __DIR_CLASSES__ . 'class.ediparser.php';
include_once __DIR_CLASSES__ . 'class.edioperation.php';
include_once __DIR_CLASSES__ . 'PHPMailer/class.phpmailer.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST')
{
	$dbObj = new DbOperation();
	$db = $dbObj->_db;
	$auth_op = new Authentication();
	if(!$user_data = $auth_op->login($_REQUEST['username'], $_REQUEST['password']))
	{
		$result['code']			= '1001';
		$result['description']	= 'Unable Authenticate';
		echo json_encode( $result );
		exit;
	}
	$_SESSION['auth']		= 1;
	$_SESSION['user_data']	= $user_data;
	
	switch($_REQUEST['ac'])
	{
	    case 'getMessageByOMID':
	        // If have a omid minor that 10 chars, return false
	        if(strlen($_REQUEST['omid']) < 10) {
	            $result['code']			= '1001';
	            $result['description']	= 'Message ID not specified';
	            echo json_encode( $result );
	            exit;
	        }
	        // O.K., set query to find required message
	        $query = 'SELECT * FROM user_messages'
	        .        " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
	        .        " AND original_message_id='" . mysql_real_escape_string($_REQUEST['omid']) . "'"
	        ;
	        // Success, message found in database, follow to get message content
	        if( $message_detail = $db->getRow($query) )
	        {
	            // Get connector and partner data from message name
	            switch( $type_transport = ediOperation::getConnectorTypeByMessageName( $message_detail['message_name']) )
	            {
	                case 'AS2':
	                    $partner_data_from  = AS2Partner::getPartnerCombination( $message_detail['message_from'] );
	                    $partner_data_to    = AS2Partner::getPartnerCombination( $message_detail['message_to'] );
	                    $message_detail['message_file_name']   = $message_detail['message_name'] . '.payload_0';
	                    break;
	                    
	                case 'OFTP2':
	                    // Start EDI Operation
	                    $edi_op = new ediOperation();
	                    $partner_data_from	= $edi_op->getPartnerDetailByMessageFrom( $message_detail['message_from'], $message_detail['user_id'] );
	                    $partner_data_to	= $edi_op->getPartnerDetailByMessageTo( $message_detail['message_to'], $message_detail['user_id'] );
	                    $message_detail['message_file_name']   = $message_detail['message_name'];
	                    break;
	                            
	                 default:
	                    $result['code']			= '1001';
	                    $result['description']	= 'Unable to get Partner data';
	                    echo json_encode( $result );
	                    exit;
	            }
	            $message_detail['message_connector']   = $type_transport;
	            $messages_dir = AS2_DIR_CUSTOMERS . '/' . $_SESSION['user_data']['user_id'] . '/' . $message_detail['message_path'] . '/';	            
	            $message_content = file_get_contents($messages_dir . $message_detail['message_file_name']);
	            // O.K., message are in  path, content is o.k.
	            if($message_content !== false)
	            {
	                // O.K., file is good, set result for output
					$export_message['message_id']                      = $message_detail['message_id'];
					$export_message['message_from']                    = $message_detail['message_from'];
					$export_message['message_to']                      = $message_detail['message_to'];
					$export_message['message_date']                    = $message_detail['message_date'];
					$export_message['message_subject']                 = $message_detail['message_subject'];
					$export_message['message_real_path']               = $messages_dir . $file_name;
					$export_message['message_original_encoding']       = mb_detect_encoding($message_content, 'UTF-8,ISO-8859-1,WINDOWS-1252');
					if($export_message['message_original_encoding'] != 'UTF-8') {
					    $message_content = utf8_encode($message_content);
					}
					$export_message['message_content']                 = str_replace(array("\r\n","\r","\n"),"", $message_content);
    	            list( $start_edi_format, $start_edi_version ) = explode( ',', $partner_data_from['partner_edi_format'] );
	                list( $dest_edi_format, $dest_edi_version ) = explode( ',', $partner_data_to['partner_edi_format'] );
	                // Start parsing message for export in XML format
	                $translator = new EDI($export_message['message_content'], 'string');
	                // Set message details
	                $export_message['message_edi_standard']            = $translator->edi_standard;
	                $export_message['message_edi_standard_type']       = $translator->edi_standard_type;
	                $export_message['message_edi_type']                = $translator->edi_message_type;
	                $export_message['message_content_xml']             = $translator->edi_message_xml;
	                // If translate are requested
	                if($_REQUEST['translate'] == 1)
	                {
	                    try
	                    {
	                       $translated_file_content = $translator->ediTranslateTo(strtoupper($dest_edi_format), strtoupper($dest_edi_version));
	                       $export_message['message_content_translated']	= $translated_file_content;
	                    }
	                    catch (EDI_Exception $exc)
	                    {
	                       $result['code']			= '1001';
	                       $result['description']	= 'EDI Translator error: ' . $exc->getMessage();
	                       echo json_encode( $result );
	                       exit;
	                    }
	                }
	                $result['code']			= '1000';
	                $result['description']	= 'Operation success';
	                $result['data']			= $export_message;
	                echo json_encode( $result );
	                exit;
	            }
	            // Message not found, exit with error
	            $result['code']			= '1001';
	            $result['description']	= 'Good message ID but file not found in directory';
	            echo json_encode( $result );
	        }
	        // OMID string not found, exit.
	        $result['code']			= '1001';
	        $result['description']	= 'Message ID not found';
	        echo json_encode( $result );	         
	        break;
	        
	    case 'getMessagesListByDate':
	        // Date must expressed with: AAAA-MM-DD
	        if($_REQUEST['date'])
	        {
	            list($year, $month, $day) = explode("-", $_REQUEST['date']);
	            // Check if date is valid
	            if(checkdate ($month, $day, $year))
	            {
	                $add_query = " AND message_date >= '" . $year . '-' . $month . '-' . $day . "'";
	            }
	            
	        }
	        $query = 'SELECT * FROM user_messages'
	        .        " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
	        .        " AND message_path='Inbox'"
	        .        $add_query
	        .        " ORDER BY message_id DESC"
	        ;
	        if( $list_messages = $db->GetAll($query))
	        {
	            foreach( $list_messages as $key => $single_message)
	            {
	                // Clean $single_message unused data
	                unset($single_message['message_connector']);
	                unset($single_message['message_new']);
	                unset($single_message['message_status']);
	                unset($single_message['message_status_description']);
	                $all_messages[] = $single_message;
	            }
	            $result['code']        = '1000';
	            $result['description'] = 'Operation success';
	            $result['data']        = $all_messages;
	            echo json_encode( $result );
	            exit;
	        }
	        $result['code']			= '1001';
	        $result['description']	= 'No messages found';
	        echo json_encode( $result );
	        break;

		case 'getMessages':
			// Get all user messages
			$query = 'SELECT * FROM user_messages'
			.		 " WHERE user_id='" . $_SESSION['user_data']['user_id'] . "'"
			.		 " AND message_path='Inbox'"
			.		 " AND message_new='1'"
			.		 " ORDER BY message_date DESC"
			;
			if( $list_messages = $db->GetAll($query) )
			{
				$messages_dir = AS2_DIR_CUSTOMERS . '/' . $_SESSION['user_data']['user_id'] . '/Inbox/';
				// Start EDI Operation, this is for OFTP messages
				$edi_op = new ediOperation();
				// Get all .payload_ files, that are a really edi message
				if($handle_msg = opendir($messages_dir))
				{
					while (false !== ($entry_msg = readdir($handle_msg)))
					{
						if( $entry_msg != '.' && $entry_msg !== '..' )
						{
							if(is_file($messages_dir . $entry_msg))
							{
								// Switch routines, for message send by AS2, really content file contains, in the name, "payload_"
								// Message send by OFTP contain ".original"
								// To get message from and message to, and required "partner_data", must extract
								$type_transport = false;
								$splited_filename = explode('.', $entry_msg);
								$type_file = $splited_filename[count($splited_filename)-1];
								switch($type_file) {
									case 'payload_0':
										// File is from AS2
										$type_transport = 'AS2';
										break;

									case 'original':
										// File is from OFTP2
										$type_transport = 'OFTP2';
										break;
								}
								if($type_transport != false)
								{
									foreach($list_messages as $key => $single_message)
									{
										
										if( preg_match('/' . $single_message['message_name'] . '/i', $entry_msg) )
										{
											if($type_transport == 'AS2') {
												$partner_data_from	= AS2Partner::getPartnerCombination( $single_message['message_from'] );
												$partner_data_to	= AS2Partner::getPartnerCombination( $single_message['message_to'] );
											}
											else {
												$partner_data_from	= $edi_op->getPartnerDetailByMessageFrom( $single_message['message_from'], $single_message['user_id'] );
												$partner_data_to	= $edi_op->getPartnerDetailByMessageTo( $single_message['message_to'], $single_message['user_id'] );

											}
											$message_content = file_get_contents($messages_dir . $entry_msg);
											$export_message['message_id']				= $single_message['message_id'];
											$export_message['message_customer']			= $partner_data_from['partner_org_name'];
											$export_message['message_from']				= $single_message['message_from'];
											$export_message['message_to']				= $single_message['message_to'];
											$export_message['partner_auto_translate']		= $partner_data_from['partner_auto_translate'];
											$export_message['message_date']				= $single_message['message_date'];
											$export_message['message_subject']			= $single_message['message_subject'];
											$export_message['message_real_path']			= $messages_dir . $entry_msg;
											$export_message['message_original_encoding']		= mb_detect_encoding($message_content, 'UTF-8,ISO-8859-1,WINDOWS-1252');
											if($export_message['message_original_encoding'] != 'UTF-8') {
											    $message_content = utf8_encode($message_content);
											}
											$export_message['message_original_content']		= trim(str_replace(array("\r", "\n"), '', $message_content));
											$export_message['message_content']                      = trim(str_replace(array("\r", "\n"), '', $message_content));
											if( $partner_data_from['partner_auto_translate'] == 1 )
											{
												list( $start_edi_format, $start_edi_version ) = explode( ',', $partner_data_from['partner_edi_format'] );
												list( $dest_edi_format, $dest_edi_version ) = explode( ',', $partner_data_to['partner_edi_format'] );
												try
												{
													$translator = new EDI($export_message['message_content'], 'string');
													$translated_file_content = $translator->ediTranslateTo(strtoupper($dest_edi_format), strtoupper($dest_edi_version));
													$export_message['message_content']	= $translated_file_content;
													$all_messages[] = $export_message;
												}
												catch(EDI_Exception $exc)
												{
													//Send email with error translation
													// Prepare email for send result
													$mail             = new PHPMailer();
													$mail->IsSMTP();
													$mail->SMTPAuth   = true;
													$mail->Host       = __EMAIL_HOST__;
													$mail->Port       = __EMAIL_HOST_PORT__;
													$mail->Username   = __EMAIL_USERNAME__; // SMTP account username
													$mail->Password   = __EMAIL_PASSWORD__;        // SMTP account password
													$mail->SetFrom(__EMAIL_ADDRESS__, __EMAIL_NAME__);
													$mail->AddAddress(__EMAIL_ADDRESS__, __EMAIL_NAME__); // Email recipient!
													$mail->AddAttachment($messages_dir . $entry_msg);      // attachment
													$mail->Subject = '[FAILED] Delivery EDI message';
													$mail->Body    = "EDI message translation " . $start_edi_version . " to " . $dest_edi_version . " failed.\n"
													.				 "Message source are delivered by " . $partner_data_from['partner_org_name'] . "\n"
													.				 "Reason failed translation: " . $exc->getMessage() . "\n"
													.				 "This file are in attach at this email\n"
													.				 "File name file are: " . $entry_msg . "\n"
													.				 "Delivered on " . $single_message['message_date'] . "\n\n\n\n"
													.				 "Lariotechnik EDI system"; // optional, comment out and test
													$mail->Send();
												}
											}
											else
											{
												$all_messages[]	= $export_message;
											}
										}
									}
								}
							}
						}
					}
				}
				closedir($handle_msg);
			}
			$result['code']			= '1000';
			$result['description']	= 'Operation Success';
			$result['data']			= $all_messages;
			echo json_encode( $result );
			break;

		case 'translateMessage':
			// Get Partner from and Partner to
			$partner_data_from	= AS2Partner::getPartnerCombination( $_REQUEST['message_from'] );
			$partner_data_to	= AS2Partner::getPartnerCombination( $_REQUEST['message_to'] );
			if(!$partner_data_from || !$partner_data_to)
			{
				$result['code']			= '1001';
				$result['description']	= 'Unable to get Partner data';
				echo json_encode( $result );
				exit;
			}
			list( $start_edi_format, $start_edi_version ) = explode( ',', $partner_data_from['partner_edi_format'] );
			list( $dest_edi_format, $dest_edi_version ) = explode( ',', $partner_data_to['partner_edi_format'] );
			try
			{
				$translator = new EDI($_REQUEST['message_content'], 'string');
				$translated_file_content = $translator->ediTranslateTo(strtoupper($dest_edi_format), strtoupper($dest_edi_version));
				$export_message['message_content']	= $translated_file_content;
				$result['code']			= '1000';
				$result['description']	= 'Operation success';
				$result['data']			= $export_message;
				echo json_encode( $result );
			}
			catch (EDI_Exception $exc)
			{
				$result['code']			= '1001';
				$result['description']	= 'EDI Translator error: ' . $exc->getMessage();
				echo json_encode( $result );
			}
			break;

		case 'setReadedMessages':
			$all_messages = explode('#', $_REQUEST['messages']);
			if(is_array($all_messages))
			{
				foreach($all_messages as $key => $single_message)
				{
					$query = "UPDATE user_messages SET"
					.		 " message_new='0'"
					.		 " WHERE message_id='" . (int)$single_message . "'"
					.		 " AND user_id='" . $_SESSION['user_data']['user_id'] . "'"
					;
					$db->Execute($query);
				}
			}
			$result['code']			= '1000';
			$result['description']	= 'Operation Success';
			echo json_encode( $result );
			break;
			
		case 'sendMessage':
			try
			{
				$edi_op = new ediOperation();
				// Get user data
				if( !$user_data = $edi_op->getUserData($_SESSION['user_data']['user_id']) )
				throw new AS2Exception ( 'Unable to ge User Data' );

				// Get Partner detail
				if( !$partner_detail = $edi_op->getPartnerDetailFromName($_REQUEST['message_to'], $_SESSION['user_data']['user_id']) )
				throw new AS2Exception ( 'Unable to get partner detail from ' . $partner_name );

				if( $_REQUEST['translate_message'] == 1 )
				{
					list( $start_edi_format, $start_edi_version ) = explode( ',', $user_data['partner_edi_format'] );
					list( $dest_edi_format, $dest_edi_version ) = explode( ',', $partner_detail['partner_edi_format'] );
					$original_message = $message;
					$translator = new EDI($_REQUEST['message_content'], 'string');
					$_REQUEST['message_content'] = $translator->ediTranslateTo(strtoupper($dest_edi_format), strtoupper($dest_edi_version));
				}
				try {
					$edi_sender = new ediOperation();
					$edi_sender->sendEdiMessageFromRemote($user_data['user_id'], $partner_detail, $_REQUEST['message_content']);
					$result['code'] = '1000';
					$result['description'] = 'Operation success';
					echo json_encode($result);
				}
				catch (AS2Exception $exc)
				{
					$result['code']			= '1001';
					$result['description']	= 'AS2 Transfer Failed. Error: ' . $exc->getMessage();
					echo json_encode( $result );
				}
			}
			catch (EDI_Exception $exc)
			{
				$result['code']			= '1001';
				$result['description']	= 'AS2 Transfer Failed. Error: ' . $exc->getMessage();
				echo json_encode( $result );
				exit;
			}
			
			break;

		default:
			$result['code']			= '1001';
			$result['description']	= 'Unable Authenticate';
			echo json_encode( $result );
			break;
	}
}