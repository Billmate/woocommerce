<?php

/**
 * BillmatePClass Storage interface
 *
 * This class provides an interface with which to save the PClasses easily.
 *
 * @package   BillmateAPI
 * @version   2.1.2
 * @since     2011-09-13
 */
abstract class PCStorage {

    /**
     * An array of BillmatePClasses.
     *
     * @ignore Do not show in PHPDoc.
     * @var array
     */
    protected $billmatepclasses;

    /**
     * Adds a PClass to the storage.
     *
     * @param BillmatePClass $pclass PClass object.
     * @throws BillmateException
     * @return void
     */
    public function addPClass($pclass) {
        if($pclass instanceof BillmatePClass) {
            if(!isset($this->billmatepclasses) || !is_array($this->billmatepclasses)) {
                $this->billmatepclasses = array();
            }
            if($pclass->getDescription() === null || $pclass->getType() === null) {
                //Something went wrong, do not save these!
                return;
            }
            if(!isset($this->billmatepclasses[$pclass->getEid()])) {
                $this->billmatepclasses[$pclass->getEid()] = array();
            }
            $this->billmatepclasses[$pclass->getEid()][$pclass->getId()] = $pclass;
        }
        else {
            throw new BillmateException('Error in ' . __METHOD__ . ': Supplied pclass object is not an BillmatePClass instance!');
        }
    }

    /**
     * Gets the PClass by ID.
     *
     * @param  int  $id       PClass ID.
     * @param  int  $eid      Merchant ID.
     * @param  int  $country  {@link BillmateCountry Country} constant.
     * @throws BillmateException
     * @return BillmatePClass
     */
    public function getPClass($id, $eid, $country) {
        if(!is_int($id)) {
            throw new Exception('Supplied ID is not an integer!');
        }

        if(!is_array($this->billmatepclasses)) {
            throw new Exception('No match for that eid!');
        }

        if(!isset($this->billmatepclasses[$eid]) || !is_array($this->billmatepclasses[$eid])) {
            throw new Exception('No match for that eid!');
        }

        if(!isset($this->billmatepclasses[$eid][$id]) || !$this->billmatepclasses[$eid][$id]->isValid()) {
            throw new Exception('No such pclass available!');
        }

        if($this->billmatepclasses[$eid][$id]->getCountry() !== $country) {
            throw new Exception('You cannot use this pclass with set country!');
        }

        return $this->billmatepclasses[$eid][$id];
    }

    /**
     * Returns an array of BillmatePClasses, keyed with pclass ID.
     * If type is specified, only that type will be returned.
     *
     * <b>Types available</b>:<br>
     * {@link BillmatePClass::ACCOUNT}<br>
     * {@link BillmatePClass::CAMPAIGN}<br>
     * {@link BillmatePClass::SPECIAL}<br>
     * {@link BillmatePClass::DELAY}<br>
     * {@link BillmatePClass::MOBILE}<br>
     *
     * @param  int   $eid     Merchant ID.
     * @param  int   $country {@link BillmateCountry Country} constant.
     * @param  int   $type    PClass type identifier.
     * @throws BillmateException
     * @return array An array of {@link BillmatePClass PClasses}.
     */
    public function getPClasses($eid, $country, $type = null) {
        if(!is_int($country)) {
            throw new Exception('You need to specify a country!');
        }

        $tmp = false;
        if(is_array($this->billmatepclasses)) {
            $tmp = array();
            foreach($this->billmatepclasses as $eid => $billmatepclasses) {
                $tmp[$eid] = array();
                foreach($billmatepclasses as $pclass) {
                    if(!$pclass->isValid()) {
                        continue; //Pclass invalid, skip it.
                    }
                    if($pclass->getEid() === $eid && $pclass->getCountry() === $country) {
                        if($pclass->getType() === $type || $type === null) {
                            $tmp[$eid][$pclass->getId()] = $pclass;
                        }
                    }
                }
            }
        }

        return $tmp;
    }

    /**
     * Loads the PClasses and calls {@link self::addPClass()} to store them in runtime.
     * URI can be location to a file, or a db prefixed table.
     *
     * @param  string $uri  URI to stored PClasses.
     * @throws BillmateException|Exception
     * @return void
     */
    abstract public function load($uri);

    /**
     * Takes the internal PClass array and stores it.
     * URI can be location to a file, or a db prefixed table.
     *
     * @param  string  $uri  URI to stored PClasses.
     * @throws BillmateException|Exception
     * @return void
     */
    abstract public function save($uri);

    /**
     * Removes the internally stored billmatepclasses.
     *
     * @param  string  $uri  URI to stored PClasses.
     * @throws BillmateException|Exception
     * @return void
     */
    abstract public function clear($uri);
}
