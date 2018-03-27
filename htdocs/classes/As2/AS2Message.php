<?php

/**
 * AS2Secure - PHP Lib for AS2 message encoding / decoding
 * 
 * @author  Sebastien MALOT <contact@as2secure.com>
 * 
 * @copyright Copyright (c) 2010, Sebastien MALOT
 * 
 * Last release at : {@link http://www.as2secure.com}
 * 
 * This file is part of AS2Secure Project.
 *
 * AS2Secure is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AS2Secure is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AS2Secure.
 * 
 * @license http://www.gnu.org/licenses/lgpl-3.0.html GNU General Public License
 * @version 0.9.0
 * 
 */

class AS2Message extends AS2Abstract {

	protected $mic_checksum = false;

	public function __construct($data, $params = array()) {
		parent::__construct($data, $params);

		if ($data instanceof AS2Request){
			$this->path = $data->getPath();
		}
		elseif ($data instanceof Horde_MIME_Part){
			$this->path = AS2Adapter::getTempFilename();
			file_put_contents($this->path, $data->toString(true));
		}
		elseif ($data){
			if (!isset($params['is_file']) || $params['is_file'])
			$this->addFile($data, '', '', true);
			else
			$this->addFile($data, '', '', false);
		}

		if (isset($params['mic'])){
			$this->mic_checksum = $params['mic'];
		}
	}

	/**
     * Add file to the message
     * 
     * @param string  $data        The content or the file
     * @param string  $mimetype    The mimetype of the message
     * @param boolean $is_file     If file
     * @param string  $encoding    The encoding to use for transfert
     * 
     * @return boolean
     */
	public function addFile($data, $mimetype = '', $filename = '', $is_file = true, $encoding = 'base64'){
		if (!$is_file){
			$file    = AS2Adapter::getTempFilename();
			AS2Log::info(false, 'AddFile: create new AS2 message. File name are: ' . $file);
			file_put_contents($file, $data);
			AS2Log::info(false, 'AddFile: Putting content data into file success. Finish addFile');
			$data    = $file;
			$is_file = true;
		}
		else {
			if (!$filename) $filename = basename($data);
		}

		if (!$mimetype) $mimetype = AS2Adapter::detectMimeType($data);

		AS2Log::info(false, 'AddFile: mimetype are: ' . $mimetype);
		AS2Log::info(false, 'AddFile: encoding are: ' . $encoding);

		$this->files[] = array('path'     => $data,
		'mimetype' => $mimetype,
		'filename' => $filename,
		'encoding' => $encoding);

		return true;
	}

	/**
     * Return files which compose the message (should contain at least one file)
     * 
     * @return array
     */
	public function getFiles(){
		return $this->files;
	}

	/**
     * Return the last calculated checksum
     * 
     * @return string
     */
	public function getMicChecksum() {
		return $this->mic_checksum;
	}

	/**
     * Return the url to send message
     * 
     * @return string
     */
	public function getUrl() {
		return $this->getPartnerTo()->send_url;
	}

	/**
     * Return the authentication to use to send message to the partner
     * 
     * @return array
     */
	public function getAuthentication() {
		return array('method'   => $this->getPartnerTo()->send_credencial_method,
		'login'    => $this->getPartnerTo()->send_credencial_login,
		'password' => $this->getPartnerTo()->send_credencial_password);
	}

