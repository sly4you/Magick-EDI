/**
 * SUBMODAL v1.6
 * Used for displaying DHTML only popups instead of using buggy modal windows.
 *
 * By Subimage LLC
 * http://www.subimage.com
 *
 * Contributions by:
 * 	Eric Angel - tab index code
 * 	Scott - hiding/showing selects for IE users
 *	Todd Huss - inserting modal dynamically and anchor classes
 *
 * Up to date code can be found at http://submodal.googlecode.com
 */
function addEvent(obj, evType, fn){
 if (obj.addEventListener){
    obj.addEventListener(evType, fn, false);
    return true;
 } else if (obj.attachEvent){
    var r = obj.attachEvent("on"+evType, fn);
    return r;
 } else {
    return false;
 }
}
function removeEvent(obj, evType, fn, useCapture){
  if (obj.removeEventListener){
    obj.removeEventListener(evType, fn, useCapture);
    return true;
  } else if (obj.detachEvent){
    var r = obj.detachEvent("on"+evType, fn);
    return r;
  } else {
    alert("Handler could not be removed");
  }
}

/**
 * Code below taken from - http://www.evolt.org/article/document_body_doctype_switching_and_more/17/30655/
 *
 * Modified 4/22/04 to work with Opera/Moz (by webmaster at subimage dot com)
 *
 * Gets the full width/height because it's different for most browsers.
 */
function getViewportHeight() {
	if (window.innerHeight!=window.undefined) return window.innerHeight;
	if (document.compatMode=='CSS1Compat') return document.documentElement.clientHeight;
	if (document.body) return document.body.clientHeight; 

	return window.undefined; 
}
function getViewportWidth() {
	var offset = 17;
	var width = null;
	if (window.innerWidth!=window.undefined) return window.innerWidth; 
	if (document.compatMode=='CSS1Compat') return document.documentElement.clientWidth; 
	if (document.body) return document.body.clientWidth; 
}

/**
 * Gets the real scroll top
 */
function getScrollTop() {
	if (self.pageYOffset) // all except Explorer
	{
		return self.pageYOffset;
	}
	else if (document.documentElement && document.documentElement.scrollTop)
		// Explorer 6 Strict
	{
		return document.documentElement.scrollTop;
	}
	else if (document.body) // all other Explorers
	{
		return document.body.scrollTop;
	}
}
function getScrollLeft() {
	if (self.pageXOffset) // all except Explorer
	{
		return self.pageXOffset;
	}
	else if (document.documentElement && document.documentElement.scrollLeft)
		// Explorer 6 Strict
	{
		return document.documentElement.scrollLeft;
	}
	else if (document.body) // all other Explorers
	{
		return document.body.scrollLeft;
	}
}

// Popup code
var gPopupMask = null;
var gPopupContainer = null;
var gPopFrame = null;
var gReturnFunc;
var gPopupIsShown = false;
var gDefaultPage = "/loading.html";
var gHideSelects = false;
var gReturnVal = null;

var gTabIndexes = new Array();
// Pre-defined list of tags we want to disable/enable tabbing into
var gTabbableTags = new Array("A","BUTTON","TEXTAREA","INPUT"); //,"IFRAME");	

// If using Mozilla or Firefox, use Tab-key trap.
/*
if (!document.all) {
	document.onkeypress = keyDownHandler;
}
*/
/**
 * Initializes popup code on load.	
 */
function initPopUp() {
	// Add the HTML to the body
	theBody = document.getElementsByTagName('BODY')[0];
	popmask = document.createElement('div');
	popmask.id = 'popupMask';
	popcont = document.createElement('div');
	popcont.id = 'popupContainer';
	popcont.innerHTML = '' +
		  '<div id="popupCloseBox">' +
		    '<div id="popupCloseBtn" onclick="hidePopWin(false);"></div>' +
		  '</div>' +
		  '<div id="popupFrameLoader">Loading Request</div>' +
		  '<div id="popupFrameLoaderError">Sorry, Ajax request failed.</div>' +
		  '<div id="popupFrame"></div>';
	theBody.appendChild(popmask);
	theBody.appendChild(popcont);
	
	gPopupMask = document.getElementById("popupMask");
	gPopupContainer = document.getElementById("popupContainer");
	gPopFrame = document.getElementById("popupFrame");
	
	// check to see if this is IE version 6 or lower. hide select boxes if so
	// maybe they'll fix this in version 7?
	var brsVersion = parseInt(window.navigator.appVersion.charAt(0), 10);
	if (brsVersion <= 6 && window.navigator.userAgent.indexOf("MSIE") > -1) {
		gHideSelects = true;
	}
	
	// Add onclick handlers to 'a' elements of class submodal or submodal-width-height
	var elms = document.getElementsByTagName('a');
	for (i = 0; i < elms.length; i++) {
		if (elms[i].className.indexOf("submodal") == 0) { 
			// var onclick = 'function (){showPopWin(\''+elms[i].href+'\','+width+', '+height+', null);return false;};';
			// elms[i].onclick = eval(onclick);
			elms[i].onclick = function(){
				// default width and height
				var width = 400;
				var height = 200;
				// Parse out optional width and height from className
				params = this.className.split('-');
				if (params.length == 3) {
					width = parseInt(params[1]);
					height = parseInt(params[2]);
				}
				showPopWin(this.href,width,height,null); return false;
			}
		}
	}
}
addEvent(window, "load", initPopUp);

 /**
	* @argument width - int in pixels
	* @argument height - int in pixels
	* @argument url - url to display
	* @argument returnFunc - function to call when returning true from the window.
	* @argument showCloseBox - show the close box - default true
	*/
