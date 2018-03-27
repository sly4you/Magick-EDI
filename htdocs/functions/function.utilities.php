<?php

/*
function login($username, $password)
{
	global $dbConnection;
	$query = 'SELECT * FROM user_authentication WHERE'
	.		 "username='" . $
	
}
*/

function getSingle ($table, $condiction='1=1', $debug=false)
{
	global $dbConnection;
	// Imposto il debug
	$dbConnection->debug = $debug;
	$recordSet = $dbConnection->Execute ('SELECT * FROM ' . $table . ' WHERE ' . $condiction);
	$result = $recordSet->RecordCount ();
	if ($result <= 0)
	{
		$result = -1;
	}
	else
	{
		$result = $recordSet->fields;
	}
	return $result;
}

function getSingleFreeSql ($sql, $debug=false)
{
	global $dbConnection;
	// Imposto il debug
	$dbConnection->debug = $debug;
	$recordSet = $dbConnection->Execute ($sql);
	$result = $recordSet->RecordCount ();
	if ($result <= 0)
	{
		$result = -1;
	}
	else
	{
		$result = $recordSet->fields;
	}
	return $result;
}

function getList ($table, $condiction='1=1', $debug=false)
{
	global $dbConnection;
	// Imposto il debug
	$dbConnection->debug = $debug;
	$recordSet = $dbConnection->Execute ('SELECT * FROM ' . $table . ' WHERE '  . $condiction);
	$result = $recordSet->RecordCount ();
	if ($result <= 0)
	{
		$result = -1;
	}
	else
	{
		$result = array ();
		$x = 0;
		while (!$recordSet->EOF)
		{
			$result[$x] = $recordSet->fields;
			$x += 1;
			$recordSet->MoveNext();
		}
	}
	return $result;
}

function getListCondictionWithPaging ($table, $condiction='1=1', $debug=false) {
         // Queste variabili sono indispensabili per il corretto funzionamento della funzione.
         // Dichiarandole globali, non ho necessita' di riportarle all'interno degli argomenti della funzione.
         // Questa scelta e' fatta poiche' risparmia la scrittura della dichiarazione del valore della variabile
         // ogni qualvolta lo sviluppatore richiama questa funzione
         $pagePos = $_REQUEST['pagePos'];
         $NUMBER_ELEMENT_FOR_PAGE = $_REQUEST['NUMBER_ELEMENT_FOR_PAGE'] ;
         $EXTRA_ARGUMENT_NAVIGATION = $_REQUEST['EXTRA_ARGUMENT_NAVIGATION'] ;
         $CLASS_LINK_STYLE = $_REQUEST['CLASS_LINK_STYLE'];
         // Valutazione della variabili
         if (!isset($pagePos)) $pagePos = 0;
         if (!isset($NUMBER_ELEMENT_FOR_PAGE)) $NUMBER_ELEMENT_FOR_PAGE = 10;
         if (!isset($EXTRA_ARGUMENT_NAVIGATION)) $EXTRA_ARGUMENT_NAVIGATION = '1=1';
         global $dbConnection;
         // Imposto il debug
         $dbConnection->debug = $debug;
         // Impostazione della query per il conteggio degli elementi che soddisfano la ricerca
         $sql =  'SELECT * FROM ' . $table . ' WHERE ' . $condiction;
         // Esecuzione della query
         $recordSet = $dbConnection->Execute ($sql);
         // Prelevo del risultato
         $recordNumber = $recordSet->RecordCount();
         // Se non c'e' un risultato, restituisce -1
         if (!$recordNumber) {
             $result = -1;
         }
         // La ricerca ha degli elementi
         else {
             // Inizializzo la classe per il paging
             $pager = new Paging ($recordNumber, $pagePos, $NUMBER_ELEMENT_FOR_PAGE, $EXTRA_ARGUMENT_NAVIGATION, 5, $CLASS_LINK_STYLE);
             // Carico i della classe di Paging
             $result[arrayPaging] = $pager->getPagingArray();
             $result[arrayRowPaging] = $pager->getPagingRowArray();
             // Imposto la ricerca degli elementi nel database
             $recordSet = $dbConnection->Execute ('SELECT * FROM ' . $table . ' WHERE ' . $condiction . ' LIMIT ' . $pagePos . ',' . $NUMBER_ELEMENT_FOR_PAGE);
             // Prelevo tutti i record della ricerca
             $pageSubject = array ();
             $x = 0;
             while (!$recordSet->EOF) {
                     $pageSubject[$x] = $recordSet->fields;
                     $x += 1;
                     $recordSet->MoveNext();
             }
             // Imposto nell'array il risultato. Questi sono gli elementi SOGGETTO della pagina !!!!
             $result[pageSubject] = $pageSubject;
             return $result;
         }
         // Restituisco il risultato
         return $result;
}

