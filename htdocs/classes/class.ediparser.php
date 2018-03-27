<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of the PEAR EDI package.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT license that is available
 * through the world-wide-web at the following URI:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category  File_Formats 
 * @package   EDI
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2008 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   SVN: $Id: EDI.php,v 1.1.1.1 2008/09/14 16:22:06 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/Electronic_Data_Interchange
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Include exceptions raised by this package.
 */
require_once __DIR_EDI__ . 'EDI/Exception.php';

/**
 * This class is the main entry point of the EDI package.
 * It contains two factory methods that allows you to:
 *   - retrieve a parser to process your edi documents with the 
 *     EDI::parserFactory() method;
 *   - retrieve a new interchange to write your EDI document from scratch with
 *     the EDI::interchangeFactory() method.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    David JEAN LOUIS <izimobil@gmail.com>
 * @copyright 2008 David JEAN LOUIS
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/Electronic_Data_Interchange
 * @since     Class available since release 0.1.0
 */
final class EDI
{
	protected $euritmo_header_line = 'BGM';

	/**
	 * Function __construct
	 * 
	 * Main constructor of class
	 *
	 * @param string $edi_file
	 */
	function __construct($edi_file, $type='file')
	{
		if($type == 'file')
		{
			if( !file_exists($edi_file) )
			{
				throw new EDI_Exception( 'File ' . $edi_file . ' not found' );
			}
			$edi_string = file_get_contents($edi_file);
		}
		else
		{
			$edi_string = $edi_file;
		}
		if( strlen($edi_string) == 0 )
		{
			throw new EDI_Exception( 'File ' . $edi_file . ' appear not valid EDI document' );
		}
		// Check where format of document are passed (xml or edi), get a standard and message type
		$this->available_edi_standard	= $this->getAvailableEdiStandardAndMessageType();
		$this->edi_file					= $edi_file;
		$this->edi_string				= $edi_string;
		$edi_message_properties			= $this->validateEdiMessage( $this->edi_string );
		$this->edi_standard				= $edi_message_properties['edi_standard'];
		$this->edi_standard_type		= $edi_message_properties['edi_standard_type'];
		$this->edi_message_type			= $edi_message_properties['edi_message_type'];
		$this->edi_message_xml			= $edi_message_properties['edi_message_xml'];
	}

	/**
	 * validateEdiMessage
	 * 
	 * Check if edi file are a valid edi message
	 *
	 * @param string $edi_string
	 * @return array $result
	 */
	public function validateEdiMessage( $edi_string )
	{
		$this->edi_file_format = $this->getFileFormat( $edi_string );
		if( !method_exists($this, 'getEdiStandardFrom' . $this->edi_file_format . 'File') )
		{
			throw new EDI_Exception( 'File format ' . $this->edi_file_format . ' not know' );
		}
		return call_user_func(array( &$this,'getEdiStandardFrom' . $this->edi_file_format . 'File'), $this->edi_string);
	}

