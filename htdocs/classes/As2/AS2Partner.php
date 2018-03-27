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

class AS2Partner {
	// general information
	protected $is_local = false;
	protected $user_id  = '';
	protected $name     = '';
	protected $id       = '';
	protected $email    = '';
	protected $comment  = '';

	// security
	protected $sec_pkcs12               = ''; // must contain private/certificate/ca chain
	protected $sec_pkcs12_password      = '';

	protected $sec_certificate          = ''; // must contain certificate/ca chain

	protected $sec_signature_algorithm  = self::SIGN_SHA1;
	protected $sec_encrypt_algorithm    = self::CRYPT_3DES;

	// sending data
	protected $send_compress            = false;
	protected $send_url                 = ''; // full url including "http://" or "https://"
	protected $send_subject             = 'AS2 Message Subject';
	protected $send_content_type        = 'application/EDI-Consent';
	protected $send_credencial_method   = self::METHOD_NONE;
	protected $send_credencial_login    = '';
	protected $send_credencial_password = '';
	protected $send_encoding            = self::ENCODING_BASE64;

	// notification process
	protected $mdn_url                  = '';
	protected $mdn_subject              = 'AS2 MDN Subject';
	protected $mdn_request              = self::ACK_SYNC;
	protected $mdn_signed               = true;
	protected $mdn_credencial_method    = self::METHOD_NONE;
	protected $mdn_credencial_login     = '';
	protected $mdn_credencial_password  = '';

	// event trigger connector
	protected $connector_class          = 'AS2Connector';

	//
	protected static $stack = array();

	// security methods
	const METHOD_NONE   = 'NONE';
	const METHOD_AUTO   = CURLAUTH_ANY;
	const METHOD_BASIC  = CURLAUTH_BASIC;
	const METHOD_DIGECT = CURLAUTH_DIGEST;
	const METHOD_NTLM   = CURLAUTH_NTLM;
	const METHOD_GSS    = CURLAUTH_GSSNEGOTIATE;

	// transfert content encoding
	const ENCODING_BASE64 = 'base64';
	const ENCODING_BINARY = 'binary';

	// ack methods
	const ACK_SYNC  = 'SYNC';
	const ACK_ASYNC = 'ASYNC';

	//
	const SIGN_NONE = 'none';
	const SIGN_SHA1 = 'sha1';
	const SIGN_MD5  = 'md5';

	// http://www.openssl.org/docs/apps/enc.html#SUPPORTED_CIPHERS
	const CRYPT_NONE    = 'none';
	const CRYPT_RC2_40  = 'rc2-40'; // default
	const CRYPT_RC2_64  = 'rc2-64';
	const CRYPT_RC2_128 = 'rc2-128';
	const CRYPT_DES     = 'des';
	const CRYPT_3DES    = 'des3';
	const CRYPT_AES_128 = 'aes128';
	const CRYPT_AES_192 = 'aes192';
	const CRYPT_AES_256 = 'aes256';

	/**
     * Return the list of available signatures
     * 
     * @return array
     */
	public static function getAvailablesSignatures()
	{
		return array('NONE' => self::SIGN_NONE,
		'SHA1' => self::SIGN_SHA1,
		);
	}

	/**
     * Return the list of available cypher
     * 
     * @return array
     */
	public static function getAvailablesEncryptions()
	{
		return array('NONE'    => self::CRYPT_NONE,
		'RC2_40'  => self::CRYPT_RC2_40,
		'RC2_64'  => self::CRYPT_RC2_64,
		'RC2_128' => self::CRYPT_RC2_128,
		'DES'     => self::CRYPT_DES,
		'3DES'    => self::CRYPT_3DES,
		'AES_128' => self::CRYPT_AES_128,
		'AES_192' => self::CRYPT_AES_192,
		'AES_256' => self::CRYPT_AES_256,
		);
	}

