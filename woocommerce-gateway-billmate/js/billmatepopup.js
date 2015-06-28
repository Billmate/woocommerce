//https://github.com/paulirish/matchMedia.js/

window.matchMedia||(window.matchMedia=function(){"use strict";var e=window.styleMedia||window.media;if(!e){var t=document.createElement("style"),n=document.getElementsByTagName("script")[0],r=null;t.type="text/css";t.id="matchmediajs-test";n.parentNode.insertBefore(t,n);r="getComputedStyle"in window&&window.getComputedStyle(t,null)||t.currentStyle;e={matchMedium:function(e){var n="@media "+e+"{ #matchmediajs-test { width: 1px; } }";if(t.styleSheet){t.styleSheet.cssText=n}else{t.textContent=n}return r.width==="1px"}}}return function(t){return{matches:e.matchMedium(t||"all"),media:t||"all"}}}())

//https://raw.github.com/paulirish/matchMedia.js/master/matchMedia.addListener.js
(function(){if(window.matchMedia&&window.matchMedia("all").addListener){return false}var e=window.matchMedia,t=e("only all").matches,n=false,r=0,i=[],s=function(t){clearTimeout(r);r=setTimeout(function(){for(var t=0,n=i.length;t<n;t++){var r=i[t].mql,s=i[t].listeners||[],o=e(r.media).matches;if(o!==r.matches){r.matches=o;for(var u=0,a=s.length;u<a;u++){s[u].call(window,r)}}}},30)};window.matchMedia=function(r){var o=e(r),u=[],a=0;o.addListener=function(e){if(!t){return}if(!n){n=true;window.addEventListener("resize",s,true)}if(a===0){a=i.push({mql:o,listeners:u})}u.push(e)};o.removeListener=function(e){for(var t=0,n=u.length;t<n;t++){if(u[t]===e){u.splice(t,1)}}};return o}})()

