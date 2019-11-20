var billmateSpecialPaymentLang = '';
var billmateSpecialPaymentEid = 0;
var billmatePClassId = '';
var link_text_specialpayment = '';
function InitBillmateSpecialPaymentElements(obj, eid, lang, linktext, pcid)
{
    if (document.getElementById(obj) == null) {
        return;
    }
    billmateSpecialPaymentLang = lang;
    billmateSpecialPaymentEid = eid;
    billmatePClassId = pcid;
    link_text_specialpayment = linktext;
    var link_text_closebutton = 'St&auml;ng';
    var billmatewidth = '500px';
    var billmatehight = '300px';

    switch(lang)
    {
        case 'se':
        case 'swe':
            /*link_text_specialpayment = 'L&auml;s mer';*/
            link_text_closebutton = 'St&auml;ng';
            billmateSpecialPaymentLang ='se';
            billmatewidth = '500px';
            billmatehight = '300px';
            break;
        case 'dk':
        case 'dnk':
            /*link_text_specialpayment = 'L&aelig;s mer'*/
            link_text_closebutton = 'Luk vindue';
            billmateSpecialPaymentLang = 'dk';
            billmatewidth = '500px';
            billmatehight = '300px';
            break;
        case 'no':
        case 'nok':
        case 'nor':
            /*link_text_specialpayment = 'Les mer'*/
            link_text_closebutton = 'Lukk';
            billmateSpecialPaymentLang = 'no';
            billmatewidth = '500px';
            billmatehight = '300px';
            break;
        case 'fi':
        case 'fin':
            /*link_text_specialpayment = 'Lue lis&auml;&auml;';*/
            link_text_closebutton = 'Sulje';
            billmateSpecialPaymentLang = 'fi';
            billmatewidth = '500px';
            billmatehight = '300px';
            break;
        case 'de':
        case 'deu':
            /*link_text_specialpayment = 'Lesen Sie mehr';*/
            link_text_closebutton = 'Schliessen';
            billmateSpecialPaymentLang = 'de';
            billmatewidth = '500px';
            billmatehight = '300px';
            break;
        case 'nl':
        case 'nld':
            /*link_text_specialpayment = 'Lees meer';*/
            link_text_closebutton = 'Sluit';
            billmateSpecialPaymentLang = 'nl';
            billmatewidth = '500px';
            billmatehight = '300px';
            break;

    }
    // set the link text
    document.getElementById(obj).innerHTML = link_text_specialpayment;
    // Create the container element
    var div = document.createElement('div');
    div.id = 'billmate_specialpayment_popup';
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
    iframe.id = 'iframe_billmate_specialpayment';
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
        document.getElementById('billmate_specialpayment_popup').style.display = 'none';
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
function ShowBillmateSpecialPaymentPopup()
{
    var scroll = self.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop;
    var top = scroll + 50;

    document.getElementById('billmate_specialpayment_popup').style.top = top + 'px';

    // Set the source for the iframe to the current language and estore    
    document.getElementById('iframe_billmate_specialpayment').src = 'https://static.billmate.com/external/html/vinter_' + billmateSpecialPaymentLang + '.html';

    // Last we display the popup
    document.getElementById('billmate_specialpayment_popup').style.display = 'block';
}

// This method adds an event
function addBillmateSpecialPaymentEvent(fn) {
    if ( window.attachEvent ) {
        this.attachEvent('onload', fn);
    } else
{
        this.addEventListener('load', fn, false );
    }
}