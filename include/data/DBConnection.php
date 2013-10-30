<?php

require_once dirname(__FILE__) . '/../config.php';
require_once _FUMBOL_DATA_ACCESS_PATH;


/**
 * Class to connect to the Dabases supported by Photofeed base code
 * Author: Doctor Blecker (Before was Arthur Brown)
 */
class DBConnection {

    private static $connections = array();

    public static function getDataAccess() {
        return self::getAccess('FumbolDataAccess', array());
    }


    private static function getAccess($class, $parameters=array()) {
        $key = $class . json_encode($parameters); 
        if (isset(self::$connections[$key])){
            return self::$connections[$key];
        }
        $reflection = new ReflectionClass($class);
        $res = $reflection->newInstanceArgs($parameters);
        return self::$connections[$key] = $res;
    }

    private static function closeSingle($connection) {
        if(get_class($connection)!='BufferDataAccess'){ //we dont want to close the conn to the buffer
            if ($connection->isInTransaction() && _APP_DEBUG)
                throw  new Exception('Trying to close a connection while in a transaction');
            if ($connection)
                $connection->close();
        }
    }

	
    public static function closeAll() {
       foreach(self::$connections as $conn) 
      	self::closeSingle($conn);
    }

}

class DBConnectionException extends Exception {

  private $connection;

  public function __construct($connection, $message = null, $code = 0, Exception $previous = null) {
    $this->connection = $connection;
    parent::__construct($message, $code, $previous);
  }

  public function getConnection() {
    return $this->connection;
  }

}

