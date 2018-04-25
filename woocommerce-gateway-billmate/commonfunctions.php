<?php
define('BILLPLUGIN_VERSION','3.2.0');
define('BILLMATE_CLIENT','PHP:Woocommerce:'.BILLPLUGIN_VERSION);
define('BILLMATE_SERVER','2.1.9');

require_once(BILLMATE_LIB . 'Billmate.php');
require_once(BILLMATE_LIB . 'billmatecalc.php');
require_once dirname( __FILE__ ) .'/utf8.php';

if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH.'wp-admin/includes/plugin.php');
}

function convertToUTF8($str) {
    $enc = mb_detect_encoding($str);

    if ($enc && $enc != 'UTF-8') {
        return iconv($enc, 'UTF-8', $str);
    } else {
        return $str;
    }
}
function wc_bm_errors($message){
	global $woocommerce;
	$message = convertToUTF8($message);
    if(!is_admin()){
        if(version_compare(WC_VERSION, '2.0.0', '<')){
            $woocommerce->add_error( $message );
        } elseif (function_exists('wc_add_notice')) {
            wc_add_notice( $message, 'error' );
        }
    } else {
        add_action('admin_notices','billmate_admin_notice');
        function billmate_admin_notice() {
            ?>
            <div class="error notice">
                <p><?php _e( 'There has been an error with the payment!', 'billmate' ); ?></p>
            </div>
            <?php
        }
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


if(!class_exists('BillmateOrder')){
    class BillmateOrder {

        private $order;
        private $orderData;
        private $allowedCountries;
        private $paymentterms;
        private $customerPno;

        private $articlesTotal;
        private $articlesTotalTax;

        private $shop_country;

        public function __construct($order) {
            $this->order = $order;
            $this->orderData = array();
            $this->allowedCountries = array();
            $this->paymentterms = 0;

            $this->articlesTotal = 0;
            $this->articlesTotalTax = 0;

            $this->fetch_shop_country();
        }

        // Return shop country code, SE if Sweden
        public function fetch_shop_country() {
            $this->shop_country = get_option('woocommerce_default_country');
            // Check if woocommerce_default_country includes state as well. If it does, remove state
            if (strstr($this->shop_country, ':')) {
                $this->shop_country = current(explode(':', $this->shop_country));
            }
            return $this->shop_country;
        }

        public function setCustomerPno($pno = "") {
            $this->customerPno = $pno;
        }

        public function setAllowedCountries($countries = array()) {
            $this->allowedCountries = $countries;
        }

        public function setPaymentterms($paymentterms) {
            $this->paymentterms = $paymentterms;
        }

        public function getCustomerData() {
            $this->orderData['Customer'] = array();
            if($this->customerPno != "") {
                $this->orderData['Customer']['pno'] = $this->customerPno;
            }

            $this->orderData['Customer']['nr'] = $this->getCustomerNrData();
            $this->orderData['Customer']['Billing'] = $this->getCustomerBillingData();
            $this->orderData['Customer']['Shipping'] = $this->getCustomerShippingData();

            return $this->orderData['Customer'];
        }

        public function getCustomerPnoData() {
            if(isset($this->orderData['Customer']['pno'])) {
                return $this->orderData['Customer']['pno'];
            } else {
                if(!isset($this->orderData['Customer'])) {
                    $this->orderData['Customer'] = array();
                }
            }
            $this->orderData['Customer']['pno'] = $this->customerPno;
            return $this->orderData['Customer']['pno'];
        }

        public function getCustomerNrData() {
            if(isset($this->orderData['Customer']['nr'])) {
                return $this->orderData['Customer']['nr'];
            } else {
                if(!isset($this->orderData['Customer'])) {
                    $this->orderData['Customer'] = array();
                }
            }

            if($this->is_wc3()) {
                $orderUserId = $this->order->get_user_id();
                $this->orderData['Customer']['nr'] = (empty($orderUserId ) || $orderUserId <= 0) ? '': $orderUserId;
            } else {
                $this->orderData['Customer']['nr'] = empty($this->order->user_id ) || $this->order->user_id<= 0 ? '': $this->order->user_id;
            }

            return $this->orderData['Customer']['nr'];
        }

        public function getCustomerBillingData() {

            if(isset($this->orderData['Customer']['Billing'])) {
                return $this->orderData['Customer']['Billing'];
            } else {
                if(!isset($this->orderData['Customer'])) {
                    $this->orderData['Customer'] = array();
                }
            }

            if($this->is_wc3()) {
                $this->orderData['Customer']['Billing'] = array(
                    'firstname' => $this->utf8Encode($this->order->get_billing_first_name()),
                    'lastname' => $this->utf8Encode($this->order->get_billing_last_name()),
                    'company' => $this->utf8Encode($this->order->get_billing_company()),
                    'street' => $this->utf8Encode($this->order->get_billing_address_1()),
                    'street2' => $this->utf8Encode($this->order->get_billing_address_2()),
                    'zip' => $this->order->get_billing_postcode(),
                    'city' => $this->utf8Encode($this->order->get_billing_city()),
                    'country' => $this->order->get_billing_country(),
                    'phone' => $this->order->get_billing_phone(),
                    'email' => $this->order->get_billing_email()
                );
            } else {
                $this->orderData['Customer']['Billing'] = array(
                    'firstname' => $this->utf8Encode($this->order->billing_first_name),
                    'lastname' => $this->utf8Encode($this->order->billing_last_name),
                    'company' => $this->utf8Encode($this->order->billing_company),
                    'street' => $this->utf8Encode($this->order->billing_address_1),
                    'street2' => $this->utf8Encode($this->order->billing_address_2),
                    'zip' => $this->order->billing_postcode,
                    'city' => $this->utf8Encode($this->order->billing_city),
                    'country' => $this->order->billing_country,
                    'phone' => $this->order->billing_phone,
                    'email' => $this->order->billing_email
                );
            }

            if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) {
                require_once('split-address.php');
                $billmate_billing_address           = $this->orderData['Customer']['Billing']['street'];
                $splitted_address                   = splitAddress($billmate_billing_address);
                $billmate_billing_address           = $splitted_address[0];
                $billmate_billing_house_number      = $splitted_address[1];
                $billmate_billing_house_extension   = $splitted_address[2];
                $this->orderData['Customer']['Billing']['street'] = $billmate_billing_address;
            }

            return $this->orderData['Customer']['Billing'];
        }

        public function getCustomerShippingData() {
            if(isset($this->orderData['Customer']['Shipping'])) {
                return $this->orderData['Customer']['Shipping'];
            } else {
                if(!isset($this->orderData['Customer'])) {
                    $this->orderData['Customer'] = array();
                }
            }

            // Customer billing need to be set
            $this->getCustomerBillingData();

            if ( $this->order->get_shipping_method() == '' ) {
                $this->orderData['Customer']['Shipping'] = $this->orderData['Customer']['Billing'];

                $this->orderData['Customer']['Shipping'] = array(
                    'firstname' => $this->orderData['Customer']['Billing']['firstname'],
                    'lastname' => $this->orderData['Customer']['Billing']['lastname'],
                    'company' => $this->orderData['Customer']['Billing']['company'],
                    'street' => $this->orderData['Customer']['Billing']['street'],
                    'street2' => $this->orderData['Customer']['Billing']['street2'],
                    'zip' => $this->orderData['Customer']['Billing']['zip'],
                    'city' => $this->orderData['Customer']['Billing']['city'],
                    'country' => $this->orderData['Customer']['Billing']['country'],
                    'phone' => $this->orderData['Customer']['Billing']['phone']
                );

                return $this->orderData['Customer']['Shipping'];
            }

            if($this->is_wc3()) {
                $this->orderData['Customer']['Shipping'] = array(
                    'firstname' => $this->utf8Encode($this->order->get_shipping_first_name()),
                    'lastname' => $this->utf8Encode($this->order->get_shipping_last_name()),
                    'company' => $this->utf8Encode($this->order->get_shipping_company()),
                    'street' => $this->utf8Encode($this->order->get_shipping_address_1()),
                    'street2' => $this->utf8Encode($this->order->get_shipping_address_2()),
                    'zip' => $this->order->get_shipping_postcode(),
                    'city' => $this->utf8Encode($this->order->get_shipping_city()),
                    'country' => $this->order->get_shipping_country(),
                    'phone' => $this->order->get_billing_phone()
                );
            } else {
                $this->orderData['Customer']['Shipping'] = array(
                    'firstname' => $this->utf8Encode( $this->order->shipping_first_name),
                    'lastname' => $this->utf8Encode( $this->order->shipping_last_name),
                    'company' => $this->utf8Encode( $this->order->shipping_company),
                    'street' => $this->utf8Encode( $this->order->shipping_address_1),
                    'street2' => $this->utf8Encode( $this->order->shipping_address_2),
                    'zip' => $this->utf8Encode( $this->order->shipping_postcode),
                    'city' => $this->utf8Encode( $this->order->shipping_city),
                    'country' => $this->order->shipping_country,
                    'phone' => $this->order->billing_phone
                );
            }

            if ( $this->shop_country == 'NL' || $this->shop_country == 'DE' ) {
                require_once('split-address.php');
                $billmate_shipping_address          = $this->orderData['Customer']['Shipping']['street'];
                $splitted_address                   = splitAddress($billmate_shipping_address);
                $billmate_shipping_address          = $splitted_address[0];
                $billmate_shipping_house_number     = $splitted_address[1];
                $billmate_shipping_house_extension  = $splitted_address[2];
                $this->orderData['Customer']['Shipping']['street'] = $billmate_shipping_address;
            }

            return $this->orderData['Customer']['Shipping'];
        }


        public function getPaymentInfoData() {
            if(isset($this->orderData['PaymentInfo'])) {
                return $this->orderData['PaymentInfo'];
            }

            // Customer billing need to be set
            $this->getCustomerBillingData();

            $this->orderData['PaymentInfo'] = array();
            $this->orderData['PaymentInfo']['paymentdate'] = (string)date('Y-m-d');

            if($this->paymentterms > 0) {
                $this->orderData['PaymentInfo']['paymentterms'] = $this->paymentterms;
            }

            $this->orderData['PaymentInfo']['yourreference'] = $this->orderData['Customer']['Billing']['firstname'].' '.$this->orderData['Customer']['Billing']['lastname'];
            return $this->orderData['PaymentInfo'];
        }

        public function getArticlesTotal() {
            return $this->articlesTotal;
        }

        public function getArticlesTotalTax() {
            return $this->articlesTotalTax;
        }

        public function getArticlesData() {
            /* Return articles and discout to be used in Billmate API requests */
            if(isset($this->orderData['Articles'])) {
                return $this->orderData['Articles'];
            } else {
                $this->orderData['Articles'] = array();
            }

            /* Articles */
            $total = 0;
            $totalTax = 0;

            $isOrderDiscount = true;    /* If true, all articles have discount, if false, discount is for individual articles */
            $orderArticles = array();

            if (sizeof($this->order->get_items())>0) {
                foreach ($this->order->get_items() as $item) {
                    $_product = $this->order->get_product_from_item( $item );

                    if ($_product->exists() && $item['qty']) {

                        /* Formatting the product data that will be sent as api requests */
                        $billmateProduct = new BillmateProduct($_product, $item);

                        $item_tax_percentage    = $billmateProduct->getTaxRate();
                        $sku                    = $billmateProduct->getSku();

                        /** Start with price including tax */
                        $_quantity              = (int)$item['qty'];
                        $_aprice_with_tax       = $this->order->get_item_total($item, true);
                        $_aprice_without_tax    = $_aprice_with_tax;
                        $_aprice_tax            = 0;

                        $_total                 = $this->order->get_line_total($item, true);
                        $_total_inc_tax         = $_total;
                        $_total_tax             = 0;

                        /* Subtotal is article original price and not affected by discount */
                        $_sub_aprice                = $this->order->get_item_subtotal($item,true);
                        $_sub_aprice_without_tax    = $_sub_aprice;

                        $_subtotal                  = $this->order->get_line_subtotal($item,true);
                        $_subtotal_inc_tax          = $_subtotal;
                        $_subtotal_tax              = 0;

                        if ($item_tax_percentage > 0) {
                            $_aprice_without_tax /= (1 + ($item_tax_percentage / 100));
                            $_aprice_tax = $_aprice_with_tax - $_aprice_without_tax;

                            $_total /= (1 + ($item_tax_percentage / 100));
                            $_total_tax = $_total_inc_tax - $_total;

                            $_sub_aprice_without_tax /= (1 + ($item_tax_percentage / 100));

                            $_subtotal /= (1 + ($item_tax_percentage / 100));
                            $_subtotal_tax = $_subtotal_inc_tax - $_subtotal;
                        }

                        $_discount          = 0; /* Discount percentage */
                        $_discountTotal     = 0;
                        $_discountTotalTax  = 0;

                        if ($_total != $_subtotal) {
                            /* Assume discount when total and subtotal not match */
                            if ($_total > 0) {
                                $_discount = (1 - ($_total / $_subtotal)) * 100;
                            }
                            $_discount_total = $_subtotal - $_total;
                            $_discount_total_tax = $_subtotal_tax - $_total_tax;
                        } else {
                            /* This article does not have discount, discount is for individual articles */
                            $isOrderDiscount = false;
                        }

                        /** Discount cant be lower than 0 */
                        $_discount              = ($_discount < 0)              ? 0 : $_discount;
                        $_discount_total        = ($_discount_total < 0)        ? 0 : $_discount_total;
                        $_discount_total_tax    = ($_discount_total_tax < 0)    ? 0 : $_discount_total_tax;

                        $orderArticle = array(
                            'quantity'              => $_quantity,
                            'artnr'                 => $sku,
                            'title'                 => $billmateProduct->getTitle(),
                            'aprice'                => round($_aprice_without_tax * 100),
                            'taxrate'               => round($item_tax_percentage),
                            'discount'              => $_discount,
                            'withouttax'            => round($_total * 100),
                            'total'                 => round($_total * 100),
                            'total_tax'             => round($_total_tax * 100),
                            'total_inc_tax'         => round($_total_inc_tax * 100),

                            /** Price with no discount */
                            'sub_aprice'            => round($_sub_aprice_without_tax * 100),
                            'subtotal'              => round($_subtotal * 100),
                            'subtotal_tax'          => round($_subtotal_tax * 100),
                            'subtotal_inc_tax'      => round($_subtotal_inc_tax * 100),

                            'discount_total'        => round($_discount_total * 100),
                            'discount_total_tax'    => round($_discount_total_tax * 100),

                            'aprice_with_tax'       => round($_aprice_with_tax * 100),
                            'sub_aprice_with_tax'   => round($_sub_aprice * 100)
                        );

                        $orderArticles[] = $orderArticle;

                        /* Add to order total */
                        $total          += $_total          * 100;
                        $totalTax       += $_total_tax      * 100;
                        $subtotal       += $_subtotal       * 100;
                        $subtotalTax    += $_subtotal_tax   * 100;

                    } // endif
                } // endforeach
            }   // endif

            $total = 0;
            $totalTax = 0;

            $discountTotals = array();
            $discountTotalTaxs = array();

            foreach ($orderArticles AS $orderArticle) {
                /* 
                 * Use discounted price if product discount
                 * If discount is for complete order, add discount later as new row 
                 */
                $taxrate = $orderArticle['taxrate'];

                if(!isset($discountTotals[$taxrate])) {
                    $discountTotals[$taxrate] = 0;
                }

                if(!isset($discountTotalTaxs[$taxrate])) {
                    $discountTotalTaxs[$taxrate] = 0;
                }

                $article = array(
                    'quantity'      => $orderArticle['quantity'],
                    'artnr'         => $orderArticle['artnr'],
                    'title'         => $orderArticle['title'],
                    'aprice'        => $orderArticle['sub_aprice'],
                    'taxrate'       => $orderArticle['taxrate'],
                    'discount'      => $orderArticle['discount'],
                    'withouttax'    => $orderArticle['withouttax']
                );
                $_total = $orderArticle['withouttax'];
                $_totalTax = $orderArticle['total_tax'];

                if ($isOrderDiscount == true) {
                    /* Discount is on total order and not on item level */
                    $article = array(
                        'quantity'      => $orderArticle['quantity'],
                        'artnr'         => $orderArticle['artnr'],
                        'title'         => $orderArticle['title'],
                        'aprice'        => $orderArticle['sub_aprice'],
                        'taxrate'       => $orderArticle['taxrate'],
                        'discount'      => 0,
                        'withouttax'    => $orderArticle['subtotal']
                    );
                    $_total = $orderArticle['subtotal'];
                    $_totalTax = $orderArticle['subtotal_tax'];

                    $discountTotals[$taxrate] += $orderArticle['discount_total'];
                    $discountTotalTaxs[$taxrate] += $orderArticle['discount_total_tax'];
                }

                $this->orderData['Articles'][] = $article;

                $total += $_total;
                $totalTax += $_totalTax;
            }

            /* Additional fees */
            $orderFeesArticles = BillmateOrder::getOrderFeesAsOrderArticles();
            $this->orderData['Articles'] = array_merge($this->orderData['Articles'], $orderFeesArticles);
            foreach($orderFeesArticles AS $orderFeesArticle) {
                $total += $orderFeesArticle['aprice'];
                $totalTax += ($orderFeesArticle['aprice'] * ($orderFeesArticle['taxrate']/100));
            }

            /**
             * Check if order items have multiple taxrates
             * Will be used when determine if use own calculated discount or from store
             */
            $isOneTaxrate = true;
            $orderTaxrate = 0;
            foreach ($orderArticles AS $orderArticle) {
                if ($orderTaxrate < 1) {
                    $orderTaxrate = $orderArticle['taxrate'];
                }
                if ($orderTaxrate != $orderArticle['taxrate']) {
                    $isOneTaxrate = false;
                }
            }

            /* Order discount */
            if (    $isOrderDiscount == true
                    && count($discountTotals) > 0
                    && $isOneTaxrate ==  false
            ) {
                // Order by taxrate ASC
                ksort($discountTotals);
                foreach($discountTotals AS $key => $discountAmount) {
                    if($discountAmount > 0) {
                        $this->orderData['Articles'][] = array(
                            'quantity'   => (int)1,
                            'artnr'    => "",
                            'title'    => sprintf(__('Discount %s%% tax', 'billmate'),round($key,0)),
                            'aprice'    => -abs($discountAmount),
                            'taxrate'      => (int)$key,
                            'discount' => (float)0,
                            'withouttax' => -abs($discountAmount),
                        );

                        $total -= $discountAmount;
                        $totalTax -= (isset($discountTotalTaxs[$key]) ? $discountTotalTaxs[$key] : 0);
                    }
                }
            }

            /** Use woocommerce discount when discount affect all order items and all items have same taxrate */
            if (    $isOrderDiscount == true
                    && $this->order->get_discount_total() > 0
                    && $isOneTaxrate == true
            ) {
                $discountAmount = $this->order->get_discount_total();
                $discountAmount = round($discountAmount * 100);
                $this->orderData['Articles'][] = array(
                    'quantity'      => (int)1,
                    'artnr'         => "",
                    'title'         => sprintf(__('Discount %s%% tax', 'billmate'),round($taxrate,0)),
                    'aprice'        => abs($discountAmount),
                    'taxrate'       => $orderTaxrate,
                    'discount'      => (float)0,
                    'withouttax'    => -abs($discountAmount),
                );

                $total      -= $discountAmount;
                $totalTax   -= round($this->order->get_discount_tax() * 100);
            }

            foreach ($this->orderData['Articles'] AS $i => $article) {
                $this->orderData['Articles'][$i]['aprice'] = round($article['aprice']);
                $this->orderData['Articles'][$i]['taxrate'] = round($article['taxrate']);
                $this->orderData['Articles'][$i]['withouttax'] = round($article['withouttax']);
            }

            $this->articlesTotal = $total;
            $this->articlesTotalTax = $totalTax;

            return $this->orderData['Articles'];
        }

        public function getCartShippingData() {
            if (!isset($this->orderData['Cart'])) {
                $this->orderData['Cart'] = array();
            }
            $price = $this->getCartShipping();
            $this->orderData['Cart']['Shipping'] = array(
                'withouttax' => $price['price'],
                'taxrate' => $price['taxrate']
            );

            return $this->orderData['Cart']['Shipping'];
        }

        public function getCartShipping() {

            $taxrate        = 0;
            $shipping_total = 0;
            $shipping_tax   = 0;

            if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                $order_shipping_total   = $this->order->get_shipping_total();
                $order_shipping_tax     = $this->order->get_shipping_tax();

                $cart_shipping_total    = WC()->cart->shipping_total;
                $cart_shipping_tax      = WC()->cart->shipping_tax_total;

                $shipping_total     = $order_shipping_total;
                $shipping_tax       = $order_shipping_tax;

                /*
                 * When shipping is available in cart and total is same as order, use cart tax
                 * In this case cart shipping tax is more accurate than order shipping tax
                 */
                if (    $cart_shipping_total > 0
                        && $order_shipping_total > 0
                        && $shipping_tax > 0
                        && $order_shipping_tax > 0
                        && $cart_shipping_total == $order_shipping_total
                ) {
                    $shipping_tax = $cart_shipping_tax;
                }

                if ($order_shipping_tax > 0 AND is_object(WC()->cart) == true AND method_exists(WC()->cart, 'get_cart_item_tax_classes') == true) {
                    // Get shipping tax rate from cart
                    $rates = current(WC_Tax::get_shipping_tax_rates());
                    if (is_array($rates) AND isset($rates['rate'])) {
                        $taxrate = round($rates['rate']);
                    }
                }
            } else {
                $shipping_total   = $this->order->order_shipping;
                $shipping_tax     = $this->order->order_shipping_tax;
            }

            /**
             * We dont know if $order_shipping_total is rounded or not
             * We know that $order_shipping_tax is not rounded and is accurate
             * Get $order_shipping_total based on tax and taxrate
             */
            if ($shipping_total > 0 AND $shipping_tax > 0) {
                if ($taxrate > 0) {
                    $shipping_total = $shipping_tax / ($taxrate / 100);
                } else {
                    /** No taxrate available, get taxrate based on $shipping_total and $shipping_tax */
                    $taxrate = ($shipping_tax / $shipping_total) * 100;
                }
            }

            return array(
                "price"             => round($shipping_total * 100),
                "taxrate"           => round($taxrate),
                'tax'               => round($shipping_tax * 100),
                "price_with_tax"    => round(($shipping_total + $shipping_tax) * 100)
            );
        }

        public function is_wc3() {
            return version_compare(WC_VERSION, '3.0.0', '>=');
        }

        private function utf8Encode($param = "") {
            if($param) {
                $param = mb_convert_encoding($param,'UTF-8','auto');
            }
            return $param;
        }


        public static function getOrderFeesAsOrderArticles() {
            global $woocommerce;

            /* Return additional fees that are not invoice fee as order article */
            $billmateOrderArticles = array();

            if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '>' ) ) {
                if (is_object(WC()->cart) == false OR method_exists(WC()->cart, 'get_fees') == false) {
                    return $billmateOrderArticles;
                }

                $fees = WC()->cart->get_fees();
                foreach($fees as $fee){
                    if(strtolower($fee->id) != strtolower(__('Invoice fee','billmate')) AND strtolower($fee->name) != strtolower(__('Invoice fee','billmate'))) {
                        $tax = new WC_Tax();
                        $invoicetax = $tax->get_rates($fee->tax_class);
                        $rate = array_pop($invoicetax);
                        $rate = $rate['rate'];

                        $billmateOrderArticles[] = array(
                            'quantity'   => 1,
                            'artnr'    => $fee->id,
                            'title'    => $fee->name,
                            'aprice'    =>  ($fee->amount * 100),
                            'taxrate'      => $rate,
                            'discount' => 0,
                            'withouttax' => ($fee->amount * 100)
                        );
                    }
                }
            }
            return $billmateOrderArticles;
        }

        /**
         * Maybe convert price to customer selected currency if available
         * @param float|int
         * @return float|int
         */
        public static function maybeConvertCurrency($price) {
            /** When WPML multi currency is available use selected currency rate on $invoice_fee_withtax that will be displayed to customer */
            if (function_exists('has_filter') AND has_filter('wcml_raw_price_amount')) {
                $price = apply_filters('wcml_raw_price_amount', $price);
            }
            return $price;
        }

        public static function formatPrice($price = 0) {
            return round($price * 100);
        }


        public static function getTaxRateFromClass($tax_class) {
            $tax = new WC_Tax();
            $rate = $tax->get_rates($tax_class);
            $rate = array_pop($rate);
            $rate = $rate['rate'];
            return $rate;
        }

        public static function getFormattedInvoiceFee() {
            $price_with_tax = 0;
            $price          = 0;
            $taxrate        = 0;
            $tax            = 0;

            $invoice_fee    = new WC_Gateway_Billmate_Invoice;
            $taxrate        = self::getTaxRateFromClass($invoice_fee->invoice_fee_tax_class);
            $price          = self::maybeConvertCurrency($invoice_fee->invoice_fee);
            $price_with_tax = $price;

            if ($price > 0 AND $taxrate > 0) {
                $price_with_tax *= (1 + ($taxrate / 100));
                $tax = $price_with_tax - $price;
            }

            $price_with_tax = wc_format_decimal( $price_with_tax, get_option( 'woocommerce_price_num_decimals' ) );
            if ($taxrate > 0) {
                $price  = $price_with_tax / (1 + ($taxrate/100));
                $tax    = $price_with_tax - $price;
            }

            return array(
                'price_with_tax'    => self::formatPrice($price_with_tax),
                'price'             => self::formatPrice($price),
                'taxrate'           => round($taxrate),
                'tax'               => self::formatPrice($tax),
                'raw_price'         => $invoice_fee->invoice_fee,
                'raw_tax_class'     => $invoice_fee->invoice_fee_tax_class
            );
        }

    }
}