	/**
     * Build message and encode it (signing and/or crypting)
     * 
     */
	public function encode() {
		if (!$this->getPartnerFrom() instanceof AS2Partner || !$this->getPartnerTo() instanceof AS2Partner)
		{
			AS2Log::error(false, 'EncodeFile: Partner from or Partner to object malformed');
			throw new AS2Exception('Object not properly initialized');
		}
		// initialisation
		$this->mic_checksum = false;
		$this->setMessageId(self::generateMessageID($this->getPartnerFrom()));
		AS2Log::info(false, 'EncodeFile: generate message ID: ' . $this->getMessageId() );


		// chargement et construction du message
		$files = $this->getFiles();

		// initial message creation : mime_part
		// TODO : use adapter to build multipart file
		try {
			AS2Log::info(false, 'EncodeFile: initial message creation. Build mime part' );
			// managing all files (parts)
			$parts = array();
			foreach($files as $file){
				$mime_part = new Horde_MIME_Part($file['mimetype']);
				$mime_part->setContents(file_get_contents($file['path']));
				$mime_part->setName($file['filename']);
				if ($file['encoding'])
				$mime_part->setTransferEncoding($file['encoding']);

				$parts[] = $mime_part;
			}
			if (count($parts) > 1){
				AS2Log::info(false, 'EncodeFile: Message have more that one part. Build multipart/mixed' );
				// handling multipart file
				$mime_part = new Horde_MIME_Part('multipart/mixed');
				foreach($parts as $part)
				$mime_part->addPart($part);
			}
			else{
				// handling mono part (body)
				$mime_part = $parts[0];
				AS2Log::info(false, 'EncodeFile: Message have one part. Build monopart body' );
			}

			$file = AS2Adapter::getTempFilename();
			file_put_contents($file, $mime_part->toString());
			AS2Log::info(false, 'EncodeFile: assembling file success. Now real encode' );
		}
		catch(Exception $e)
		{
			AS2Log::error(false, 'EncodeFile: ' . $e );
			throw $e;
			return false;
		}

		// signing file if wanted by Partner_To
		if ($this->getPartnerTo()->sec_signature_algorithm != AS2Partner::SIGN_NONE) {
			AS2Log::info(false, 'EncodeFile: Partner have signature algorithm' . $this->getPartnerTo()->sec_signature_algorithm );
			AS2Log::info(false, 'EncodeFile: Try sign message');
			try {
				$file = $this->adapter->sign($file, $this->getPartnerTo()->send_compress, $this->getPartnerTo()->send_encoding);
				$this->is_signed = true;
				$this->signed_file = $file;
				//echo file_get_contents($file);
				$this->mic_checksum = AS2Adapter::getMicChecksum($file);
				AS2Log::info(false, 'EncodeFile: File encoded with success');
			}
			catch(Exception $e) {
				AS2Log::error(false, 'EncodeFile: signature operation failed. ' . $e);
				throw $e;
				return false;
			}
		}
		// crypting file if wanted by Partner_To
		if ($this->getPartnerTo()->sec_encrypt_algorithm != AS2Partner::CRYPT_NONE) {
			AS2Log::info(false, 'EncodeFile: Partner have encrypt algorithm' . $this->getPartnerTo()->sec_encrypt_algorithm );
			AS2Log::info(false, 'EncodeFile: Try encrypt message');
			try {
				$file = $this->adapter->encrypt($file);
				$this->is_crypted = true;
				$this->encrypted_file = $file;
				AS2Log::info(false, 'EncodeFile: File encrypted with success');
			}
			catch(Exception $e) {
				AS2Log::error(false, 'EncodeFile: encrypt operation failed' . $e);
				throw $e;
				return false;
			}
		}

		$this->path = $file;
		/*if ($mime_part->getTransferEncoding() == 'base64'){
		file_put_contents($this->path, base64_decode($mime_part->toString(false)));
		}
		else{
		file_put_contents($this->path, $mime_part->toString());
		}*/
		AS2Log::info(false, 'EncodeFile: set message header');
		// headers setup
		$headers = array(
		'AS2-From'                     => $this->getPartnerFrom()->id,
		'AS2-To'                       => $this->getPartnerTo()->id,
		'AS2-Version'                  => '1.0',
		'From'                         => $this->getPartnerFrom()->email,
		'Subject'                      => $this->getPartnerFrom()->send_subject,
		'Message-ID'                   => $this->getMessageId(),
		'Mime-Version'                 => '1.0',
		'Disposition-Notification-To'  => $this->getPartnerFrom()->send_url,
		'Recipient-Address'            => $this->getPartnerTo()->send_url,
		'User-Agent'                   => 'AS2Secure - PHP Lib for AS2 message encoding / decoding',
		);

		if ($this->getPartnerTo()->mdn_signed) {
			$headers['Disposition-Notification-Options'] = 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha1';
		}
		AS2Log::info(false, 'EncodeFile: Disposition-Notification-Options set to: ' . $headers['Disposition-Notification-Options']);

		if ($this->getPartnerTo()->mdn_request == AS2Partner::ACK_ASYNC) {
			$headers['Receipt-Delivery-Option'] = $this->getPartnerFrom()->send_url;
		}
		AS2Log::info(false, 'EncodeFile: Receipt-Delivery-Option set to: ' . $headers['Receipt-Delivery-Option']);

		$this->headers = new AS2Header($headers);

		// look for additionnal headers from message
		// eg : content-type
		$content = file_get_contents($this->path);
		$this->headers->addHeadersFromMessage($content);
		if (strpos($content, "\n\n") !== false) $content = substr($content, strpos($content, "\n\n") + 2);
		file_put_contents($this->path, $content);

		AS2Log::info(false, 'EncodeFile: set message header ended. EncodeFile finish.');
		return true;
	}

