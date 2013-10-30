<?php
require_once dirname(__FILE__) . '/include/config.php';
require_once _CONVOCATORIA_PATH;

$q = strtolower($_GET["q"]);
if (!$q) return;

$c = new convocatoria();

foreach($c->getJugadoresByName($q) as $rs) {
    $cname = $rs['nombre'];
	echo trim($rs['nombre'])."|".$rs['id']."\n";
}
?>