	/**
	 * getEdiStandardFromEdiFile
	 *
	 * Extract where is a Edi Standard, standard type and message type form passed edistring
	 * in edi format
	 * 
	 * @param string $edi_string
	 * @return array $result
	 */
	public function getEdiStandardFromEdiFile( $edi_string )
	{
		// If document begin chars are BGM, means that are a EANCOM Euritmo
		if (substr($edi_string, 0, 3) == $this->euritmo_header_line) {
			// Check if standard are supported
			if(array_key_exists('EDI_EANCOM_EURITMO', $this->available_edi_standard))
			{
				// Check where is a message type
				foreach($this->available_edi_standard['EDI_EANCOM_EURITMO'] as $key => $single_message_type)
				{
					$match = '/' . $single_message_type . '/i';
					if( preg_match($match, $edi_string) )
					{
						$this->parser = self::parserFactory('EANCOM');
						$this->interch = $this->parser->parseString($edi_string, strtoupper($single_message_type));
						$result['edi_standard']			= 'EANCOM';
						$result['edi_standard_type']	= 'EURITMO';
						$result['edi_message_type']		= strtoupper($single_message_type);
						$result['edi_message_xml']		= $this->interch->toXml();
						return $result;
					}
				}
			}
		}
		else
		{
			foreach ($this->available_edi_standard as $key => $single_standard_available_messages)
			{
				list($not_used, $edi_standard, $edi_standard_type) = explode('_', $key);
				// Into Edifact document, document standard type are defined with letter#delimeter#numbernumber#delimeter
				// Modify variable edi_standard_type in this mode
				$letter_edifact_type	= substr($edi_standard_type, 0,1);
				$number_edifact_type	= substr($edi_standard_type,1);
				foreach($single_standard_available_messages as $k => $single_message_type)
				{
					list($message_type, $ext) = explode('.', $single_message_type);
					$match_standard	= '/' . $message_type . ':' . $letter_edifact_type . ':' . $number_edifact_type . '/i';
					if( preg_match($match_standard, $edi_string) )
					{
						$this->parser = self::parserFactory(strtoupper($edi_standard));
						$this->interch = $this->parser->parseString($edi_string, $message_type);
						$result['edi_standard']			= strtoupper($edi_standard);
						$result['edi_standard_type']	= strtoupper($edi_standard_type);
						$result['edi_message_type']		= strtoupper($message_type);
						$result['edi_message_xml']		= $this->interch->toXml();
						return $result;
					}
				}
			}
		}
		throw new EDI_Exception( 'Unsupported EDI ' . strtoupper($edi_standard) . ' ' . strtoupper($edi_standard_type) . ' message type.' );
	}

	/**
	 * getEdiStandardFromXmlFile
	 *
	 * Extract where is a Edi Standard, standard type and message type form passed edistring
	 * in xml format
	 * 
	 * @param string $edi_string
	 * @return array $result
	 */
	public function getEdiStandardFromXmlFile( $edi_string )
	{
		print_r( $edi_string );
		exit;
	}