	/**
     * Decode message extracting files from message
     * 
     * @return array    List of files extracted
     */
	public function decode() {
		$this->files = $this->adapter->extract($this->getPath());

		return true;
	}

	/**
     * Generate a MDN from the message
     * 
     * @param object  $exception   The exception if error handled
     * 
     * @return object              The MDN generated
     */
	public function generateMDN($exception = null) {
		$mdn = new AS2MDN($this);

		$message_id = $this->getHeader('message-id');
		$partner    = $this->getPartnerTo()->id;
		$mic        = $this->getMicChecksum();

		$mdn->setAttribute('Original-Recipient',  'rfc822; "' . $partner . '"');
		$mdn->setAttribute('Final-Recipient',     'rfc822; "' . $partner . '"');
		$mdn->setAttribute('Original-Message-ID', $message_id);
		if ($mic)
		$mdn->setAttribute('Received-Content-MIC', $mic);

		if (is_null($exception)){
			$mdn->setMessage('The AS2 message has been received.');
			$mdn->setAttribute('Disposition-Type', 'processed');
		}
		else {
			if (!$exception instanceof AS2Exception)
			$exception = new AS2Exception($exception->getMessage());

			$mdn->setMessage($exception->getMessage());
			$mdn->setAttribute('Disposition-Type', 'failure');
			$mdn->setAttribute('Disposition-Modifier', $exception->getLevel() . ': ' . $exception->getMessageShort());
		}

		return $mdn;
	}

	/**
	* ********** Hack by Enrico Valsecchi to save message on personalized directory
	*/



	/**
     * Save the content of the request for futur handle and/or backup
     * 
     * @param content       The content to save (mandatory)
     * @param headers       The headers to save (optional)
     * @param filename      The filename to use if known (optional)
     * @param type          Values : raw | decrypted | payload (mandatory)
     * 
     * @return       String  : The main filename
     */
	public static function saveInboxMessage($message_id, $message_from, $message_to, $original_message_id, $subject, $headers, $content, $filename, $type='raw')
	{
		umask(000);
		$partner_data_from = AS2Partner::getPartnerCombination($message_from);
		$partner_data_to = AS2Partner::getPartnerCombination($message_to);
		$dir = AS2_DIR_CUSTOMERS . $partner_data_from['user_id'] . '/Inbox/';
		$dir = realpath($dir);
		@mkdir($dir, 0777, true);

		switch($type){
			case 'raw':
				file_put_contents($dir . '/' . $filename, $content);
				if (count($headers)){
					file_put_contents( $dir . '/' . $filename . '.header', $headers );
				}
				$message_id = self::saveMessageOnDb( $partner_data_from['user_id'], $message_from, $message_to, $original_message_id, $subject, $filename, 'Inbox', date('Y-m-d H:i:s'), 1, 0, __RAW_MESSAGE_RECEIVED__ );
				break;

			case 'decrypted':
				file_put_contents($dir . '/' . $filename, $content);
				self::updateMessageOnDb( $message_id, 1, __DECRYPTED_MESSAGE_RECEIVED__ );
				break;

			case 'payload':
				file_put_contents($dir . '/' . $filename, $content);
				self::updateMessageOnDb( $message_id, 2, __DECODED_MESSAGE_RECEIVED__ );
				break;

		}
		return $message_id;
	}