if(!class_exists('BillmateProduct')) {
    /* Formatting the product data that will be sent as api requests */
    class BillmateProduct {
        private $product;
        private $orderItem;

        public function __construct($product, $orderItem = array()) {
            $this->product = $product;
            $this->orderItem = $orderItem;
        }

        public function getTitle() {
            $name = $this->product->get_title();
            if (isset($this->orderItem['name']) AND trim($this->orderItem['name']) != "") {
                $name = $this->orderItem['name'];
            }

            if($this->product->is_type('variation')) {
                $name = $this->product->get_title();

                if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                    $name .= ' - ' . wc_get_formatted_variation($this->product, true);
                } else {
                    $name .= ' - ' . $this->product->get_formatted_variation_attributes(true);
                }
            }

            if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                $name = wc_clean($name);
            } else {
                $name = woocommerce_clean($name);
            }

            return $name;
        }


        public function getSku()
        {
            if ( $this->product->get_sku() ) {
                $sku = $this->product->get_sku();
            } else {
                if(version_compare(WC_VERSION, '3.0.0', '>=')) {
                    $sku = $this->product->get_id();
                } else {
                    $sku = $this->product->id;
                }
            }
            return $sku;
        }


        public function getTaxRate()
        {
            $item_tax_percentage = 0;

            // is product taxable?
            if ($this->product->is_taxable()) {
                $taxClass = $this->product->get_tax_class();
                $tax = new WC_Tax();
                $rates = $tax->get_rates($taxClass);
                $item_tax_percentage = 0;
                foreach ($rates as $row) {
                    // Is it Compound Tax?
                    if (isset($row['compund']) && $row['compound'] == 'yes') {
                        $item_tax_percentage += $row['rate'];
                    } else {
                        $item_tax_percentage = $row['rate'];
                    }
                }
            }
            return $item_tax_percentage;
        }


    }
}


