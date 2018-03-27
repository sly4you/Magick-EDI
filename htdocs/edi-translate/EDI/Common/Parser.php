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
 * @version   SVN: $Id: Parser.php,v 1.1.1.1 2008/09/14 16:22:20 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/Electronic_Data_Interchange
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Base abstract class for EDI formats parsers.
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
abstract class EDI_Common_Parser
{
    // Properties {{{

    /**
     * An instance of EDI_Interchange.
     *
     * @var EDI_Common_CompositeElement $interchange
     *
     * @access protected
     */
    protected $interchange = false;

    /**
     * Buffer containing the string being parsed.
     *
     * @var string $buffer
     *
     * @access protected
     */
    protected $buffer = '';

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param array $params An array of parameters
     *
     * @access public
     * @return void
     */
    public function __construct(Array $params=array())
    {
    }

    // }}}
    // parse() {{{

    /**
     * Parses given edi file and return an EDI_Common_CompositeElement instance
     * or throw an EDI_Exception if the file cannot be found or if an error
     * occurs.
     *
     * @param string $file Path to the edi file
     * @param string $msgtype Needed for EURITMO standard, ignored for others
     *
     * @access public
     * @return EDI_Common_CompositeElement the interchange instance
     * @throws EDI_Exception
     */
    public function parse($file, $msgtype = NULL)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new EDI_Exception(
                'Cannot access edi file "' . $file . '"',
                E_EDI_FILE_NOT_FOUND
            );
        }
        return $this->parseString(file_get_contents($file), $msgtype);
    }

    // }}}
    // parseString() {{{

    /**
     * Parses given edi string and return an EDI_Common_CompositeElement
     * instance or throws an EDI_Exception if an error occurs.
     *
     * @param string $string The EDI string to parse
     *
     * @abstract
     * @access public
     * @return EDI_Common_CompositeElement the interchange instance
     * @throws EDI_Exception
     */
    abstract public function parseString($string, $msgtype);

    // }}}
}
