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
 * @copyright 2012 YetOpen S.r.l.
 * @license   http://opensource.org/licenses/mit-license.php MIT License 
 * @version   SVN: $Id: MappingProvider.php,v 1.1.1.1 2008/09/14 16:22:20 izi Exp $
 * @link      http://pear.php.net/package/EDI
 * @link      http://en.wikipedia.org/wiki/EDIFACT
 * @link      http://www.unece.org/trade/untdid/welcome.htm
 * @since     File available since release 0.1.0
 * @filesource
 */

/**
 * A class to convert an EDI_{$standard}_Interchange to another.
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

class EDI_EANCOM_Translator
{
    // Properties {{{

    private static $fields = array (
        'BGM1001' => 'document_type',
        'BGM1004' => 'document_number', 
        'BGM9014' => 'document_date',
        'BGM9043' => 'document_time',
        'BGM9026' => 'sender_code',
        'BGM9027' => 'sender_code_type',
        'BGM9023' => 'buyer_code',
        'BGM9024' => 'buyer_code_type',

        'NAS3227' => 'seller_delivery_point_code',
        'NAS9048' => 'seller_delivery_point_code_type',
        'NAS9053' => 'seller_name',
        'NAS9033' => 'seller_address',
        'NAS3164' => 'seller_city',
        'NAS3251' => 'seller_zip',
        'NAS3207' => 'seller_country',
        'NAS9076' => 'seller_vat',

        'NAI3227' => 'buyer_delivery_point_code',
        'NAI9048' => 'buyer_delivery_point_code_type',
        'NAI9053' => 'buyer_name',
        'NAI9033' => 'buyer_address',
        'NAI3164' => 'buyer_city',
        'NAI3251' => 'buyer_zip',
        'NAI3207' => 'buyer_country',
        'NAI9076' => 'buyer_vat',

        'FTX6345' => 'currency',

        'PAT4279' => 'payment_terms_qualifier',
        'PAT9078' => 'payment_date_reference', // always CCYYMMDD
        'PAT2475' => 'payment_date_reference_code', 
        'PAT2009' => 'payment_date_time_relation_code', 
        'PAT2151' => 'payment_date_time_of_period_coded', 
        'PAT2152' => 'payment_date_time_periods', 
        'PAT9079' => 'payment_amount', 
        'PAT9080' => 'payment_percentage', 
        'PAT9081' => 'payment_description', 
    );

    private static $convert = array (
        'document_type' => array (
            "INVOIC" => "380",
            "NOTACC" => "381",
            "NOTADD" => "383",
         ),
         'sender_code_type' => array (
            "VA" => "VA",
            "91" => "ZZZ",
            "92" => "ZZZ",
         ),
    );

    private $destination = array ();

    /**
     * Variable for holding temporary array
     *
     * @var array $xmlspecs
     * @access protected
     * @static
     */
    protected $sourceInterchange;

    // }}}
    // parseOrigin() {{{

    /**
     * Parse the original interchange and populates the intermediate array.
     *
     * @param mixed $sourceIch Source interchange
     *
     * @access public
     * @static
     * @throws EDI_Exception
     */
    public function parseOrigin($sourceIch, $segment = null)
    {
    	$x = new SimpleXMLElement ($sourceIch->toXml());
        foreach ($x as $c) {
            echo "parsing $c->id\n";
            if ($c instanceof EDI_EANCOM_Segment) {
                $segment = $c->id;
                print_r("Standard segment ----> " . $c->id . "\n");
            } else if ($c instanceof EDI_EANCOM_DataElement) {
                $key = $segment.$c->id;
                // Some special segments have additional data

                if (array_key_exists ($key, $this->fields))
                	print_r("Not standard segment: " . $this->fields . ' --> ' . $key . ' with value --> ' . $c->value . "\n" );
                    $this->destination [$translationFields [$key]] = $c->value;
            } else {
                $this->parseOrigin ($c, $segment);
            }

        }
    }
    // }}}
    public function dump () { var_dump ($this->destination);}
}
