var billmateinvoicelang = "";
var billmateinvoiceeid = 0;
var billmateinvoiceefee = 0;
function InitBillmateInvoiceElements(obj, eid, lang, charge)
{
		if (document.getElementById(obj) == null) {
			return;
		}
		billmateinvoicelang = lang;
		billmateinvoiceeid = eid;
		if(charge)
			billmateinvoiceefee = charge;
		else
			billmateinvoiceefee = 0;
		var link_text_invoice = "Villkor f&ouml;r faktura";
		var link_text_closebutton = "St&auml;ng";
	    var billmatewidth = '500px';
        var billmatehight = '630px';
		switch(billmateinvoicelang)
		{
			case 'se':
			case 'swe':
				link_text_invoice = "Villkor f&ouml;r faktura";
				link_text_closebutton = "St&auml;ng";
				billmateinvoicelang = "se";
				billmatewidth = '500px';
				billmatehight = '510px';
			break;
			case 'dk':
			case 'dnk':
				link_text_closebutton = "Luk vindue";
				link_text_invoice = "Vilk&aring;r for faktura"
				billmateinvoicelang = "dk";
				billmatewidth = '500px';
				billmatehight = '490px';
			break;
			case 'no':
			case 'nok':
			case 'nor':
				link_text_invoice = "Vilk&aring;r for faktura"
				link_text_closebutton = "Lukk";
				billmateinvoicelang = "no";
				billmatewidth = '500px';
				billmatehight = '490px';
			break;
			case 'fi':
			case 'fin':
				link_text_invoice = "Laskuehdot";
				link_text_closebutton = "Sulje";
				billmateinvoicelang = "fi";
				billmatewidth = '500px';
				billmatehight = '500px';
			break;
			case 'de':	
			case 'deu':	
				link_text_invoice = "Rechnungsbedingungen";
				link_text_closebutton = "Schliessen";
				billmateinvoicelang = "de";
				billmatewidth = '500px';
				billmatehight = '570px';
			break;
			case 'nl':
			case 'nld':
				link_text_invoice = "Factuurvoorwaarden";
				link_text_closebutton = "Sluit";
				billmateinvoicelang = "nl";
				billmatewidth = '500px';
				billmatehight = '510px';
			break;
		
		}
		// set the link text
		document.getElementById(obj).innerHTML = link_text_invoice;
		// Create the container element
		var div = document.createElement('div');
		div.id = 'billmate_invoice_popup';
		div.style.display = 'none';
		div.style.backgroundColor = '#ffffff';
		div.style.border = 'solid 1px black';
		div.style.width = billmatewidth;
		div.style.position = 'absolute';
		div.style.left = (document.documentElement.offsetWidth/2 - 250) + 'px';
		div.style.top = '50px';
		div.style.zIndex = 9999;
		div.style.padding = '10px';
		
		// create the iframe
		var iframe = document.createElement('iframe');
		iframe.id = 'iframe_billmate_invoice';
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
			document.getElementById('billmate_invoice_popup').style.display = 'none';
			return false;
		};
		// Create the link text
		a.innerHTML = link_text_closebutton;
		// Append the link to the div
		div.appendChild(a);
		
		// Append the div
		document.body.insertBefore(div,null);
}
// This method adds an event
function addBillmateInvoiceEvent(fn) {
  if ( window.attachEvent ) {
        this.attachEvent('onload', fn);
  } else
  {
    this.addEventListener('load', fn, false );
	}
}
function ajax_load(obj){
    var hidden = jQuery('<input type="hidden" name="geturl" id="billmategeturl" value="1" />');
    jQuery('#hidden_data input[type=hidden]').each(function(){
        $this = jQuery(this);
        var $j = jQuery;
        var id = $this.attr('id');
        $j('#billing'+id).val( $this.val( ) );
        $j('#shipping'+id).val( $this.val( ) );
    });
	if(jQuery('.checkout').length > 0){
		jQuery('.checkout').prepend(hidden);

	} else {
		jQuery('#order_review').prepend(hidden);

	}
    jQuery('#place_order').trigger('click');
}

jQuery(document).ready(function(){
jQuery('form.checkout').on( 'change', 'input[name="payment_method"]', function() {
dirtyInput = this;
jQuery('body').trigger('update_checkout');
})
})

