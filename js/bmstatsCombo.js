//*************** VISISTAT CODE **********************
//Copyright 2003-2009, All Rights Reserved.

function SaaS() {

var vs="DID="+DID;
	vs+="&MyPage="+MyPageName;
	vs+="&MyID="+MyID;
	vs+="&TitleTag="+document.title;
    vs+="&Page="+window.location.pathname.substring(window.location.pathname.lastIndexOf('\\') + 1);
	vs+="&Hst="+document.domain;
    vs+="&width="+screen.width;
	vs+="&height="+screen.height;
	vs+="&ColDep="+screen.colorDepth;
	vs+="&Lang="+navigator.language;
	vs+="&Cook="+navigator.cookieEnabled;

	var vsr;
	try {
		vsr="Reff="+escape(parent==self?window.document.referrer:parent.document.referrer);
	}
	catch(e) {
		vsr="Reff="+escape(window.document.referrer);
	}

	vsr = vsr.replace(/&/g, "AND");

	var	vsd="FullPage="+document.URL;
	vsd = vsd.replace(/&/g, "AND");

	var purl = "PMCD="+document.URL;

	var flaver='';
	var n=navigator;
	if (n.plugins && n.plugins.length) {
		for (var i=0;i<n.plugins.length;i++) {
			if (n.plugins[i].name.indexOf('Shockwave Flash')!=-1) { flaver=n.plugins[i].description.split('Shockwave Flash ')[1]; break; }
		}
	}
	else if (window.ActiveXObject) {
		for (var i=10;i>=2;i--) {
			try {
				var fl=eval("new ActiveXObject('ShockwaveFlash.ShockwaveFlash."+i+"');");
				if (fl) { flaver=i; break; }
		   }
		   catch(e) {}
	  }
	}

	var rand = Math.random();
	sniffer = new Image();
	sniffer.src = myHTTP+'\/\/stats.sa-as.com\/index.php?'+vs+'&'+vsr+'&'+vsd+'&'+purl+'&Fla='+flaver+'&r='+rand;

}

function VSLT(LinkName) {
	var random = Math.random();
	sniff = new Image();
	sniff.src= myHTTP+'\/\/stats.sa-as.com\/index.php?DID='+DID+'&LinkName='+LinkName+'&r='+random;
}

function msrec(e) {

	if (navigator.appName == "Netscape") { msx = e.pageX; msy = e.pageY; }
	else { msx = event.clientX+document.body.scrollLeft; msy = event.clientY+document.body.scrollTop; }

	var rand = Math.random();
	var pw = screen.width;
	var ph = document.body.scrollHeight;
	msxy = new Image();

	msxy.src= myHTTP+'\/\/stats.sa-as.com\/tm.php?r='+rand+'&DID='+DID+'&pw='+pw+'&ph='+ph+'&msx='+msx+'&msy='+msy+'&mspage='+window.location.pathname.substring(window.location.pathname.lastIndexOf('\\') + 1);
}

//*************** VISISTAT CODE **********************

//***************** BRIDGEMAILSYSTEM CODE ***********************
function debug(msg) {
 //alert(msg);
}

function fetchDomain() {

  var name = document.domain;
  if(name.substring(0,4)=="www.")
    name=name.substring(4, name.length);

  var ary=name.split(".");
  var len=ary.length;
  if(len<3)
    return name;

  var element = ary[len-1];
  if(element.length<3)
      return name;

  name = ary[len-2]+"."+ary[len-1];
  return name;
}

function DT_setcookie(name, value, expirydays) {
  var expiry = new Date();
  expiry.setDate(expiry.getDate() + expirydays);
  document.cookie = (name+"="+escape(value)+"; expires="+expiry.toGMTString()+"; path=/; domain="+fetchDomain());
  debug("setCookie():"+document.cookie);
}

function deleteCookie(name) {
  DT_setcookie(name, '', -1); //browser seeing expiry date (-1) has passed, immediately removes the cookie.
}


function DT_getcookie(name) {

  name = name+"=";
  var start = document.cookie.indexOf(name);
  if(start==-1)
	return null;

  start += (name.length);
  var end = document.cookie.indexOf(";", start); // First;after start
  if (end == -1)
     end = document.cookie.length; // failed indexOf = -1

  var value = document.cookie.substring(start, end);
  return unescape(value);
}

