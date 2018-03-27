<?php
class As2MagickEdiServer extends As2Server {
	
    const TYPE_MESSAGE = 'Message';
    const TYPE_MDN     = 'MDN';
    
    var $_db			= false;

    /**
     * Handle a request (server side)
     * 
     * @param request     (If not set, get data from standard input)
     * 
     * @return request    The request handled
     */
    public static function handleRequest($request = null)
    {
        // handle any problem in case of SYNC MDN process
        ob_start();

        try {
            $error = null;
            if (is_null($request)){
                // content loading
                $data = file_get_contents('php://input');
                if (!$data) throw new AS2Exception('An empty AS2 message (no content) was received, the message will be suspended.');
	            // Try to connect on Db
	            if( !$this->_db = new dbOperation() ) {
	            	throw new AS2Exception('Sorry, AS2 Db connection failed. I cannot retry partner info. The message will be suspended.');
	            }
                
                // headers loading
                $headers = AS2Header::parseHttpRequest();
                if (!count($headers)) throw new AS2Exception('An empty AS2 message (no headers) was received, the message will be suspended.');
                
                // check content of headers
                if (!$headers->exists('message-id')) throw new AS2Exception('A malformed AS2 message (no message-id) was received, the message will be suspended.');
                if (!$headers->exists('as2-from'))   throw new AS2Exception('An AS2 message was received that did not contain the AS2-From header.');
                if (!$headers->exists('as2-to'))     throw new AS2Exception('An AS2 message was received that did not contain the AS2-To header.');
                
                // Check if message have a valid combination customer/partner
                if (!$this->getAs2CustomerPartnerData( trim($headers->getHeader('as2-from')), trim($headers->getHeader('as2-to')) ))
                {
                	throw new AS2Exception($this->error_description . ' The message will be suspended.');
                }
                // main save action
                $filename = self::saveMessage($data, $headers);
                
                // request building
                $request = new AS2Request($data, $headers); // TODO : implement AS2Header into AS2Request
                
                // warning / notification
                if (trim($headers->getHeader('as2-from')) == trim($headers->getHeader('as2-to'))) AS2Log::warning($headers->getHeader('message-id'), 'The AS2-To name is identical to the AS2-From name.');
                // log some informations
                AS2Log::info($headers->getHeader('message-id'), 'Incoming transmission is a AS2 message, raw message size: ' . round(strlen($data)/1024, 2) . ' KB.');
                
                // try to decrypt data
                $decrypted = $request->decrypt();
                // save data if encrypted
                if ($decrypted) {
                    $content = file_get_contents($decrypted);
                    self::saveMessage($content, array(), $filename.'.decrypted', 'decrypted');
                }
            }
            elseif (!$request instanceof AS2Request){
                throw new AS2Exception('Unexpected error occurs while handling AS2 message : bad format');
            }
            else {
                $headers = $request->getHeaders();
            }
            
            $object = $request->getObject();
        }
        catch(Exception $e){
            // get error while handling request
            $error = $e;
            //throw $e;
        }
        
        //
        $mdn = null;

        if ($object instanceof AS2Message || (!is_null($error) && !($object instanceof AS2MDN))){
            $object_type = self::TYPE_MESSAGE;
            AS2Log::info(false, 'Incoming transmission is a Message.');
            
            try {
                if (is_null($error)){
                    $object->decode();
                    $files = $object->getFiles();
                    AS2Log::info(false, count($files) . ' payload(s) found in incoming transmission.');
                    foreach($files as $key => $file){
                        $content = file_get_contents($file['path']);
                        AS2Log::info(false, 'Payload #' . ($key+1) . ' : ' . round(strlen($content) / 1024, 2) . ' KB / "' . $file['filename'] . '".');
                        self::saveMessage($content, array(), $filename . '.payload_'.$key, 'payload');
                    }

                    $mdn = $object->generateMDN($error);
                    $mdn->encode($object);
                }
                else {
                    throw $error;
                }
            }
            catch(Exception $e){
                $params = array('partner_from' => $headers->getHeader('as2-from'),
                                'partner_to'   => $headers->getHeader('as2-to'));
                $mdn = new AS2MDN($e, $params);
                $mdn->setAttribute('original-message-id', $headers->getHeader('message-id'));
                $mdn->encode();
            }
        }
        elseif ($object instanceof AS2MDN) {
            $object_type = self::TYPE_MDN;
            AS2Log::info(false, 'Incoming transmission is a MDN.');
        }
        else {
            AS2Log::error(false, 'Malformed data.');
        }
        
        // call Connector object to handle specific actions
        try {
            if ($request instanceof AS2Request) {
                // build arguments
                $params = array('from'   => $headers->getHeader('as2-from'),
                                'to'     => $headers->getHeader('as2-to'),
                                'status' => '',
                                'data'   => '');
                if ($error) {
                    $params['status'] = AS2Connector::STATUS_ERROR;
                    $params['data']   = array('object'  => $object,
                                              'error'   => $error);
                }
                else {
                    $params['status'] = AS2Connector::STATUS_OK;
                    $params['data']   = array('object'  => $object,
                                              'error'   => null);
                }
            
                // call PartnerTo's connector
                if ($request->getPartnerTo() instanceof AS2Partner) {
                    $connector = $request->getPartnerTo()->connector_class;
                    call_user_func_array(array($connector, 'onReceived' . $object_type), $params);
                }
            
                // call PartnerFrom's connector
                if ($request->getPartnerFrom() instanceof AS2Partner) {
                    $connector = $request->getPartnerFrom()->connector_class;
                    call_user_func_array(array($connector, 'onSent' . $object_type), $params);
                }
            }
        }
        catch(Exception $e) {
            $error = $e;
        }

        // build MDN
        if (!is_null($error) && $object_type == self::TYPE_MESSAGE) {
            $params = array('partner_from' => $headers->getHeader('as2-from'),
                            'partner_to'   => $headers->getHeader('as2-to'));
            $mdn = new AS2MDN($e, $params);
            $mdn->setAttribute('original-message-id', $headers->getHeader('message-id'));
            $mdn->encode();
        }

        // send MDN
        if (!is_null($mdn)){
            if (!$headers->getHeader('receipt-delivery-option')) {
                // SYNC method

                // re-active output data
                ob_end_clean();

                // send headers
                foreach($mdn->getHeaders() as $key => $value) {
                    $header = str_replace(array("\r", "\n", "\r\n"), '', $key . ': ' . $value);
                    header($header);
                }
                
                // output MDN
                echo $mdn->getContent();

                AS2Log::info(false, 'An AS2 MDN has been sent.');
            }
            else {
                // ASYNC method

                // cut connection and wait a few seconds
                self::closeConnectionAndWait(5);

                // delegate the mdn sending to the client
                $client = new AS2Client();
                $result = $client->sendRequest($mdn);
                if ($result['info']['http_code'] == '200'){
                    AS2Log::info(false, 'An AS2 MDN has been sent.');
                }
                else{
                    AS2Log::error(false, 'An error occurs while sending MDN message : ' . $result['info']['http_code']);
                }
            }
        }
        
        return $request;
    }
    
    function getAs2CustomerPartnerData( $as2From, $as2To )
    {
    	$query = 'SELECT * FROM partner_connector_as2'
    	.		 " WHERE partner_type='remote'"
    	.		 " AND partner_from='" . mysql_real_escape_string($asFrom) . "'"
    	.		 " AND partner_to='" . mysql_real_escape_string($asTo) . "'"
    	;
    	if( !$partner_connector_data = $this->_db->getRow($query) )
    	{
    		$this->error_description = 'Sorry, partner ' . $as2From . ' not found.';
    		return false;
    	}
    	// O.K., as2 params are found in system. Now, get others data from partner
    	// identifying table and check if user are active
    	$query = 'SELECT partner_identifying.*, user_autentication.user_id FROM partner_identifying'
    	.		 ' LEFT JOIN user_authentication ON user_autentication.user_id=partner_identifying.user_id'
    	.		 " WHERE partner_identifying.partner_id='" . $partner_connector_data['partner_id'] . "'"
    	.		 " AND user_authentication.status='1'"
    	;
    	if( !$partner_data = $this->_db->getRow($query) )
    	{
    		$this->error_description = 'Sorry, partner ' . $as2From . ' found but user are not active.';
    		return false;
    	}
    	
    }	
}