	/**
     * Return an AS2Partner object for a specified Partner ID
     * 
     * @param partner_id   String : Partner ID (case sensitive) corresponds to AS2-To / AS2-From headers
     * @param reload       Boolean : Allow to reload config from file
     * 
     * @return                object : The partner requested
     */
	public static function getPartner($partner_id, $reload = false)
	{
		if ($partner_id instanceof AS2Partner)
		return $partner_id;
		$partner_id = str_replace( '"', '', $partner_id );
		// Check if exists a partner from/to combination
		if(!$partner_data = self::getPartnerCombination($partner_id))
		{
			throw new AS2Exception('The partner ' . $partner_id . " does not exists or are not active.");
		}
		// Set value for local or remote user
		if( $partner_data['partner_local'] == '1')
		$partner_data['partner_local'] = true;
		else
		$partner_data['partner_local'] = false;
		if( strlen($partner_data['partner_sec_pkcs12']) > 0 )
		$partner_data['partner_sec_pkcs12'] = AS2_DIR_CUSTOMERS . $partner_data['user_id'] . '/certs/' . $partner_data['partner_sec_pkcs12'];

		if( strlen($partner_data['partner_sec_certificate']) > 0 )
		$partner_data['partner_sec_certificate'] = AS2_DIR_CUSTOMERS . $partner_data['user_id'] . '/certs/' . $partner_data['partner_sec_certificate'];

		$data = array(  'is_local'                  => $partner_data['partner_local'], // Remote or Local
		'user_id'                   => $partner_data['user_id'],
		'name'                      => $partner_data['partner_name'],
		'id'                        => $partner_data['partner_name'],
		'email'                     => $partner_data['partner_email'],
		'comment'  =>               '',
		// security
		'sec_pkcs12'                => $partner_data['partner_sec_pkcs12'],
		'sec_pkcs12_password'       => $partner_data['partner_sec_pkcs12_password'],
		'sec_certificate'           => $partner_data['partner_sec_certificate'],
		'sec_signature_algorithm'   => strtolower($partner_data['partner_sec_signature_algorithm']),
		'sec_encrypt_algorithm'     => strtolower($partner_data['partner_sec_encrypt_algoritm']),
		'send_url'                  => $partner_data['partner_send_url'],
		'send_credencial_method'    => $partner_data['partner_send_credencial_method'],
		'send_credencial_login'     => $partner_data['partner_send_credencial_login'],
		'send_credencial_password'  => $partner_data['partner_send_credencial_password'],
		// notification process
		'mdn_request'               => strtoupper($partner_data['partner_mdn_request']),
		);
		// create new instance
		$partner = new self($data);
		// put into stack for fast access
		self::$stack[$partner_id] = $partner;
		return $partner;



		/**
         * ********* OLD METHOD WITH FILE READ ***************
         */
		/*
		$partner_id = trim($partner_id, '"');

		// existance file check (caution : Partner name is case sensitive)
		$conf = AS2_DIR_PARTNERS . basename($partner_id) . '.conf';
		if (!file_exists($conf)) throw new AS2Exception('The partner doesn\'t exist : "' . $partner_id . '".');

		// get from stack instance
		if (!$reload && isset(self::$stack[$partner_id])){
		return self::$stack[$partner_id];
		}

		// loading config file
		$data = array();
		include $conf;

		// parse and build object
		if (is_array($data) && count($data)){
		// create new instance
		$partner = new self($data);

		// put into stack for fast access
		self::$stack[$partner_id] = $partner;
		return $partner;
		}

		// error if not found
		throw new AS2Exception('The partner profile isn\'t correctly loaded.');
		*/
	}

	function getPartnerCombination( $partner_combination )
	{
		list($partner_from, $partner_to) = explode('#', str_replace('"', '', $partner_combination) );
		return self::checkPartnerCombination($partner_from, $partner_to);
	}