if(!class_exists('BillmateAdminNotice')) {
    class BillmateAdminNotice {

        private static $instance;
        public $notices;

        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function _add_notice($type = 'error', $notice = '', $link_url = '', $link_title = '') {
            if ($notice != '') {
                if (!isset($this->notices[$type])) {
                    $this->notices[$type] = array();
                }
                // $this->notices[$type][] = $message;
                $this->notices[$type][] = array(
                    'title' => 'Billmate',
                    'notice' => $notice,
                    'link_url' => $link_url,
                    'link_title' => $link_title
                );
            }
        }

        public function _get_notices() {
            return $this->notices;
        }

        public static function add_error($notice = '', $link_url = '', $link_title = '') {
            $instance = self::get_instance();
            $instance->_add_notice('error', $notice, $link_url, $link_title);
        }

        public static function add_info($notice = '', $link_url = '', $link_title = '') {
            $instance = self::get_instance();
            $instance->_add_notice('info', $notice, $link_url, $link_title);
        }

        public static function add_success($notice = '', $link_url = '', $link_title = '') {
            $instance = self::get_instance();
            $instance->_add_notice('success', $notice, $link_url, $link_title);
        }

        public static function add_warning($notice = '', $link_url = '', $link_title = '') {
            $instance = self::get_instance();
            $instance->_add_notice('warning', $notice, $link_url, $link_title);
        }


        public static function show_notices() {
            $instance = self::get_instance();

            $notices = $instance->_get_notices();
            if (is_array($notices) AND count($notices) > 0) {
                foreach ($notices AS $type => $_notices) {
                    $class = 'notice notice-info';
                    if (in_array($type, array('info', 'error', 'success', 'warning'))) {
                        $class = 'notice notice-'.$type;
                    }

                    foreach ($_notices AS $message) {
                        if (is_array($message) AND isset($message['title']) AND isset($message['notice'])) {

                            $link = '';
                            if (    isset($message['link_title'])
                                    && isset($message['link_url'])
                                    && $message['link_title'] != ''
                                    && $message['link_url'] != ''
                            ) {
                                $link = sprintf('<a href="%1$s">%2$s</a>',esc_html( $message['link_url'] ),esc_html( $message['link_title'] ));
                            }

                            printf(
                                '<div class="%1$s"><p><strong>%2$s</strong> - %3$s '.$link.'</p></div>',
                                esc_attr( $class ),
                                $imgHtml .esc_html( $message['title'] ),
                                esc_html( $message['notice'] )
                            );
                        }
                    }
                }
            }
        }

    }

}