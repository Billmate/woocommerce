<?php
/**
 * @package BillmateAPI
 */

/**
 * Include the {@link PCStorage} interface.
 */
require_once('storage.intf.php');

/**
 * MySQL storage class for BillmatePClass
 *
 * This class is an MySQL implementation of the PCStorage interface.<br>
 * Config field pcURI needs to match format: user:passwd@addr:port/dbName.dbTable<br>
 * Port can be omitted.<br>
 *
 * <b>Acceptable characters</b>:<br>
 * Username: [A-Za-z0-9_]<br>
 * Password: [A-Za-z0-9_]<br>
 * Address:  [A-Za-z0-9_.]<br>
 * Port:     [0-9]<br>
 * DB name:  [A-Za-z0-9_]<br>
 * DB table: [A-Za-z0-9_]<br>
 *
 * To allow for more special characters, and to avoid having<br>
 * a regular expression that is too hard to understand, you can<br>
 * use an associative array:<br>
 * <code>
 * array(
 *   "user" => "myuser",
 *   "passwd" => "mypass",
 *   "dsn" => "localhost",
 *   "db" => "mydatabase",
 *   "table" => "mytable"
 * );
 * </code>
 *
 * @package     BillmateAPI
 * @deprecated  Deprecated since 2.1, better to use PDO based SQLStorage
 * @see         SQLStorage
 * @version     2.1.2
 * @since       2011-09-13
 * @copyright   Copyright (c) 2010 Billmate AB (http://billmate.com)
 */
class MySQLStorage extends PCStorage {

    /**
     * Database name.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $dbName;

    /**
     * Database table.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $dbTable;

    /**
     * Database address.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $addr;

    /**
     * Database username.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $user;

    /**
     * Database password.
     *
     * @ignore Do not show in PHPDoc.
     * @var string
     */
    protected $passwd;

    /**
     * MySQL DB link resource.
     *
     * @ignore Do not show in PHPDoc.
     * @var resource
     */
    protected $link;

    /**
     * Class constructor
     * @ignore Does nothing.
     */
    public function __construct() {
    }

    /**
     * Class destructor
     * @ignore Does nothing.
     */
    public function __destruct() {
    }

    /**
     * Connects to the DB and checks if DB and table exists.
     *
     * @ignore Do not show in PHPDoc.
     * @throws Exception
     * @return void
     */
    protected function connect() {
        $this->link = mysql_connect($this->addr, $this->user, $this->passwd);
        if($this->link === false) {
            throw new Exception('Failed to connect to database! ('.mysql_error().')');
        }

        if(!mysql_query('CREATE DATABASE IF NOT EXISTS `'.$this->dbName.'`', $this->link)) {
            throw new Exception('Database not existing, failed to create! ('.mysql_error().')');
        }

        $create = mysql_query(
                "CREATE TABLE IF NOT EXISTS `".$this->dbName."`.`".$this->dbTable."` (
                    `eid` int(10) unsigned NOT NULL,
                    `id` int(10) unsigned NOT NULL,
                    `type` tinyint(4) NOT NULL,
                    `description` varchar(255) NOT NULL,
                    `months` int(11) NOT NULL,
                    `interestrate` decimal(11,2) NOT NULL,
                    `invoicefee` decimal(11,2) NOT NULL,
                    `startfee` decimal(11,2) NOT NULL,
                    `minamount` decimal(11,2) NOT NULL,
                    `country` int(11) NOT NULL,
                    `expire` int(11) NOT NULL,
                    KEY `id` (`id`)
                )", $this->link
        );

