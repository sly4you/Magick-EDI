<?

$valori = array ();
$position = array (); //FIXME

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


function getCompositeEDIElement ($id, $dati)
{
    global $destMapping, $destStandard, $destRelease, $documento, $position;
    $cc = "EDI_{$destStandard}_CompositeDataElement";
    $elid = (string)$id;
    $el = $destMapping::find($elid);
    $e = new $cc ();
    $e->id = (string)$el ['id'];
    $e->name = (string)$el['name'];
    $e->description = (string)$el['desc'];
    foreach ($el as $c) {
        if (!empty($dati[(string)$c['id']])) // Puo' creare problemi se ci sono dei campi vuoti in mezzo?
            $e [] = getEDIElement ($c['id'], $dati[(string)$c['id']]);
    }

    return $e;
}

function getEDIElement ($id, $valore)
{
    global $destMapping, $destStandard, $destRelease, $documento, $position;

    $cc = "EDI_{$destStandard}_DataElement";
    $elid = (string)$id;
    if (array_key_exists ($elid, $position))
        $position [$elid] ++;
    else
        $position [$elid] = 1;

    if ($position[$elid] > 1) // FIXME
        return;
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
    global $destMapping, $destStandard, $destRelease, $documento, $position;

    $position = array();

    // Recupero la struttura del record
    $mapping = $destMapping::find($dati ['9001'],$destRelease);
    $cc = "EDI_{$destStandard}_Segment";
    $elt = new $cc (); 
    $elt->id = (string)$dati ['9001'];
    $elt->name = (string)$mapping['name'];
    $elt->description = (string)$mapping['desc'];

    // Iter su tutti gli elementi del segmento
    foreach ($mapping as $c) {
        if (strtoupper(substr($c['id'],0,1) == "C") || strtoupper(substr($c['id'],0,1) == "S"))
            $elt [] = getCompositeEDIElement ($c['id'], $dati);
        else
            $elt [] = getEDIElement ($c['id'], $dati[(string)$c['id']]);
    }
    // Torno il segmento richiesto
    $documento [] = $elt;
}

function parse($str) 
{
    global $documento,$valori;
    parseHelper(new SimpleXmlIterator($str, null));

    // Footer
    scriviRiga(array(
        '9001'=>'UNT',
        '0074'=>(count($documento)-2),
        '0065'=>$valori["msgrefnum"],
    ));
    scriviRiga(array(
        '9001'=>'UNZ',
        '0036'=>1,
        '0020'=>$valori["msgrefnum"],
    ));


}

function valDft ($val, $dft) 
{
    return empty($val)?$dft:$val;
}

