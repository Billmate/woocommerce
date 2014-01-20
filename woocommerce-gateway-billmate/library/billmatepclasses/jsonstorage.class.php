<?php
/**
 * Include the {@link PCStorage} interface.
 */
require_once('storage.intf.php');

/**
 * JSON storage class for BillmatePClass
 *
 * This class is an JSON implementation of the PCStorage interface.
 *
 * @package   BillmateAPI
 * @version   2.1.2
 */
class JSONStorage extends PCStorage {

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
     * Checks if the file is writeable, readable or if the directory is.
     *
     * @ignore Do not show in PHPDoc.
     * @param  string $jsonFile
     * @throws error
     * @return void
     */
    protected function checkURI($jsonFile) {
        //If file doesn't exist, check the directory.
        if(!file_exists($jsonFile)) {
            $jsonFile = dirname($jsonFile);
        }

        if(!is_writable($jsonFile)) {
            throw new Exception("Unable to write to $jsonFile!");
        }

        if(!is_readable($jsonFile)) {
            throw new Exception("Unable to read $jsonFile!");
        }
    }

    /**
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
            throw new BillmateException('Error in ' . __METHOD__ . ': ' . $e->getMessage());
        }
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
            $arr = json_decode(file_get_contents($uri), true);
            if(count($arr) > 0)  {
                foreach($arr as $billmatepclasses) {
                    if(count($billmatepclasses) > 0) {
                        foreach($billmatepclasses as $pclass) {
                            $this->addPClass(new BillmatePClass($pclass));
                        }
                    }
                }
            }
        }
        catch(Exception $e) {
            throw new BillmateException('Error in ' . __METHOD__ . ': ' . $e->getMessage());
        }
    }

    /**
     * @see PCStorage::save()
     */
    public function save($uri) {
        try {
            $this->checkURI($uri);
            $output = array();
            foreach($this->billmatepclasses as $eid => $billmatepclasses) {
                foreach($billmatepclasses as $pclass) {
                    if(!isset($output[$eid])) {
                        $output[$eid] = array();
                    }
                    $output[$eid][] = $pclass->toArray();
                }
            }
            if(count($this->billmatepclasses) > 0) {
                file_put_contents($uri, json_encode($output));
            }
            else {
                file_put_contents($uri, "");
            }

        }
        catch(Exception $e) {
            throw new BillmateException('Error in ' . __METHOD__ . ': ' . $e->getMessage());
        }
    }
}
