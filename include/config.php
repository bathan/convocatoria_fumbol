<?php

date_default_timezone_set('America/Buenos_Aires');

require_once dirname(__FILE__) . "/config.local.php";

$defaultValues = array(
    '_DB_NAME' => 'fumbol',
 	'_DB_USER' => 'root',
    '_DB_PASS' => 'pajarito',
    '_DB_CONN_ERROR_RETRIES'=>2,
    '_MAX_PLAYERS'=>10,
//    '_SELECCION_EMAIL'=>'bathan@gmail.com',
  //  '_SELECCION_EMAIL'=>'hanskait@gmail.com',
    '_SELECCION_EMAIL'=>'seleccion@fumbol.com.ar',
	'_DIAS_CONVOCATORIA' => '2', /* el dia 1 es lunes, martes 2 blablabla*/
);



foreach ($defaultValues as $name => $val) {
    if (!defined($name))
        define($name, $val);
}

//class paths
define("_DBCONNECTION_PATH", _APP_PATH . "/include/data/DBConnection.php");
define("_BASE_DATA_ACCESS_PATH", _APP_PATH . "/include/data/BaseDataAccess.php");
define("_FUMBOL_DATA_ACCESS_PATH", _APP_PATH . "/include/data/FumbolDataAccess.php");
define("_CONVOCATORIA_PATH",_APP_PATH . "/include/class/convocatoria.php");


//-- Convocatoria @ Fumbol = Vd@$9o@sBV3t

define("_DIAS_SEMANA", "Domingo,Lunes,Martes,Miercoles,Jueves,Viernes,Sábado");