// Questa funzione richiede una query SQL completa \\
function getListFreeSqlWithPaging ($sql, $debug=false) {
         // Queste variabili sono indispensabili per il corretto funzionamento della funzione.
         // Dichiarandole globali, non ho necessita' di riportarle all'interno degli argomenti della funzione.
         // Questa scelta e' fatta poiche' risparmia la scrittura della dichiarazione del valore della variabile
         // ogni qualvolta lo sviluppatore richiama questa funzione
         $pagePos = $_REQUEST['pagePos'];
         $NUMBER_ELEMENT_FOR_PAGE = $_REQUEST['NUMBER_ELEMENT_FOR_PAGE'] ;
         $EXTRA_ARGUMENT_NAVIGATION = $_REQUEST['EXTRA_ARGUMENT_NAVIGATION'] ;
         $CLASS_LINK_STYLE = $_REQUEST['CLASS_LINK_STYLE'];
         // Valutazione della variabili
         if (!isset($pagePos)) $pagePos = 0;
         if (!isset($NUMBER_ELEMENT_FOR_PAGE)) $NUMBER_ELEMENT_FOR_PAGE = 10;
         if (!isset($EXTRA_ARGUMENT_NAVIGATION)) $EXTRA_ARGUMENT_NAVIGATION = '1=1';
         global $dbConnection;
         // Imposto il debug
         $dbConnection->debug = $debug;
         // Esecuzione della query
         $recordSet = $dbConnection->Execute ($sql);
         // Prelevo del risultato
         $recordNumber = $recordSet->RecordCount();
         // Se non c'e' un risultato, restituisce -1
         if (!$recordNumber) {
             $result = -1;
         }
         // La ricerca ha degli elementi
         else {
             // Inizializzo la classe per il paging
             $pager = new Paging ($recordNumber, $pagePos, $NUMBER_ELEMENT_FOR_PAGE, $EXTRA_ARGUMENT_NAVIGATION, 5, $CLASS_LINK_STYLE);
             // Carico i della classe di Paging
             $result[arrayPaging] = $pager->getPagingArray();
             $result[arrayRowPaging] = $pager->getPagingRowArray();
             // Imposto la ricerca degli elementi nel database
             $recordSet = $dbConnection->Execute ($sql . ' LIMIT ' . $pagePos . ', ' . $NUMBER_ELEMENT_FOR_PAGE);
             // Prelevo tutti i record della ricerca
             $pageSubject = array ();
             $x = 0;
             while (!$recordSet->EOF) {
                     $pageSubject[$x] = $recordSet->fields;
                     $x += 1;
                     $recordSet->MoveNext();
             }
             // Imposto nell'array il risultato. Questi sono gli elementi SOGGETTO della pagina !!!!
             $result[pageSubject] = $pageSubject;
             return $result;
         }
         // Restituisco il risultato
         return $result;
}