	/**
	 * getAvailableEdiStandardAndMessageType
	 * 
	 * Get available edi standard and related messages
	 *
	 * @return array $edi_standard_type
	 * 
	 */
	public function getAvailableEdiStandardAndMessageType()
	{
		if(!is_dir(__DIR_EDI_STANDARD_DATA__))
		{
			throw new EDI_Exception( 'EDI Standard directory data not found.' );
		}
		if ($handle = opendir(__DIR_EDI_STANDARD_DATA__)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != '.' && $entry != '..')
				{
					$standard_dir = __DIR_EDI_STANDARD_DATA__ . $entry;
					$messages_dir = $standard_dir . '/messages/';
					if(is_dir($messages_dir))
					{
						if($handle_msg = opendir($messages_dir))
						{
							while (false !== ($entry_msg = readdir($handle_msg)))
							{
								if( $entry_msg != '.' && $entry_msg !== '..' )
								{
									if(is_file($messages_dir . $entry_msg))
									{
										list($message_type, $ext) = explode('.', $entry_msg);
										$edi_standard_type[$entry][] = $message_type;
									}
								}
							}
							closedir($handle_msg);
						}
					}
				}
			}
			closedir($handle);
		}
		return $edi_standard_type;
	}

	/**
	 * getAvailableEdiMessages
	 *
	 * Return where type of message are available for specified standard
	 * and standard type
	 * 
	 * @param string $edi_standard
	 * @param string $edi_standard_type
	 * @return array $available_messages_type
	 */
	public function getAvailableEdiMessages( $edi_standard, $edi_standard_type )
	{
		$dir_edi_standard = __AB_PATH__ . 'data/EDI_' . strtoupper($edi_standard) . '_' . strtoupper($edi_standard_type) . '/messages/';
		if(!is_dir($dir_edi_standard))
		{
			throw new EDI_Exception( 'EDI Standard directory ' . $edi_standard . '/' . $edi_standard_type . ' not found.' );
		}
		if ($handle = opendir($dir_edi_standard)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					if(is_file($dir_edi_standard . $entry))
					{
						list($message_type, $ext) = explode('.', $entry);
						$available_messages_type[] = $message_type;
					}
				}
			}
			closedir($handle);
		}
		return $available_messages_type;
	}
	// EDI::parserFactory {{{

	/**
     * Factory method returning an instance of the EDI parser matching the
     * given standard.
     *
     * Usage example:
     *
     * <code>
     * require_once 'EDI.php';
     *
     * try {
     *     // get a parser for the UN/EDIFACT standard
     *     $parser  = EDI::parserFactory('EDIFACT');
     *     $interch = $parser->parse('myfile.edi');
     *     // do something with your interchange object...
     * } catch (Exception $exc) {
     *     echo $exc->getMessage();
     *     exit(1);
     * }
     * </code>
     *
     * @param string $standard The edi standard (EDIFACT, ASC12...)
     * @param array  $params   An array of parameters to pass to the parser
     *
     * @access public
     * @return EDI_Common_Parser Instance of EDI_Parser_Common abstract class
     * @throws EDI_Exception If the EDI standard is not supported
     */    
	final public static function parserFactory($standard, $params = array())
	{
		$include_file = __DIR_EDI__ . 'EDI/' . $standard . '/Parser.php';
		if (!file_exists ($include_file))
		throw new EDI_Exception('Unable to find parser for standard ' . $standard . ', ' . EDI_Exception::E_UNSUPPORTED_STANDARD);

		include_once $include_file;
		$cls = "EDI_{$standard}_Parser";
		if (!class_exists($cls)) {
			throw new EDI_Exception('Unsupported standard ' . $standard . ', ' . EDI_Exception::E_UNSUPPORTED_STANDARD);
		}
		return new $cls($params);
	}

	// }}}
	// EDI::interchangeFactory {{{

	/**
     * Factory method returning an instance of the EDI interchange matching
     * the given standard.
     *
     * Usage example:
     *
     * <code>
     * require_once 'EDI.php';
     *
     * // construct an UN/EDIFACT standard interchange
     * try {
     *     $interch = EDI::interchangeFactory('EDIFACT');
     *     // set your values here...
     *     // write an edi file from your interchange object
     *     $edifile = fopen('myfile.edi', 'w');
     *     fwrite($edifile, $interch->toEDI());
     *     fclose($edifile);
     * } catch (Exception $exc) {
     *     echo $exc->getMessage();
     *     exit(1);
     * }
     * </code>
     *
     * @param string $standard The edi standard (EDIFACT, ASC12...)
     * @param array  $params   An array of params to pass to the interchange
     *
     * @access public
     * @return EDI_Common_Element Instance of EDI_Common_Element abstract class
     * @throws EDI_Exception If the EDI standard is not supported
     */
	final public static function interchangeFactory($standard, $params=array())
	{
		include_once __DIR_EDI__ . 'EDI/' . $standard . '/Elements.php';
		$cls = "EDI_{$standard}_Interchange";
		if (!class_exists($cls)) {
			throw new EDI_Exception('Unsupported standard ' . $standard . ', ' . EDI_Exception::E_UNSUPPORTED_STANDARD);
		}
		return new $cls($params);
	}

	/**
	 * getEdiFileFormat
	 *
	 * Get where is a file format for $edistring
	 * 
	 * @param string $edistring
	 * 
	 * @access public
	 * @return string of file type (edi or xml)
	 *
	 */
	final public static function getFileFormat( $edi_string )
	{
		libxml_use_internal_errors( true );
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadXML( $edi_string );
		$errors = libxml_get_errors();
		if (!$errors)
		return 'Xml';

		return 'Edi';
	}

	/**
	 * ediTranslateTo
	 * 
	 * Translate edi message from format to another
	 *
	 * @param unknown_type $edistring
	 */
	public function ediTranslateTo($dest_standard, $dest_standard_type)
	{
		$translator_file = __DIR_EDI_STANDARD_DATA__ . 'EDI_' . $this->edi_standard . '_' . $this->edi_standard_type . '/translate/' . $dest_standard . '_' . $dest_standard_type . '/' . strtolower($this->edi_message_type) . '.php';
		if( !is_file($translator_file) )
		{
			throw new EDI_Exception('Unsupported translation document ' . $dest_standard . ' ' . $dest_standard_type . ' ' . $this->edi_message_type . ' ' . EDI_Exception::E_UNSUPPORTED_STANDARD);
		}
		include_once($translator_file);
		
		//$parser  = EDI::parserFactory($dest_standard);
		//$destMapping = "EDI_{$dest_standard}_MappingProvider";

		/*$documento = EDI::interchangeFactory ($destStandard, array( 'directory'			=> $destRelease,
																	'syntaxIdentifier'	=> 'UNOC',
																	'syntaxVersion'		=> 3
																	));
		*/
		
		$class_name = strtolower($this->edi_message_type . $this->edi_standard_type . $dest_standard_type);
		try {
			$translate = new $class_name($dest_standard, $dest_standard_type);
			$translate->parse($this->edi_message_xml);
			return $translate->toEDI();
		}
		catch (Exception $exc)
		{
			throw new EDI_Exception($exc->getMessage());
		}
		
	}

	/**
 	 * Converts a simpleXML element into an array. Preserves attributes.<br/>
	 * You can choose to get your elements either flattened, or stored in a custom
	 * index that you define.<br/>
	 * For example, for a given element
	 * <code>
	 * <field name="someName" type="someType"/>
	 * </code>
	 * <br>
	 * if you choose to flatten attributes, you would get:
	 * <code>
	 * $array['field']['name'] = 'someName';
	 * $array['field']['type'] = 'someType';
	 * </code>
	 * If you choose not to flatten, you get:
	 * <code>
	 * $array['field']['@attributes']['name'] = 'someName';
	 * </code>
	 * <br>__________________________________________________________<br>
	 * Repeating fields are stored in indexed arrays. so for a markup such as:
	 * <code>
	 * <parent>
	 *     <child>a</child>
	 *     <child>b</child>
	 *     <child>c</child>
	 * ...
	 * </code>
	 * you array would be:
	 * <code>
	 * $array['parent']['child'][0] = 'a';
	 * $array['parent']['child'][1] = 'b';
	 * ...And so on.
	 * </code>
	 * @param simpleXMLElement    $xml            the XML to convert
	 * @param boolean|string    $attributesKey    if you pass TRUE, all values will be
	 *                                            stored under an '@attributes' index.
	 *                                            Note that you can also pass a string
	 *                                            to change the default index.<br/>
	 *                                            defaults to null.
	 * @param boolean|string    $childrenKey    if you pass TRUE, all values will be
	 *                                            stored under an '@children' index.
	 *                                            Note that you can also pass a string
	 *                                            to change the default index.<br/>
	 *                                            defaults to null.
	 * @param boolean|string    $valueKey        if you pass TRUE, all values will be
	 *                                            stored under an '@values' index. Note
	 *                                            that you can also pass a string to
	 *                                            change the default index.<br/>
	 *                                            defaults to null.
	 * @return array the resulting array.
	 */
	function simpleXMLToArray(SimpleXMLElement $xml,$attributesKey=null,$childrenKey=null,$valueKey=null){

		if($childrenKey && !is_string($childrenKey)){$childrenKey = '@children';}
		if($attributesKey && !is_string($attributesKey)){$attributesKey = '@attributes';}
		if($valueKey && !is_string($valueKey)){$valueKey = '@values';}

		$return = array();
		$name = $xml->getName();
		$_value = trim((string)$xml);
		if(!strlen($_value)){$_value = null;};

		if($_value!==null){
			if($valueKey){$return[$valueKey] = $_value;}
			else{$return = $_value;}
		}

		$children = array();
		$first = true;
		foreach($xml->children() as $elementName => $child){
			$value = simpleXMLToArray($child,$attributesKey, $childrenKey,$valueKey);
			if(isset($children[$elementName])){
				if(is_array($children[$elementName])){
					if($first){
						$temp = $children[$elementName];
						unset($children[$elementName]);
						$children[$elementName][] = $temp;
						$first=false;
					}
					$children[$elementName][] = $value;
				}else{
					$children[$elementName] = array($children[$elementName],$value);
				}
			}
			else{
				$children[$elementName] = $value;
			}
		}
		if($children){
			if($childrenKey){$return[$childrenKey] = $children;}
			else{$return = array_merge($return,$children);}
		}

		$attributes = array();
		foreach($xml->attributes() as $name=>$value){
			$attributes[$name] = trim($value);
		}
		if($attributes){
			if($attributesKey){$return[$attributesKey] = $attributes;}
			else{$return = array_merge($return, $attributes);}
		}

		return $return;
	}

	// }}}
}
