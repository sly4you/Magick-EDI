<?
DEFINE("OPEN_SSL_CONF_PATH", ""); //point to your config file

class ISPMOpenSSL
{
	var $private_key;			// private key
	var $public_key;			// public key
	var $private_key_passwd;	// password for private key
	var $csr;					// certificate signing request
	var $days_validity;
	var $config					= array('config' => '/etc/ssl/openssl.cnf');

	function __construct() {}

	function buildCSR($country_name, $state_or_province, $locality_name, $organization_name, $organization_unit_name, $common_name, $email_address, $bit_size, $days_validity=365)
	{
		$dn['countryName']				= strtoupper($country_name);	// required
		$dn['stateOrProvinceName']		= $state_or_province;			// required
		$dn['organizationName']			= $organization_name;			// required
		$dn['commonName']				= $common_name;					// required
		$dn['emailAddress']				= $email_address;				// optional
		
		if($locality_name != '')
			$dn['localityName']			= $locality_name;				// optional
			
		if($organization_unit_name != '')
			$dn['organizationalUnitName']	= $organization_unit_name;	// optional
			
		$this->config['private_key_bits']	= (int)$bit_size;
		$this->days_validity				= (int)$days_validity;

		// Make private key
		$private_key	= openssl_pkey_new($this->config);
		// Make certificate request
		$csr			= openssl_csr_new($dn, $privkey, $this->config);
		// Sign and build certificate
		$sscert			= openssl_csr_sign($csr, null, $private_key, $this->days_validity, $this->config);
		
		// Set certificate request for export
		openssl_csr_export($csr, $this->csr);
		// Set signed certificate for export
		openssl_x509_export($sscert, $this->public_key);
		// Set private key for export
		openssl_pkey_export($private_key, $this->private_key, $this->private_key_passwd, $this->config);
		
		print_r($this->csr);
		
		print_r($this->private_key);
		exit;
	}

	function setPrivateKeyPasswd($private_key_passwd) {
		$this->private_key_passwd = $private_key_passwd;
	}

	function getPrivateKey() {
		return $this->private_key;
	}

	function getPrivateKeyPasswd() {
		return $this->private_key_passwd;
	}

	function getPublicKey() {
		return $this->public_key;
	}

	function getCSR() {
		return $this->csr;
	}
}
?>
