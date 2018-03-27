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
 * @version   SVN: $Id: Elements.php,v 1.1.1.1 2008/09/14 16:22:20 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/Electronic_Data_Interchange
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Include common functions.
 */
require_once __AB_PATH__ . 'EDI/Common/Utils.php';

/**
 * Abstract base class for all EDI elements.
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
abstract class EDI_Common_Element
{
    // Properties {{{

    /**
     * Properties array used by overloading.
     *
     * @var array $properties
     * @access protected
     */
    protected $properties = array(
        'id'          => null,
        'name'        => null,
        'description' => null,
        'value'       => null
    );

    // }}}
    // __construct {{{

    /**
     * Constructor.
     *
     * @param array $params An array of parameters
     *
     * @access public
     * @return void
     */
    public function __construct($params = array())
    {
        if (is_array($params)) {
            foreach ($params as $k=>$v) {
                if (property_exists($this, $k)) {
                    $this->$k = $v;
                }
            }
        }
    }

    // }}}
    // toEDI() {{{

    /**
     * Returns the edi representation of the element.
     *
     * @abstract
     * @access public
     * @return string
     */
    abstract public function toEDI();

    // }}}
    // translateTo() {{{

    /**
     * Returns the edi representation of the element translated to the selected destination standard
     *
     * @abstract
     * @param string $destStandard Destination standard name (EDI, EANCOM)
     * @param string $destRelease Destination standard release (D96A...)
     * @param string $destMessage Destination message name
     * @param string $setHeader Include header & footer
     * @access public
     * @return string
     */
    abstract public function translateTo($destStandard, $destRelease = null, $destMessage = null, $setHeader = true);

    // }}}
    // toXml() {{{

    /**
     * Returns the xml representation of the element.
     *
     * @param bool $verbose If set to true xml comments will be included
     * @param int  $indent  The number of spaces for indentation
     *
     * @abstract
     * @access public
     * @return string
     */
    abstract public function toXml($verbose = false, $indent = 0);

    // }}}
    // __get() {{{

    /**
     * Overload method for getting properties.
     *
     * @param string $name Name of property
     *
     * @access public
     * @return mixed
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }
    }

    // }}} 
    // __set() {{{

    /**
     * Overload method for setting properties.
     *
     * @param string $name Name of property
     * @param mixed  $val  Value of property
     *
     * @access public
     * @return void
     */
    public function __set($name, $val)
    {
        $this->properties[$name] = $val;
    }

    // }}} 
    // __isset() {{{

    /**
     * Overload method for checking if the property is set.
     *
     * @param string $name Name of property
     *
     * @access public
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->properties[$name]);
    }

    // }}} 
    // __unset() {{{

    /**
     * Overload method for deleting a property.
     *
     * @param string $name Name of property
     *
     * @access public
     * @return void
     */
    public function __unset($name)
    {
        unset($this->properties[$name]);
    }

    // }}} 
}

