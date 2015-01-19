<?php
define('BILLPLUGIN_VERSION','1.23.1');
define('BILLMATE_VERSION','PHP:Woocommerce:'.BILLPLUGIN_VERSION);

require_once(BILLMATE_LIB . 'BillMate.php');
require_once(BILLMATE_LIB . 'billmatecalc.php');
require_once(BILLMATE_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
require_once(BILLMATE_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
require_once dirname( __FILE__ ) .'/utf8.php';


function wc_bm_errors($message){
	global $woocommerce;
	$message = utf8_encode($message);
	if(version_compare(WC_VERSION, '2.0.0', '<')){
		$woocommerce->add_error( $message );
	} else {
		wc_add_notice( $message, 'error' );
	}
}
/**
 * Provides encoding constants.
 *
 * @package BillmateAPI
 */
if(!class_exists('BillmateEncoding')){
class BillmateEncoding {

    /**
     * PNO/SSN encoding for Sweden.
     *
     * @var int
     */
    const PNO_SE = 2;

    /**
     * PNO/SSN encoding for Norway.
     *
     * @var int
     */
    const PNO_NO = 3;

    /**
     * PNO/SSN encoding for Finland.
     *
     * @var int
     */
    const PNO_FI = 4;

    /**
     * PNO/SSN encoding for Denmark.
     *
     * @var int
     */
    const PNO_DK = 5;

    /**
     * PNO/SSN encoding for Germany.
     *
     * @var int
     */
    const PNO_DE = 6;

    /**
     * PNO/SSN encoding for Netherlands.
     *
     * @var int
     */
    const PNO_NL = 7;

    /**
     * Encoding constant for customer numbers.
     *
     * @see Billmate::setCustomerNo()
     * @var int
     */
    const CUSTNO = 1000;

    /**
     * Encoding constant for email address.
     *
     * @var int
     */
    const EMAIL = 1001;

    /**
     * Encoding constant for cell numbers.
     *
     * @var int
     */
    const CELLNO = 1002;

    /**
     * Encoding constant for bank bic + account number.
     *
     * @var int
     */
    const BANK_BIC_ACC_NO = 1003;

    /**
     * Returns a regexp string for the specified encoding constant.
     *
     * @param  int    $enc    PNO/SSN encoding constant.
     * @return string The regular expression.
     * @throws BillmateException
     */
    public static function getRegexp($enc) {
        switch($enc) {
            /**
             * All positions except C contain numbers 0-9.
             *
             * PNO:
             * YYYYMMDDCNNNN, C = -|+  length 13
             * YYYYMMDDNNNN                   12
             * YYMMDDCNNNN                    11
             * YYMMDDNNNN                     10
             *
             * ORGNO:
             * XXXXXXNNNN
             * XXXXXX-NNNN
             * 16XXXXXXNNNN
             * 16XXXXXX-NNNN
             *
             */
            case self::PNO_SE:
                return '/^[0-9]{6,6}(([0-9]{2,2}[-\+]{1,1}[0-9]{4,4})|([-\+]{1,1}[0-9]{4,4})|([0-9]{4,6}))$/';
                break;

            /**
             * All positions contain numbers 0-9.
             *
             * Pno
             * DDMMYYIIIKK    ("fodelsenummer" or "D-nummer") length = 11
             * DDMMYY-IIIKK   ("fodelsenummer" or "D-nummer") length = 12
             * DDMMYYYYIIIKK  ("fodelsenummer" or "D-nummer") length = 13
             * DDMMYYYY-IIIKK ("fodelsenummer" or "D-nummer") length = 14
             *
             * Orgno
             * Starts with 8 or 9.
             *
             * NNNNNNNNK      (orgno)                         length = 9
             */
            case self::PNO_NO:
                return '/^[0-9]{6,6}((-[0-9]{5,5})|([0-9]{2,2}((-[0-9]{5,5})|([0-9]{1,1})|([0-9]{3,3})|([0-9]{5,5))))$/';
                break;

            /**
             * Pno
             * DDMMYYCIIIT
             * DDMMYYIIIT
             * C = century, '+' = 1800, '-' = 1900 och 'A' = 2000.
             * I = 0-9
             * T = 0-9, A-F, H, J, K-N, P, R-Y
             *
             * Orgno
             * NNNNNNN-T
             * NNNNNNNT
             * T = 0-9, A-F, H, J, K-N, P, R-Y
             */
            case self::PNO_FI:
                return '/^[0-9]{6,6}(([A\+-]{1,1}[0-9]{3,3}[0-9A-FHJK-NPR-Y]{1,1})|([0-9]{3,3}[0-9A-FHJK-NPR-Y]{1,1})|([0-9]{1,1}-{0,1}[0-9A-FHJK-NPR-Y]{1,1}))$/i';
                break;

            /**
             * Pno
             * DDMMYYNNNG       length 10
             * G = gender, odd/even for men/women.
             *
             * Orgno
             * XXXXXXXX         length 8
             */
            case self::PNO_DK:
                return '/^[0-9]{8,8}([0-9]{2,2})?$/';
                break;

            /**
             * Pno
             * DDMMYYYYG         length 9
             * DDMMYYYY                 8
             *
             * Orgno
             * XXXXXXX                  7  company org nr
             */
            case self::PNO_NL:
            case self::PNO_DE:
                return '/^[0-9]{7,9}$/';
                break;

            /**
             * Validates an email.
             */
            case self::EMAIL:
                return '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z0-9-][a-zA-Z0-9-]+)+$/';
                break;

            /**
             * Validates a cellno.
             *
             */
            case self::CELLNO:
                return '/^07[\ \-0-9]{8,13}$/';
                break;

            default:
                throw new Exception('Error in ' . __METHOD__ . ': Unknown PNO/SSN encoding constant! ('.$enc.')', 50091);
        }
    }

    /**
     * Checks if the specified PNO is correct according to specified encoding constant.
     *
     * @param  string $pno  PNO/SSN string.
     * @param  int    $enc  {@link BillmateEncoding PNO/SSN encoding} constant.
     * @return bool   True if correct.
     * @throws BillmateException
     */
    public static function checkPNO($pno, $enc) {
        $regexp = self::getRegexp($enc);

        if($regexp === false) {
            return true;
        }
        else {
            return (preg_match($regexp, $pno)) ? true : false;
        }
    }

    /**
     * Class constructor.
     * Disable instantiation.
     */
    private function __construct() {

    }

} //End BillmateEncoding
}

/**
 * Provides flags/constants used for various methods.
 *
 * @package BillmateAPI
 */
if(!class_exists('BillmateFlags')){
class BillmateFlags {

    /**
     * Specifies that no flag is to be used.
     *
     * @var int
     */
    const NO_FLAG = 0;

//Gender flags
    /**
     * Indicates that the person is a female.<br>
     * Use "" or null when unspecified.<br>
     *
     * @var int
     */
    const FEMALE = 0;

    /**
     * Indicates that the person is a male.<br>
     * Use "" or null when unspecified.<br>
     *
     * @var int
     */
    const MALE = 1;

//Order status constants
    /**
     * This signifies that the invoice or reservation is accepted.
     *
     * @var int
     */
    const ACCEPTED = 1;

    /**
     * This signifies that the invoice or reservation is pending, will be set to accepted or denied.
     *
     * @var int
     */
    const PENDING = 2;

    /**
     * This signifies that the invoice or reservation is <b>denied</b>.
     *
     * @var int
     */
    const DENIED = 3;

//Get_address constants
    /**
     * A code which indicates that all first names should be returned with the address.<br>
     *
     * Formerly refered to as GA_OLD.
     *
     * @var int
     */
    const GA_ALL = 1;

    /**
     * A code which indicates that only the last name should be returned with the address.<br>
     *
     * Formerly referd to as GA_NEW.
     *
     * @var int
     */
    const GA_LAST = 2;

    /**
     * A code which indicates that the given name should be returned with the address.
     * If no given name is registered, this will behave as {@link BillmateFlags::GA_ALL GA_ALL}.
     *
     */
    const GA_GIVEN = 5;

//Article/goods constants
    /**
     * Quantity measured in 1/1000s.
     *
     * @var int
     */
    const PRINT_1000 = 1;

    /**
     * Quantity measured in 1/100s.
     *
     * @var int
     */
    const PRINT_100 = 2;

    /**
     * Quantity measured in 1/10s.
     *
     * @var int
     */
    const PRINT_10 = 4;

    /**
     * Indicates that the item is a shipment fee.
     *
     * Update_charge_amount (1)
     *
     * @var int
     */
    const IS_SHIPMENT = 8;

    /**
     * Indicates that the item is a handling fee.
     *
     * Update_charge_amount (2)
     *
     * @var int
     */
    const IS_HANDLING = 16;

    /**
     * Article price including VAT.
     *
     * @var int
     */
    const INC_VAT = 32;

//Miscellaneous
    /**
     * Signifies that this is to be displayed in the checkout.<br>
     * Used for part payment.<br>
     *
     * @var int
     */
    const CHECKOUT_PAGE = 0;

    /**
     * Signifies that this is to be displayed in the product page.<br>
     * Used for part payment.<br>
     *
     * @var int
     */
    const PRODUCT_PAGE = 1;

    /**
     * Signifies that the specified address is billing address.
     *
     * @var int
     */
    const IS_BILLING = 100;

    /**
     * Signifies that the specified address is shipping address.
     *
     * @var int
     */
    const IS_SHIPPING = 101;

//Invoice and Reservation
    /**
     * Indicates that the purchase is a test invoice/part payment.
     *
     * @var int
     */
    const TEST_MODE = 2;

    /**
     * PClass id/value for invoices.
     *
     * @see BillmatePClass::INVOICE.
     * @var int
     */
    const PCLASS_INVOICE = -1;

//Invoice
    /**
     * Activates an invoices automatically, requires setting in Billmate Online.
     *
     * If you designate this flag an invoice is created directly in the active state,
     * i.e. Billmate will buy the invoice immediately.
     *
     * @var int
     */
    const AUTO_ACTIVATE = 1;

    /**
     * Creates a pre-pay invoice.
     *
     * @var int
     */
    const PRE_PAY = 8;

    /**
     * Used to flag a purchase as sensitive order.
     *
     * @var int
     */
    const SENSITIVE_ORDER = 1024;

    /**
     * Used to return an array with long and short ocr number.
     *
     * @see Billmate::addTransaction()
     * @var int
     */
    const RETURN_OCR = 8192;

    /**
     * Specifies the shipment type as normal.
     *
     * @var int
     */
    const NORMAL_SHIPMENT = 1;

    /**
     * Specifies the shipment type as express.
     *
     * @var int
     */
    const EXPRESS_SHIPMENT = 2;

//Mobile (Invoice) flags
    /**
     * Marks the transaction as Billmate mobile.
     *
     * @var int
     */
    const M_PHONE_TRANSACTION = 262144;

    /**
     * Sends a pin code to the phone sent in pno.
     *
     * @var int
     */
    const M_SEND_PHONE_PIN = 524288;

//Reservation flags
    /**
     * Signifies that the amount specified is the new amount.
     *
     * @var int
     */
    const NEW_AMOUNT = 0;

    /**
     * Signifies that the amount specified is to be added.
     *
     * @var int
     */
    const ADD_AMOUNT = 1;

    /**
     * Sends the invoice by mail when activating a reservation.
     *
     * @var int
     */
    const RSRV_SEND_BY_MAIL = 4;

    /**
     * Sends the invoice by e-mail when activating a reservation.
     *
     * @var int
     */
    const RSRV_SEND_BY_EMAIL = 8;

    /**
     * Used for partial deliveries, this flag saves the reservation number so it can be used again.
     *
     * @var int
     */
    const RSRV_PRESERVE_RESERVATION = 16;

    /**
     * Used to flag a purchase as sensitive order.
     *
     * @var int
     */
    const RSRV_SENSITIVE_ORDER = 32;

    /**
     * Marks the transaction as Billmate mobile.
     *
     * @var int
     */
    const RSRV_PHONE_TRANSACTION = 512;

    /**
     * Sends a pin code to the mobile number.
     *
     * @var int
     */
    const RSRV_SEND_PHONE_PIN = 1024;

    /**
     * Class constructor.
     * Disable instantiation.
     */
    private function __construct() {

    }
}


/**
 * Provides currency constants for the supported countries.
 *
 * @package BillmateAPI
 */
class BillmateCurrency {

    /**

     * Currency constant for Swedish Crowns (SEK).
     *
     * @var int
     */
    const SEK = 0;

    /**
     * Currency constant for Norwegian Crowns (NOK).
     *
     * @var int
     */
    const NOK = 1;

    /**
     * Currency constant for Euro.
     *
     * @var int
     */
    const EUR = 2;

    /**
     * Currency constant for Danish Crowns (DKK).
     *
     * @var int
     */
    const DKK = 3;

    /**
     * Class constructor.
     * Disable instantiation.
     */
    private function __construct() {

    }

    /**
     * Converts a currency code, e.g. 'eur' to the BillmateCurrency constant.
     *
     * @param  string  $val
     * @return int|null
     */
    public static function fromCode($val) {
       switch(strtolower($val)) {
            case 'dkk':
                return self::DKK;
            case 'eur':
            case 'euro':
                return self::EUR;
            case 'nok':
                return self::NOK;
            case 'sek':
                return self::SEK;
            default:
                return null;
       }
    }

    /**
     * Converts a BillmateCurrency constant to the respective language code.
     *
     * @param  int  $val
     * @return string|null
     */
    public static function getCode($val) {
        switch($val) {
            case self::DKK:
                return 'dkk';
            case self::EUR:
                return 'eur';
            case self::NOK:
                return 'nok';
            case self::SEK:
                return 'sek';
            default:
                return null;
        }
    }

} //End BillmateCurrency
}

/**
 * Provides language constants (ISO639) for the supported countries.
 *
 * @package BillmateAPI
 */
if(!class_exists('BillmateLanguage')){
class BillmateLanguage {

    /**
     * Language constant for Danish (DA).<br>
     * ISO639_DA
     *
     * @var int
     */
    const DA = 27;

    /**
     * Language constant for German (DE).<br>
     * ISO639_DE
     *
     * @var int
     */
    const DE = 28;

    /**
     * Language constant for English (EN).<br>
     * ISO639_EN
     *
     * @var int
     */
    const EN = 31;

    /**
     * Language constant for Finnish (FI).<br>
     * ISO639_FI
     *
     * @var int
     */
    const FI = 37;

    /**
     * Language constant for Norwegian (NB).<br>
     * ISO639_NB
     *
     * @var int
     */
    const NB = 97;

    /**
     * Language constant for Dutch (NL).<br>
     * ISO639_NL
     *
     * @var int
     */
    const NL = 101;

    /**
     * Language constant for Swedish (SV).<br>
     * ISO639_SV
     *
     * @var int
     */
    const SV = 138;

    /**
     * Class constructor.
     * Disable instantiation.
     */
    private function __construct() {

    }

    /**
     * Converts a language code, e.g. 'de' to the BillmateLanguage constant.
     *
     * @param  string  $val
     * @return int|null
     */
    public static function fromCode($val) {
        switch(strtolower($val)) {
            case 'en':
                return self::EN;
            case 'da':
                return self::DA;
            case 'de':
                return self::DE;
            case 'fi':
                return self::FI;
            case 'nb':
                return self::NB;
            case 'nl':
                return self::NL;
            case 'sv':
                return self::SV;
            default:
                return null;
        }
    }

    /**
     * Converts a BillmateLanguage constant to the respective language code.
     *
     * @param  int  $val
     * @return string|null
     */
    public static function getCode($val) {
        switch($val) {
            case self::EN:
                return 'en';
            case self::DA:
                return 'da';
            case self::DE:
                return 'de';
            case self::FI:
                return 'fi';
            case self::NB:
                return 'nb';
            case self::NL:
                return 'nl';
            case self::SV:
                return 'sv';
            default:
                return null;
        }
    }

} //End BillmateLanguage
}

/**
 * Provides country constants (ISO3166) for the supported countries.
 *
 * @package BillmateAPI
 */
if(!class_exists('BillmateCountry')){
	class BillmateCountry {

		/**
		 * Country constant for Denmark (DK).<br>
		 * ISO3166_DK
		 *
		 * @var int
		 */
		const DK = 59;

		/**
		 * Country constant for Finland (FI).<br>
		 * ISO3166_FI
		 *
		 * @var int
		 */
		const FI = 73;

		/**
		 * Country constant for Germany (DE).<br>
		 * ISO3166_DE
		 *
		 * @var int
		 */
		const DE = 81;

		/**
		 * Country constant for Netherlands (NL).<br>
		 * ISO3166_NL
		 *
		 * @var int
		 */
		const NL = 154;

		/**
		 * Country constant for Norway (NO).<br>
		 * ISO3166_NO
		 *
		 * @var int
		 */
		const NO = 164;

		/**
		 * Country constant for Sweden (SE).<br>
		 * ISO3166_SE
		 *
		 * @var int
		 */
		const SE = 209;

		/**
		 * Class constructor.
		 * Disable instantiation.
		 */
		private function __construct() {
		}

		/**
		 * Converts a country code, e.g. 'de' or 'deu' to the BillmateCountry constant.
		 *
		 * @param  string  $val
		 * @return int|null
		 */
		public static function fromCode($val) {
			switch(strtolower($val)) {
				case 'swe':
				case 'se':
					return self::SE;
				case 'nor':
				case 'no':
					return self::NO;
				case 'dnk':
				case 'dk':
					return self::DK;
				case 'fin':
				case 'fi':
					return self::FI;
				case 'deu':
				case 'de':
					return self::DE;
				case 'nld':
				case 'nl':
					return self::NL;
				default:
					return null;
			}
		}

		/**
		 * Converts a BillmateCountry constant to the respective country code.
		 *
		 * @param  int  $val
		 * @param  bool $alpha3  Whether to return a ISO-3166-1 alpha-3 code
		 * @return string|null
		 */
		public static function getCode($val, $alpha3 = false) {
			 switch($val) {
				case BillmateCountry::SE:
					return ($alpha3) ? 'swe' : 'se';
				case BillmateCountry::NO:
					return ($alpha3) ? 'nor' : 'no';
				case BillmateCountry::DK:
					return ($alpha3) ? 'dnk' : 'dk';
				case BillmateCountry::FI:
					return ($alpha3) ? 'fin' : 'fi';
				case BillmateCountry::DE:
					return ($alpha3) ? 'deu' : 'de';
				case self::NL:
					return ($alpha3) ? 'nld' : 'nl';
				default:
					return null;
			}
		}
		public static function getCountryData( $country = 'SE' ){
			switch (strtoupper($country)) {
				// Sweden
				case 'SWE':
				case 'SE':
				case 209:
					$country = 209;
					$language = 138;
					$encoding = 2;
					$currency = 0;
					break;
				// Finland
				case 'FIN':
				case 'FI':
				case 73:
					$country = 73;
					$language = 37;
					$encoding = 4;
					$currency = 2;
					break;
				// Denmark
				case 'DNK':
				case 'DK':
				case 59:
					$country = 59;
					$language = 27;
					$encoding = 5;
					$currency = 3;
					break;
				// Norway
				case 'NOR':
				case 'NO':
				case 164:
					$country = 164;
					$language = 97;
					$encoding = 3;
					$currency = 1;
					break;
				// Germany
				case 'DEU':
				case 'DE':
				case 81:
					$country = 81;
					$language = 28;
					$encoding = 6;
					$currency = 2;
					break;
				// Netherlands
				case 'NLD':
				case 'NL':
				case 154:
					$country = 154;
					$language = 101;
					$encoding = 7;
					$currency = 2;
					break;
			}
			return array('country'=>$country,'language'=> $language, 'encoding' => $encoding,'currency' => $currency );
		}
		public static function getSwedenData(){
			$country = 209;
			$language = 138;
			$encoding = 2;
			$currency = 0;
			return array('country'=>$country,'language'=> $language, 'encoding' => $encoding,'currency' => $currency );
		}
	} //End BillmateCountry
}
