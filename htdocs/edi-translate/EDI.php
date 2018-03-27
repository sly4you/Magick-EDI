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
require_once 'EDI/Exception.php';

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

	protected $header_line = 'UNA';

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
		$include_file = "EDI/$standard/Parser.php";
		if (!file_exists ($include_file))
		throw new EDI_Exception("Unable to find parser for standard $standard.", EDI_Exception::E_UNSUPPORTED_STANDARD);

		include_once $include_file;
		$cls = "EDI_{$standard}_Parser";
		if (!class_exists($cls)) {
			throw new EDI_Exception("Unsupported standard $standard.", EDI_Exception::E_UNSUPPORTED_STANDARD);
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
		include_once "EDI/$standard/Elements.php";
		$cls = "EDI_{$standard}_Interchange";
		if (!class_exists($cls)) {
			throw new EDI_Exception( "Unsupported standard $standard.", EDI_Exception::E_UNSUPPORTED_STANDARD);
		}
		return new $cls($params);
	}
	// }}}
}
