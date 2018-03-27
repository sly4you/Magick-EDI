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
 * @version   SVN: $Id: Elements.php,v 1.1.1.1 2008/09/14 16:22:20 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Include the base element classes.
 */
require_once __AB_PATH__ . 'EDI/Common/Elements.php';

/**
 * Represents an EDIFACT interchange.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_Interchange extends EDI_Common_CompositeElement
{
    // Properties {{{

    /**
     * Boolean used (internally, but could be used from outside this class if 
     * necessary) to determine if the parsed edifact document had an explicit
     * service string advice.
     *
     * @var bool $hasServiceStringAdvice
     * @access public
     */
    public $hasServiceStringAdvice = true;

    /**
     * The segment terminator, default "'".
     *
     * @var string $segmentTerminator
     * @static
     * @access public
     */
    public static $segmentTerminator = "\n";

    /**
     * The element separator, default "".
     *
     * @var string $elementSeparator
     * @access public
     * @static
     */
    public static $elementSeparator = '';

    /**
     * The release char, none in EANCOM
     *
     * @var string $releaseChar
     * @static
     * @access public
     */
    public static $releaseChar = '';

    /**
     * The data separator, default "".
     *
     * @var string $dataSeparator
     * @static
     * @access public
     */
    public static $dataSeparator = '';

    /**
     * The decimal char, default ".".
     *
     * @var string $decimalChar
     * @static
     * @access public
     */
    public static $decimalChar = '.';

    /**
     * The error message if the interchange is not valid.
     *
     * @var string $decimalChar
     * @see EDI_EDIFACT_Interchange::isValid()
     * @access public
     */
    public $errorMessage = '';

    // }}}
    // __construct {{{

    /**
     * Constructor
     *
     * @param array $params An array of parameters
     *
     * @access public
     * @return void
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    // }}}
    // loadConfig() {{{

    /**
     * Parses the config file $cfgfile and for each valid entry of the config 
     * file the method tries to assign the value of the entry to the property
     * pointed by the key with the EDI_EDIFACT_Interchange::set() method.
     *
     * @param string $cfgfile path to the config file
     *
     * @access public
     * @return void
     * @throws EDI_Exception
     */
    public function loadConfig($cfgfile)
    {
        if (!file_exists($cfgfile)) {
            throw new EDI_Exception('config file $cfgfile not found.');
        }
        if (!is_readable($cfgfile) || !($fh = fopen($cfgfile, 'r'))) {
            throw new EDI_Exception('config file $cfgfile is not readable.');
        }
        $conf = array();
        while (!feof($fh) && ($l = trim(fgets($fh))) !== false) {
            if (preg_match('/^([^#]+)\s*=\s*([^#]+).*$/', $l, $t)) {
                $conf[trim($t[1])] = trim($t[2]);
            }
        }
        fclose($fh);
    }

    // }}}
    // isValid() {{{

    /**
     * Returns true if the interchange is valid and false otherwise.
     * If not valid an error message is set in the errorMessage property.
     *
     * An optional argument strict can be passed, if set to true the method 
     * will skip type (alpha, numeric, alphanumeric) and length checks.
     * An exemple:
     *
     * <code>
     * $interchange = EDI::interchangeFactory('EDIFACT');
     * // build interchange
     * // [..]
     * if (!$interchange->isValid()) {
     *     fwrite(STDERR, $interchange->errorMessage);
     *     exit(1);
     * }
     * </code>
     *
     * @param bool $strict If set to true type and length checks are skipped
     *
     * @access public
     * @return boolean
     */
    public function isValid($strict=true)
    {
        try {
            $parser = EDI::parserFactory('EDIFACT');
            $parser->parseString($this->toEDI());
        } catch (EDI_Exception $exc) {
            $this->errorMessage = $exc->getMessage();
            return false;
        }
        return true;
    }

    // }}}
}

