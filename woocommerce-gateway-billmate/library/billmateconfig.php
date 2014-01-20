<?php
/**
 * @package   BillmateAPI
 * @version   2.1.2
 * @since     2011-09-13
 */
class BillmateConfig implements ArrayAccess {

    /**
     * An array containing all the options for this config.
     *
     * @ignore Do not show in PHPDoc.
     * @var array
     */
    protected $options;

    /**
     * If set to true, saves the config.
     *
     * @var bool
     */
    public static $store = true;

    /**
     * URI to the config file.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $file;

    /**
     * Class constructor
     *
     * Loads specified file, or default file, if {@link BillmateConfig::$store} is set to true.
     *
     * @param  string  $file  URI to config file, e.g. ./config.json
     */
    public function __construct($file = null) {
        $this->options = array();
        if($file) {
            $this->file = $file;
            if(is_readable($this->file)) {
                $this->options = json_decode(file_get_contents($this->file), true);
            }
        }
    }

    /**
     * Clears the config.
     *
     * @return void
     */
    public function clear() {
        $this->options = array();
    }

    /**
     * Class destructor
     *
     * Saves specified file, or default file, if {@link BillmateConfig::$store} is set to true.
     */
    public function __destruct() {
        if(self::$store && $this->file) {
            if((!file_exists($this->file) && is_writable(dirname($this->file))) || is_writable($this->file)) {
                file_put_contents($this->file, json_encode($this->options));
            }
        }
    }

    /**
     * Returns true whether the field exists.
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        if(isset($this->options[$offset])) {
            return true;
        }
        return false;
    }

    /**
     * Used to get the value of a field.
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        if(!$this->offsetExists($offset)) {
            return null;
        }
        return $this->options[$offset];
    }

    /**
     * Used to set a value to a field.
     *
     * @param  mixed $field
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) {
        $this->options[$offset] = $value;
    }

    /**
     * Removes the specified field.
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset) {
        unset($this->options[$offset]);
    }
}