/**
 * Abstract base class for EDI composite elements.
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
abstract class EDI_Common_CompositeElement extends EDI_Common_Element 
    implements ArrayAccess, Countable, RecursiveIterator
{
    // Properties {{{

    /**
     * Array containing children elements.
     *
     * @var array $properties
     * @access public
     */
    protected $children = array();

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
        $str = '';
        foreach ($this->children as $child) {
            if ($child instanceof EDI_Common_Element) {
                $str .= $child->toEDI();
            }
        }
        return $str;
    }

    // }}}
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

        $msgrefnum = date ("YmdHis");
        require_once "EDI/{$destStandard}/MappingProvider.php";
        $destMapping = "EDI_{$destStandard}_MappingProvider";
        // Standard headers required in EDIFACT: UNB, UNH
        if ($setHeader) {
            // UNB
            $cc = "EDI_{$destStandard}_Segment";
            $mapping = $destMapping::find("UNB", $destRelease);
            $elt = new $cc ();
            $elt->id = "UNB";
            $elt->name = (string)$mapping['name'];
            $elt->description = (string)$mapping['desc'];

            $cc = "EDI_{$destStandard}_DataElement";
            $cda = "EDI_{$destStandard}_CompositeDataElement";

            // S001
            $ce = new $cda ();
            $ce->id = "S001";
            // 
            $elid = "0001";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = "UNOC";
            $ce [] = $e;
            // 
            $elid = "0002";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = 3;
            $ce [] = $e;
            // S009
            $elt [] = $ce;
            //  MITTENTE
            $elid = "0004";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = "";
            $elt [] = $e;
            //  DESTINATARIO
            $elid = "0010";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = "";
            $elt [] = $e;
            // S004
            $ce = new $cda ();
            $ce->id = "S004";
            // 
            $elid = "0017";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = date ("ymd");
            $ce [] = $e;
            // 
            $elid = "0019";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = date ("Hi");
            $ce [] = $e;
            // S004
            $elt [] = $ce;
            // 
            $elid = "0020";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = "";
            $elt [] = $e;
            // S005
            $ce = new $cda ();
            $ce->id = "S005";
            // 
            $elid = "0022";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = "";
            $ce [] = $e;
            // S005
            $elt [] = $ce;
            // 
            $elid = "0026";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = $msgrefnum;
            $elt [] = $e;

            $destDoc [] = $elt;

            // UNH
            $cc = "EDI_{$destStandard}_Segment";
            $mapping = $destMapping::find("UNH", $destRelease);
            $elt = new $cc ();
            $elt->id = "UNH";
            $elt->name = (string)$mapping['name'];
            $elt->description = (string)$mapping['desc'];

            $cc = "EDI_{$destStandard}_DataElement";
            // Message reference number
            $elid = "0062";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = $msgrefnum;
            $elt [] = $e;

            // S009
            $ce = new $cda ();
            $ce->id = "S009";

            // Message type
            $elid = "0065";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = $destMessage;
            $ce [] = $e;

            // Message version number
            $elid = "0052";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = substr ($destRelease, 0, 1);
            $ce [] = $e;

            // Message release number
            $elid = "0054";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = substr ($destRelease, 1);
            $ce [] = $e;

            // Controlling angency
            $elid = "0051";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = "UN";
            $ce [] = $e;

            // S009
            $elt [] = $ce;

            $destDoc [] = $elt;
        }

        foreach ($this->children as $child) {
            if ($child instanceof EDI_Common_Element) {
                foreach ($child->translateTo($destStandard, $destRelease, $destMessage, false) as $e)
                    $destDoc [] = $e;
            }
        }
        $cnt = count ($destDoc) - 2; // minus two headers

        // Standard footer required in EDIFACT (UNT, UNZ)
        if ($setHeader) {
            // UNT
            $cc = "EDI_{$destStandard}_Segment";
            $mapping = $destMapping::find("UNT", $destRelease);
            $elt = new $cc ();
            $elt->id = "UNT";
            $elt->name = (string)$mapping['name'];
            $elt->description = (string)$mapping['desc'];

            $cc = "EDI_{$destStandard}_DataElement";
            // Message reference number
            $elid = "0074";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = $cnt;
            $elt [] = $e;

            // Message type
            $elid = "0065";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = $msgrefnum;
            $elt [] = $e;

            $destDoc [] = $elt;

            // UNZ
            $cc = "EDI_{$destStandard}_Segment";
            $mapping = $destMapping::find("UNZ", $destRelease);
            $elt = new $cc ();
            $elt->id = "UNZ";
            $elt->name = (string)$mapping['name'];
            $elt->description = (string)$mapping['desc'];

            $cc = "EDI_{$destStandard}_DataElement";
            // 
            $elid = "0036";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = 1;
            $elt [] = $e;

            // Message type
            $elid = "0020";
            $el = $destMapping::find($elid, $destRelease);
            $e = new $cc ();
            $e->id = $elid;
            $e->name = (string)$el['name'];
            $e->description = (string)$el['desc'];
            $ml = (int)$el['maxlength'];
            $e->value = $msgrefnum;
            $elt [] = $e;

            $destDoc [] = $elt;
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
        $str = '';
        if ($indent == 0) {
            // root node
            $str .= '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        }
        $blank = str_repeat(' ', $indent);
        $id    = strtolower($this->id);
        $str  .= $blank . '<' . $id;
        if (!empty($this->name)) {
            $name = EDI_Common_Utils_escapeXmlString($this->name);
            $str .= ' name="' . utf8_encode($name) . '"';
        }
        $str .= '>';
        foreach ($this->children as $child) {
            if ($child instanceof EDI_Common_Element) {
                $str .= "\n" . $child->toXml($verbose, $indent+4);
            }
        }
        $str .= "\n" . $blank . '</' . $id . '>';
        return $str;
    }

    // }}}
    // offsetExists() {{{

    /**
     * Implementation of ArrayAccess::offsetExists()
     * 
     * @param int $offset Offset to check
     *
     * @access public
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->children[$offset]);
    }

    // }}}
    // offsetGet() {{{

    /**
     * Implementation of ArrayAccess::offsetGet()
     * 
     * @param int $offset Offset to retrieve
     *
     * @access public
     * @return EDI_Common_Element
     */
    public function offsetGet($offset)
    {
        if (isset($this->children[$offset])) { 
            return $this->children[$offset];
        }
    }

    // }}}
    // offsetSet() {{{

    /**
     * Implementation of ArrayAccess::offsetSet()
     * 
     * @param int                $offset Offset to modity
     * @param EDI_Common_Element $value  Value to set
     *
     * @access public
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (empty($offset)) {
            $offset = count($this->children);
        }
        $this->children[$offset] = $value;
    }

    // }}}
    // offsetUnset() {{{

    /**
     * Implementation of ArrayAccess::offsetUnset()
     * 
     * @param int $offset Offset to delete
     *
     * @access public
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->children[$offset]);
    }

    // }}}
    // count() {{{

    /**
     * Implementation of Countable::count()
     *
     * @access public
     * @return int
     */
    public function count()
    {
        return count($this->children);
    }

    // }}}
    // rewind() {{{

    /**
     * Implementation of Iterator::rewind()
     *
     * @access public
     * @return void
     */
    public function rewind()
    {
        reset($this->children);
    }

    // }}}
    // current() {{{

    /**
     * Implementation of Iterator::current()
     *
     * @access public
     * @return EDI_Common_Element
     */
    public function current()
    {
        return current($this->children);
    }

    // }}}
    // key() {{{

    /**
     * Implementation of Iterator::key()
     *
     * @access public
     * @return int
     */
    public function key()
    {
        return key($this->children);
    }

    // }}}
    // next() {{{

    /**
     * Implementation of Iterator::next()
     *
     * @access public
     * @return EDI_Common_Element
     */
    public function next()
    {
        return next($this->children);
    }

    // }}}
    // valid() {{{

    /**
     * Implementation of Iterator::valid()
     *
     * @access public
     * @return bool
     */
    public function valid()
    {
        return $this->current() !== false;
    }

    // }}}
    // getChildren() {{{

    /**
     * Implementation of RecursiveIterator::getChildren()
     *
     * @access public
     * @return bool
     */
    public function getChildren()
    {
        return $this->current();
    }

    // }}}
    // hasChildren() {{{

    /**
     * Implementation of RecursiveIterator::hasChildren()
     *
     * @access public
     * @return bool
     */
    public function hasChildren()
    {
        return $this->current() instanceof EDI_Common_CompositeElement;
    }

    // }}}
    // find() {{{

    /**
     * Find the EDI_Common_Element matching $value, the method compares first
     * the the element id with the value provided, then, its name.
     *
     * This method always return an array() that can be empty if no matching 
     * elements were found.
     *
     * @param string $value Value to search for
     *
     * @access public
     * @return mixed
     */
    public function find($value)
    {
        $ret = array();
        $it  = new RecursiveIteratorIterator(
            $this,
            RecursiveIteratorIterator::SELF_FIRST
        );
        while ($it->valid()) {
            $child = $it->current();
            if ($child->id == $value || $child->name == $value) {
                $ret[] = $child;
            }
            $it->next();
        }
        return $ret;
    }

    // }}} 
}
