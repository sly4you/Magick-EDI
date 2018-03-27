<?php
ini_set('display_errors','On');
error_reporting(E_ALL | E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
session_start();
set_time_limit(0);


define ('__AB_PATH__', dirname(__FILE__) . '/' );

include_once __AB_PATH__ . 'constants/constants_file.php';
include_once __AB_PATH__ . 'configuration/configuration.php';
include_once __DIR_CLASSES__ . 'class.httprequest.php';



$parameters['ac']	= 'getMessages';
$parameters['username']	= __REMOTE_USERNAME__;
$parameters['password']	= __REMOTE_PASSWORD__;

try {
	$result_content = httpRequest::parseHttpRequestJsonResponse(__REMOTE_HOST__, __REMOTE_PATH__, $parameters, __REMOTE_METHOD__, false);
	if( $result_content['code'] != '1000' )
		throw new Exception( $result_content['description']);
	print_r($result_content);
	exit;
	// For any message in result content, get data and try translation to remote system
	if(is_array($result_content['data']))
	{
		foreach($result_content['data'] as $key => $single_message)
		{
			$file_name	= str_replace('.', '-', $single_message['message_subject']) . '-' . date('dmY_His', strtotime($single_message['message_date'])) . '.txt';
			if(!is_dir(__DIR_MESSAGES_INBOX__ . strtolower($single_message['message_customer'])))
			{
				mkdir(__DIR_MESSAGES_INBOX__ . strtolower(($single_message['message_customer'])));
			}				
			file_put_contents( __DIR_MESSAGES_INBOX__ . strtolower($single_message['message_customer']) . '/' . $file_name, $single_message['message_content'] );
			$readed_message[] = $single_message['message_id'];
		}
		$parameters['ac'] = 'setReadedMessages';
		$paramenter['messages'] = implode('#', $single_messages);
		$result_content = httpRequest::parseHttpRequestJsonResponse(__REMOTE_HOST__, __REMOTE_PATH__, $parameters, __REMOTE_METHOD__, false);
	}
}
catch( Exception $e )
{
	echo $e->getMessage();
}
?>
