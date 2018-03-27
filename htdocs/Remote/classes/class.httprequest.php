<?php


class httpRequest
{
	function sendHttpRequest( $host, $path, $parameters, $type='POST' )
	{
		
		$opts = array('http' =>	array('method'  => $type,
									  'header'  => 'Content-type: application/x-www-form-urlencoded',
									  'content' => http_build_query($parameters)
									  ));

		$context = stream_context_create($opts);
		if(!$result_content = file_get_contents($host . '/' . $path, false, $context) )
			throw new Exception('Unable to connect with remote host ' . $host);

		return $result_content;		
	}
	
	function parseHttpRequestJsonResponse( $host, $path, $parameters, $type='POST' )
	{
		$response = httpRequest::sendHttpRequest( $host, $path, $parameters, $type);
		if( !$json_result = json_decode($response, true) )
			throw new Exception('Unable to get valid json response ' . json_last_error());
			
		return $json_result;
	}
}