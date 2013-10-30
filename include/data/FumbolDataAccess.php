<?php
require_once dirname(__FILE__) . '/../config.php';
require_once _DBCONNECTION_PATH;
require_once _BASE_DATA_ACCESS_PATH;

class FumbolDataAccess extends BaseDataAccess {

    public function __construct() {
        parent::__construct();
    }

    protected function connect() {
        $link = mysql_connect(_DB_HOST, _DB_USER, _DB_PASS, true);
        mysql_select_db(_DB_NAME, $link);
        return $link;
    }
}