function showPopWin(url, width, height, returnFunc, showCloseBox) {
	gPopupIsShown = true;
	//disableTabIndexes();
	gPopupMask.style.display = "block";
	gPopupContainer.style.display = "block";
	// set the url
	//gPopFrame.src = url;
	//gPopFrame.innerHTML = url;
	gReturnFunc = returnFunc;
	// for IE
	if (gHideSelects == true) {
		hideSelectBoxes();
	}
	// New Code Position
    real_width = document.getElementById('popupContainer').offsetWidth;
    real_height = document.getElementById('popupContainer').offsetHeight;
	gPopupContainer.style.width =  real_width + "px";
	gPopupContainer.style.height = real_height + "px";
	
	centerPopWin(real_width, real_height)
	getAjaxPopupWinRequest( url );
}

function getAjaxPopupWinRequest( url ) {
	var req = CreateXmlHttpReq();
	req.onreadystatechange = function() {
		if(req.readyState == 4) {
			if (req.status == 200)
			{
				document.getElementById('popupFrame').innerHTML = req.responseText;
				document.getElementById('popupFrame').style.display = 'block';
				document.getElementById('popupFrameLoader').style.display = 'none';
				document.getElementById('popupFrameLoaderError').style.display = 'none';
				document.getElementById('popupContainer').style.width = 'auto';
				document.getElementById('popupContainer').style.height = 'auto';
				centerPopWin();
			}
			else {
				document.getElementById('popupFrame').innerHTML = '';
				document.getElementById('popupFrame').style.display = 'none';
				document.getElementById('popupFrameLoader').style.display = 'none';
				document.getElementById('popupFrameLoaderError').style.display = 'block';
				document.getElementById('popupContainer').style.width = 'auto';
				document.getElementById('popupContainer').style.height = 'auto';
				centerPopWin();
			}
		}
	}
	req.open('GET', url, true);
	req.send(null);
}

function sendAjaxPopupWinForm( formName, context, location )
{
	if( document.forms[formName] )
	{
		//var form = document.forms[formName]
		var form_data = getFormValue(formName);
		//for(i=0; i<form.elements.length; i++)
		//{
		//	form_data += form.elements[i].name + '=' + encodeURIComponent(form.elements[i].value) + '&';
		//}
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
					document.getElementById('popupFrame').innerHTML = req.responseText;
					document.getElementById('popupFrame').style.display = 'block';
					document.getElementById('popupFrameLoader').style.display = 'none';
					document.getElementById('popupFrameLoaderError').style.display = 'none';	
					document.getElementById('popupContainer').style.width = 'auto';
					document.getElementById('popupContainer').style.height = 'auto';
					centerPopWin();
				}
			}
			else
			{
				document.getElementById('popupFrame').innerHTML = '';
				document.getElementById('popupFrame').style.display = 'none';
				document.getElementById('popupFrameLoader').style.display = 'block';
				document.getElementById('popupFrameLoaderError').style.display = 'none';
				document.getElementById('popupContainer').style.width = 'auto';
				document.getElementById('popupContainer').style.height = 'auto';
				centerPopWin();
			}
		}
		req.open('POST', url, true);
		req.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		req.send(form_data);
	}
}

function getFormValue(form_name)
{
	var form = document.forms[form_name];
	var form_data = '';
	for(i=0; i<form.elements.length; i++)
	{
		if(form.elements[i].type == 'checkbox' )
		{
			form_data += form.elements[i].name + '=' + getRadioValue(form.elements[i]) + '&';	
		}
		else
		{
			form_data += form.elements[i].name + '=' + encodeURIComponent(form.elements[i].value) + '&';
		}
	}
	return form_data;
}

function getRadioValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++)
	{
		if(radioObj[i].checked)
		{
			return radioObj[i].value;
		}
	}
	return "";
}
//
var gi = 0;
function centerPopWin(width, height) {
	if (gPopupIsShown == true) {
		if (width == null || isNaN(width)) {
			width = gPopupContainer.offsetWidth;
		}
		if (height == null) {
			height = gPopupContainer.offsetHeight;
		}
		//var theBody = document.documentElement;
		var theBody = document.getElementsByTagName("BODY")[0];
		//theBody.style.overflow = "hidden";
		var scTop = parseInt(getScrollTop(),10);
		var scLeft = parseInt(theBody.scrollLeft,10);
	
		setMaskSize();
		
		//window.status = gPopupMask.style.top + " " + gPopupMask.style.left + " " + gi++;
		
		//var titleBarHeight = parseInt(document.getElementById("popupTitleBar").offsetHeight, 10);
		
		var fullHeight = getViewportHeight();
		var fullWidth = getViewportWidth();
		gPopupContainer.style.top = (scTop + ((fullHeight - (height)) / 2)) + "px";
		gPopupContainer.style.left =  (scLeft + ((fullWidth - width) / 2)) + "px";
	}
}
addEvent(window, "resize", centerPopWin);
addEvent(window, "scroll", centerPopWin);
window.onscroll = centerPopWin;


/**
 * Sets the size of the popup mask.
 *
 */
function setMaskSize() {
	var theBody = document.getElementsByTagName("BODY")[0];
			
	var fullHeight = getViewportHeight();
	var fullWidth = getViewportWidth();
	
	// Determine what's bigger, scrollHeight or fullHeight / width
	if (fullHeight > theBody.scrollHeight) {
		popHeight = fullHeight;
	} else {
		popHeight = theBody.scrollHeight;
	}
	
	if (fullWidth > theBody.scrollWidth) {
		popWidth = fullWidth;
	} else {
		popWidth = theBody.scrollWidth;
	}
	
	gPopupMask.style.height = popHeight + "px";
	gPopupMask.style.width = popWidth + "px";
}

/**
 * @argument callReturnFunc - bool - determines if we call the return function specified
 * @argument returnVal - anything - return value 
 */
function hidePopWin(callReturnFunc) {
	gPopupIsShown = false;
	var theBody = document.getElementsByTagName("BODY")[0];
	theBody.style.overflow = "";
	restoreTabIndexes();
	if (gPopupMask == null) {
		return;
	}
	gPopupMask.style.display = "none";
	gPopupContainer.style.display = "none";
	
	if (callReturnFunc == true && gReturnFunc != null) {
		// Set the return code to run in a timeout.
		// Was having issues using with an Ajax.Request();
		gReturnVal = window.frames["popupFrame"].returnVal;
		window.setTimeout('gReturnFunc(gReturnVal);', 1);
	}
	document.getElementById('popupFrame').innerHTML = '';
	document.getElementById('popupFrame').style.display = 'none';
	document.getElementById('popupFrameLoaderError').style.display = 'none';
	document.getElementById('popupFrameLoader').style.display = 'block';
	//gPopFrame.src = gDefaultPage;
	// display all select boxes
	if (gHideSelects == true) {
		displaySelectBoxes();
	}
}

/**
 * Sets the popup title based on the title of the html document it contains.
 * Uses a timeout to keep checking until the title is valid.
 */
function setPopTitle() {
	return;
	if (window.frames["popupFrame"].document.title == null) {
		window.setTimeout("setPopTitle();", 10);
	} else {
		document.getElementById("popupTitle").innerHTML = window.frames["popupFrame"].document.title;
	}
}

// Tab key trap. iff popup is shown and key was [TAB], suppress it.
// @argument e - event - keyboard event that caused this function to be called.
function keyDownHandler(e) {
    if (gPopupIsShown && e.keyCode == 9)  return false;
}

// For IE.  Go through predefined tags and disable tabbing into them.
function disableTabIndexes() {
	if (document.all) {
		var i = 0;
		for (var j = 0; j < gTabbableTags.length; j++) {
			var tagElements = document.getElementsByTagName(gTabbableTags[j]);
			for (var k = 0 ; k < tagElements.length; k++) {
				gTabIndexes[i] = tagElements[k].tabIndex;
				tagElements[k].tabIndex="-1";
				i++;
			}
		}
	}
}

// For IE. Restore tab-indexes.
function restoreTabIndexes() {
	if (document.all) {
		var i = 0;
		for (var j = 0; j < gTabbableTags.length; j++) {
			var tagElements = document.getElementsByTagName(gTabbableTags[j]);
			for (var k = 0 ; k < tagElements.length; k++) {
				tagElements[k].tabIndex = gTabIndexes[i];
				tagElements[k].tabEnabled = true;
				i++;
			}
		}
	}
}


/**
 * Hides all drop down form select boxes on the screen so they do not appear above the mask layer.
 * IE has a problem with wanted select form tags to always be the topmost z-index or layer
 *
 * Thanks for the code Scott!
 */
function hideSelectBoxes() {
  var x = document.getElementsByTagName("SELECT");

  for (i=0;x && i < x.length; i++) {
    x[i].style.visibility = "hidden";
  }
}

/**
 * Makes all drop down form select boxes on the screen visible so they do not 
 * reappear after the dialog is closed.
 * 
 * IE has a problem with wanting select form tags to always be the 
 * topmost z-index or layer.
 */
function displaySelectBoxes() {
  var x = document.getElementsByTagName("SELECT");

  for (i=0;x && i < x.length; i++){
    x[i].style.visibility = "visible";
  }
}
