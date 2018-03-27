<?php
require_once __DIR_CLASSES__ . 'class.dboperation.php';

class ediOperation
{
	var $error_code			= '';
	var $error_description	= '';

	function __construct()
	{
		$dbObj = new dbOperation();
		$this->_db = $dbObj->_db;
	}

	function sendEdiMessageFromRemote( $user_id, $partner_detail, $message)
	{
		// Call function to send edi document
		$function_name = 'send' . strtoupper($partner_detail['partner_connector']) . 'Message';
		try {
			call_user_func_array(array(&$this, $function_name), array($user_id, $partner_detail, $message));
		}
		catch (AS2Exception $exc)
		{
			throw new AS2Exception($exc->getMessage());
		}
	}

	function sendEdiMessage($user_id, $partner_id, $message, $translate_message=0)
	{
		// Get user data
		if( !$user_data = $this->getUserData($user_id) )
		throw new AS2Exception('Unable to get User Data');

		// Get Partner detail
		if( !$partner_detail = $this->getPartnerDetail($partner_id, $user_id) )
		throw new AS2Exception('Unable to get Partner Data');

		try {
			// If partner have a specific edi format and translate_message are set to 1
			// transform edi document to format allowed on customer
			if( $translate_message == 1 )
			{
				list( $start_edi_format, $start_edi_version ) = explode( ',', $user_data['partner_edi_format'] );
				list( $dest_edi_format, $dest_edi_version ) = explode( ',', $partner_detail['partner_edi_format'] );
				$original_message = $message;
				$translator = new EDI($_REQUEST['message_content'], 'string');
				$message = $translator->ediTranslateTo(strtoupper($dest_edi_format), strtoupper($dest_edi_version));
			}
			call_user_func_array(array( &$this, 'send' . strtoupper($partner_detail['partner_connector']) . 'Message'), array($user_id, $partner_detail, $message, $original_message));
		}
		catch (AS2Exception $exc )
		{
			throw new AS2Exception($exc->getMessage());
		}
	}

	function getUserData( $user_id )
	{
		$query = 'SELECT * FROM partner_identifying'
		.		 " WHERE user_id='" . (int)$user_id . "'"
		.		 " AND default_profile='1'"
		;
		if( !$user_detail = $this->_db->getRow($query) )
		{
			return false;
		}
		return $user_detail;
	}

	function getPartnerDetail( $partner_id, $user_id )
	{
		$query = 'SELECT * FROM partner_identifying'
		.		 " WHERE partner_id='" . (int)$partner_id . "'"
		.		 " AND user_id='" . (int)$user_id . "'"
		;
		if( !$partner_identifying = $this->_db->getRow($query) )
		{
			return false;
		}
		$query = 'SELECT * FROM partner_connector_' . strtolower($partner_identifying['partner_connector'])
		.		 " WHERE partner_id='" . $partner_identifying['partner_id'] . "'"
		;
		if( !$connector_detail = $this->_db->getRow($query) )
		{
			return false;
		}
		$partner_identifying['connector'] = $connector_detail;
		return $partner_identifying;
	}

	function getPartnerDetailFromName( $partner_name, $user_id )
	{
		$query = 'SELECT * FROM partner_identifying'
		.		 " WHERE partner_org_name='" . mysql_real_escape_string($partner_name) . "'"
		.		 " AND user_id='" . (int)$user_id . "'"
		;
		if( !$partner_identifying = $this->_db->getRow($query) )
		{
			return false;
		}
		$query = 'SELECT * FROM partner_connector_' . strtolower($partner_identifying['partner_connector'])
		.		 " WHERE partner_id='" . $partner_identifying['partner_id'] . "'"
		;
		if( !$connector_detail = $this->_db->getRow($query) )
		{
			return false;
		}
		$partner_identifying['connector'] = $connector_detail;
		return $partner_identifying;
	}

	function getPartnerDetailByMessageFrom( $message_from, $user_id )
	{
		$query = 'SELECT * FROM partner_identifying'
		.		" WHERE partner_email='" . mysql_real_escape_string($message_from) . "'"
		.		" AND user_id='" . (int)$user_id . "'"
		;
		if( !$partner_identifying = $this->_db->getRow($query) )
		{
			return false;
		}
		return $partner_identifying;
	}