function parseHelper($iter, $path = array ()) 
{
    global $valori;
    foreach($iter as $key=>$val) {
        $newpath = array_merge ($path, array($key));
        $attuale = implode(">",$newpath);
        //echo "PATH: $attuale\n";
        if ($iter->hasChildren()) {
            call_user_func (__FUNCTION__, $val, $newpath);
        } else {
            $valori [$attuale] = strval($val);
        }

        // Qui ho finito di elaborare ricorsivamente l'elemento. Quindi analizzo i vari dati raccolti
        switch ($attuale)  {
        case "desadv>bgm":
                $valori["msgrefnum"] = date ("YmdHis");
                scriviRiga(array(
                    '9001'=>'UNB',
                    '0001'=>"UNOC",
                    '0002'=>"3",
                    '0004'=>"MITTENTE",
                    '0010'=>"DESTINATARIO",
                    '0017'=>date ("ymd"),
                    '0019'=>date ("hi"),
                    '0020'=>"",
                    '0022'=>"",
                    '0026'=>$valori["msgrefnum"],
                ));
                scriviRiga(array(
                    '9001'=>'UNH',
                    '0062'=>$valori["msgrefnum"],
                    '0065'=>"DESADV",
                    '0052'=>"D",
                    '0054'=>"96A",
                    '0051'=>"UN",
                ));
                scriviRiga(array(
                    '9001'=>'BGM',
                    '1001'=>"351",
                    '1004'=>$valori["desadv>bgm>e1004"],
                    '1225'=>valDft($valori["desadv>bgm>e1225"],9),
                ));
                scriviRiga(array(
                    '9001'=>'DTM',
                    '2380'=>$valori["desadv>bgm>e9014"],
                    '2379'=>valDft($valori["desadv>bgm>e2379"],102),
                    '2005'=>valDft($valori["desadv>bgm>e2005"],137),
                ));
                $valori["linenr"] = 1;
            break;
        case "desadv>rff":
                scriviRiga(array( 
                    '9001'=>'RFF',
                    '1153'=>$valori["desadv>rff>e1153"],
                    '1154'=>$valori["desadv>rff>e1154"],
                ));
                scriviRiga(array(
                    '9001'=>'DTM',
                    '2380'=>$valori["desadv>rff>e9078"],
                    '2379'=>valDft($valori["desadv>rff>e2379"],102),
                ));
            break;
        case "desadv>nad":
                scriviRiga(array(
                    '9001'=>'NAD',
                    '3035'=>$valori["desadv>nad>e3035"],
                    '3039'=>$valori["desadv>nad>e3039"],
                    '3036'=>$valori["desadv>nad>e9053"],
                    '3042'=>$valori["desadv>nad>e9033"],
                    '3164'=>$valori["desadv>nad>e3164"],
                    '3229'=>$valori["desadv>nad>e3229"],
                    '3251'=>$valori["desadv>nad>e3251"],
                    '3207'=>$valori["desadv>nad>e3207"],
                ));
                scriviRiga(array(
                    '9001'=>'NAD',
                    '3035'=>"BY",
                    '3039'=>"9874", // Vibra
                    '3055'=>"91",
                ));
                scriviRiga(array(
                    '9001'=>'NAD',
                    '3035'=>"SU",
                    '3039'=>"CODICESUPPLIER", // Codice Lariotecnick
                    '3055'=>"92",
                ));
            break;
        case "desadv>lin_group>lin":
                scriviRiga(array(
                    '9001'=>'CPS',
                    '7164'=>$valori["linenr"] ++,
                ));
                scriviRiga(array(
                    '9001'=>'LIN',
                    '1229'=>'1',
                    '1082'=>$valori["desadv>lin_group>lin>e1082"],
                    '7140'=>$valori["desadv>lin_group>lin>e7140"],
                    '7143'=>$valori["desadv>lin_group>lin>e7143"],
                ));
                scriviRiga(array(
                    '9001'=>'PIA',
                    '4347'=>'1',
                    '7143'=>'BP',
                    '7140'=>$valori["desadv>lin_group>lin>e9012"],
                ));
                scriviRiga(array(
                    '9001'=>'IMD',
                    '7077'=>valDft($valori["desadv>lin_group>lin>e7077"],"A"),
                    '7009'=>$valori["desadv>lin_group>lin>e9017"],
                ));

                $conv1 = array (
                    'L01'=>'113',
                    'L02'=>'61',
                    'L03'=>'193',
                );
                scriviRiga(array(
                    '9001'=>'QTY',
                    '6063'=>$conv1[$valori["desadv>lin_group>lin>e9062"]],
                    '6060'=>$valori["desadv>lin_group>lin>e6060"],
                    '6411'=>$valori["desadv>lin_group>lin>e6411"],
                ));
            break;
        case "desadv>cnt":
                scriviRiga(array(
                    '9001'=>'CNT',
                    '6069'=>$valori["desadv>cnt>e6069"],
                    '6066'=>$valori["desadv>cnt>e6066"],
                    '6411'=>$valori["desadv>cnt>e6411"],
                ));
            break;
        }
    }

    return ;
}


error_reporting (E_ALL ^ E_NOTICE);

$destStandard = "EDIFACT";
$destRelease = "D96A";
require_once dirname(__FILE__).'/../EDI.php';
$parser  = EDI::parserFactory($destStandard);
$destMapping = "EDI_{$destStandard}_MappingProvider";

$documento = EDI::interchangeFactory ($destStandard, array(
            'directory'        => $destRelease,
            'syntaxIdentifier' => 'UNOC',
            'syntaxVersion'    => 3
            )); 




parse (file_get_contents (dirname(__FILE__)."/../examples/vibra_des_adv.edi.xml"));

echo $documento->toEDI();

?>
