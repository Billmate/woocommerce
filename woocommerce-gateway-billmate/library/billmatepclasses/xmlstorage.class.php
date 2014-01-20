<?php

/**
 * Include the {@link PCStorage} interface.
 */
require_once('storage.intf.php');

/**
 * XML storage class for BillmatePClass
 *
 * This class is an XML implementation of the PCStorage interface.
 *
 * @package   BillmateAPI
 * @version   2.1.2
 * @since     2011-09-13
 */
class XMLStorage extends PCStorage {

    /**
     * The internal XML document.
     *
     * @ignore Do not show in PHPDoc.
     * @var DOMDocument
     */
    protected $dom;

    /**
     * XML version for the DOM document.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $version = '1.0';

    /**
     * Encoding for the DOM document.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $encoding = 'ISO-8859-1';

    /**
     * Class constructor
     * @ignore Does nothing.
     */
    public function __construct() {
        $this->dom = new DOMDocument($this->version, $this->encoding);
        $this->dom->formatOutput = true;
        $this->dom->preserveWhiteSpace = false;
    }

    /**
     * Checks if the file is writeable, readable or if the directory is.
     *
     * @param  string $xmlFile URI to XML file.
     * @throws Exception
     * @return void
     */
    protected function checkURI($xmlFile) {
        //If file doesn't exist, check the directory.
        if(!file_exists($xmlFile)) {
            $xmlFile = dirname($xmlFile);
        }

        if(!is_writable($xmlFile)) {
            throw new Exception("Unable to write to $xmlFile!");
        }

        if(!is_readable($xmlFile)) {
            throw new Exception("Unable to read $xmlFile!");
        }
    }

    /**
     * Class destructor
     * @ignore Does nothing.
     */
    public function __destruct() {

    }

    /**
     * @see PCStorage::load()
     */
    public function load($uri) {
        try {
            $this->checkURI($uri);
            if(!file_exists($uri)) {
                //Do not fail, if file doesn't exist.
                return;
            }
            if(!@$this->dom->load($uri)) {
                throw new Exception("Failed to parse $uri!");
            }

            $xpath = new DOMXpath($this->dom);
            foreach($xpath->query('/billmate/estore') as $estore) {
                $eid = $estore->getAttribute('id');

                foreach($xpath->query('pclass', $estore) as $node) {
                    $pclass = new BillmatePClass();
                    $pclass->setId($node->getAttribute('pid'));
                    $pclass->setType($node->getAttribute('type'));
                    $pclass->setEid($eid);
                    $pclass->setDescription($xpath->query('description', $node)->item(0)->textContent);
                    $pclass->setMonths($xpath->query('months', $node)->item(0)->textContent);
                    $pclass->setStartFee($xpath->query('startfee', $node)->item(0)->textContent);
                    $pclass->setInvoiceFee($xpath->query('invoicefee', $node)->item(0)->textContent);
                    $pclass->setInterestRate($xpath->query('interestrate', $node)->item(0)->textContent);
                    $pclass->setMinAmount($xpath->query('minamount', $node)->item(0)->textContent);
                    $pclass->setCountry($xpath->query('country', $node)->item(0)->textContent);
                    $pclass->setExpire($xpath->query('expire', $node)->item(0)->textContent);

                    $this->addPClass($pclass);
                }
            }
        }
        catch(Exception $e) {
            throw new BillmateException("Error in " . __METHOD__ . ": " .$e->getMessage());
        }
    }

    /**
     * Creates DOMElement for all fields for specified PClass.
     *
     * @ignore Do not show in PHPDoc.
     * @param  BillmatePClass $pclass
     * @return array Array of DOMElements.
     */
    protected function createFields($pclass) {
        $fields = array();

        //This is to prevent HTMLEntities to be converted to the real character.
        $fields[] = $this->dom->createElement('description');
        end($fields)->appendChild($this->dom->createTextNode($pclass->getDescription()));
        $fields[] = $this->dom->createElement('months', $pclass->getMonths());
        $fields[] = $this->dom->createElement('startfee', $pclass->getStartFee());
        $fields[] = $this->dom->createElement('invoicefee', $pclass->getInvoiceFee());
        $fields[] = $this->dom->createElement('interestrate', $pclass->getInterestRate());
        $fields[] = $this->dom->createElement('minamount', $pclass->getMinAmount());
        $fields[] = $this->dom->createElement('country', $pclass->getCountry());
        $fields[] = $this->dom->createElement('expire', $pclass->getExpire());

        return $fields;
    }

    /**
     * @see PCStorage::save()
     */
    public function save($uri) {
        try {
            $this->checkURI($uri);

            //Reset DOMDocument.
            if(!$this->dom->loadXML("<?xml version='$this->version' encoding='$this->encoding'?"."><billmate/>")) {
                throw new Exception('Failed to load initial XML.');
            }

            ksort($this->billmatepclasses, SORT_NUMERIC);
            $xpath = new DOMXpath($this->dom);
            foreach($this->billmatepclasses as $eid => $billmatepclasses) {
                $estore = $xpath->query('/billmate/estore[@id="'.$eid.'"]');
                if($estore === false || $estore->length === 0) {
                    //No estore with matching eid, create it.
                    $estore = $this->dom->createElement('estore');
                    $estore->setAttribute('id', $eid);
                    $this->dom->documentElement->appendChild($estore);
                }
                else {
                    $estore = $estore->item(0);
                }

                foreach($billmatepclasses as $pclass) {
                    if($eid != $pclass->getEid()) {
                        continue; //This should never occur.
                    }

                    $pnode = $this->dom->createElement('pclass');

                    foreach($this->createFields($pclass) as $field) {
                        $pnode->appendChild($field);
                    }
                    $pnode->setAttribute('pid', $pclass->getId());
                    $pnode->setAttribute('type', $pclass->getType());

                    $estore->appendChild($pnode);
                }
            }

            if(!$this->dom->save($uri)) {
                throw new Exception('Failed to save XML document!');
            }
        }
        catch(Exception $e) {
            throw new BillmateException("Error in " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * This uses unlink (delete) to clear the billmatepclasses!
     *
     * @see PCStorage::clear()
     */
    public function clear($uri) {
        try {
            $this->checkURI($uri);
            unset($this->billmatepclasses);
            if(file_exists($uri)) {
                unlink($uri);
            }
        }
        catch(Exception $e) {
            throw new BillmateException("Error in " . __METHOD__ . ": " . $e->getMessage());
        }
    }
}
