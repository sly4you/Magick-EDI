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
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   SVN: $Id: example1.php,v 1.1.1.1 2008/09/14 16:22:06 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/Electronic_Data_Interchange
 * @since     File available since release 0.1.0
 * @filesource
 */
ini_set('display_errors','On');
define('__AB_PATH__', dirname(__FILE__) . '/' );
/**
 * Include the EDI class.
 */
require_once 'EDI.php';

try {
    $parser  = EDI::parserFactory('EANCOM');
    $interch = $parser->parse(dirname(__FILE__) . '/examples/desadv_03102012.edi', 'DESADV');
//    $interch = $parser->parse(dirname(__FILE__) . '/invoice_a.txt', 'INVOIC');
//    $interch = $parser->parse(dirname(__FILE__) . '/invoice.txt', 'ORDERS');
    // do something with the edi interchange instance
    echo $interch->toXml();

//    echo $interch->translateTo ('EDIFACT','D96A');

} catch (EDI_Exception $exc) {
    echo $exc->getMessage() . "\n";
    exit(1);
}
