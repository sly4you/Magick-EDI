<?php
/**
 * @version		$Id: filterinput.php 9422 2007-11-23 18:56:44Z tcp $
 * @package		Joomla.Framework
 * @subpackage	Filter
 * @copyright	Copyright (C) 2005 - 2007 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// Check to ensure this file is within the rest of the framework
defined( '_EASY_WEB_' ) or die ( 'UNAUTHORIZED TO GET RESOURCE' );

/**
 * JFilterInout is a class for filtering input from any data source
 *
 * Forked from the php input filter library by: Daniel Morris <dan@rootcube.com>
 * Original Contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider, Chris Tobin and Andrew Eddie.
 *
 * @author		Louis Landry <louis.landry@joomla.org>
 * @package 	Joomla.Framework
 * @subpackage		Filter
 * @since		1.5
 */
class FilterInput
{
	var $table_chars_string = '';
	var $_errorObj = false;
	
	/**
	 * Constructor for inputFilter class. Only first parameter is required.
	 *
	 * @access	protected
	 * @param	array	$tagsArray	list of user-defined tags
	 * @param	array	$attrArray	list of user-defined attributes
	 * @param	int		$tagsMethod	WhiteList method = 0, BlackList method = 1
	 * @param	int		$attrMethod	WhiteList method = 0, BlackList method = 1
	 * @param	int		$xssAuto	Only auto clean essentials = 0, Allow clean blacklisted tags/attr = 1
	 * @since	1.5
	 */
	
	function __construct($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1)
	{
	}
	