/**
 * Represents an edifact functional group.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_FunctionalGroup extends EDI_Common_CompositeElement
{
}

/**
 * Represents an edifact message.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_Message extends EDI_Common_CompositeElement
{
}

/**
 * Represents an edifact segment group.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_SegmentGroup extends EDI_Common_CompositeElement
{
}

/**
 * Represents an eancom container.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
abstract class EDI_EANCOM_Container extends EDI_Common_CompositeElement
{
    // translateTo() {{{

    /**
     * Returns the edi representation of the element.
     *
     * @param string $destStandard Destination standard name (EDI, EANCOM)
     * @param string $destRelease Destination standard release (D96A...)
     * @param string $destMessage Destination message name
     * @param string $setHeader Include header & footer
     * @access public
     * @return string
     */
    public function translateTo($destStandard, $destRelease = null, $destMessage = null, $setHeader = true)
    {
        // Instantiate destination parserFactory
        $destDoc = EDI::interchangeFactory ($destStandard, array(
            'directory'        => $destRelease,
            'syntaxIdentifier' => 'UNOC',
            'syntaxVersion'    => 3
            ));

        foreach ($this->children as $child) {
            if ($child instanceof EDI_Common_Element) {
                foreach ($child->translateTo($destStandard, $destRelease, $destMessage, false) as $e)
                    $destDoc [] = $e;
            }
        }
        return $destDoc;
    }

    // }}}
    // toXml() {{{

    /**
     * Returns the xml representation of the element.
     *
     * @param bool $verbose If set to true xml comments will be included
     * @param int  $indent  The number of spaces for indentation
     *
     * @access public
     * @return string
     */
    public function toXml($verbose = false, $indent = 0)
    {
        $ret = array();
        foreach ($this->children as $child) {
            if ($child instanceof EDI_Common_Element) {
                $ret[] = $child->toXml($verbose, $indent);
            }
        }
        return implode("\n", $ret);
    }

    // }}}
}

