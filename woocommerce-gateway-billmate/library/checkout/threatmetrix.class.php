<?php
/**
 * ThreatMetrix is a fraud prevention and device identification software.
 *
 * @ignore    Do not show in PHPDoc.
 * @package   BillmateAPI
 * @version   2.1.2
 * @since     2011-09-13
 */
class ThreatMetrix extends CheckoutHTML {

    /**
     * The ID used in conjunction with the Billmate API.
     *
     * @var int
     */
    const ID = 'dev_id_1';

    /**
     * ThreatMetrix organizational ID.
     *
     * @var string
     */
    protected $orgID = 'qicrzsu4';

    /**
     * Session ID for the client.
     *
     * @var string
     */
    protected $sessionID;

    /**
     * Hostname used to access ThreatMetrix.
     *
     * @var string
     */
    protected $host = 'h.online-metrix.net';

    /**
     * Protocol used to access ThreatMetrix.
     *
     * @var string
     */
    protected $proto = 'https';

    /**
     * Class constructor
     */
    public function __construct() {
    }

    /**
     * Class destructor
     */
    public function __destruct() {

    }

    /**
     * @see CheckoutHTML::toHTML()
     * @param  Billmate   $billmate  The API instance
     * @param  int      $eid
     * @return void
     */
    public function init($billmate, $eid) {
        if(!is_int($eid)) {
            throw new BillmateException('Error in ' . __METHOD__ . ': eid is not an integer!');
        }
        if(isset($_SESSION)) {
            if(!isset($_SESSION[self::ID]) || strlen($_SESSION[self::ID]) < 40) {
                $_SESSION[self::ID] = parent::getSessionID($eid);
                $this->sessionID = $_SESSION[self::ID];
            }
            else {
                $this->sessionID = $_SESSION[self::ID];
            }
        }
        else {
            $this->sessionID = parent::getSessionID($eid);
        }

        $billmate->setSessionID(self::ID, $this->sessionID);
    }

    /**
     * @see CheckoutHTML::clear()
     * @return void
     */
    public function clear() {
        if(isset($_SESSION) && isset($_SESSION[self::ID])) {
            $_SESSION[self::ID] = null;
            unset($_SESSION[self::ID]);
        }
    }

    /**
     * @see CheckoutHTML::toHTML()
     */
    public function toHTML() {
        return "<p style=\"display: none; background:url($this->proto://$this->host/fp/clear.png?org_id=$this->orgID&session_id=$this->sessionID&m=1)\"></p>
        <script src=\"$this->proto://$this->host/fp/check.js?org_id=$this->orgID&session_id=$this->sessionID\" type=\"text/javascript\"></script>
        <img src=\"$this->proto://$this->host/fp/clear.png?org_id=$this->orgID&session_id=$this->sessionID&m=2\" alt=\"\" >
        <object type=\"application/x-shockwave-flash\" style=\"display: none\" data=\"$this->proto://$this->host/fp/fp.swf?org_id=$this->orgID&session_id=$this->sessionID\" width=\"1\" height=\"1\" id=\"obj_id\">
            <param name=\"movie\" value=\"$this->proto://$this->host/fp/fp.swf?org_id=$this->orgID&session_id=$this->sessionID\" />
            <div></div>
        </object>";
    }
}
