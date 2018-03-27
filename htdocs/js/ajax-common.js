// JavaScript Document
function CreateXmlHttpReq() {
	var req = false;
	if (typeof XMLHttpRequest != "undefined")
	req = new XMLHttpRequest();
	if (!req && typeof ActiveXObject != "undefined") {
		try {
			req=new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e1) {
			try {
				req=new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e2) {
				try {
					req=new ActiveXObject("Msxml2.XMLHTTP.4.0");
				} catch (e3) {
					req=null;
				}
			}
		}
	}

	if(!req && window.createRequest)
	req = window.createRequest();

	if (!req)
	{
		alert( "You Browser does not support Ajax.");
		return false;
	}
	return req;
}

function sendAjaxRequest( url )
{
	var req = CreateXmlHttpReq();
	if( !req )
	{
		return 0;
	}
	req.onreadystatechange = function(){
		if (req.readyState == 4)
		{
			// stato della ricerca accolta
			if (req.status == 200)
			{
				return req.responseText;
			}
			else {
				return 0;
				//alert('Problema nella gestione dei dati: ' + req.responseText);
			}
		}

	};
	req.open('GET', url, true);
	req.send(null);
}

function processRequest(req)
{
	if (req.readyState == 4)
	{
		// stato della ricerca accolta
		if (req.status == 200)
		{
			return req.responseText;

		}
		else{
			alert('Problema nella gestione dei dati: ' + req.responseText);
		}
	}
}

function checkLogin()
{
	var req = CreateXmlHttpReq();
	if( !req )
	{
		return false;
	}
	var url = "index.php";
	var username = document.getElementById("username").value;
	var password = document.getElementById("password").value;
	document.getElementById("login-failed").style.display = "none";
	document.getElementById("login-loader").style.display = "block";
	req.onreadystatechange = function() {processLoginRequest(req ); };
	req.open('POST', url, true);
	req.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	req.send('mode=ajax&chapter=login&ac=login&username=' + username + '&password=' + password);
}
function checkLogout()
{
	var req = CreateXmlHttpReq();
	if( !req )
	{
		return false;
	}
	req.onreadystatechange = function() {
		if (req.readyState == 4)
		{
			// stato della ricerca accolta
			if (req.status == 200)
			{
				window.location.href = 'index.php';
			}
			else{
				alert('Problema nella gestione dei dati: ' + req.responseText);
			}
		}
	};
	req.open('GET', 'index.php?mode=ajax&chapter=login&ac=clogout', true);
	req.send(null);
}

function processLoginRequest(req)
{
	// stato della ricerca inviata
	if (req.readyState == 4)
	{
		// stato della ricerca accolta
		if (req.status == 200)
		{
			eval(statusLogin(req.responseText));
		}else{
			alert('Problema nella gestione dei dati: ' + req.responseText);
		}
	}
}

function statusLogin(Status)
{
	if(Status == 1)
	{
		document.getElementById("login-loader").style.display = "none";
		document.getElementById("login-success").style.display = "block";
		window.location.href = 'index.php';
	}
	else
	{
		document.getElementById("login-loader").style.display = "none";
		document.getElementById("login-failed").style.display = "block";
	}
}

function getPostAction(req, data)
{
	req.onreadystatechange = processRequest;
	req.open('POST', url, true);
	req.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	req.send(data);
}


function getAjaxContent ( context, location ) {
	var req = CreateXmlHttpReq();
	if( !req )
	{
		return false;
	}
	var url = 'index.php?mode=ajax&' + context + '&rand=' + escape(Math.random());
	req.onreadystatechange = function() {
		if (req.readyState == 4)
		{
			// stato della ricerca accolta
			if (req.status == 200)
			{
				document.getElementById(location).innerHTML = req.responseText;
				if(document.getElementById(location + '-loader'))
				{
					document.getElementById(location + '-loader').style.display = 'none';
				}
				document.getElementById(location).style.display = 'block';
				// Eval Js code new block page
				ajaxEvalJS( location );
			}
			else{
				alert('Problema nella gestione dei dati: ' + req.responseText);
			}
		}
		else
		{
			if(document.getElementById(location + '-loader'))
			{
				document.getElementById(location + '-loader').style.display = 'block';
			}
			document.getElementById(location).style.display = 'none';
		}
	};
	req.open('GET', url, true);
	req.send(null);
}

function changeElementStatus( context, element, status )
{
	if( document.getElementById(element) )
	{
		document.getElementById(element).className = context + '-' + status;
	}
}

function ajaxDelete( context, location )
{
	var inputs = document.getElementsByTagName("input"); //or document.forms[0].elements;
	var cbs = []; //will contain all checkboxes
	var checked = []; //will contain all checked checkboxes
	var string = '';
	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].type == "checkbox") {
			cbs.push(inputs[i]);
			if (inputs[i].checked) {
				string += inputs[i].value + '|';
			}
		}
	}
	if( string.length < 2 )
	{
		alert( 'Nessun elemento selezionato!' );
		return false;
	}
	if( confirm( 'Sei scuro di voler eliminari gli elementi selezionati?' ) )
	{
		getAjaxContent ( 'chapter=' + context + '&ac=delete&string=' + string, location );
	}
}

function ajaxSendForm( formName, context, location, popup )
{
	if( document.forms[formName] )
	{
		var form = document.forms[formName]
		var form_data = '';
		for(i=0; i<form.elements.length; i++)
		{
			form_data += form.elements[i].name + '=' + form.elements[i].value + '&';
		}
		var req = CreateXmlHttpReq();
		if( !req )
		{
			return false;
		}
		var url = 'index.php?mode=ajax&' + context + '&rand=' + escape(Math.random());
		req.onreadystatechange = function() {
			if (req.readyState == 4)
			{
				// stato della ricerca accolta
				if (req.status == 200)
				{
					document.getElementById(location).innerHTML = req.responseText;
					if(document.getElementById(location + 'Loader'))
					{
						document.getElementById(location + 'Loader').style.display = 'none';
					}
					document.getElementById(location).style.display = 'block';
					// Eval Js code new block page
					ajaxEvalJS( location );
				}
				else{
					alert('Problema nella gestione dei dati: ' + req.responseText);
				}
			}
			else
			{
				if(document.getElementById(location + 'Loader'))
				{
					document.getElementById(location + 'Loader').style.display = 'block';
				}
				document.getElementById(location).style.display = 'none';
			}
			if( popup == 1 )
			{
				
			}
		}
		req.open('POST', url, true);
		req.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		req.send(form_data);
	}
}

ajaxEvalJS = function(elementId) {
	var scripts = document.getElementById(elementId).getElementsByTagName('script');
	var code;
	for (var i = 0; i < scripts.length; i++) {
		code =	scripts[i].innerHTML ? scripts[i].innerHTML :
		scripts[i].text ? scripts[i].text :
		scripts[i].textContent;
		try {
			eval(code);
		} catch(e) {
			alert(e);
		}
	}
}
