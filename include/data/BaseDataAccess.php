<?php

require_once dirname(__FILE__) . '/../config.php';
require_once _DBCONNECTION_PATH;


abstract class BaseDataAccess {

    private static $logUserStreams=null;
    private $link = null;
    private $throwErrors = true;
    private $inTransaction=false;

    public final function getLinkIdentifier() {
        if (!$this->link)
                $this->initialize ();
        return $this->link;
    }
    
    public final function isInTransaction(){
        return $this->inTransaction;
    }

    public final function getThrowErrors() {
        return $this->throwErrors;
    }

    public final function setThrowErrors($throwErrors) {
        $this->throwErrors = $throwErrors;
    }

    public function getLogErrors() {
        return true;
    }


    // -- Constructor
    public function __construct() {
        $this->initialize();
    }

    private function initialize() {
        $this->link = $this->connect();
    }

    protected abstract function connect();

    /**
     * Executes a query in the database. Compatible with any kind of query.
     * Then returns a value according to the type of query executed
     * @param string $query SQL to execute
     * @param int $num_rows For selects, returns the returned rows, otherwise the affected rows
     * @param bool $return Enables or disables the return value. Disabling it may improve performance
     * @return mixed For selects, returns the result identifier, for inserts the last_insert_id. Otherwise true if success, false if fails
     */
    public final function execute($query, &$num_rows=-1, $return=true) {
        $retries=_DB_CONN_ERROR_RETRIES;
        $error=null;
        $errorno=-1;

        //Do not display errors or warnings, we are handling errors ourselves inside the current function
        while($retries>=0){
            //Check if we are not connected to the DB, then connect inmediately
            if ($this->link == null){
                $this->initialize();
            }

            $result=false;
            if($this->link){
                $result = mysql_query($query, $this->link);
            }

            if ($result === false) { //there was an error on the query, or we are not connected to the db
                if($this->link){
                    //if we are connected, get the error from mysql
                    $errorno = mysql_errno($this->link);
                    $error = mysql_error($this->link);
                }

                if($this->link==null || !mysql_ping($this->link)){
                    //If we got here it means we are not connected to mysql, therefore lets retry
                    $this->link=null;
                    $retries--;
                    usleep(100 * 1000); //sleep 100ms before retrying
                    continue;
                }
            }
            
            //if we got to this point, means we should not retry
            break;
        }
        restore_error_handler(); //restore whatever the error handler was before

        //OK we exited the retry-while, we now need to see if there was any error or if we actually
        //have a result

        if($this->link==null){ //are we connected to the DB???
            throw new Exception("La conn se fue a la mierda man.");
        }elseif($result===false){//we dont have a valid result, there was an error on the SQL
            $errorfull="($errorno) $error\n\nQuery was: $query";

            $num_rows = -1;
            if ($this->throwErrors){
                throw new Exception($errorfull);
            }
        }else{//No errors
            if ($result === true) { //Query was an insert,update or delete
                $num_rows = mysql_affected_rows($this->link);
                if ($return) {
                    //Important! we need to actually query for the last_insert_id() to prevent
                    //the mysql driver to cast it as integer. That way really long integers dont break
                    $rid=mysql_query('SELECT last_insert_id() lid',$this->link);
                    $aid=mysql_fetch_assoc($rid);
                    $id = $aid['lid'];

                    if ($id)
                        $result = $id; //if the last insert id exists, then return it :)
                }
            } else{ //Query was a select
                $num_rows = mysql_num_rows($result);
            }
            return $result;
        }

    }

    /**
     * Executes a query and fetch the associative array with the result
     * @param string $query sql
     * @param int $num_rows rows affected
     * @return The rows returned or null
     */
    public final function executeAndFetch($query, &$num_rows=-1) {
        $fetch = array();
        $result = $this->execute($query, $num_rows);
        if ($result && $num_rows > 0) {
            while ($r = mysql_fetch_assoc($result)) {
                $fetch[] = $r;
            }
            mysql_free_result($result);
        }

        return $fetch;
    }

    /**
     * Execute a query and returns the first row of the result
     * @param string $query sql
     * @param int $num_rows rows affected
     * @return The row returned or null
     */
    public final function executeAndFetchSingle($query, &$num_rows=-1) {
        $res = $this->executeAndFetch($query, $num_rows);
        if (count($res) > 0)
            return $res[0];
        else
            return null;
    }
    
    
    /**
     * Generates an insert. The columns on the insert statement will be the ones on the first row
     * @param type $table
     * @param type $rows
     * @param type $num_rows 
     */
    public final function insert($table,$rows,$update=false,&$num_rows=-1){
        if(count($rows)>0){
            $columns=array_keys($rows[0]);
            $sql='INSERT INTO '.$table.'('.implode(',',$columns).')  VALUES ';
            $values=array();
            foreach($rows as $row){
                $rv=array();
                foreach($columns as $c){
                    $v=$row[$c];
                    if (is_null($v))
                        $v='$$NULL$$'; //Crappy token for null values
                    if(is_string($v))
                        $v=$this->escape($v);
                    $rv[]=$v;
                }
                $values[]='"'.implode('","',$rv).'"';
            }
            $sql.='('.implode('),(',$values).')';
            $sql=str_replace('"$$NULL$$"','null' , $sql);
            if($update){
                $sql.=' ON DUPLICATE KEY UPDATE ';
                $first=true;
                foreach($columns as $c){
                    if(!$first)
                        $sql.=',';
                    else
                        $first=false;
                    $sql.=$c.'=values('.$c.')';
                }
            }
        }
        //echo "\n\n\n\n$sql\n\n\n\n";
        $this->execute($sql,$num_rows);
    }

    /**
     * Begins a transaction
     */
    public final function begin() {
        $this->execute("BEGIN");
        $this->inTransaction=true;
    }

    /**
     * Commits a transaction
     */
    public final function commit() {
        $this->execute("COMMIT");
        $this->inTransaction=false;
    }

    /**
     * Gets the auto_increment_increment variable
     */
    public final function getAutoIncrementIncrement() {
        $sql = 'SELECT @@auto_increment_increment `inc`';
        $res = $this->executeAndFetchSingle($sql);
        return $res['inc'];
    }

    /**
     * Gets the auto_increment_offset variable
     */
    public final function getAutoIncrementOffset() {
        $sql = 'SELECT @@auto_increment_offset `off`';
        $res = $this->executeAndFetchSingle($sql);
        return $res['off'];
    }
    
    public final function getConnectionCount(){
        $sql='SHOW PROCESSLIST';
        $res=$this->executeAndFetch($sql);
        return count($res);
    }

    /**
     * Rollbacks a transaction
     */
    public final function rollback() {
        $this->execute("ROLLBACK");
        $this->inTransaction=false;
    }

    /**
     * Escapes a string or an array of strings
     * @param mixed $str Can be a string or an array of strings
     * @return mixed The escaped string or the array of escaped strings 
     */
    public function escape($str) {

            return mysql_real_escape_string($str);
       
    }

    public final function close() {
        if ($this->link)
            mysql_close($this->link);
        $this->link = null;
    }
}

