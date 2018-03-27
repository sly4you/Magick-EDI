<?php

class desadv {

	var $valori		= array ();
	var $position	= array (); //FIXME
	var $rfrpor		= false; // FIXME

	function __construct($dest_standard, $dest_release)
	{
		$this->dest_standard	= $dest_standard;
		$this->dest_release		= $dest_release;
		$this->dest_mapping		= "EDI_{$dest_standard}_MappingProvider";
		$this->parser			= EDI::parserFactory($dest_standard);
		$this->translated_doc	= EDI::interchangeFactory ($dest_standard, array(
		'directory'		=> $dest_release,
		'syntaxIdentifier'	=> 'UNOC',
		'syntaxVersion'		=> 1
		));
	}

	function toEDI()
	{
		//return $this->translated_doc->toEDI();

		return str_replace("\n", '', $this->translated_doc->toEDI());
	}

	function myLog ($msg)
	{

	}

	function myFormat ($num, $int, $dec, $has_sign = FALSE)
	{
		$sign = "";
		if ($has_sign)
		if ($num < 0) {
			$sign = "-";
			$num = abs($num);
		} else
		$sign = "+";

		return $sign.str_pad (number_format ((int)$num, $dec, "", ""), $int+$dec, "0", STR_PAD_LEFT);
	}


	// Trasforma un numero in formato euritmo (posizionale) in un float/int
	function euritmoNumberToInt ($num, $int, $dec, $has_sign = FALSE)
	{
		$multiplier = 1;
		if ($has_sign) {
			if (substr($num,0,1) == "-")
			$multiplier = -1;
			$num = substr($num,1);
		}

		$intpart = (int)substr($num,0,$int);
		$decpart = (int)substr($num,$int);

		return $multiplier * ($intpart + ($decpart / (pow(10,$dec))));
	}

	function preparaDate ($tipo, $valore) {
		$dt = array ();
		switch ($tipo) {
			case "718":
				$dt['2380'] = $valore;
				$dt['2379'] = "713";
				break;

			case "102":
				$dt['2380'] = substr($valore,0,8);
				$dt['2379'] = "102";
				break;

			default:
				$dt['2380'] = $valore;
				$dt['2379'] = $tipo;
				break;

		}
		return $dt;
	}
	function getCompositeEDIElement ($id, $dati)
	{
		//global $destMapping, $destStandard, $destRelease, $documento, $position;
		$cc = "EDI_{$this->dest_standard}_CompositeDataElement";
		$elid = (string)$id;
		$destMapping = $this->dest_mapping;
		$el = $destMapping::find($elid);
		$e = new $cc ();
		$e->id = (string)$el ['id'];
		$e->name = (string)$el['name'];
		$e->description = (string)$el['desc'];
		$tmparr = array ();
		foreach ($el as $c) {
			// Tengo in memoria tutti i campi del composite element, e scrivo solo se c'e' un elemento vuoto, questo per evitare
			// di scrivere dei ::: finali vuoti
			$tmparr [] = $this->getEDIElement ($c['id'], $dati[(string)$c['id']]);
			if (!empty($dati[(string)$c['id']])) {
				foreach ($tmparr as $t)
				$e [] = $t;
				$tmparr = array ();
			}
		}

		return $e;
	}

	function getEDIElement ($id, $valore)
	{
		//global $destMapping, $destStandard, $destRelease, $documento, $position;

		$cc = "EDI_{$this->dest_standard}_DataElement";
		$elid = (string)$id;
		if (array_key_exists ($elid, $this->position))
		$this->position [$elid] ++;
		else
		$this->position [$elid] = 1;

		if ($this->position[$elid] > 1) // FIXME
		return;
		$destMapping = $this->dest_mapping;
		$el = $destMapping::find($id);
		$e = new $cc ();
		$e->id = (string)$el ['id'];
		$e->name = (string)$el['name'];
		$e->description = (string)$el['desc'];
		$e->maxlength = (string)$el['maxlength'];
		$ml = (int)$el['maxlength'];
		if ($ml > 0) {
			// Se serve, accorcio
			$valore = substr ($valore, 0, $ml);
		}
		$e->value = $valore;

		return $e;
	}

	function scriviRiga ($dati = array ())
	{
		//global $destMapping, $destStandard, $destRelease, $documento, $position;

		$this->position = array();
		// Recupero la struttura del record
		$destMapping = $this->dest_mapping;
		$mapping = $destMapping::find($dati ['9001'], $this->dest_release);
		$cc = "EDI_{$this->dest_standard}_Segment";
		$elt = new $cc ();
		$elt->id = (string)$dati ['9001'];
		$elt->name = (string)$mapping['name'];
		$elt->description = (string)$mapping['desc'];

		// Iter su tutti gli elementi del segmento
		foreach ($mapping as $c) {
			if (strtoupper(substr($c['id'],0,1) == "C") || strtoupper(substr($c['id'],0,1) == "S"))
			$elt [] = $this->getCompositeEDIElement ($c['id'], $dati);
			else
			$elt [] = $this->getEDIElement ($c['id'], $dati[(string)$c['id']]);
		}
		// Torno il segmento richiesto
		$this->translated_doc [] = $elt;
	}