/**
 * Represents an eancom segment group container.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_SegmentGroupContainer extends EDI_EANCOM_Container
{
}

/**
 * Represents an eancom segment container.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_SegmentContainer extends EDI_EANCOM_Container
{
}

/**
 * Represents an eancom segment.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_Segment extends EDI_Common_CompositeElement
{
    // toEDI() {{{

    /**
     * Returns the edi representation of the element.
     *
     * @access public
     * @return string
     */
    public function toEDI()
    {
		// $tokens = array($this->id); //FIXME rimosso perche' intralcia la scrittura del record
        //FIXME visto che e' stato gestito come campo a se (9001) in tutti i segmenti, la parse se lo aspetta
        //FIXME bisognerebbe fare in modo di rendere la parse compatibile senza il campo 9001 su tutti i segmenti, e quindi
        //FIXME sistemare anche la traduzione per non passare il codice riga come elemento 
        foreach ($this as $child) {
            $tokens[] = $child === null ? '' : $child->toEDI();
        }
        // remove empty entries at the end
        $i = count($tokens);
        while (--$i) {
            if ($tokens[$i] !== '') {
                break;
            }
        }
        $tokens = array_slice($tokens, 0, $i+1);
        $str    = implode(EDI_EANCOM_Interchange::$elementSeparator, $tokens);
        return $str . EDI_EANCOM_Interchange::$segmentTerminator;
    }

    // }}}
    // translateTo() {{{

    /**
     * Returns the edi representation of the element translated to the selected destination standard
     *
     * @access public
     * @return string
     */
    public function translateTo($destStandard, $destRelease = null, $destMessage = null, $setHeader = false)
    {
        require_once "EDI/{$destStandard}/MappingProvider.php";
        
        // 1. find the translation rules for this segment
        $tRules = EDI_EANCOM_MappingProvider::find ("translate_{$destStandard}_{$destRelease}_{$destMessage}_".$this->id);
        $neededDestSegments = explode (",", $tRules ['requires']);
        
        // Compose destination segments
        $destMapping = "EDI_{$destStandard}_MappingProvider";
        foreach ($neededDestSegments as $ns) {
            // Array containing destination's ID position.
            // If the same element ID is specified twice in the destination segment, $position [ID] will be 2 in the second instance
            $position = array ();
            // Create destination segment
            $mapping = $destMapping::find($ns, $destRelease);
            $cc = "EDI_{$destStandard}_Segment";
            $elt = new $cc ();
            $elt->id = (string)$ns;
            $elt->name = (string)$mapping['name'];
            $elt->description = (string)$mapping['desc'];

            // Parse all destination elements, ignoring groups
            foreach ($mapping as $c) {

                $cc = "EDI_{$destStandard}_DataElement";
                if (substr ($c ['id'], 0, 1) == 'C') {
                    $cd = "EDI_{$destStandard}_CompositeDataElement";
                    $cid = new $cd ();
                    $cid->id = $c ['id'];

                    $cel = $destMapping::find($c ['id'], $destRelease);
                    foreach ($cel as $ci) {
                        $elid = (string)$ci ['id'];
                        if (array_key_exists ($elid, $position))
                            $position [$elid] ++;
                        else
                            $position [$elid] = 1;
                        $el = $destMapping::find($ci ['id'], $destRelease);
                        $e = new $cc ();
                        $e->id = $elid;
                        $e->name = (string)$cel['name'];
                        $e->description = (string)$cel['desc'];
                        $ml = (int)$cel['maxlength'];
                        $val = $this->translateResult ($el ['id'], $tRules, $position [$elid]);
                        if ($ml > 0)
                            $val = substr ($val, 0, $ml);
                        $e->value = $val;
                        $cid [] = $e;
                    }
                    $elt [] = $cid;
                } else {
                    $elid = (string)$c ['id'];
                    if (array_key_exists ($elid, $position))
                        $position [$elid] ++;
                    else
                        $position [$elid] = 1;
                    $el = $destMapping::find($c ['id'], $destRelease);
                    $e = new $cc ();
                    $e->id = (string)$el ['id'];
                    $e->name = (string)$el['name'];
                    $e->description = (string)$el['desc'];
                    $ml = (int)$el['maxlength'];
                    $val = $this->translateResult ($el ['id'], $tRules, $position [$elid]);
                    if ($ml > 0)
                        $val = substr ($val, 0, $ml);
                    $e->value = $val;
                    $elt [] = $e;
                }

            }
            $ret [] = $elt;
        }

        return $ret;
    }

    // }}}
    // translateTo() {{{

    /**
     * Returns the destination value for the destination element, parsing translation rules
     *
     * @access private 
     * @param string Destination element code
     * @param string Xml translation rules
     * @param int Optional position of the field
     * @return string returns the value, or null if not found
     */
    private function translateResult($elementId, $rules, $pos = NULL)
    {
        foreach ($rules as $k => $v) {
            $convert = array ();
            // Create a temporary array for faster conversion
            for ($i = 0; $v->code[$i] != NULL; $i ++) {
                if ((bool)$v->code[$i]['notimplemented'])
                    $convert [(string)$v->code[$i]['source']] = NULL;
                else
                    $convert [(string)$v->code[$i]['source']] = (string)$v->code[$i]['value'];
            }
            // If I'm elaborating a field with destination position different from the one I'm in just return blank
            if ($v ['position'] != "" && $pos != NULL && (int)$v ['position'] != $pos)
                return "";
            $srcPos = 0;
            if ((int)$v ['srcpos'] != 0)
                $srcPos = (int)$v ['srcpos'];
            if ((string)$v ['becomes'] == $elementId) {
                $searchWhat = (string)$v ['id'];
            } else if ((string)$v ['id'] == $elementId) {
                $searchWhat = $elementId;
            } else {
                // Ignore?
                continue;
            }

            $this->rewind();
            $n = $this->find ($searchWhat);
            if (empty ($n))
                $retval = (string)$v ['default_value'];
//            if (!empty ($convert) && array_key_exists ((string)$n[$srcPos]->value, $convert))
            if (!empty ($convert))
                $retval = $convert [(string)$n[$srcPos]->value];
            else if (array_key_exists ($srcPos, $n))
                $retval =  (string)$n[$srcPos]->value;

            // Do formatting
            $format = (string)$v['format'];
            if (!empty ($format)) {
                preg_match ("/(?P<sign>[\-,+]{0,1})(?P<int>\d+),(?P<dec>\d+)/", $format, $ret);
                $newval = (float)substr ($retval, 0, strlen ($retval) - (int)$ret ['dec']) .",".substr ($retval, -(int)$ret ['dec']);
            }
            return $retval;
        }
    }
    // }}}
}

