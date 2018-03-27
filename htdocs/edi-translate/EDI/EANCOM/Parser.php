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
 * @version   SVN: $Id: Parser.php,v 1.1.1.1 2008/09/14 16:22:20 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * Include required classes.
 */
require_once __AB_PATH__ . 'EDI/Common/Parser.php';
require_once __AB_PATH__ . 'EDI/Common/Utils.php';
require_once __AB_PATH__ . 'EDI/EANCOM/Elements.php';
require_once __AB_PATH__ . 'EDI/EANCOM/MappingProvider.php';

/**
 * A class to parse the UN/EDIFACT format, it can parse every version of the 
 * UN/EDIFACT directories from 1988 to nowadays and support the UN/EDIFACT 
 * syntax version 1, 2, 3 and 4.
 *
 * Note that you should not instanciate this class directly but use the
 * EDI::parserFactory() method instead.
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
 * @see       EDI::parserFactory()
 * @since     Class available since release 0.1.0
 */
class EDI_EANCOM_Parser extends EDI_Common_Parser
{
	// Properties {{{

	/**
     * If set to false, the parser will only issue warnings for data type and 
     * length errors.
     *
     * @param bool $strictValidation
     * @access protected
     */
	public $strictValidation = false;

	/**
     * Array that contains various informations about the parsed message:
     *  - syntax_id: the syntax identifier (ex: UNOA)
     *  - syntax_version: the syntax version (ex: V4)
     *  - directory: the edifact directory (ex: D96A)
     *  - message_id: the id of the message (ex: APERAK)
     *
     * @param array $interchangeInfo
     * @access protected
     */
	protected $interchangeInfo = array();

	/**
     * EURITMO message type (INVOIC, DESADV...)
     *
     * @param string $messageType
     * @access protected
     */
	protected $messageType = '';

	/**
     * 
     * 
     * 
     * 
     */
	protected $xml_header = '<?xml version="1.0" encoding="utf-8"?>';

	// }}}
	// __construct {{{

	/**
     * Constructor
     *
     * @param array $params An array of parameters.
     *
     * @access public
     * @return void
     */
	public function __construct($params = array())
	{
		parent::__construct($params);
	}

	// }}}
	// parseString() {{{