	function checkPartnerCombination( $partner_from, $partner_to )
	{
		$dbObj = new DbOperation();
		$db = $dbObj->_db;
		// Get partner from db table. Partner are passed with combination partnerfrom#partnerto

		$partner_from   = str_replace('"', '', $partner_from);
		$partner_to     = str_replace('"', '', $partner_to);

		// This class are called from all AS2 system and get partner id simple
		// by partnerName value, create and return value.
		// This work fine until as2 connector work with only one customer.
		// Because Edi Magick Project was designed to work in multi-user environment,
		// to work fine must:
		// 1) Check user validity
		// 2) Check if combination from/to are valid for single customer
		// 3) Different messagges repository for customer
		// Prof-of-concept:
		// When system call AS2Partner class to extract AS2 customer data, system check
		// partner FROM existance.
		// If in DB are more that one partner, means that more of customer work with this
		// remote as system. This is possible, yes.
		// If system can't have possibility to distinct under which customer message sended
		// must be saved, scenario are that external system send message, and local system
		// save it under first customer saved in db.

		// Check how many AS2 customer profile with same name are saved in system
		$query = 'SELECT COUNT(*) as Total FROM partner_connector_as2'
		.		 " WHERE partner_name='" . mysql_real_escape_string($partner_from) . "'"
		;
		$total = $db->Execute($query);
		switch($total->fields['Total'])
		{
			// One customer profile found in system
			case '1':
				// Get customer profile, check if user are active and check if partner_to
				// exists under same user
				$query = 'SELECT partner_connector_as2.*, partner_identifying.*, user_authentication.status FROM partner_connector_as2'
				.		 ' LEFT JOIN partner_identifying ON partner_identifying.partner_id=partner_connector_as2.partner_id'
				.		 ' LEFT JOIN user_authentication ON user_authentication.user_id=partner_identifying.user_id'
				.		 " WHERE partner_connector_as2.partner_name='" . mysql_real_escape_string($partner_from) . "'"
				.		 " AND user_authentication.status='1'"
				;
				if(!$partner_from_data = $db->getRow($query))
				{
					return false;
				}
				// O.K., profile exists. Check if partner_to profile exists
				$query = 'SELECT partner_connector_as2.*, partner_identifying.* FROM partner_connector_as2'
				.		 ' LEFT JOIN partner_identifying ON partner_identifying.partner_id=partner_connector_as2.partner_id'
				.		 " WHERE partner_connector_as2.partner_name='" . mysql_real_escape_string($partner_to) . "'"
				.		 " AND partner_identifying.user_id='" . $partner_from_data['user_id'] . "'"
				;
				if(!$partner_to_data = $db->getRow($query))
				{
					return false;
				}
				return $partner_from_data;
				break;
				// Customer profile does not exists
			case '0':
				return false;
				break;
				// More Customer profiles founds in system
			default:
				// This case are possible only when remote customer call local system, because
				// Edi Magick assig always different stationId for any customer
				$query = 'SELECT partner_connector_as2.*, partner_identifying.*, user_authentication.user_id FROM partner_connector_as2'
				.		 ' LEFT JOIN partner_identifying ON partner_identifying.partner_id=partner_connector_as2.partner_id'
				.		 ' LEFT JOIN user_authentication ON user_authentication.user_id=partner_identifying.user_id'
				.		 " WHERE partner_connector_as2.partner_name='" . mysql_real_escape_string($partner_to) . "'"
				.		 " AND user_authentication.status='1'"
				;
				// If partner to does not exist, return false
				if(!$partner_to_data = $db->getRow($query))
				{
					return false;
				}
				// O.K., partner exist. Now, get partner_from data associated at user
				$query = 'SELECT partner_connector_as2.*, partner_identifying.* FROM partner_connector_as2'
				.		 ' LEFT JOIN partner_identifying ON partner_identifying.partner_id=partner_connector_as2.partner_id'
				.		 " WHERE partner_connector_as2.partner_name='" . mysql_real_escape_string($partner_from) . "'"
				.		 " AND partner_identifying.user_id='" . $partner_to_data['user_id'] . "'"
				;
				if(!$partner_from_data = $db->getRow($query))
				{
					return false;
				}
				return $partner_to_data;
				break;
		}
		// Check if
	}
	/**
     * Restricted constructor
     * 
     * @param data       The data to set from
     */
	protected function __construct($data)
	{
		// set properties with data
		foreach($data as $key => $value){
			if (!property_exists($this, $key) || is_null($value))
			continue;

			$this->$key = $value;
		}
	}

	/**
     * Magic getter
     * 
     * @param key    Property name
     * 
     * @return Return a property of this class
     */
	public function __get($key){
		if (property_exists($this, $key))
		return $this->$key;
		else
		return null; // for strict processes : throw new Exception
	}

	/**
     * Magic setter
     * 
     * @param key      Property name
     * @param value    New value to set
     * 
     */
	public function __set($key, $value){
		if (property_exists($this, $key))
		$this->$key = $value;
		// for strict processes : throw new Exception if property doesn't exists
	}

	/**
     * Magic method
     * 
     */
	public function __toString(){
		return $this->id;
	}
}