	function getPartnerDetailByMessageTo( $message_to, $user_id )
	{
		$query = 'SELECT * FROM partner_identifying'
		.		" WHERE partner_email='" . mysql_real_escape_string($message_to) . "'"
		.		" AND user_id='" . (int)$user_id . "'"
		;
		if( !$partner_identifying = $this->_db->getRow($query) )
		{
			return false;
		}
		return $partner_identifying;
	}
	
	function getConnectorTypeByMessageName( $message_name )
	{
	    $splited_filename = explode('.', $message_name);
	    $type_file = $splited_filename[count($splited_filename)-1];
	    switch($type_file) {
	        case 'as2':
	        case 'payload_0':
	            // File is from AS2
	            $type_transport = 'AS2';
	            break;
	             
	        case 'original':
	        case 'oftp2':
	            // File is from OFTP2
	            $type_transport = 'OFTP2';
	            break;
	    }
        return $type_transport;	     
	}

 	function translateEdiMessage($start_format, $dest_format, $message)
	{
		list( $start_edi_format, $start_edi_version ) = explode( ',', $start_format );
		list( $dest_edi_format, $dest_edi_version ) = explode( ',', $dest_format );
		require_once __DIR_CLASSES__ . 'class.ediparser.php';
		$translator	= new EDI($message, 'string');
		return $translator->ediTranslateTo(strtoupper($dest_edi_format), strtoupper($dest_edi_version));
	}

	function sendAS2Message( $user_id, $partner_data, $message_data, $original_message=false )
	{
		// Get AS2 connector data for user
		$query = 'SELECT partner_identifying.*, partner_connector_as2.* FROM partner_identifying'
		.		 ' LEFT JOIN partner_connector_as2 ON partner_connector_as2.partner_id=partner_identifying.partner_id'
		.		 " WHERE partner_identifying.user_id='" . (int)$user_id . "'"
		.		 " AND partner_identifying.default_profile='1'"
		;
		if( !$user_connector = $this->_db->getRow($query) )
		throw new AS2Exception ( 'Unable to select Connector for ' . $partner_data['partner_org_name'] );

		include_once __DIR_CLASSES__ . 'As2/AS2Constants.php';
		$params = array('partner_from'  => $user_connector['partner_name'] . '#' . $partner_data['connector']['partner_name'],
						'partner_to'    => $partner_data['connector']['partner_name'] . '#' . $user_connector['partner_name']);

		// Save message in Outbox dir
		$filename 	= AS2Message::buildFileName();
		$message_id	= AS2Message::saveOutboxMessage(false, $params['partner_from'], $params['partner_to'], '', '', '', $message_data, $filename, 'payload');
		$message = new AS2Message(false, $params);
		$message->addFile(AS2_DIR_CUSTOMERS . $user_id . '/Outbox/' . $filename . '.payload');

		$message->encode();

		if($message->isSigned())
		{
			$message_id = AS2Message::saveOutboxMessage($message_id, $params['partner_from'], $params['partner_to'], '', '', '', file_get_contents($message->signed_file), $filename, 'signed');
		}
		if($message->isCrypted())
		{
			$message_id = AS2Message::saveOutboxMessage($message_id, $params['partner_from'], $params['partner_to'], $message->getHeader('message-id'), '', $message->getHeaders()->toFormattedArray(), file_get_contents($message->encrypted_file), $filename, 'raw');
		}
		$client = new AS2Client();
		$result = $client->sendRequest($message);
	}

	function getEdiTempFileName()
	{
		list($micro, ) = explode(' ', microtime());
		$micro = str_pad(round($micro * 1000), 3, '0');
		$host = ($_SERVER['REMOTE_ADDR']?$_SERVER['REMOTE_ADDR']:'unknownhost');
		$filename = date('YmdHis') . $micro . '_' . $host . '.as2';
		return $filename;
	}
}
