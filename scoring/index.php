<?php
require_once dirname(__FILE__) . '/../include/config.php';
require_once _CONVOCATORIA_PATH;
?>

<!DOCTYPE html>
<html>
<head>
<title>Convocatoria Fumbolistica - Calificaciones </title>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<link rel="stylesheet" href="../jRating/jRating.jquery.css" type="text/css" />
</head>

<body>

<?php
//-- TODO , una suerte de login screen o algo para validar quien es el que está poniendo los puntos.

$id_jugador_logged = 48;

$c = new convocatoria();

$jugadores_convocatoria_actual = [];
$convocatorias_incompletas = $c->getIncompleteConvocatorias();
if($c->getStatus() != convocatoria::STATUS_CERRADA) {
    $jugadores_convocatoria_actual = $c->getConvocados();
    $categorias = $c->getCategorias();
}

$jugadores_para_calificar = [];
foreach($jugadores_convocatoria_actual as $fumbolista) {
    if($fumbolista["id"]!=$id_jugador_logged) {
        $jugadores_para_calificar[] = $fumbolista;
    }
}

$classes = [];
$categorias_width = round(70/count($categorias),0)."%";

?>
<table style="width: 80%" border="1">
    <tr>
        <td width="30%">Fumbolista</td>
        <?php
        $cols = 0 ;
        foreach($categorias as $c) {
            $cols++;
            echo '<td width="'.$categorias_width.'">'.$c["nombre"].'</td>';
        }
        ?>
    </tr>
    <tr>
        <td colspan="<?php echo ($cols+1);?>">&nbsp;</td>
    </tr>
    <?php
    foreach($jugadores_para_calificar as $fumbolista) {
        ?>
        <tr>
            <td width="30%"><?php echo $fumbolista["nombre"]; ?></td>
            <?php
            foreach($categorias as $c) {
                $class_name = 'fumbolista_'.$fumbolista["id"].'_'.$c["id"];
                $classes[] = $class_name;
                echo '<td width="'.$categorias_width.'"><div class="'.$class_name.'" data-average="10" data-id="'.$class_name.'" log-id="'.$id_jugador_logged.'"></div></td>';
            }
            ?>
        </tr>
        <?php
    }
    ?>

</table>

<div class="datasSent">
    Datas sent to the server :
    <p></p>
</div>
<div class="serverResponse">
    Server response :
    <p></p>
</div>
<?php

?>

<script type="text/javascript" src="../jRating/jquery.js"></script>
<script type="text/javascript" src="../jRating/jRating.jquery.js"></script>
<script type="text/javascript">
    $(document).ready(function(){

        <?php
        foreach($classes as $cname) {
            ?>
            $('.<?php echo $cname; ?>').jRating({
                type:'small',
                length : 5,
                decimalLength : 1
            });
            <?php
            }
        ?>
    });
</script>
</body>
</html>