if( typeof modalWin == 'undefined' ){

var xxx_modalPopupWindow = null;
var popupshowed = false;
function CreateModalPopUpObject() {
    if (xxx_modalPopupWindow == null) {
        xxx_modalPopupWindow = new ModalPopupWindow();
    }
    return xxx_modalPopupWindow;
}
function noPressButton(){
	modalWin.HideModalPopUp();
}
function ModalPopupWindow() {
    var strOverLayHTML = '<div id="divOverlay" style="position:absolute;z-index:999; background-color:WHITE; filter: alpha(opacity = 70);opacity:0.7;"></div><div id="divFrameParent" style="position:absolute;z-index:9999	; display:none;background-color:white;border:1px solid;-moz-box-shadow: 0 0 10px 10px #BBB;-webkit-box-shadow: 0 0 10px 10px #BBB;box-shadow: 0 0 10px 10px #BBB;padding:10px;line-height:21px;font-size:15px;color:#000;text-align:left;font-family:Arial,Helvetica,sans-serif;"	class="Example_F"><div class="heading" id="spanOverLayTitle"></div><div id="divMessage" style="display:none;"><span id="spanMessage"></span></div><span id="spanLoading"></span></div>'
    var orginalHeight;
    var orginalWidth;
    var btnStyle="";
    var maximize = false;
	div = document.createElement("div");
	div.innerHTML = strOverLayHTML;
    document.body.appendChild(div);

    this.ResizePopUp = function(height, width) {
        var divFrameParent = document.getElementById("divFrameParent");
        var divOverlay = document.getElementById("divOverlay");
        var left = (window.screen.availWidth - width) / 2;
        var top = (window.screen.availHeight - height) / 2;
        var xy = GetScroll();
        if (maximize) {
            left = xy[0] + 10;
            top = xy[1] + 10;
        } else {
            left += xy[0];
            top += xy[1];
        }
        divFrameParent.style.top = top + "px";
        divFrameParent.style.left = left + "px";
        divFrameParent.style.height = height + "px";
        divFrameParent.style.width = width + "px";
		ShowDivInCenter("divFrameParent");
    }
    var onPopUpCloseCallBack = null;
    var callbackArray = null;

    this.SetButtonStyle = function (_btnStyle) {
      btnStyle =_btnStyle;
    }
    
    function ApplyBtnStyle(){
    }
    
    function __InitModalPopUp(height, width, title) {
		
        orginalWidth = width;
        orginalHeight = height;
        maximize = false;
        var divFrameParent = document.getElementById("divFrameParent");
        var divOverlay = document.getElementById("divOverlay");
        var left = (window.screen.availWidth - width) / 2;
        var top = (window.screen.availHeight - height) / 2;
        var xy = GetScroll();
        left += xy[0];
        top += xy[1];
        document.getElementById("spanOverLayTitle").innerHTML = title;
        divOverlay.style.top = "0px";
        divOverlay.style.left = "0px";
        var e = document;
        var c = "Height";
        var maxHeight = Math.max(e.documentElement["client" + c], e.body["scroll" + c], e.documentElement["scroll" + c], e.body["offset" + c], e.documentElement["offset" + c]);
        c = "Width";
        var maxWidth = Math.max(e.documentElement["client" + c], e.body["scroll" + c], e.documentElement["scroll" + c], e.body["offset" + c], e.documentElement["offset" + c]);
        divOverlay.style.height = maxHeight + "px";
        divOverlay.style.width = maxWidth - 2 + "px";
        divOverlay.style.display = "";
        divFrameParent.style.display = "";
        //$('#divFrameParent').animate({ opacity: 1 }, 2000);
        divFrameParent.style.top = (top-100) + "px";
        divFrameParent.style.left = left + "px";
        //divFrameParent.style.height = height + "px";
        divFrameParent.style.width = width + "px";
        onPopUpCloseCallBack = null;
        callbackArray = null;
    }
    this.ShowMessage = function (message, height, width, title) {
        __InitModalPopUp(height, width, title);
		popupshowed = true;
        document.getElementById("spanMessage").innerHTML = message;
        document.getElementById("divMessage").style.display = "";
        document.getElementById("spanLoading").style.display = "none";
        ApplyBtnStyle();
		ShowDivInCenter("divFrameParent");
    }
    this.ShowConfirmationMessage = function (message, height, width, title, onCloseCallBack, firstButtonText, onFirstButtonClick, secondButtonText, onSecondButtonClick) {
        this.ShowMessage(message, height, width, title);
        var maxWidth = 100;
        document.getElementById("spanMessage").innerHTML = message;
        document.getElementById("divMessage").style.display = "";
        document.getElementById("spanLoading").style.display = "none";
        if (onCloseCallBack != null && onCloseCallBack != '') {
            onPopUpCloseCallBack = onCloseCallBack;
        }
        ApplyBtnStyle();
    }
    function ShowLoading() {
        document.getElementById("spanLoading").style.display = "";
    }
    this.HideModalPopUp = function () {
		popupshowed = false;
		jQuery("#billmategeturl").remove();
        var divFrameParent = document.getElementById("divFrameParent");
        var divOverlay = document.getElementById("divOverlay");
        divOverlay.style.display = "none";
        divFrameParent.style.display = "none";
        if (onPopUpCloseCallBack != null && onPopUpCloseCallBack != '') {
            onPopUpCloseCallBack();
        }
    }
    this.CallCallingWindowFunction = function (index, para) {
        callbackArray[index](para);
    }
    this.ChangeModalPopUpTitle = function (title) {
        document.getElementById("spanOverLayTitle").innerHTML = title;
    }

    function setParentVariable(variableName, variableValue) {
        window[String(variableName)] = variableValue;
    }

    function GetScroll() {
        if (window.pageYOffset != undefined) {
            return [pageXOffset, pageYOffset];
        } else {
            var sx, sy, d = document,
                r = d.documentElement,
                b = d.body;
            sx = r.scrollLeft || b.scrollLeft || 0;
            sy = r.scrollTop || b.scrollTop || 0;
            return [sx, sy];
        }
    }
}


function AddEvent(html_element, event_name, event_function) 
{       
   if(html_element.attachEvent) //Internet Explorer
      html_element.attachEvent("on" + event_name, function() {event_function.call(html_element);}); 
   else if(html_element.addEventListener) //Firefox & company
      html_element.addEventListener(event_name, event_function, false); //don't need the 'call' trick because in FF everything already works in the right way          
} 
var modalWin = new CreateModalPopUpObject();
function closefunc(obj){
	checkout.setLoadWaiting(false);
	modalWin.HideModalPopUp();
}
function reviewstep(){
}

function ShowMessage(content,wtitle){
	if(matchMedia('(max-width: 800px)').matches){
		modalWin.ShowMessage(content,370,250,wtitle);
	}else if(matchMedia('(min-width: 800px)').matches){
		modalWin.ShowMessage(content,280,500,wtitle);
	}
}
AddEvent(window,'resize',function(){
	if( popupshowed ){
		if(matchMedia('(min-width: 500px) and (max-width: 800px)').matches){
			modalWin.ResizePopUp(370,250);
		}else if(matchMedia('(min-width: 800px)').matches){
			modalWin.ResizePopUp(280,500);
		}
	}
});
AddEvent(window,'load',function(){
    window.$ = $ = jQuery;
    jQuery.getScript("https://efinance.se/billmate/base.js", function(){
        jQuery("#billmate_invoice").Terms("villkor",{invoicefee: billmate_invoice_fee_price}, "#billmate_invoice");
        jQuery("#billmate_partpayment").Terms("villkor_delbetalning",{eid: billmate_eid,effectiverate:34}, "#billmate_partpayment");
    });
    jQuery('#getaddress').on('click',function(e){
        e.preventDefault();
        if('#getaddresserror')
            $('#getaddresserror').remove();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {action: 'getaddress',pno: $('[name="pno"]').val()},
            success: function(response){
                var result = JSON.parse(response);
                if(result.success){
                    if(typeof result.data.firstname != 'undefined') {
                        $('#billing_first_name').val(result.data.firstname);
                        $('#billing_last_name').val(result.data.lastname);
                    } else {
                        $('#billing_company').val(result.data.company);
                    }
                    $('#billing_address_1').val(result.data.street);
                    $('#billing_postcode').val(result.data.zip);
                    $('#billing_city').val(result.data.city);
                    if(result.data.email != '')
                        $('#billing_email').val(result.data.email);

                    if(result.data.phone != ''){
                        $('#billing_phone').val(result.data.phone);
                    }
                    $('#billing_country').val(result.data.country);
                } else {
                    var message = '<div id="getaddresserror" class="woocommerce-error">'+result.message+'</div>';
                    $('#getaddresserr').html(message);
                }
            }
        })
    });
});
 function ShowDivInCenter(divId)
{
    try
    {
		var div = document.getElementById(divId);
		divWidth = document.getElementById("divFrameParent").offsetWidth;
        divHeight = document.getElementById("divFrameParent").offsetHeight;

        // Get the x and y coordinates of the center in output browser's window 
        var centerX, centerY;
        if (self.innerHeight)
        {
            centerX = self.innerWidth;
            centerY = self.innerHeight;
        }
        else if (document.documentElement && document.documentElement.clientHeight)
        {
            centerX = document.documentElement.clientWidth;
            centerY = document.documentElement.clientHeight;
        }
        else if (document.body)
        {
            centerX = document.body.clientWidth;
            centerY = document.body.clientHeight;
        }
 
        var offsetLeft = (centerX - divWidth) / 2;
        var offsetTop = (centerY - divHeight) / 2;
 
        // The initial width and height of the div can be set in the
        // style sheet with display:none; divid is passed as an argument to // the function
        var ojbDiv = document.getElementById(divId);
         
        left = (offsetLeft) / 2 + window.scrollX;
        top = (offsetTop) / 2 + window.scrollY;

        ojbDiv.style.position = 'absolute';
        ojbDiv.style.top = top + 'px';
        ojbDiv.style.left = offsetLeft + 'px';
        ojbDiv.style.display = "block";

    }
    catch (e) {}
}

function Action1(){
alert('Action1 is excuted');
modalWin.HideModalPopUp();
}

function Action2(){
alert('Action2 is excuted');
modalWin.HideModalPopUp();
}

function EnrollNow(msg){
modalWin.HideModalPopUp();
modalWin.ShowMessage(msg,200,400,'User Information',null,null);
}

function EnrollLater(){
modalWin.HideModalPopUp();
modalWin.ShowMessage(msg,200,400,'User Information',null,null);
}

}