function getParameter(queryString, parameterName) {

 var begin = -1;
 var end = -1;
 var pname = parameterName+"=";

 if ( queryString.length > 0 ) {
    begin = queryString.indexOf (pname);
    if ( begin != -1 ) {
        // Add the length (integer) to the beginning
        begin += (pname.length);

        // Multiple parameters are separated by the "&" sign
        end = queryString.indexOf ( "&" , begin );
        if ( end == -1 ) {
              end = queryString.length
        }
        return queryString.substring(begin, end);
    }
 }
 return null;
}

function getBMSURL(url) {

  if(url=='')
        return '';

  url = (url.indexOf('http://')==-1 && url.indexOf('https://')==-1)? 'http://'+url: url;

  var bmsTK =  getBMSTrackingParam();
  if(bmsTK!=null && bmsTK!='') {
        if(url.indexOf('?')!=-1) {
            return url+'&'+bmsTK;
        } else {
            return url+'?'+bmsTK;
        }
  }
  return url;           
}

function submitBMSURL(url) {
  document.location.href=getBMSURL(url);           
}


//set cookie & return the value.
function getBMSTrackingParam() {

var cookieName = 'bms.tk';
var myPara = getParameter(document.location.search, cookieName);
var thecookie = DT_getcookie(cookieName);

  if(myPara!=null && myPara!='') {
	return cookieName+'='+myPara;

  } else if(thecookie!=null && thecookie!='') {
	return cookieName+'='+thecookie;
  }
  return '';
}

function submitBMSForm(formId) {

  if(formId=='' || formId==null || document.getElementById(formId)==null)
        return;

  var mform = document.getElementById(formId);
	
  var url = mform.action;
  mform.action = getBMSURL(url);
  //alert('Form Target URL: '+mform.action);
  mform.submit();

}

function sniffUpTK(tk) {
  DT_setcookie("bms.tk", tk, 365);
  logVisit(tk, "o");
}

function logVisit(ticket, type) {

//return if pass less than 1000ms == 1sec.
if(type=="c" && pass<1000)
  return;

var myPr = (document.location.protocol=="http:")? "http:": "https:";
var myURL = myPr+'//content.bridgemailsystem.com/pms/events/logCK.jsp';
var myRef = (parent==self)? window.document.referrer: parent.document.referrer;
var myParas = "BMS_DID="+BMS_DID+"&bms.tk="+ticket;

myParas += "&pageTitle="+escape(document.title);
myParas += "&pageFullURL="+escape(document.URL);
myParas += "&pageDomain="+escape(document.domain);
myParas += "&screenHeight="+screen.height;
myParas += "&screenWidth="+screen.width;
myParas += "&referrer="+escape(myRef);
myParas += "&cookieEnabled="+navigator.cookieEnabled;
myParas += "&random="+myRandom;
myParas += "&event="+type;//open 'o' or click 'c' event
myParas += "&pass="+pass;

//alert(myURL);

 var logme = new Image(0,0);
 logme.src = myURL+'?'+myParas+'&ete='+Math.random();
}

//set cookie & return the value.
function getSetCookie(cookieName, title) {

var myPara = getParameter(document.location.search, cookieName);
var thecookie = DT_getcookie(cookieName);

if(myPara!=null && myPara!='') {
   DT_setcookie(cookieName, myPara, 365);
   debug(title+" ReqPara found & set as Cookie N:"+cookieName+", V:"+myPara);

} else {
 myPara = ((thecookie==null)? "":thecookie);
 debug(title+" Cookie found V:"+myPara);
}
return myPara;
}

function BMSClickEvent() {
  var tk = getSetCookie("bms.tk", "BMS Ticket");
  if(tk!=null && tk!='' && BMS_DID!=null && BMS_DID!='') {
 	pass = (new Date()).getTime() - myTime;
	myTime = (new Date()).getTime();
	logVisit(tk, "c");
  }
}

function clickEvent(e) {
  BMSClickEvent();
  msrec(e);
}

//sniff paras & cookies and log the visit.
function sniffUp() {

  var tk = getSetCookie("bms.tk", "BMS Ticket");
  if(tk!=null && tk!='' && BMS_DID!=null && BMS_DID!='')
	logVisit(tk, "o");
}

//for bridgestatz & visistat.
function afetchBMSID() {

   var id = getSetCookie("bms.id", "BMS Email");
   return ((id==null || id=='')? null: id);
}

var myRandom = (Math.random()*10);
var myTime = (new Date()).getTime();
var pass = 0;

var myHTTP =document.location.protocol||'http:';

var msx = 0;
var msy = 0;
var MyPageName;
var MyID;

//get up & sniff the air.
window.document.onmousedown = clickEvent;