        if(!$create) {
            throw new Exception('Table not existing, failed to create! ('.mysql_error().')');
        }
    }

    /**
     * Splits the URI in format: user:passwd@addr/dbName.dbTable<br>
     *
     * To allow for more special characters, and to avoid having<br>
     * a regular expression that is too hard to understand, you can<br>
     * use an associative array:<br>
     * <code>
     * array(
     *   "user" => "myuser",
     *   "passwd" => "mypass",
     *   "dsn" => "localhost",
     *   "db" => "mydatabase",
     *   "table" => "mytable"
     * );
     * </code>
     *
     * @ignore Do not show in PHPDoc.
     * @param  string|array $uri Specified URI to database and table.
     * @throws Exception
     * @return void
     */
    protected function splitURI($uri) {
        if(is_array($uri)) {
            $this->user = $uri['user'];
            $this->passwd = $uri['passwd'];
            $this->addr = $uri['dsn'];
            $this->dbName = $uri['db'];
            $this->dbTable = $uri['table'];
        }
        else if(preg_match('/^([\w-]+):([\w-]+)@([\w\.-]+|[\w\.-]+:[\d]+)\/([\w-]+).([\w-]+)$/', $uri, $arr) === 1) {
            /*
              [0] => user:passwd@addr/dbName.dbTable
              [1] => user
              [2] => passwd
              [3] => addr
              [4] => dbName
              [5] => dbTable
            */
            if(count($arr) != 6) {
                throw new Exception('URI is invalid! Missing field or invalid characters used!');
            }

            $this->user = $arr[1];
            $this->passwd = $arr[2];
            $this->addr = $arr[3];
            $this->dbName = $arr[4];
            $this->dbTable = $arr[5];
        }
        else {
            throw new Exception('URI to MySQL is not valid! ( user:passwd@addr/dbName.dbTable )');
        }
    }

    /**
     * @see PCStorage::load()
     */
    public function load($uri) {
        try {
            $this->splitURI($uri);
            $this->connect();
            if(($result = mysql_query('SELECT * FROM `'.$this->dbName.'`.`'.$this->dbTable.'`', $this->link)) === false) {
                throw new Exception('SELECT query failed! ('.mysql_error().')');
            }
            while($row = mysql_fetch_assoc($result)) {
                $this->addPClass(new BillmatePClass($row));
            }
        }
        catch(Exception $e) {
            throw new BillmateException("Error in " . __METHOD__ . ": " .$e->getMessage());
        }
    }

    /**
     * @see PCStorage::save()
     */
    public function save($uri) {
        try {
            $this->splitURI($uri);

            $this->connect();
            if(!is_array($this->billmatepclasses) || count($this->billmatepclasses) == 0) {
                return;
            }

            foreach($this->billmatepclasses as $billmatepclasses) {
                foreach($billmatepclasses as $pclass) {
                    //Remove the pclass if it exists.
                    mysql_query("DELETE FROM `".$this->dbName.'`.`'.$this->dbTable."` WHERE `id` = '".$pclass->getId()."' AND `eid` = '".$pclass->getEid()."'");

                    //Insert it again.
                    $result = mysql_query(
                        "INSERT INTO `".$this->dbName.'`.`'.$this->dbTable."`
                           (`eid`, `id`, `type`, `description`, `months`, `interestrate`, `invoicefee`, `startfee`, `minamount`, `country`, `expire`)
                         VALUES
                           ('".$pclass->getEid()."',
                            '".$pclass->getId()."',
                            '".$pclass->getType()."',
                            '".$pclass->getDescription()."',
                            '".$pclass->getMonths()."',
                            '".$pclass->getInterestRate()."',
                            '".$pclass->getInvoiceFee()."',
                            '".$pclass->getStartFee()."',
                            '".$pclass->getMinAmount()."',
                            '".$pclass->getCountry()."',
                            '".$pclass->getExpire()."')", $this->link
                    );
                    if($result === false) {
                        throw new Exception('INSERT INTO query failed! ('.mysql_error().')');
                    }
                }
            }
        }
        catch(Exception $e) {
            throw new BillmateException("Error in " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * @see PCStorage::clear()
     */
    public function clear($uri) {
        try {
            $this->splitURI($uri);
            unset($this->billmatepclasses);
            $this->connect();

            mysql_query("DELETE FROM `".$this->dbName."`.`".$this->dbTable."`", $this->link);
        }
        catch(Exception $e) {
            throw new BillmateException("Error in " . __METHOD__ . ": " . $e->getMessage());
        }
    }
}