	function parse($str)
	{
		//global $documento,$valori;
		$this->parseHelper(new SimpleXmlIterator($str, null));

		// Footer
		$this->scriviRiga(array('9001'=>'UNT',
		'0074'=>count($this->translated_doc),
		'0062'=>$this->valori["msgrefnum"],
		));

		$this->scriviRiga(array('9001'=>'UNZ',
		'0036'=>1,
		'0020'=>$this->valori["msgrefnum"],
		));
	}

	function valDft ($val, $dft)
	{
		return empty($val)?$dft:$val;
	}

	function parseHelper($iter, $path = array ())
	{
		//global $valori;
		foreach($iter as $key=>$val) {
			$newpath = array_merge ($path, array($key));
			$attuale = implode(">",$newpath);
			//echo "PATH: $attuale\n";
			if ($iter->hasChildren()) {
				//call_user_func(__FUNCTION__, $val, $newpath);
				call_user_func(array( &$this,__FUNCTION__), $val, $newpath);
			} else {
				$this->valori[$attuale] = strval($val);
			}

			// Qui ho finito di elaborare ricorsivamente l'elemento. Quindi analizzo i vari dati raccolti
			switch ($attuale)  {
				case "desadv>bgm":
					$this->valori["msgrefnum"] = date ("YmdHis");
					$this->scriviRiga(array(
					'9001'=>'UNB',
					'0001'=>"UNOC",
					'0002'=>"1",
					'0004'=>$this->valori["desadv>bgm>e9023"],  // MITTENTE STRINGA CODIFICATA
					'0010'=>$this->valori["desadv>bgm>e9026"], // DESTINATARIO STRINGA MODIFICATA
					'0017'=>date ("ymd"),
					'0019'=>date ("hi"),
					'0020'=>"",
					'0022'=>"",
					'0026'=>$this->valori["msgrefnum"],
					));

					$this->scriviRiga(array(
					'9001'=>'UNH',
					'0062'=>$this->valori["msgrefnum"],
					'0065'=>"DESADV",
					'0052'=>"D",
					'0054'=>"96A",
					'0051'=>"UN",
					));

					$this->scriviRiga(array(
					'9001'=>'BGM',
					'1001'=>"351",
					'1004'=>$this->valori["desadv>bgm>e1004"],
					'1225'=>$this->valDft($this->valori["desadv>bgm>e1225"],9),
					));

					/* dal 2012.09.24 il record 137 (11 di euritmo) viene inviato da swing */
					/*
					$this->scriviRiga(array(
					'9001'=>'DTM',
					'2380'=>$this->valori["desadv>bgm>e9014"],
					'2379'=>$this->valDft($this->valori["desadv>bgm>e2379"],102),
					'2005'=>$this->valDft($this->valori["desadv>bgm>e2005"],137),
					));
					*/
					$this->valori["linenr"] = 1;
					break;

				case "desadv>rff":
					/*
					if( $this->valori['desadv>rff>e1153'] == 'AAS')
					{
					$this->scriviRiga(array(
					'9001'=>'RFF',
					'1153'=>$this->valori["desadv>rff>e1153"],
					'1154'=>$this->valori["desadv>rff>e1154"],
					));
					}

					/*
					$this->scriviRiga(array(
					'9001'=>'DTM',
					'2005'=>'ZZZ',
					'2380'=>$this->valori["desadv>rff>e9078"],
					'2379'=>$this->valDft($this->valori["desadv>rff>e2379"],102),
					));
					*/
					break;

				case "desadv>dtm":
					$dtmconv = array (
					'11'=>'137',
					'358'=>'132',
					);
					$dt = $this->preparaDate ($this->valori["desadv>dtm>e2379"], $this->valori["desadv>dtm>e2380"]);
					$tmp = array(
					'9001'=>'DTM',
					'2005'=>$dtmconv[$this->valori["desadv>dtm>e2005"]],
					);
					$this->scriviRiga($tmp+$dt);
					break;

				case "desadv>nad":
					$trad3055 = array (
					'ZZ'=>'ZZZ',
					'91'=>'91',
					'92'=>'92',
					'14'=>'9',
					'VA'=>'ZZZ', // non esiste un codice per la p.iva
					);

					switch($this->valori["desadv>nad>e3035"])
					{
						case "SU":
							$this->scriviRiga(array(
							'9001'=>'NAD',
							'3035'=>'CZ',
							'3055'=>$trad3055[$this->valori["desadv>nad>e9048"]],
							'3039'=>$this->valori["desadv>nad>e3039"],
							'3036'=>$this->valori["desadv>nad>e9053"],
							'3042'=>$this->valori["desadv>nad>e9033"],
							'3164'=>$this->valori["desadv>nad>e3164"],
							'3229'=>$this->valori["desadv>nad>e3229"],
							'3251'=>$this->valori["desadv>nad>e3251"],
							'3207'=>$this->valori["desadv>nad>e3207"],
							));

							break;

						case "BY":
							$this->scriviRiga(array(
							'9001'=>'NAD',
							'3035'=>'CN',
							'3055'=>$trad3055[$this->valori["desadv>nad>e9048"]],
							'3039'=>$this->valori["desadv>nad>e3039"],
							'3036'=>$this->valori["desadv>nad>e9053"],
							'3042'=>$this->valori["desadv>nad>e9033"],
							'3164'=>$this->valori["desadv>nad>e3164"],
							'3229'=>$this->valori["desadv>nad>e3229"],
							'3251'=>$this->valori["desadv>nad>e3251"],
							'3207'=>$this->valori["desadv>nad>e3207"],
							));

							break;
							
						case "DP":
							$this->scriviRiga(array(
							'9001'=>'LOC',
							'3227'=>11,
							'3225'=>$this->valori["desadv>nad>e3039"],
							'3055'=>$trad3055[$this->valori["desadv>nad>e9048"]],
							));
							break;
					}
					break;
				case "desadv>lin_group>lin":
					$this->scriviRiga(array(
					'9001'=>'CPS',
					'7164'=>$this->valori["linenr"] ++,
					));

					$this->scriviRiga(array(
					'9001'=>'LIN',
					//                    '1229'=>'1',
					'1082'=>$this->valori["desadv>lin_group>lin>e1082"],
					'7143'=>'IN',
					'7140'=>$this->valori["desadv>lin_group>lin>e9012"],
					));

					// Scriviamo il codice prodotto del compratore come IN
					$this->scriviRiga(array(
					'9001'=>'PIA',
					'4347'=>'5',
					'7140'=>$this->valori["desadv>lin_group>lin>e7140"],
					//'7143'=>$this->valori["desadv>lin_group>lin>e9058"],
					'7143'=>'SA',
					));
					/*
					$this->scriviRiga(array(
					'9001'=>'IMD',
					'7077'=>$this->valDft($this->valori["desadv>lin_group>lin>e7077"],"A"),
					'7009'=>$this->valori["desadv>lin_group>lin>e9017"],
					));
					*/
					$conv1 = array (
					'L01'=>'12',
					'L02'=>'61',
					'L03'=>'193',
					);

					$this->scriviRiga(array(
					'9001'=>'QTY',
					'6063'=>$conv1[$this->valori["desadv>lin_group>lin>e9062"]],
					'6060'=>$this->euritmoNumberToInt($this->valori["desadv>lin_group>lin>e6060"],12,3),
					'6411'=>$this->valori["desadv>lin_group>lin>e6411"],
					));
					break;

				case "desadv>lin_group>rfr":
					// Per questo sistema scriviamo tutti i dati a livello di riga!
					$this->scriviRiga(array(
					'9001'=>'RFF',
					'1153'=>$this->valori["desadv>lin_group>rfr>e1153"],
					'1154'=>$this->valori["desadv>lin_group>rfr>e1154"],
					));
					/*
					switch( $this->valori["desadv>lin_group>rfr>e1153"] )
					{
					case "ON":
					$this->scriviRiga(array(
					'9001'=>'RFF',
					//'1153'=>$this->valori["desadv>lin_group>rfr>e1153"],
					'1153'=>'ON',
					'1154'=>$this->valori["desadv>lin_group>rfr>e1154"],
					));

					break;

					case "POR":
					$this->scriviRiga(array(
					'9001'=>'RFF',
					//'1153'=>$this->valori["desadv>lin_group>rfr>e1153"],
					'1153'=>'AAK',
					'1154'=>$this->valori["desadv>lin_group>rfr>e1154"],
					));
					break;
					}

					/*
					$this->scriviRiga(array(
					'9001'=>'DTM',
					'2380'=>$this->valori["desadv>lin_group>rfr>e9078"],
					'2379'=>'102',
					));
					*/
					break;

				case "desadv>cnt":
					/*
					$this->scriviRiga(array(
					'9001'=>'CNT',
					'6069'=>$this->valori["desadv>cnt>e6069"],
					'6066'=>$this->euritmoNumberToInt($this->valori["desadv>cnt>e6066"],12,3),
					'6411'=>$this->valori["desadv>cnt>e6411"],
					));
					*/
					break;
			}
		}
		return true;
	}

}