/**
 * Represents an edifact composite data element.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_CompositeDataElement extends EDI_Common_CompositeElement
{
    // toEDI() {{{

    /**
     * Returns the edi representation of the element.
     *
     * @access public
     * @return string
     */
    public function toEDI()
    {
        $tokens = array();
        foreach ($this as $child) {
            $tokens[] = $child === null ? '' : $child->toEDI();
        }
        // remove empty entries at the end
        $i = count($tokens);
        while (--$i > 0) {
            if ($tokens[$i] !== '') {
                break;
            }
        }
        $tokens = array_slice($tokens, 0, $i+1);
        return implode(EDI_EANCOM_Interchange::$dataSeparator, $tokens);
    }

    // }}}
}

/**
 * Represents an edifact data element.
 *
 * @category  File_Formats
 * @package   EDI
 * @author    Lorenzo Milesi <maxxer@yetopen.it>
 * @copyright 2011 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_DataElement extends EDI_Common_Element
{
    // Constants {{{

    /**
     * Type constants
     */
    const TYPE_DATE      = 1; // YYYY/MM/DD
    const TYPE_TIME      = 2; // HH:MM:SS or HH:MM
    const TYPE_TIMESTAMP = 3;

    /**
     * Element maxlength
     */
    var $maxlength = 0;

    // }}}
    // getValue() {{{

    /**
     * Returns the value of the element.
     *
     * @access public
     * @return mixed
     */
    public function getValue()
    {
        return EDI_Common_Utils_unescapeEDIString($this->value,
            EDI_EANCOM_Interchange::$releaseChar,
            EDI_EANCOM_Interchange::$segmentTerminator,
            EDI_EANCOM_Interchange::$elementSeparator,
            EDI_EANCOM_Interchange::$dataSeparator);
    }

    // }}}
    // setValue() {{{

    /**
     * Set the value of the element.
     *
     * @param mixed $value Value to set
     *
     * @access public
     * @return void
     */
    public function setValue($value)
    {
        $this->value = EDI_Common_Utils_escapeEDIString($value,
            EDI_EANCOM_Interchange::$releaseChar,
            EDI_EANCOM_Interchange::$segmentTerminator,
            EDI_EANCOM_Interchange::$elementSeparator,
            EDI_EANCOM_Interchange::$dataSeparator);
    }

    // }}}
    // toEDI() {{{

    /**
     * Returns the edi representation of the element.
     *
     * @access public
     * @return string
     */
    public function toEDI()
    {
        return str_pad ($this->value, $this->maxlength);
    }

    // }}}
    // translateTo() {{{

    /**
     * Returns the edi representation of the element.
     *
     * @param string $destStandard Destination standard name (EDI, EANCOM)
     * @param string $destRelease Destination standard release (D96A...)
     * @param string $destMessage Destination message name (DESADV, INVOIC...)
     * @access public
     * @return string
     */
    public function translateTo($destStandard, $destRelease = null, $destMessage = null, $setHeader = true)
    {
        throw new EDI_Exception('Not implemented');
    }

    // }}}
    // toXml() {{{

    /**
     * Returns the xml representation of the element.
     *
     * @param bool $verbose If set to true xml comments will be included
     * @param int  $indent  The number of spaces for indentation
     *
     * @access public
     * @return string
     */
    public function toXml($verbose = false, $indent = 0)
    {
        $blank = str_repeat(' ', $indent);
        $cls   = get_class($this);
        $node  = strtolower(substr($cls, strrpos($cls, '_')+1));
        $str   = '';
        if ($verbose && !empty($this->description)) {
            $desc = EDI_Common_Utils_escapeXmlString($this->description);
            $str .= $blank . '<!-- ' . utf8_encode($desc) . ' -->' . "\n";
        }
        $id   = 'e' . strtolower($this->id);
        $str .= sprintf('%s<%s name="%s">%s</%s>',
            $blank,
            $id,
            utf8_encode(EDI_Common_Utils_escapeXmlString($this->name)),
            utf8_encode(EDI_Common_Utils_escapeXmlString($this->getValue())),
            $id);
        return $str;
    }

    // }}}
}
