var billmatepartpaymentlang = "";
var billmatepartpaymenteid = 0;
var billmatesum = 0;
function InitBillmatePartPaymentElements(obj, eid, lang, linktext, sum)
{
		if (document.getElementById(obj) == null) {
			return;
		}
		billmatepartpaymentlang = lang;
		billmatepartpaymenteid = eid;
		billmatesum = sum;
		var link_text_partpayment = linktext;
		var link_text_closebutton = "St&auml;ng";
		var billmatewidth = '500px';
        var billmatehight = '630px';
		
		switch(lang)
		{
			case 'se':
			case 'swe':
				/*link_text_partpayment = "L&auml;s mer";*/
				link_text_closebutton = "St&auml;ng";
				billmatepartpaymentlang = "se";
				billmatewidth = '500px';
				billmatehight = '490px';
			break;
			case 'dk':
			case 'dnk':
				link_text_closebutton = "Luk vindue";
				/*link_text_partpayment = "L&aelig;s mere"*/
				billmatepartpaymentlang = "dk";
				billmatewidth = '500px';
				billmatehight = '530px';
			break;
			case 'no':
			case 'nok':
			case 'nor':
				link_text_closebutton = "Lukk";
				/*link_text_partpayment = "Les mer"*/
				billmatepartpaymentlang = "no";
				billmatewidth = '500px';
				billmatehight = '560px';
			break;
			case 'fi':
			case 'fin':
				/*link_text_partpayment = "Lue lis&auml;&auml;";*/
				link_text_closebutton = "Sulje";
				billmatepartpaymentlang = "fi";
				billmatewidth = '500px';
				billmatehight = '570px';
			break;
			case 'de':
			case 'deu':	
				/*link_text_partpayment = "Lesen Sie mehr!";*/
				link_text_closebutton = "Schliessen";
				billmatepartpaymentlang = "de";
				billmatewidth = '500px';
				billmatehight = '660px';
			break;
			case 'nl':	
			case 'nld':	
				/*link_text_partpayment = "Lees meer!";*/
				link_text_closebutton = "Sluit";
				billmatepartpaymentlang = "nl";
				billmatewidth = '510px';
				billmatehight = '690px';
			break;
		
		}
		// set the link text
		document.getElementById(obj).innerHTML = link_text_partpayment;		
		// Create the container element
		var div = document.createElement('div');
		div.id = 'billmate_partpayment_popup';
		div.style.display = 'none';
		div.style.backgroundColor = '#ffffff';
		div.style.border = 'solid 1px black';
		div.style.width = billmatewidth;
		div.style.position = 'absolute';
		div.style.left = (document.documentElement.offsetWidth/2 - 250) + 'px';
		div.style.top = '50px';
		div.style.zIndex = 99999;
		div.style.padding = '10px';
		
		// create the iframe
		var iframe = document.createElement('iframe');
		iframe.id = 'iframe_billmate_partpayment';
		iframe.frameBorder = 0;
		iframe.style.border = 0;
		iframe.style.width = billmatewidth;
		iframe.style.height = billmatehight;		
		div.appendChild(iframe);
		
		// Create the a element that closes the popup
		var a = document.createElement('a');
		a.href = '#';
		a.style.color = '#000000';
		a.onclick = function() {
			document.getElementById('billmate_partpayment_popup').style.display = 'none';
			return false;
		};
		// Create the link text
		a.innerHTML = link_text_closebutton;
		// Append the link to the div
		div.appendChild(a);
		
		// Append the div
		document.body.insertBefore(div,null);
}

// eid : Estore ID
// lang : The language in the popup (country code)
function ShowBillmatePartPaymentPopup()
{	
			var scroll = self.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop;
			var top = scroll + 50;		
			
			document.getElementById('billmate_partpayment_popup').style.top = top + 'px';
	
	// Set the source for the iframe to the current language and estore
	document.getElementById('iframe_billmate_partpayment').src = 'https://online.billmate.com/account_' + billmatepartpaymentlang + '.yaws?eid=' + billmatepartpaymenteid;
		
	// Last we display the popup
	document.getElementById('billmate_partpayment_popup').style.display = 'block';
}

// This method adds an event
function addBillmatePartPaymentEvent(fn) {
  if ( window.attachEvent ) {
        this.attachEvent('onload', fn);
  } else
  {
    this.addEventListener('load', fn, false );
	}
}