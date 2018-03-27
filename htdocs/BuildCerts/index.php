<?
session_start();

include "OpenSSL.php";

$bitsizes = array( "512", "1024", "2048" );

$countries = array(
		"af" => "Afghanistan",
		"ax" => "Aland Islands",
		"al" => "Albania",
		"dz" => "Algeria",
		"as" => "American Samoa",
		"ad" => "Andorra",
		"ao" => "Angola",
		"ai" => "Anguilla",
		"aq" => "Antarctica",
		"ag" => "Antigua and Barbuda",
		"ar" => "Argentina",
		"am" => "Armenia",
		"aw" => "Aruba",
		"ac" => "Ascension Island",
		"au" => "Australia",
		"at" => "Austria",
		"az" => "Azerbaijan",
		"bs" => "Bahamas",
		"bh" => "Bahrain",
		"bd" => "Bangladesh",
		"bb" => "Barbados",
		"by" => "Belarus",
		"be" => "Belgium",
		"bz" => "Belize",
		"bj" => "Benin",
		"bm" => "Bermuda",
		"bt" => "Bhutan",
		"bo" => "Bolivia",
		"ba" => "Bosnia and Herzegovina",
		"bw" => "Botswana",
		"bv" => "Bouvet Island",
		"br" => "Brazil",
		"io" => "British Indian Ocean Territory",
		"bn" => "Brunei Darussalam",
		"bg" => "Bulgaria",
		"bf" => "Burkina Faso",
		"bi" => "Burundi",
		"kh" => "Cambodia",
		"cm" => "Cameroon",
		"ca" => "Canada",
		"cv" => "Cap Verde",
		"ky" => "Cayman Islands",
		"cf" => "Central African Republic",
		"td" => "Chad",
		"cl" => "Chile",
		"cn" => "China",
		"cx" => "Christmas Island",
		"cc" => "Cocos (Keeling) Islands",
		"co" => "Colombia",
		"km" => "Comoros",
		"cg" => "Congo,  Republic of",
		"cd" => "Congo,  The Democratic Republic of the",
		"ck" => "Cook Islands",
		"cr" => "Costa Rica",
		"ci" => "Cote d'Ivoire",
		"hr" => "Croatia/Hrvatska",
		"cu" => "Cuba",
		"cy" => "Cyprus",
		"cz" => "Czech Republic",
		"dk" => "Denmark",
		"dj" => "Djibouti",
		"dm" => "Dominica",
		"do" => "Dominican Republic",
		"tp" => "East Timor",
		"ec" => "Ecuador",
		"eg" => "Egypt",
		"sv" => "El Salvador",
		"gq" => "Equatorial Guinea",
		"er" => "Eritrea",
		"ee" => "Estonia",
		"et" => "Ethiopia",
		"fk" => "Falkland Islands (Malvina)",
		"fo" => "Faroe Islands",
		"fj" => "Fiji",
		"fi" => "Finland",
		"fr" => "France",
		"gf" => "French Guiana",
		"pf" => "French Polynesia",
		"tf" => "French Southern Territories",
		"ga" => "Gabon",
		"gm" => "Gambia",
		"ge" => "Georgia",
		"de" => "Germany",
		"gh" => "Ghana",
		"gi" => "Gibraltar",
		"gr" => "Greece",
		"gl" => "Greenland",
		"gd" => "Grenada",
		"gp" => "Guadeloupe",
		"gu" => "Guam",
		"gt" => "Guatemala",
		"gg" => "Guernsey",
		"gn" => "Guinea",
		"gw" => "Guinea-Bissau",
		"gy" => "Guyana",
		"ht" => "Haiti",
		"hm" => "Heard and McDonald Islands",
		"va" => "Holy See (City Vatican State)",
		"hn" => "Honduras",
		"hk" => "Hong Kong",
		"hu" => "Hungary",
		"is" => "Iceland",
		"in" => "India",
		"id" => "Indonesia",
		"ir" => "Iran, Islamic Republic of",
		"iq" => "Iraq",
		"ie" => "Ireland",
		"im" => "Isle of Man",
		"il" => "Israel",
		"it" => "Italy",
		"jm" => "Jamaica",
		"jp" => "Japan",
		"je" => "Jersey",
		"jo" => "Jordan",
		"kz" => "Kazakhstan",
		"ke" => "Kenya",
		"ki" => "Kiribati",
		"kp" => "Korea, Democratic People's Republic",
		"kr" => "Korea, Republic of",
		"kw" => "Kuwait",
		"kg" => "Kyrgyzstan",
		"la" => "Lao People's Democratic Republic",
		"lv" => "Latvia",
		"lb" => "Lebanon",
		"ls" => "Lesotho",
		"lr" => "Liberia",
		"ly" => "Libyan Arab Jamahiriya",
		"li" => "Liechtenstein",
		"lt" => "Lithuania",
		"lu" => "Luxembourg",
		"mo" => "Macau",
		"mk" => "Macedonia,  Former Yugoslav Republic",
		"mg" => "Madagascar",
		"mw" => "Malawi",
		"my" => "Malaysia",
		"mv" => "Maldives",
		"ml" => "Mali",
		"mt" => "Malta",
		"mh" => "Marshall Islands",
		"mq" => "Martinique",
		"mr" => "Mauritania",
		"mu" => "Mauritius",
		"yt" => "Mayotte",
		"mx" => "Mexico",
		"fm" => "Micronesia,  Federal State of",
		"md" => "Moldova,  Republic of",
		"mc" => "Monaco",
		"mn" => "Mongolia",
		"me" => "Montenegro",
		"ms" => "Montserrat",
		"ma" => "Morocco",
		"mz" => "Mozambique",
		"mm" => "Myanmar",
		"na" => "Namibia",
		"nr" => "Nauru",
		"np" => "Nepal",
		"nl" => "Netherlands",
		"an" => "Netherlands Antilles",
		"nc" => "New Caledonia",
		"nz" => "New Zealand",
		"ni" => "Nicaragua",
		"ne" => "Niger",
		"ng" => "Nigeria",
		"nu" => "Niue",
		"nf" => "Norfolk Island",
		"mp" => "Northern Mariana Islands",
		"no" => "Norway",
		"om" => "Oman",
		"pk" => "Pakistan",
		"pw" => "Palau",
		"ps" => "Palestinian Territories, Occupied",
		"pa" => "Panama",
		"pg" => "Papua New Guinea",
		"py" => "Paraguay",
		"pe" => "Peru",
		"ph" => "Philippines",
		"pn" => "Pitcairn Island",
		"pl" => "Poland",
		"pt" => "Portugal",
		"pr" => "Puerto Rico",
		"qa" => "Qatar",
		"re" => "Reunion Island",
		"ro" => "Romania",
		"ru" => "Russian Federation",
		"rw" => "Rwanda",
		"sh" => "Saint Helena",
		"kn" => "Saint Kitts and Nevis",
		"lc" => "Saint Lucia",
		"pm" => "Saint Pierre and Miquelon",
		"vc" => "Saint Vincent and the Grenadines",
		"ws" => "Samoa",
		"sm" => "San Marino",
		"st" => "Sao Tome and Principe",
		"sa" => "Saudi Arabia",
		"sn" => "Senegal",
		"rs" => "Serbia",
		"sc" => "Seychelles",
		"sl" => "Sierra Leone",
		"sg" => "Singapore",
		"sk" => "Slovak Republic",
		"si" => "Slovenia",
		"sb" => "Solomon Islands",
		"so" => "Somalia",
		"za" => "South Africa",
		"gs" => "South Georgia and the South Sandwich Islands",
		"es" => "Spain",
		"lk" => "Sri Lanka",
		"sd" => "Sudan",
		"sr" => "Suriname",
		"sj" => "Svalbard and Jan Mayen Islands",
		"sz" => "Swaziland",
		"se" => "Sweden",
		"ch" => "Switzerland",
		"sy" => "Syrian Arab Republic",
		"tw" => "Taiwan",
		"tj" => "Tajikistan",
		"tz" => "Tanzania",
		"th" => "Thailand",
		"tl" => "Timor-Leste",
		"tg" => "Togo",
		"tk" => "Tokelau",
		"to" => "Tonga",
		"tt" => "Trinidad and Tobago",
		"tn" => "Tunisia",
		"tr" => "Turkey",
		"tm" => "Turkmenistan",
		"tc" => "Turks and Caicos Islands",
		"tv" => "Tuvalu",
		"ug" => "Uganda",
		"ua" => "Ukraine",
		"ae" => "United Arab Emirates",
		"uk" => "United Kingdom",
		"us" => "United States",
		"um" => "United States Minor Outlying Islands",
		"uy" => "Uruguay",
		"uz" => "Uzbekistan",
		"vu" => "Vanuatu",
		"ve" => "Venezuela",
		"vn" => "Vietnam",
		"vg" => "Virgin Islands (British)",
		"vi" => "Virgin Islands (USA)",
		"wf" => "Wallis and Futuna Islands",
		"eh" => "Western Sahara",
		"ye" => "Yemen",
		"zm" => "Zambia",
		"zw" => "Zimbabwe" );