// Questa funzione richiede una query SQL completa \\
function getListFreeSqlWithPagingJavascript ($sql, $javascript_function, $debug=false) {
         // Queste variabili sono indispensabili per il corretto funzionamento della funzione.
         // Dichiarandole globali, non ho necessita' di riportarle all'interno degli argomenti della funzione.
         // Questa scelta e' fatta poiche' risparmia la scrittura della dichiarazione del valore della variabile
         // ogni qualvolta lo sviluppatore richiama questa funzione
         $pagePos = $_REQUEST['pagePos'];
         $NUMBER_ELEMENT_FOR_PAGE = $_REQUEST['NUMBER_ELEMENT_FOR_PAGE'] ;
         $EXTRA_ARGUMENT_NAVIGATION = $_REQUEST['EXTRA_ARGUMENT_NAVIGATION'] ;
         $CLASS_LINK_STYLE = $_REQUEST['CLASS_LINK_STYLE'];
         // Valutazione della variabili
         if (!isset($pagePos)) $pagePos = 0;
         if (!isset($NUMBER_ELEMENT_FOR_PAGE)) $NUMBER_ELEMENT_FOR_PAGE = 10;
         if (!isset($EXTRA_ARGUMENT_NAVIGATION)) $EXTRA_ARGUMENT_NAVIGATION = '1=1';
         global $dbConnection;
         // Imposto il debug
         $dbConnection->debug = $debug;
         // Esecuzione della query
         $recordSet = $dbConnection->Execute ($sql);
         // Prelevo del risultato
         $recordNumber = $recordSet->RecordCount();
         // Se non c'e' un risultato, restituisce -1
         if (!$recordNumber) {
             $result = -1;
         }
         // La ricerca ha degli elementi
         else {
             // Inizializzo la classe per il paging
             $pager = new Paging ($recordNumber, $pagePos, $NUMBER_ELEMENT_FOR_PAGE, $EXTRA_ARGUMENT_NAVIGATION, 5, $CLASS_LINK_STYLE);
             // Carico i della classe di Paging
             $result[arrayPaging] = $pager->getPagingArrayJavascript( $javascript_function );
             $result[arrayRowPaging] = $pager->getPagingRowArrayJavascript( $javascript_function );
             // Imposto la ricerca degli elementi nel database
             $recordSet = $dbConnection->Execute ($sql . ' LIMIT ' . $pagePos . ', ' . $NUMBER_ELEMENT_FOR_PAGE);
             // Prelevo tutti i record della ricerca
             $pageSubject = array ();
             $x = 0;
             while (!$recordSet->EOF) {
                     $pageSubject[$x] = $recordSet->fields;
                     $x += 1;
                     $recordSet->MoveNext();
             }
             // Imposto nell'array il risultato. Questi sono gli elementi SOGGETTO della pagina !!!!
             $result[pageSubject] = $pageSubject;
             return $result;
         }
         // Restituisco il risultato
         return $result;
}

// Questa funzione richiede una query SQL completa \\
function getListFreeSql ($sql, $debug=false)
{
	global $dbConnection;
	// Imposto il debug
	$dbConnection->debug = $debug;
	// Esecuzione della query
	$recordSet = $dbConnection->Execute ($sql);
	$result = $recordSet->RecordCount ();
	if ($result <= 0)
	{
		$result = -1;
	}
	else
	{
		$result = array ();
		$x = 0;
		while (!$recordSet->EOF)
		{
			$result[$x] = $recordSet->fields;
			$x += 1;
			$recordSet->MoveNext();
		}
	}
	return $result;
}

function getListDistinct ($table, $column, $condiction='1=1', $debug=false)
{
	global $dbConnection;
	// Imposto il debug
	$dbConnection->debug = $debug;
	$recordSet = $dbConnection->Execute ('SELECT DISTINCT ' . $column . ' FROM ' . $table . ' WHERE ' . $condiction);
	$result = $recordSet->RecordCount ();
	if ($result <= 0)
	{
		$result = -1;
	}
	else
	{
		$result = array ();
		$x = 0;
		while (!$recordSet->EOF)
		{
			$result[$x] = $recordSet->fields;
			$x += 1;
			$recordSet->MoveNext();
		}
	}
	return $result;
}

function calculatePercentualeSconto ($prezzo, $prezzoScontato, $iva=0) {
         $divisore = 100 + $iva;
         // Calcola i prezzi al netto dell'iva
         $prezzoNetto = ($prezzo * 100) / $divisore;
         $prezzoNettoScontato = ($prezzoScontato * 100) / $divisore;
         // Calcola il valore dello sconto
         $sconto = $prezzoNetto - $prezzoNettoScontato;
         // Calcola la percentuale di sconto
         $percentualeSconto = ($sconto * 100) / $prezzoNetto;
         return $percentualeSconto;
}