	public static function saveOutboxMessage($message_id, $message_from, $message_to, $original_message_id, $subject, $headers, $content, $filename, $type='payload')
	{
		umask(000);
		$partner_data_from = AS2Partner::getPartnerCombination($message_from);
		$partner_data_to = AS2Partner::getPartnerCombination($message_to);
		$dir = realpath(AS2_DIR_CUSTOMERS . $partner_data_from['user_id'] . '/Outbox/');
		@mkdir($dir, 0777, true);

		switch($type){

			case 'payload':
				file_put_contents($dir . '/' . $filename . '.payload', $content);
				$message_id = self::saveMessageOnDb( $partner_data_from['user_id'], $message_from, $message_to, $original_message_id, $subject, $filename, 'Outbox', date('Y-m-d H:i:s'), 1, 0, __DECODED_MESSAGE_SAVED__ );
				break;

			case 'signed':
				file_put_contents($dir . '/' . $filename . 'signed', $content);
				self::updateMessageOnDb( $message_id, 1, __SIGNED_MESSAGE_SAVED__ );
				break;

			case 'raw':
				file_put_contents($dir . '/' . $filename, $content);
				if (count($headers)){
					file_put_contents($dir . '/' . $filename . '.header', implode("\n", $headers));
				}
				self::updateMessageOnDb( $message_id, 2, __RAW_MESSAGE_SAVED__, $original_message_id );
				break;

		}
		return $message_id;
	}


	public static function saveMessageOnDb( $user_id, $message_from, $message_to, $original_message_id, $message_subject, $message_name, $message_path, $message_date, $message_new, $status, $status_description )
	{
		$dbObj = new DbOperation();
		$db = $dbObj->_db;

		$query = 'INSERT INTO user_messages (user_id, message_from, message_to, original_message_id, message_subject, message_name, message_path, message_date, message_new, message_status, message_status_description)';
		$or_msg_rep = array('<','>');
		$query .= " values('" . (int)$user_id . "','"
		.			mysql_real_escape_string($message_from) . "','"
		.			mysql_real_escape_string($message_to) . "','"
		.			mysql_real_escape_string(str_replace($or_msg_rep, '', $original_message_id)) . "','"
		.			mysql_real_escape_string($message_subject) . "','"
		.			mysql_real_escape_string($message_name) . "','"
		.			mysql_real_escape_string($message_path) . "','"
		.			mysql_real_escape_string($message_date) . "','"
		.			mysql_real_escape_string($message_new) . "','"
		.			mysql_real_escape_string($status) . "','"
		.			mysql_real_escape_string($status_description) . "')"
		;
		$db->Execute($query);
		return $db->Insert_ID();
	}


	public static function updateMessageOnDb( $message_id, $message_status, $message_status_description, $original_message_id=false)
	{
		$dbObj = new DbOperation();
		$db = $dbObj->_db;
		if($original_message_id)
		{
			$or_msg_rep = array('<','>');
			$add_query = " original_message_id='" . mysql_real_escape_string(str_replace($or_msg_rep, '', $original_message_id)) . "',";
		}
		$query = 'UPDATE user_messages SET'
		.	$add_query
		.	 " message_status='" . (int)$message_status . "',"
		.	 " message_status_description='" . mysql_real_escape_string($message_status_description) . "'"
		.	 " WHERE message_id='" . (int)$message_id . "'"
		;

		$db->Execute($query);
		$message_id = $db->Insert_ID();
	}




	public static function buildFileName()
	{
		list($micro, ) = explode(' ', microtime());
		$micro = str_pad(round($micro * 1000), 3, '0');
		$host = ($_SERVER['REMOTE_ADDR']?$_SERVER['REMOTE_ADDR']:'unknownhost');
		$filename = date('YmdHis') . $micro . '_' . $host . '.as2';
		return $filename;
	}


	public static function sendReceivedMessageByEmail($user_id, $customer_org_name, $customer_email, $partner_org_name, $messagges)
	{
		require_once __DIR_CLASSES__ . 'PHPMailer/class.phpmailer.php';
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPAuth         = true;
		$mail->Host             = __EMAIL_HOST__;
		$mail->Port             = __EMAIL_HOST_PORT__;
		$mail->Username         = __EMAIL_USERNAME__;
		$mail->Password         = __EMAIL_PASSWORD__;
		$mail->SetFrom(__EMAIL_ADDRESS__, $customer_org_name);
		$mail->Subject = date('d-m-Y') . ' AS2 Edi file received';

		$mail->AddAddress($customer_email);
		$mail->Body = 'Messaggio AS2 ricevuto da ' . $partner_org_name;
		$dir = realpath(AS2_DIR_CUSTOMERS . $user_id . '/Inbox/');
		foreach( $messagges as $key => $single_message)
		{
			$mail->AddAttachment($dir . '/' . $single_message);
		}
		$mail->Send();
	}
}
