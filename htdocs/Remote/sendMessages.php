<?php
ini_set('display_errors','On');
//error_reporting(E_ALL | E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
session_start();
set_time_limit(0);


define ('__AB_PATH__', dirname(__FILE__) . '/' );

include_once __AB_PATH__ . 'constants/constants_file.php';
include_once __AB_PATH__ . 'configuration/configuration.php';
include_once __DIR_CLASSES__ . 'class.httprequest.php';
include_once __DIR_CLASSES__ . 'class.sendemail.php';


$parameters['ac']	= 'sendMessage';
$parameters['username']	= __REMOTE_USERNAME__;
$parameters['password']	= __REMOTE_PASSWORD__;

$handle_dir = opendir(__DIR_MESSAGES_OUTBOX__);
while (false !== ($entry_dir = readdir($handle_dir)))
{
	if( $entry_dir != '.' && $entry_dir !== '..' )
	{
		$parameters['message_to']			= $entry_dir;
		$parameters['translate_message']	= 1;
		$handle_dir_messages = opendir(__DIR_MESSAGES_OUTBOX__ . $entry_dir);
		while (false !== ($entry_message = readdir($handle_dir_messages)))
		{
			if( $entry_message != '.' && $entry_message !== '..' )
			{
				$parameters['message_content']	= file_get_contents(__DIR_MESSAGES_OUTBOX__ . $entry_dir . '/' . $entry_message);
				$result_content = httpRequest::parseHttpRequestJsonResponse(__REMOTE_HOST__, __REMOTE_PATH__, $parameters, __REMOTE_METHOD__);
				// Prepare email for send result
				$mail = new attach_mailer();
				$mail->from_name	= 'Ediservices';
				$mail->from_mail	= __EMAIL_ADDRESS_FROM__;
				$mail->mail_to		= __EMAIL_ADDRESS_TO__;
				$mail->add_attach_file(__DIR_MESSAGES_OUTBOX__ . $entry_dir . '/' . $entry_message);
				if( $result_content['code'] != '1000' )
				{
					$mail->mail_subject 	= '[FAILED] Delivery EDI message';
					$mail->html_body	= "EDI message to " . $entry_dir . " failed.<br />"
					.				 "Reason: " . $result_content['description'] . "<br />"
					.				 "This file are in attach at this email and are moved in \"Unsend\" directory<br />"
					.				 "Name of file are: " . $entry_message . "<br /><br /><br /><br />"
					.				 "Lariotechnik EDI system"; // optional, comment out and test
					
					//rename( __DIR_MESSAGES_OUTBOX__ . $entry_dir . '/' . $entry_message, __DIR_MESSAGES_UNSENT__ . '/' . $entry_message);
				}
				else
				{
					
					$mail->mail_subject = '[SUCCESS] Delivery EDI message';
					$mail->html_body    = "EDI message to " . $entry_dir . " success.<br />"
					.				 "This file are in attach at this email and are deleted from \"Outbox\" directory<br />"
					.				 "Name of file are: " . $entry_message . "<br /><br />"
					.				 "Lariotechnik EDI system"; // optional, comment out and test

					//unlink( __DIR_MESSAGES_OUTBOX__ . $entry_dir . '/' . $entry_message );
				}
				$mail->process_mail();

			}
		}
	}
}
?>
