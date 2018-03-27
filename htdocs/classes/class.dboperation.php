<?php
require_once __DIR_CLASSES__ . 'adodb/adodb.inc.php';

class dbOperation
{	
	var $_db		= false;
	var $recordSet	= false;
	
	function __construct( $debug=false )
	{
		$this->_db = NewADOConnection(__DB_TYPE__);
		$this->_db->debug = $debug;
		$this->_db->setFetchMode(ADODB_FETCH_ASSOC);
		if( !$this->_db->Connect(__DB_LOCATION__, __DB_USER__, __DB_PASSWORD__, __DB_NAME__) )
		{
			$this->error_code			= '1001';
			$this->error_description	= 'Unable to connect Database';
			return false;
		}
		return true;
	}
	
	function setQuery( $query )
	{
		if( !$this->_db->recordSet = $this->_db->Execute($query) )
		{
			$this->error_code			= '1002';
			$this->error_description	= $this->_db->ErrorMsg();
			return false;
		}
		return true;
	}
	
	function getSingle( $query )
	{
		if( !$this->_db->Execute($query) )
		{
			return false;
		}
		if( $result = $this->_db->recordSet->RecordCount() )
		{
			return false;
		}
		return $this->recordSet->fields;
	}

	function getList( $query )
	{
		if( !$this->_db->Execute($query) )
		{
			return false;
		}
		if( !$result = $this->_db->recordSet->RecordCount() )
		{
			return false;
		}
		$x = 0;
		while( !$this->_db->recordSet->EOF )
		{
			$result[$x] = $this->_db->recordSet->fields;
			$x += 1;
			$this->_db->recordSet->MoveNext();
		}
		return $result;
	}
	
}