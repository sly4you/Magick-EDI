<?php

class delfor {

	var $valori = array ();
	var $position = array (); //FIXME

	function __construct($dest_standard, $dest_release)
	{
		$this->dest_standard	= $dest_standard;
		$this->dest_release		= $dest_release;
		$this->edi_document		= '';
		$this->dest_mapping		= "EDI_{$dest_standard}_MappingProvider";
		$this->parser			= EDI::parserFactory($dest_standard,  array(
		'directory'			=> $dest_release,
		'syntaxIdentifier'	=> 'UNOA',
		'syntaxVersion'		=> 1
		));
	}

	function toEDI()
	{
		return $this->edi_document;
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

	function scriviFooter ()
	{
		// Scrive i record CNT
		if (isset ($this->valori['cnt']) && !empty($this->valori['cnt'])) {
			foreach ($this->valori['cnt'] as $k => $v) {
				$this->scriviRiga (array (
				'9001'=>"CNT",
				'6066'=>$this->myFormat (array_sum($v), 12, 3),
				'9110'=>$this->myFormat (++ $this->valori['nsegmenti'], 12, 3),
				));
			}
		}
		// Svuoto
		$this->valori['cnt'] = array();

		$this->edi_document .= $this->translated_doc->toEDI();
		unset ($this->translated_doc);
	}

	function scriviIntestazione ()
	{
		$this->translated_doc = EDI::interchangeFactory ($this->dest_standard, array(
		'directory' => $this->dest_release,
		'syntaxIdentifier'	=> 'UNOA',
		'syntaxVersion'		=> 1,
		));

		$this->valori ['nsegmenti'] = 0;
		// Scrive i record BGM, NAB, NAS
		$this->scriviRiga (array (
		'9001'=>"BGM",
		'9023'=>$this->valori ['fornitore'],
		'9024'=>$this->valori ['fornitoreTipo'],
		'9026'=>$this->valori ['compratore'],
		'9027'=>$this->valori ['compratoreTipo'],
		'1001'=>'ORDERS',
		'1004'=>$this->valori ['numeroOrdine'],
		'9014'=>$this->valori ['dataOrdineTestata'],
		));

		$this->scriviRiga (array (
		'9001'=>"NAS",
		'3227'=>$this->valori ['fornitore'],
		'9048'=>$this->valori ['fornitoreTipo'],
		));

		$this->scriviRiga (array (
		'9001'=>"NAB",
		'3227'=>$this->valori ['compratore'],
		'9048'=>$this->valori ['compratoreTipo'],
		));

		if (array_key_exists ('nad',$this->valori) && is_array($this->valori['nad']))
		foreach ($this->valori['nad'] as $nad) {
			$this->scriviRiga ($nad);
		}

		$this->scriviRiga (array (
		'9001'=>"DTM",
		'9014'=>$this->valori['dataOrdine'],
		'2005'=>"002",
		));

	}

	function scriviRiga ($dati = array ())
	{
		// Recupero la struttura del record
		$destMapping = $this->dest_mapping;
		$mapping = $destMapping::find($dati ['9001'],null,null,'orders');
		$cc = "EDI_{$this->dest_standard}_Segment";
		$elt = new $cc ();
		$elt->id = (string)$dati ['9001'];
		$elt->name = (string)$mapping['name'];
		$elt->description = (string)$mapping['desc'];

		// Iter su tutti gli elementi del segmento
		foreach ($mapping as $c) {
			$cc = "EDI_{$this->dest_standard}_DataElement";
			$elid = (string)$c ['id'];
			if (array_key_exists ($elid, $this->position))
			$this->position [$elid] ++;
			else
			$this->position [$elid] = 1;
			$el = $destMapping::find($c ['id']);
			$e = new $cc ();
			$e->id = (string)$el ['id'];
			$e->name = (string)$el['name'];
			$e->description = (string)$el['desc'];
			$e->maxlength = (string)$el['maxlength'];
			$ml = (int)$el['maxlength'];
			$val = $dati [$elid];
			if ($ml > 0) {
				// Se serve, accorcio
				$val = substr ($val, 0, $ml);
			}
			$e->value = $val;
			$elt [] = $e;
		}
		// Torno il segmento richiesto
		$this->translated_doc [] = $elt;

		$this->valori ['nsegmenti'] ++;
	}

	function parse($str)
	{
		$this->parseHelper(new SimpleXmlIterator($str, null));
		if (isset($this->valori ['numeroRigaOrdine'])) {
			$this->scriviFooter ();
		}
	}

	function parseHelper($iter, $path = array ())
	{
		foreach($iter as $key=>$val) {
			$newpath = array_merge ($path, array($key));
			$attuale = implode(">",$newpath);
			//echo "PATH: $attuale\n"; //per debug
			if ($iter->hasChildren()) {
				call_user_func (array( &$this,__FUNCTION__), $val, $newpath);
			} else {
				$this->valori [$attuale] = strval($val);
			}

			// Qui ho finito di elaborare ricorsivamente l'elemento. Quindi analizzo i vari dati raccolti
			//print_r($attuale . "\n");
			switch ($attuale)
			{
                    		case "delfor>unh":
                    			//AR (23/11/2017) Path messa per evitare che [ftl] e [nad] vengano enduplicati,
                    			//in questo modo ad ogno ordine 'UNH' di origine resetto integralmente gli array
                    			$this->valori['ftl'] = array ();
                    			$this->valori['nad'] = array ();
                    			break;
				case "delfor>dtm":
					switch ($this->valori ["delfor>dtm>c507>e2005"])
					{
						case '137': // Data ordine
						switch ($this->valori ["delfor>dtm>c507>e2379"] == '102')
						{
							case '102':
								$this->valori ['dataOrdineTestata'] = $this->valori ['delfor>dtm>c507>e2380'];
								break;

							default:
								$this->myLog ("$attuale : rilevato formato data non gestito ".$this->valori ["delfor>dtm>c507>e2379"]);
								break;
						}
						break;

						default:
							$this->myLog ("$attuale : rilevato valore DTM non gestito ".$this->valori ["delfor>dtm>c507>e2005"]);
							//echo "Pay Attention - Values not know: " . $this->valori ["delfor>dtm>c507>e2005"] . "\n\n";
							break;
					}
					break;

				case "delfor>nad_group>nad":
					switch ($this->valori ["delfor>nad_group>nad>e3035"])
					{
						case 'SE':
						case 'SU':
							$this->valori ['fornitore'] = $this->valori ['delfor>nad_group>nad>c082>e3039'];
							$this->valori ['fornitoreTipo'] = $this->valori ['delfor>nad_group>nad>c082>e3055'];
							break;

						case 'BY':
							$this->valori ['compratore'] = $this->valori ['delfor>nad_group>nad>c082>e3039'];
							$this->valori ['compratoreTipo'] = $this->valori ['delfor>nad_group>nad>c082>e3055'];
							break;

						default:
							$this->myLog ("$attuale : rilevato valore NAD non gestito ".$this->valori ["delfor>nad_group>nad>e3035"]);
							break;
					}
					break;

				case "delfor>nad_group>nad":
					switch ($this->valori ['delfor>nad_group>nad>e3035'])
					{
						case "ST":
							// Destinazione merce
							$this->valori ['nad'][] = array (
							'9001'=>"NAD",
							'3227'=>$this->valori ['delfor>nad_group>nad>c082>e3039'],
							'9048'=>$this->valori ['delfor>nad_group>nad>c082>e3055'],
							'9053'=>$this->valori ['delfor>nad_group>nad>c080>e3036'],
							);
							break;

						default:
							$this->myLog ("$attuale : rilevato valore NAD non gestito ".$this->valori ["delfor>gis_group>sg7_group>nad>e3035"]);
							break;
					}
					break;

				case "delfor>nad_group>sg8_group>lin":
					// Memorizzo l'ultima riga letta
					$this->valori ['lastLin'] = $this->valori ['delfor>nad_group>sg8_group>lin>c212>e7140'];
					$this->valori ['lastLinTipo'] = $this->valori ['delfor>nad_group>sg8_group>lin>c212>e7143'];
					break;

				case "delfor>nad_group>sg8_group>loc":
					// Punti di consegna
					// Li porto tutti, anche se in realta' EANCOM non gestisce il TIPO della riga LOC, c'è solo la destinazione
					switch ($this->valori ["delfor>nad_group>sg8_group>loc>e3227"])
					{
						case "18": //warehouse
						$this->valori ['loc'][] = array (
						'3227'=>$this->valori ["delfor>nad_group>sg8_group>loc>c517>e3225"],
						'9048'=>strlen($this->valori ["delfor>nad_group>sg8_group>loc>c517>e3055"]) ? $this->valori ["delfor>nad_group>sg8_group>loc>c517>e3055"]: "92",
						);
						break;

						case "19": //factory/plant
						$this->valori ['loc'][] = array (
						'3227'=>$this->valori ["delfor>nad_group>sg8_group>loc>c517>e3225"],
						'9048'=>strlen($this->valori ["delfor>nad_group>sg8_group>loc>c517>e3055"]) ?  $this->valori ["delfor>nad_group>sg8_group>loc>c517>e3055"]: "92",
						);
						break;

						case "159": //additional internal destination
						$this->valori ['loc'][] = array (
						'3227'=>$this->valori ["delfor>nad_group>sg8_group>loc>c517>e3225"],
						'9048'=>strlen($this->valori ["delfor>nad_group>sg8_group>loc>c517>e3055"]) ?  $this->valori ["delfor>nad_group>sg8_group>loc>c517>e3055"] : "92",
						);
						break;

						default:
							$this->myLog ("$attuale : rilevato valore NAD non gestito ".$this->valori ["delfor>nad_group>sg8_group>loc>e3227"]);
							break;
					}
					break;

				case "delfor>nad_group>sg8_group>sg10_group":
					switch ($this->valori ['delfor>nad_group>sg8_group>sg10_group>rff>c506>e1153'])
					{
						case "ON": // Numero ordine
							$this->valori ['numeroOrdine'] = $this->valori ['delfor>nad_group>sg8_group>sg10_group>rff>c506>e1154'];
						break;

						default:
							$this->myLog ("$attuale : rilevato valore NAD non gestito ".$this->valori ['delfor>nad_group>sg8_group>sg10_group>rff>c506>e1153']);
							break;
					}
					break;

				case "delfor>nad_group>sg8_group>sg12_group>sg13_group":
					// Riferimenti a livello di RIGA, non previsti in EURITMO. Se si vuole gestire diversamente occorre probabilmente
					// fare un ORDERS per ogni schedulazione (sg18_group)
					switch ($this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1153'])
					{
						case "ON": // Numero ordine
						$this->valori ['numeroOrdine'] = $this->valori ['delfor>nad_group>sg8_group>sg10_group>rff>c506>e1154'];
						break;

						case "AAK": // Numero avviso di spedizione
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Avviso di spedizione: ".$this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'].
						" del ".$this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>dtm>c507>e2380'],
						);
						break;

						case "AAN": // Numero assegnato dal buyer
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Numero schedulazione: ".$this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'],
						);
						break;
						
						case "AIF": // Schedulazione precedente
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Numero schedulazione precedente: ".$this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'],
						);
						break;

						case "AAP":
							$this->valori ['ftl'][] = array (
							'9001'=>'FTL',
							'9019'=>"IO/PO -- Rad/Line: ".$this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'],
							);
							// DEDICATO FORSHEDA!
							$this->valori['numeroOrdine'] = $this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'];
							break;

						case "AAU":
							$this->valori ['ftl'][] = array (
							'9001'=>'FTL',
							'9019'=>"IO/PO -- Rad/Line: ".$this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'],
							);
							// DEDICATO FORSHEDA!
							$this->valori['numeroOrdine'] = $this->valori ['delfor>nad_group>sg8_group>sg12_group>sg13_group>rff>c506>e1154'];
							break;

						default:
							$this->myLog ("$attuale : rilevato valore NAD non gestito ".$this->valori ["delfor>nad_group>sg8_group>sg12_group>sg13_group>rff"]);
							break;
					}
					break;

				case "delfor>nad_group>sg8_group>sg10_group": // Schedulazione
				// In realta' per forsheda non ci sono
				//$this->valori ['schedulazioni'][$cnt]['tipo'] = $this->valori ["delfor>nad_group>sg8_group>sg12_group>scc>e4017"];
				$this->valori['schedulazioni'] = array ();
				$this->valori['cntsched'] = 0;
				break;

				case "delfor>nad_group>sg8_group>sg12_group>scc": // Tipo schedulazione
				$cnt = $this->valori ['cntsched'];
				$this->valori['schedulazioni'][$cnt]['scc_tipo'] = $this->valori ['delfor>nad_group>sg8_group>sg12_group>scc>e4017'];

				// Nota sul tipo di schedulazione
				//echo "Scedulazione tipo: " . $this->valori ['delfor>nad_group>sg8_group>sg12_group>scc>e4017'] . "\n\n";
				switch ($this->valori ['delfor>nad_group>sg8_group>sg12_group>scc>e4017'])
				{
					case "1":
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Order status: Manufacture commitment",
						);
						break;

					case "2":
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Order status: Commitment for manufacturing and material",
						);
						break;

					case "3":
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Order status: Commitment for material",
						);
						break;
					case "4":
						$this->valori ['ftl'][] = array (
						'9001'=>'FTL',
						'9019'=>"Order status: Planning/Forecast",
						);
						break;
				}
				break;
				
				case "delfor>nad_group>sg8_group>sg12_group>qty": // Quantita' dell'ultima schedulazione
				// Creo schedulazione
				$cnt = $this->valori ['cntsched'];
				switch ($this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6063"]) {
					case "12":
						$this->valori ['schedulazioni'][$cnt]['quantita'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6060"];
						$this->valori ['schedulazioni'][$cnt]['quantita_tipo'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6063"];
						break;
					case "113":
						$this->valori ['schedulazioni'][$cnt]['quantita'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6060"];
						$this->valori ['schedulazioni'][$cnt]['quantita_tipo'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6063"];
						break;

					case "70":
						$this->valori ['schedulazioni'][$cnt]['quantita'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6060"];
						$this->valori ['schedulazioni'][$cnt]['quantita_tipo'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6063"];
						break;

					default:
						//$this->myLog ("$attuale : rilevato valore tipo quantita non gestito ";
						//echo "Pay Attention - Values not know: " . $this->valori["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6063"] . "\n\n";
						//$this->valori ["delfor>nad_group>sg8_group>sg12_group>qty>c186>e6063"]);
						break;
				}
				break;
				
				case "delfor>nad_group>sg8_group>sg12_group>dtm": // Data dell'ultima schedulazione
				if (!isset ($this->valori ['schedulazioni'])) {
					$this->myLog ("$attuale : schedulazione non presente");
					break;
				}
				switch ($this->valori ["delfor>nad_group>sg8_group>sg12_group>dtm>c507>e2005"]) {
					case "63":
					case "51":
					case "2":
						$cnt = $this->valori ['cntsched'];
						$this->valori ['schedulazioni'][$cnt]['dataConsegna'] =
						$this->valori ["delfor>nad_group>sg8_group>sg12_group>dtm>c507>e2380"];
						//FIXME gestire la conversione della data
						break;
					
					default:
						$this->myLog ("$attuale : rilevato valore tipo data non gestito ".
						"Pay Attention - Values not know: " . $this->valori ["delfor>nad_group>sg8_group>sg12_group>dtm>c507>e2005"]) . "\n\n";
						break;
				}
				break;
				
				case "delfor>nad_group>sg8_group>sg12_group": // Scrittura riga
				if (is_array($this->valori['schedulazioni'])) {
					foreach ($this->valori ['schedulazioni'] as $s) {
						// Se la data di consegna è più vecchia o uguale della data dell'ordine in testata
						// (ovvero, la data in cui il documento DELFOR è stato emesso), salto!
						if(strtotime($s['dataConsegna']) <= strtotime($this->valori['dataOrdineTestata']))
						continue;

						// Ignoro schedulazioni di tipo > 4
						if (array_key_exists ('scc_tipo', $s) && $s['scc_tipo'] > 4)
						continue;
						// Ignoro quantita' di tipi <> 113
						if ($s['quantita_tipo'] != "113")
						continue;
						
						// Per far capire a Swing che la schedulazione rimpiazza un ordine esistente usiamo come chiave
						// numero ordine (RFF+ON) e data schedulazione (LIN>DTM)
						// Quando cambia la chiave (nr ordine + data schedulazione + codice prodotto) occorre scrivere un nuovo documento,
						// non solo una nuova riga
						// La data ordine e' quella della schedulazione. Se vuota viene presa dalla testata dell'intero documento
						$this->valori['dataOrdine'] = strlen($s['dataConsegna']>0)?$s['dataConsegna']:$this->valori['dataOrdineTestata'];
						$newkey = $this->valori['lastLin'].$this->valori['dataOrdine'].$this->valori['numeroOrdine'];

						// Scrivo l'intestazione
						if ($newkey != $this->valori['key'] || !isset ($this->valori['numeroRigaOrdine'])) {
							if (isset($this->valori ['numeroRigaOrdine']))
								$this->scriviFooter();
							
							$this->scriviIntestazione();
							$this->valori ['numeroRigaOrdine'] = 1;
							$this->valori ['key'] = $newkey;
						}
						$this->scriviRiga (array (
						'9001'=>"LIN",
						'1082'=>$this->valori ['numeroRigaOrdine'] ++,
						'9011'=>$this->valori ['lastLin'],
						'9017'=>$this->valori ['lastLin'],
						// '9058'=>$this->valori ['lastLinTipo'],
						'6060'=>$this->myFormat ($s ['quantita'], 12, 3),
						// Unita di misura, fissa!
						'6411'=>"PCE",
						//FIXME '9037'=>, // Numero di pezzi per cartone
						));

						$this->scriviRiga (array (
						'9001'=>"DTR",
						//                    '9014'=>$s ['dataConsegna'],
						'9014'=>$this->valori ['dataOrdine'],
						'2005'=>"002",
						));

						// Note riga
						if (array_key_exists ("ftl",$this->valori) && is_array($this->valori['ftl']))
						foreach ($this->valori ['ftl'] as $f)
						{	
							$this->scriviRiga ($f);
						}

						// Array per la generazione delle righe CNT
						// se la gestiamo questo dovra' diventare un array con le diverse UM
						$this->valori ['cnt'][0][] = $s ['quantita'];
					}
				}
				// Reset variabili di linea!
				$this->valori ['schedulazioni'] = array ();
				// FIXME Probabilmente e' superfluo, così come le schedulazioni, perche' l'sg12 e' una schedulazione e al suo interno abbiamo sempre una sola quantita' per gruppo.
				// FIXME Intestazione e footer vengono gestiti dal cambio di chiave quindi com'e' strutturato ora non avremo mai piu' di una schedulazione per ciclo
				$this->valori ['cntsched'] = 0;
				unset($this->valori['ftl']);
				break;
			}
			/*
			$arr[$key][] = ($iter->hasChildren())?
			call_user_func (__FUNCTION__, $val, $path)
			: strval($val);
			*/
		}
		//    return $arr;
		return ;
	}

}