	/**
     * Parses given edi string and return an EDI_EANCOM_Document instance or
     * throws an EDI_Exception.
     *
     * @param string $edistring The EDI string
     * @param string $msgtype The EDI message type (ORDERS, DESADV, INVOIC...)
     *
     * @access public
     * @return EDI_EANCOM_CompositeDataElement
     * @throws EDI_Exception
     */
	public function parseString($edistring, $msgtype)
	{
		$this->buffer          = $edistring;
		$this->interchange     = new EDI_EANCOM_Interchange();
		$this->interchange->id = 'interchange';
		$this->messageType     = $msgtype;
		$tokens                = $this->tokenize();

		// We don't have interchange headers, need to FIXME this?

		if (empty($tokens['messages'])) {
			throw new EDI_Exception(
			'an interchange must contain at least one message',
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		foreach ($tokens['messages'] as $message) {
			$messageInstance     = $this->parseMessage($message);
			$this->interchange[] = $messageInstance;
		}

		// return the final document
		return $this->interchange;
	}

	// }}}
	// tokenize() {{{

	/**
     * Parse the edi service string advice aka UNA segment, extract syntax
     * information required for parsing and tokenize the buffer.
     *
     * @access protected
     * @return void
     * @throws EDI_Exception
     */
	protected function tokenize()
	{
		// tokenize
		$segments = EDI_Common_Utils_splitEDIString( $this->buffer,
		EDI_EANCOM_Interchange::$segmentTerminator,
		NULL,
		true
		);
		// initialize tokens variable
		$tokens                = array();
		$tokens['interchange'] = array();
		$interchange           = array();
		$tokens['groups']      = array();
		$currentGroup          = array();
		$tokens['messages']    = array();
		$currentMessage        = array();
		// Because $segment as an array that can contain header of xml document,
		// if the first line are xml document definition, cut first line,
		// second line and last line (liness that contain tag document definition. Ex: <desadv> and /<desadv>)
		if( $segments[0] == $this->xml_header )
		{
			$segments_size = sizeof($segments);
			unset($segments[0]);
			unset($segments[1]);
			unset($segments[2]);
			unset($segments[$segments_size-2]);
			unset($segments[$segments_size-1]);
		}
		foreach ($segments as $segment)
		{	
			// Get segment Name (BGM, LIN...)
			$id = substr ($segment, 0, 3);
			// Get segment message
			$msg = substr ($segment, 3);
			// We don't have headers in EURITMO! Do we need to FIXME this?
			// Compose the message segments. The second element of the array is a string with the rest of the row, we
			// don't know how to split yet, this is a Mapping job
			$tokens['messages'][0][] = array ($id, $msg);
		}
		return $tokens;
	}

	// }}}
	// parseMessage() {{{

	/**
     * Parse an euritmo message and return the correponding
     * EDI_EANCOM_Message object.
     *
     * @param array $data Data of the message
     *
     * @access protected
     * @return EDI_EANCOM_Message
     */
	protected function parseMessage($data)
	{
		if (empty ($this->messageType)) {
			throw new EDI_Exception(
			'Missing message type on parse',
			EDI_Exception::E_EDI_MAPPING_ERROR
			);
		}

		$elt = new EDI_EANCOM_Message();
		if (empty($data)) {
			throw new EDI_Exception(
			'edi file malformed !',
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		// message id
		$elt->id = $this->messageType;
		// load the mapping
		$mapping = EDI_EANCOM_MappingProvider::find($elt->id);
		// children
		$children = $mapping->children();
		$i        = 0;
		while (!empty($data)) {
			list($sId,) = $data[0];
			if (!isset($children[$i])) {
				throw new EDI_Exception(
				sprintf('invalid token "%s" in message "%s"',
				$sId, $elt->id),
				EDI_Exception::E_EDI_SYNTAX_ERROR
				);
			}
			$nodeName  = (string)$children[$i]->getName();
			$nodeId    = (string)$children[$i]['id'];
			$req       = (string)$children[$i]['required'] == 'true';
			$maxrepeat = (int)$children[$i]['maxrepeat'];
			if ($nodeName == 'segment') {
				if ($sId != $nodeId) {
					if ($req) {
						throw new EDI_Exception(
						sprintf('%s "%s" required in message "%s"',
						$nodeName, $nodeId, $elt->id),
						EDI_Exception::E_EDI_SYNTAX_ERROR
						);
					}
				} else {
					if ($maxrepeat > 1) {
						$elt[] = $this->parseSegmentRepetition(
						$sId, $data, $maxrepeat, $req);
					} else {
						$elt[] = $this->parseSegment($sId, $data, $req);
					}
				}
			} else if ($nodeName == 'group') {
				if ($maxrepeat > 1) {
					$e = $this->parseSegmentGroupRepetition($sId, $data,
					$children[$i], $maxrepeat, $req);
				} else {
					$e = $this->parseSegmentGroup($sId, $data,
					$children[$i], $req);
				}
				if ($e !== null) {
					$elt[] = $e;
				}
			}
			$i++;
		}
		if (isset($children[$i]) &&
		(string)$children[$i]['required'] == 'true') {
			throw new EDI_Exception(
			sprintf('%s "%s" is required in message "%s"',
			str_replace('_', ' ', $children[$i]->getName()),
			(string)$children[$i]['id'], $elt->id),
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt->name        = (string)$mapping['name'];
		$elt->description = (string)$mapping['desc'];
		return $elt;
	}

	// }}}
	// parseSegmentGroupRepetition() {{{

	/**
     * Handle an edifact segment group repetition (maxrepeat attribute) and
     * return the corresponding EDI_EDIFACT_SegmentGroupContainer object.
     *
     * @param string           $id        Id of the segment group (SG1, SG2...)
     * @param array            &$data     Array of segments passed by reference
     * @param SimpleXmlElement $mapping   Corresponding mapping node
     * @param int              $maxrepeat Number of times the seg. group can
     *                                    be repeated
     * @param bool             $req       Set this to false if the segment is
     *                                    optional
     *
     * @access protected
     * @return EDI_EDIFACT_SegmentGroupContainer
     * @throws EDI_Exception
     */
	protected function parseSegmentGroupRepetition($id, &$data, $mapping,
	$maxrepeat, $req=true)
	{
		$segmentGroups = array();
		$hasChildren   = false;
		foreach ($data as $sDataItem) {
			if (count($segmentGroups) == $maxrepeat) {
				throw new EDI_Exception(
				sprintf('segment group "%s" cannot be repeated more than '
				. '%d times', $id, $maxrepeat),
				EDI_Exception::E_EDI_SYNTAX_ERROR
				);
			}
			$e = $this->parseSegmentGroup($id, $data, $mapping, $req);
			if ($e === null) {
				break;
			}
			$segmentGroups[] = $e;
			$hasChildren     = true;
			// we have added a segment group, other segment groups are now
			// conditional
			$req = false;
		}
		$segCount = count($segmentGroups);
		if ($segCount == 0 && $req) {
			throw new EDI_Exception(
			"segment group \"$id\" is required.",
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt     = new EDI_EANCOM_SegmentGroupContainer();
		$elt->id = $id . '_container';
		foreach ($segmentGroups as $segmentGroup) {
			$elt[] = $segmentGroup;
		}
		return $hasChildren ? $elt : null;
	}

	// }}}
	// parseSegmentGroup() {{{

	/**
     * Parse an edifact segment group and return the correponding
     * EDI_EANCOM_SegmentGroup object.
     *
     * @param string           $id      Id of the segment group (SG1, SG2...)
     * @param array            &$data   Array of CDE or data elements
     * @param SimpleXmlElement $mapping Corresponding mapping node
     * @param bool             $req     set this to false if the CDE is optional
     *
     * @access protected
     * @return EDI_EANCOM_SegmentGroup
     * @throws EDI_Exception
     */
	protected function parseSegmentGroup($id, &$data, $mapping, $req=true)
	{
		if ($req && empty($data)) {
			throw new EDI_Exception(
			"segment group \"$id\" is required.",
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt         = new EDI_EANCOM_SegmentGroup();
		$children    = $mapping->children();
		$hasChildren = false;
		foreach ($children as $node) {
			$nodeName      = (string)$node->getName();
			$nodeId        = (string)$node['id'];
			$nodeReq       = (string)$node['required'] == 'true';
			$nodeMaxRepeat = (int)$node['maxrepeat'];
			if ($nodeName == 'segment') {
				if (empty($data)) {
					if ($req && $nodeReq) {
						throw new EDI_Exception(
						sprintf('segment "%s" is required in group "%s"',
						$nodeId, $id),
						EDI_Exception::E_EDI_SYNTAX_ERROR
						);
					}
					break;
				}
				list($sId, $sData) = $data[0];
				if ($sId != $nodeId) {
					if ($req && $nodeReq) {
						throw new EDI_Exception(
						sprintf('segment "%s" is required in group "%s"',
						$nodeId, $id),
						EDI_Exception::E_EDI_SYNTAX_ERROR
						);
					} else if ($nodeReq) {
						// we can skip this group...
						break;
					}
					continue;
				}
				if ($nodeMaxRepeat > 1) {
					$e = $this->parseSegmentRepetition($sId, $data,
					$nodeMaxRepeat, $nodeReq);
				} else {
					$e = $this->parseSegment($sId, $data, $nodeReq);
				}
			} else if ($nodeName == 'group') {
				if ($nodeMaxRepeat > 1) {
					$e = $this->parseSegmentGroupRepetition($nodeId, $data,
					$node, $nodeMaxRepeat, $nodeReq);
				} else {
					$e = $this->parseSegmentGroup($nodeId, $data, $node,
					$nodeReq);
				}
			}
			if ($e !== null) {
				$elt[]       = $e;
				$hasChildren = true;
			}
		}
		$elt->id = $id . '_group';
		return $hasChildren ? $elt : null;
	}

	// }}}
	// parseSegmentRepetition() {{{

	/**
     * Handles an edifact segment repetition (maxrepeat attribute) and return
     * the corresponding EDI_EANCOM_SegmentRepetition object.
     *
     * @param string $id        Id of the segment (UNB, UNH...)
     * @param array  &$data     Array of segments passed by reference
     * @param int    $maxrepeat Number of times the segment can be repeated
     * @param bool   $req       Set this to false if the segment is optional
     *
     * @access protected
     * @return EDI_EANCOM_Segment
     * @throws EDI_Exception
     */
	protected function parseSegmentRepetition($id, &$data, $maxrepeat, $req=true)
	{
		$segments = array();
		foreach ($data as $sDataItem) {
			list($sId,) = $sDataItem;
			if ($id == $sId) {
				if (count($segments) == $maxrepeat) {
					throw new EDI_Exception(
					sprintf("segment \"%s\" cannot be repeated more "
					. "than %d times",
					$id, $maxrepeat),
					EDI_Exception::E_EDI_SYNTAX_ERROR
					);
				}
				$segments[] = $this->parseSegment($id, $data, $req);
				// we have added a segment, other segments are now conditional
				$req = false;
			} else {
				break;
			}
		}
		$segCount = count($segments);
		if ($segCount == 0 && $req) {
			throw new EDI_Exception(
			"segment \"$id\" is required.",
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt     = new EDI_EANCOM_SegmentContainer();
		$elt->id = $id . '_container';
		foreach ($segments as $segment) {
			$elt[] = $segment;
		}
		return $elt;
	}

	// }}}
	// parseSegment() {{{

	/**
     * Parses an edifact segment (UNB,UNH etc...) and return the correponding
     * EDI_EANCOM_Segment object.
     *
     * @param string $id    Id of the segment (UNB, UNH...)
     * @param array  &$data Array of CDE or DE passed by reference
     * @param bool   $req   Set this to false if the CDE is optional
     *
     * @access protected
     * @return EDI_EANCOM_Segment
     * @throws EDI_Exception
     */
	protected function parseSegment($id, &$data, $req=true)
	{
		if ($req && empty($data)) {
			throw new EDI_Exception(
			"segment \"$id\" is required.",
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt     = new EDI_EANCOM_Segment();
		$mapping = EDI_EANCOM_MappingProvider::find($id,null,null,$this->messageType);
		// children
		$children          = $mapping->children();
		$sData = $data[0][1]; // segment data (string)
		// Here we have to split the string according to the segment (avoid first element which is line type)
		for ($i = 1; $i < count ($children); $i++) {
			// Step through each element,
			$eId  = (string)$children[$i]['id'];
			//$eLen = (int)$field['maxlength'];
			$eReq = (string)$children[$i]['required'] == 'true';

			if ($children[$i]->getName() == 'data_element') {
				$e = $this->parseDataElement($eId, $sData, $eReq);
			} else {
				$e = $this->parseCompositeDataElement($eId, $sData, $eReq);
			}
			$elt [] = $e;
		}
		if (isset($children[$i]) &&
		(string)$children[$i]['required'] == 'true') {
			throw new EDI_Exception(
			sprintf('%s "%s" is required in segment "%s"',
			str_replace('_', ' ', $children[$i]->getName()),
			(string)$children[$i]['id'], $id),
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt->id          = $id;
		$elt->name        = (string)$mapping['name'];
		$elt->description = (string)$mapping['desc'];
		// remove the segment from the stack
		array_shift($data);
		return $elt;
	}

	// }}}
	// parseCompositeDataElement() {{{

	/**
     * Parses an edi composite data element (CDE) and build the correponding
     * EDI_EANCOM_CompositeDataElement object.
     *
     * @param string $id   Id of the composite data element (S001, C507...)
     * @param array  $data Array of data element values
     * @param bool   $req  Set this to false if the CDE is optional
     *
     * @access protected
     * @return EDI_EANCOM_CompositeDataElement
     * @throws EDI_Exception
     */
	protected function parseCompositeDataElement($id, &$data, $req=true)
	{
		$elt = new EDI_EANCOM_CompositeDataElement();
		if (empty($data)) {
			if ($req) {
				throw new EDI_Exception(
				"composite data element \"$id\" is required",
				EDI_Exception::E_EDI_SYNTAX_ERROR
				);
			}
			return $elt;
		}
		$mapping  = EDI_EANCOM_MappingProvider::find($id);
		$children = $mapping->children();

		for ($i = 0; $i < count ($children); $i++) {
			$eId = (string)$children[$i]['id'];
			$elt[] = $this->parseDataElement($eId, $data, $req);
		}

		if (isset($children[$i]) &&
		(string)$children[$i]['required'] == 'true') {
			throw new EDI_Exception(
			sprintf('"%s" is required in composite data element "%s"',
			(string)$children[$i]['id'], $id),
			EDI_Exception::E_EDI_SYNTAX_ERROR
			);
		}
		$elt->id          = $id;
		$elt->name        = (string)$mapping['name'];
		$elt->description = (string)$mapping['desc'];
		return $elt;
	}

	// }}}
	// parseDataElement() {{{

	/**
     * Parses an edi data element (DE) and build the correponding
     * EDI_EANCOM_DataElement object.
     *
     * @param string $id   Id of the data element (S001, C507...)
     * @param string $data Value of the data element
     *
     * @access protected
     * @return EDI_EANCOM_DataElement
     * @throws EDI_Exception
     */
	protected function parseDataElement($id, &$data, $req)
	{
		$mapping   = EDI_EANCOM_MappingProvider::find($id);
		$maxlength = isset($mapping['maxlength']) ? (int)$mapping['maxlength']: false;
		if (!is_int ($maxlength) || $maxlength == 0)
		throw new EDI_Exception(
		sprintf('invalid length "%s" for token "%s" in segment "%s"', $maxlength, $id, $data),
		EDI_Exception::E_EDI_SYNTAX_ERROR
		);
		$value     = trim (substr ($data, 0, $maxlength));
		$data      = substr ($data, $maxlength);
		$type      = (string)$mapping['type'];
		if (empty ($value) && $req)
		throw new EDI_Exception(
		sprintf('data element "%s" required in segment "%s"', $id, rtrim($data)),
		EDI_Exception::E_EDI_SYNTAX_ERROR
		);
		// validate element value
		$l = strlen($value);
		if ($maxlength !== false && $l > $maxlength) { // not really useful in EANCOM
			// max length exceeded
			$msg = sprintf(
			'data element "%s" length must be lower than "%s", got "%s"',
			$id,
			$maxlength,
			$l
			);
		} else if ($type !== false && !empty($value)) {
			$rx = '/[0-9]+'
			.preg_quote(EDI_EANCOM_Interchange::$decimalChar).'?[0-9]*/';
			if ($type == 'n' && !preg_match($rx, $value, $tokens)) {
				// wrong type
				$msg = sprintf(
				'data element "%s" must be a numeric string',
				$id
				);
			} else if ($type == 'a' && preg_match('/[0-9]/', $value)) {
				$msg = sprintf(
				'data element "%s" must be an alphabetic string',
				$id
				);
			}
		}
		if (isset($msg)) {
			if ($this->strictValidation) {
				throw new EDI_Exception(
				$msg,
				E_EDI_SYNTAX_ERROR
				);
			}
		}
		// all is OK
		$elt              = new EDI_EANCOM_DataElement();
		$elt->value       = $value;
		$elt->id          = $id;
		$elt->maxlength   = $maxlength;
		$elt->name        = (string)$mapping['name'];
		$elt->description = (string)$mapping['desc'];
		return $elt;
	}

	// }}}
}