	/**
	 * Returns a reference to an input filter object, only creating it if it doesn't already exist.
	 *
	 * This method must be invoked as:
	 * 		<pre>  $filter = & ISPMFilterInput::getInstance();</pre>
	 *
	 * @static
	 * @param	array	$tagsArray	list of user-defined tags
	 * @param	array	$attrArray	list of user-defined attributes
	 * @param	int		$tagsMethod	WhiteList method = 0, BlackList method = 1
	 * @param	int		$attrMethod	WhiteList method = 0, BlackList method = 1
	 * @param	int		$xssAuto	Only auto clean essentials = 0, Allow clean blacklisted tags/attr = 1
	 * @return	object	The JFilterInput object.
	 * @since	1.5
	 */
	function & getInstance($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1)
	{
		static $instances;

		$sig = md5(serialize(array($tagsArray,$attrArray,$tagsMethod,$attrMethod,$xssAuto)));

		if (!isset ($instances)) {
			$instances = array();
		}

		if (empty ($instances[$sig])) {
			$instances[$sig] = new ISPMFilterInput($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
		}

		return $instances[$sig];
	}

	/**
	 * Function to check identifiyng user data
	 * 
	 * @access public
	 * @param  array $userArray list of user-defined data
	 * @return boolean
	 * 
	 */
	function checkUserIdentifying ( $userData )
	{
		$userObject = clone $userData;
		// Trim object
		$userObject = $this->trimObject($userObject);
		if( !$userObject )
		{
			$this->setError( 'WARN_BAD_OBJECT', 200);
			return false;
		}
		// Check for valid first name
		$userObject->first_name = $this->clean($userObject->first_name, 'ALNUMSP');
		if(( $userObject->first_name != $userData->first_name) || (strlen( $userObject->first_name ) < 2) )
		{
			$this->setError( 'Nome non valido', 100 );
		}

		// check for valid last name
		$userObject->last_name = $this->clean($userObject->last_name, 'ALNUMSP');
		if( ($userObject->last_name != $userData->last_name) || (strlen( $userObject->last_name ) < 2) )
		{
			$this->setError( 'Cognome non valido', 101 );
		}

		// Account type are: 0->private 1->business
		if( $userObject->business_type > 0 )
		{
		    // check for valid org name
			$userObject->org_name = $this->clean($userObject->org_name, 'ALNUMSP');
			if( ($userObject->org_name != $userData->org_name) || (strlen( $userObject->org_name ) < 2) )
			{
				$this->setError( 'Ragione Sociale non valida', 102 );
			}
			if( $userObject->country_code == 'IT' )
			{
				$userObject->vat = $this->clean($userObject->vat, 'NUM');
				if( ($userObject->vat != $userData->vat) || (strlen($userObject->vat) != 11) )
				{
					$this->setError( 'P. I.V.A. non valida', 103 );
				}
			}
			else 
			{
				$userObject->vat = $this->clean($userObject->vat, 'ALNUM');
				if( ( $userObject->vat != $userData->vat) || ( strlen($userObject->vat) == 0) )
				{
					$this->setError( 'P. I.V.A. non valida', 103 );
				}
			}
			
		}
		else 
		{
			// check for valid fiscal code
			if( $userObject->country_code == 'IT' )
			{
				$userObject->fiscal_code = $this->clean($userObject->fiscal_code, 'ALNUM');
				if( ($userObject->fiscal_code != $userData->fiscal_code) || (strlen( $userObject->fiscal_code ) != 16) )
				{
					$this->setError( 'Codice Fiscale non valido', 104 );
				}
			}
			else 
			{
				$userObject->fiscal_code = $this->clean($userObject->fiscal_code, 'ALNUM');
				if( ($userObject->fiscal_code != $userData->fiscal_code) || (strlen($userObject->fiscal_code) < 6))
				{
					$this->setError( 'Codice Fiscale non valido', 104 );
				}
				
			}
		}

		// check for valid address
		$userObject->address = $this->clean($userObject->address, 'ALNUMSP');
		if( ($userObject->address != $userData->address) || (strlen( $userObject->address ) < 5) )
		{
			$this->setError( 'Indirizzo non valido', 105 );
		}
		
		// check for postal code
		$userObject->postal_code = $this->clean($userObject->postal_code, 'ALNUM');
		if( ($userObject->postal_code != $userData->postal_code) || (strlen( $userData->postal_code ) != 5) )
		{
			$this->setError( 'Codice postale non valido', 106 );
		}
		
		// check for valid city
		$userObject->city = $this->clean($userObject->city, 'ALNUMSP');
		if( ($userObject->city != $userData->city) || (strlen( $userObject->city ) < 3) )
		{
			$this->setError( 'Città non valida', 107 );
		}
		
		// check for valid province
		$userObject->province = $this->clean($userObject->province, 'ALNUMSP');
		if( ($userObject->province != $userData->province) || (strlen( $userObject->province ) != 2) )
		{
			$this->setError( 'Provincia non valida', 108 );
		}

		// check for valid country code
		$userObject->country_code = $this->clean($userObject->country_code, 'ALNUMSP');
		if( ($userObject->country_code != $userData->country_code) || (strlen( $userObject->country_code ) != 2) )
		{
			$this->setError( 'Nazione non valida', 109 );
		}
		
		// check for valid phone
		$userObject->phone = $this->clean($userObject->phone, 'NUM');
		if( ($userObject->phone != $userData->phone) || (strlen( $userObject->phone ) < 5) )
		{
			$this->setError( 'Telefono non valido', 110 );
		}
		
		// check for valid fax
		if( $userObject->fax != '' )
		{
			$userObject->fax = $this->clean($userObject->fax, 'NUM');
			if( ($userObject->fax != $userData->fax) ||  (strlen( $userObject->fax ) < 5) )
			{
				$this->setError( 'Fax non valido', 111 );
			}
		}

		if (!$this->isEmailAddress($userObject->email))
		{
			$this->setError( 'Email non valida', 112 );
		}

		// If $this->user_id is false, $this->check(); is called for a new insert.
		// Because username and password are generated BEFORE data insert in identifying Db table,
		// jump username and password check in case of update user operation!
		/*
		if ($this->user_id)
		{
			// check for valid username
			if(!$this->checkUsername ($this->username)) 
			{
				$this->setError( JText::_( 'WARNREG_USERNAME'), 113 );
			}
			
			// check for valid password
			if(eregi( "[\<|\>|\"|\'|\%|\;|\(|\)|\&|\+|\-]", $this->password) || (strlen ($this->password) < 6))
			{
				$this->setError( JText::_( 'WARNREG_PASSWORD'), 114 );
			}
		}
		*/
		if ($this->getError())
		{
			return false;
		}
		return $userObject;
	}
	
	/**
	 * Function checkPassword
	 * 
	 * Check is give password is valid
	 * 
	 */
	function checkPassword( $password )
	{
		$check_password = $this->clean($password, 'ALNUM');
		if( ($check_password != $password) || (strlen( $password ) < 6) || (strlen( $password ) >10) )
		{
			$this->setError( 'Password non valida', 106 );
			return false;
		}
		return $password;
	}
		
	/**
	 * Function checkContract
	 * 
	 * Check if customer accept contractual condition
	 * 
	 */
	function checkContract( $contractData )
	{
		$contractObject = clone $contractData;
		// Trim object
		$contractObject = $this->trimObject( $contractObject );
		if( !$contractObject )
		{
			$this->setError( 'WARN_BAD_OBJECT', 200 );
			return false;
		}		
		// Check if privacy condition are accepted by user
		if ( $contractObject->privacy_accept != 1 )
		{
			$this->setError( 'Condizioni Privacy non accettate', 120 );
		}
		// Check if general contract condition are accepted by user
		if ( $contractObject->general_accept != 1 )
		{
			$this->setError( 'Condizioni generali non accettate', 121 );
		}
		if ($this->getError())
		{
			return false;
		}
		
		return $contractObject;
	}
	
	/**
	 * Function trimObject
	 * 
	 * Trim all data in object
	 * 
	 */
	function trimObject ($obj)
	{
		if (!is_object($obj))
		{
			return false;
		}
		if (sizeof($obj) < 1)
		{
			return false;
		}
		$obj_copy = $obj;
		foreach ($obj as $key => $single_value) {
			$obj_copy->$key = trim( $single_value );
		}
		return $obj_copy;
	}


	/**
	 * Method to be called by another php script. Processes for XSS and
	 * specified bad code.
	 *
	 * @access	public
	 * @param	mixed	$source	Input string/array-of-string to be 'cleaned'
	 * @param	string	$type	Return type for the variable (INT, FLOAT, BOOLEAN, WORD, ALNUM, CMD, BASE64, STRING, ARRAY, PATH, NONE)
	 * @return	mixed	'Cleaned' version of input parameter
	 * @since	1.5
	 * @static
	 */
	function clean($source, $type='string')
	{
		// Handle the type constraint
		switch (strtoupper($type))
		{
			case 'INT' :
			case 'INTEGER' :
				// Only use the first integer value
				preg_match('/-?[0-9]+/', (string) $source, $matches);
				$result = @ (int) $matches[0];
				break;

			case 'FLOAT' :
			case 'DOUBLE' :
				// Only use the first floating point value
				preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $source, $matches);
				$result = @ (float) $matches[0];
				break;

			case 'BOOL' :
			case 'BOOLEAN' :
				$result = (bool) $source;
				break;

			case 'WORD' :
				$result = (string) preg_replace( '/[^A-Z_]/i', '', $source );
				break;

			case 'ALNUMSP' :
				$result = (string) preg_replace( '/[^A-Z0-9_&\, \.-]/i', '', $source );
				break;

			case 'ALNUM' :
				$result = (string) preg_replace( '/[^A-Z0-9]/i', '', $source );
				break;

			case 'NUM' :
				$result = (string) preg_replace( '/[^0-9]/i', '', $source );
				break;

			case 'CMD' :
				$result = (string) preg_replace( '/[^A-Z0-9_\.-]/i', '', $source );
				$result = ltrim($result, '.');
				break;

			case 'BASE64' :
				$result = (string) preg_replace( '/[^A-Z0-9\/+=]/i', '', $source );
				break;

			case 'ARRAY' :
				$result = (array) $source;
				break;

			case 'PATH' :
				$pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
				preg_match($pattern, (string) $source, $matches);
				$result = @ (string) $matches[0];
				break;

			case 'USERNAME' :
				$result = (string) preg_replace( '/[\x00-\x1F\x7F<>"\'%&]/', '', $source );
				break;
		}
		return $result;
	}
	