?>
<head>
<style type="text/css" media="all">@import "style.css";</style>
<style type="text/css">
@import url(tier4.css);
h1 { font-size: 11pt; }
h2 { font-size: 10pt; }
h3 { font-size: 9pt; }
pre.code { font-size: 5pt; width: 450px; }
table  { width: 100%; }
table.main  { width: 500px; }
tr.odd { background-color: #eeeeee; }
tr.even { background-color: #ffffff; }
* { font-size: 7pt; }
ul.spread { line-height: 2em; }
.callout { background-color: #eeeeee; padding: 5px; border-style: solid; border-color: #cccccc; border-width: 1px 0px 1px 0px; }
blockquote {padding: 5px; border-style: solid; border-color: #66cc66; border-width: 0px 0px 0px 15px; float: left; }
</style>
</head>
<?
function getParameter( $aParam )
{
	$aParam = isset( $_POST[$aParam] ) ? $_POST[$aParam] : "";
	return $aParam;
}

$attributes = array( 
	"action" => "", 
	"valid" => "", 
	"country" => "", 
	"state" => "",
	"city" => "",
	"company" => "",
	"section" => "",
	"domain" => "",
	"email" => "",
	"passphrase" => "",
	"bitsize" => "",
	"days" => "" );

foreach( $attributes as $key=>$value )
{
	$attributes[$key] = getParameter( $key );
}
?>
<body>
<table align="center" class="main">
<tr><td>

<h1>Create Self-Signed Certificate Online</h1>
<h2>Using OpenSSL extension for Php</h2>
<h3>Certificate Information</h3>

<script>
function checkForm()
{
	if( document.form1.country.options[ document.form1.country.selectedIndex ].value == "" )
	{
		alert( "Please choose a country!" );
		document.form1.country.focus();
		return false;
	}
	else if ( document.form1.state.value == "" )
	{
		alert( "Please enter a state!" );
		document.form1.state.focus();
		return false;
	}
	else if ( document.form1.company.value == "" )
	{
		alert( "Please enter a company!" );
		document.form1.company.focus();
		return false;
	}
	else if ( document.form1.domain.value == "" )
	{
		alert( "Please enter a domain!" );
		document.form1.domain.focus();
		return false;
	}
	else if ( document.form1.passphrase.value != "" && document.form1.passphrase.value.length < 4 )
	{
		alert( "Please enter at least 4 characters if you want to set a passphrase!" );
		return false;
	}
	else if( document.form1.bitsize.options[ document.form1.bitsize.selectedIndex ].value == "" )
	{
		alert( "Please choose a bitsize!" );
		document.form1.bitsize.focus();
		return false;
	}
	else if ( document.form1.days.value == "" )
	{
		alert( "Please enter validity days!" );
		document.form1.days.focus();
		return false;
	}
	else if ( document.form1.valid.value == "" )
	{
		alert( "Please enter the security code!" );
		document.form1.valid.focus();
		return false;
	}
	return true;
}
</script>

<form name="form1" action="index.php" method="POST" onsubmit="return checkForm();">
<input type="hidden" name="action" value="generate"/>
<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr class="odd">
		<th width="45%">Country Name<span style="color:red;">*</span>:</th>
		<td><select name="country" size="1" style="width: 260px;">
			<option value="">- Select a country -</option>
<?
foreach( $countries as $k=>$v )
{
	echo "<option value=\"{$k}\"";
	if ( $attributes["country"] == $k ) echo " selected=\"selected\"";
	echo ">{$v}</option>\n";
}
?>
			</select>
		</td>
	</tr>
	<tr class="event">
		<th>State or Province Name<br/>(full name)<span style="color:red;">*</span>:</th>
		<td><input name="state" id="state" size="42" maxlength="255" type="text" value="<?= $attributes["state"] ?>"></td>
	</tr>
	<tr class="odd">
		<th>Locality Name<br/>(city):</th>
		<td><input name="city" id="city" size="42" maxlength="255" type="text" value="<?= $attributes["city"] ?>"></td>
	</tr>
	<tr class="even">
		<th>Organization Name<br/>(company)<span style="color:red;">*</span>:</th>
		<td><input name="company" id="company" size="42" maxlength="255" type="text" value="<?= $attributes["company"] ?>"></td>
	</tr>
	<tr class="odd">
		<th>Organizational Unit Name<br/>(section):</th>
		<td><input name="section" id="section" size="42" maxlength="255" type="text" value="<?= $attributes["section"] ?>"></td>
	</tr>
	<tr class="even">
		<th>Common Name<br/>(my.domain.com or *.domain.com) <span style="color:red;">*</span>: </th>
		<td><input name="domain" id="domain" size="42" maxlength="255" type="text" value="<?= $attributes["domain"] ?>"></td>
	</tr>
	<tr class="odd">
		<th>Email Address: </th>
		<td><input name="email" id="email" size="42" maxlength="255" type="text" value="<?= $attributes["email"] ?>"></td>
	</tr>
	<tr class="even">
		<th>Private key passphrase<br/>(4 chars min if set):</th>
		<td><textarea cols="32" rows="3" name="passphrase" id="passphrase"><?= $attributes["passphrase"] ?></textarea></td>
	</tr>
	<tr class="odd">
		<th>RSA private key bit size<span style="color:red;">*</span>:</th>
		<td>
			<select name="bitsize" id="bitsize">
				<option value="">- Select bit size -</option>
<?
foreach( $bitsizes as $v )
{
	echo "<option value=\"{$v}\"";
	if ( $attributes["bitsize"] == $v ) echo " selected=\"selected\"";
	echo ">{$v}</option>";
}
?>
			</select>
	</td></tr>
	<tr class="even">
		<th>Number of days the certificate is valid<span style="color:red;">*</span>: </th>
		<td><input name="days" id="days" size="4" maxlength="5" value="365" type="text" value="<?= $attributes["days"] ?>"></td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr class="odd">
		<th>Security code<br/>(to prevent from automated submission))<span style="color:red;">*</span>: </th>
		<td><img src="valid.php" name="valid_img"/> <a href="javascript:void(0);" onclick="document.images.valid_img.src= 'valid.php?' + new Date().getTime();">Refresh</a><br>Enter the 3 black characters from the image above:<br/><input name="valid" id="valid" size="3" maxlength="3"></td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<td><span style="color:red;">* = required</span></td>
		<td>
			<input name="generate" value="Generate" style="width: 100px;" type="submit">
		</td>
	</tr>
</table>
</form>

<?php
$generate = false;
if ($attributes["action"] == "generate")
{
	if ( $attributes["country"]	!= "" && $attributes["state"] != "" && $attributes["company"] != "" && $attributes["domain"] != ""
		&& $attributes["bitsize"] != "" && $attributes["days"] != "" && $attributes["valid"] != "" )
	{
		$generate = true;
	}
	else
	{
		echo "<span style=\"color:red; font-weight: bold;\">Please check the mandatory attributes!</span>";
	}
}

$csr = "";
$ossl = "";
if ( $generate )
{
	if( $_SESSION["captcha"] == $attributes["valid"] )
	{
		$ossl = new ISPMOpenSSL();
		//create a key pair
		$passphrase = trim( $attributes["passphrase"] );
		if ( $passphrase != "" )
			$ossl->setPrivateKeyPasswd($passphrase);
		
		$ossl->buildCSR($attributes["country"],
						$attributes["state"],
						$attributes["city"],
						$attributes["company"],
						$attributes["section"],
						$attributes["domain"],
						$attributes["email"],
						$attributes["bitsize"],
						$attributes["days"] );
		$csr = $ossl->getCSR();
	}
	else
	{
		echo "<span style=\"color:red; font-weight: bold;\">Wrong security code! Enter the 3 black characters from the image.</span>";
	}
}
print_r($csr);

print_r($ossl->getPrivateKey());
exit;
echo "<h3>Certificate Signing Request</h3>";

echo "Certificate signing request is:<br/><br/><textarea rows=\"12\" cols=\"60\">".$csr."</textarea>";

echo "<h3>Public Key Certificate</h3>";
$publickey = "";
if ( $generate ) $publickey = $ossl->getPublicKey();
echo "Public Key is:<br/><br/><textarea rows=\"12\" cols=\"60\">".$publickey."</textarea>";

echo "<h3>Private Key</h3>";
$privatekey = "";
if ( $generate ) $privatekey = $ossl->getPrivateKey();
echo "Private Key is:<br/><br/><textarea rows=\"12\" cols=\"60\">".$privatekey."</textarea>";
?>

</td></tr>
</table>
</body>
