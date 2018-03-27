<?php

require_once __DIR_CLASSES__ . 'class.dboperation.php';

class Authentication {

	function __construct()
	{
		$dbObj = new dbOperation();
		$this->_db = $dbObj->_db;
		return true;
	}
	
	function login($username, $password)
	{
		$query = 'SELECT user_authentication.user_id, user_identifying.* FROM user_authentication'
		.		 ' LEFT JOIN user_identifying ON user_identifying.user_id=user_authentication.user_id'
		.		 " WHERE user_authentication.username='" . mysql_real_escape_string($username) . "'"
		.		 " AND user_authentication.password='" . mysql_real_escape_string($password) . "'"
		.		 " AND user_authentication.status='1'";
		if( !$user = $this->_db->getRow($query) )
		{
			return false;
		}
		return $user;
	}
	
	function logout()
	{
		session_destroy();
		return true;
	}
}




?>