	/**
	 * Verifies that the string is in a proper e-mail address format.
	 *
	 * @static
	 * @param string $email String to be verified.
	 * @return boolean True if string has the correct format; false otherwise.
	 * @since 1.5
	 */
	function isEmailAddress($email)
	{

		// Split the email into a local and domain
		$atIndex	= strrpos($email, "@");
		$domain		= substr($email, $atIndex+1);
		$local		= substr($email, 0, $atIndex);

		// Check Length of domain
		$domainLen	= strlen($domain);
		if ($domainLen < 1 || $domainLen > 255) {
			return false;
		}

		// Check the local address
		// We're a bit more conservative about what constitutes a "legal" address, that is, A-Za-z0-9!#$%&\'*+/=?^_`{|}~-
		$allowed	= 'A-Za-z0-9!#&*+=?_-';
		$regex		= "/^[$allowed][\.$allowed]{0,63}$/";
		if ( ! preg_match($regex, $local) ) {
			return false;
		}

		// No problem if the domain looks like an IP address, ish
		$regex		= '/^[0-9\.]+$/';
		if ( preg_match($regex, $domain)) {
			return true;
		}

		// Check Lengths
		$localLen	= strlen($local);
		if ($localLen < 1 || $localLen > 64) {
			return false;
		}

		// Check the domain
		$domain_array	= explode(".", $domain);
		$regex		= '/^[A-Za-z0-9-]{0,63}$/';
		foreach ($domain_array as $domain ) {

			// Must be something
			if ( ! $domain ) {
				return false;
			}

			// Check for invalid characters
			if ( ! preg_match($regex, $domain) ) {
				return false;
			}

			// Check for a dash at the beginning of the domain
			if ( strpos($domain, '-' ) === 0 ) {
				return false;
			}

			// Check for a dash at the end of the domain
			$length = strlen($domain) -1;
			if ( strpos($domain, '-', $length ) === $length ) {
				return false;
			}

		}

		return true;
	}

	/**
	 * Set an error message
	 *
	 * Use this method in preference of accessing the $_error attribute directly!
	 * 
	 * @param	string $error Error message
	 * @access	public
	 * @since	1.5
	 * @todo 	Change dependent code to call the API, not access $_error directly
	 */
	function setError ($error_desc, $error_num=false)
	{
		$this->_errorObj->$error_num = $error_desc;
	}

	/**
	 * Get error
	 * 
	 * With this function get error group in object called _errorObj 
	 *
	 * @param	int		Not Used
	 * @param	boolean	Not Used
	 * @return	object	Error message
	 * @access	public
	 * @since	1.5
	 * @todo 	Change dependent code to call the API, not access $_error directly
	 */
	function getError()
	{
		return $this->_errorObj;
	}

}