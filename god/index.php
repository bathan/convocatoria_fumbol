<?php
require_once dirname(__FILE__) . '/../include/config.php';
require_once _CONVOCATORIA_PATH;

$c = new convocatoria();

$jugadores_convocatoria_actual = array();
$convocatorias_incompletas = $c->getIncompleteConvocatorias();
if($c->getStatus() != convocatoria::STATUS_CERRADA) {
    $jugadores_convocatoria_actual = $c->getConvocados();
}
$categorias = $c->getCategorias();



if(isset($_REQUEST["act"])) {
    switch ($_REQUEST["act"]) {
        case godActions::SACAR_JUGADOR: {
        $c->removeJugador($_REQUEST["id"]);
        break;
        }
        case godActions::MARCAR_GANADOR: {
        $c->marcarGanador($_REQUEST["e"],$_REQUEST["id"]);
        break;
        }
        case godActions::SACAR_JUGADOR_Y_PENALIZAR: {
        $c->removeJugador($_REQUEST["id"],true);
        break;
        }
        case godActions::AGREGAR_JUGADOR: {
        $c->agregarJugador($_REQUEST["nombre_jugador"]);
        break;
        }
        case godActions::MARCAR_EMPATE: {
        $c->marcarEmpate($_REQUEST["id"]);
        break;
        }
        case godActions::MARCAR_CATEGORIAS: {
        $tmp = array();
        foreach($categorias as $cat) {
            if(isset($_POST["chk_cat_".$cat["id"]])){
                $tmp[$cat["id"]] = $_POST["chk_cat_".$cat["id"]];
            }
        }
        $c->marcarCategoriasJugadores($tmp);

        if(isset($_REQUEST["chk_jdp"])) {
            $c->marcarEstrellita($_REQUEST["chk_jdp"]);
        }

        break;
        }
    }

    header("location: "._APP_URL."/god");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Convocatoria Fumbolistica</title>
    <style>
        .icon {
            height:20px;
            width:20px;
            background-image:url('<?=_APP_URL?>/img/icons.png'); /*your location of the image may differ*/
        }

        .llave {
            background-position:-262px 44px;
        }
        .sacarPenalty {
            background-position:-262px -25px;
        }
    </style>

</head>

<body style="padding-top: 0px;padding-left:5px;padding-right:5px;">

<script >
    function sacar(id,jugador) {
        if(confirm("Sacas a "+jugador+"?")) {
            location.href = '<?=_APP_URL?>/god/?act=<?=godActions::SACAR_JUGADOR?>&id='+id;
        }
    }

    function sacarPenalty(id,jugador) {
        if(confirm("Sacas a "+jugador+" con penalidad?")) {
            location.href = '<?=_APP_URL?>/god/?act=<?=godActions::SACAR_JUGADOR?>&id='+id;
        }
    }

</script>

<?
$i = 1;
if(count($jugadores_convocatoria_actual)>0) {
    echo "<h2>Convocatoria Actual</h2>";
      echo '<table cellpadding="2" cellspacing="2" border="0" width="180">';
    foreach($jugadores_convocatoria_actual as $jugador) {
        echo '<tr>
                <td width="80%">'.$jugador["nombre"].'</td>
                <td width="10%" align="center"><a href="#" onClick="javascript:sacar('.$jugador["id"].',\''.$jugador["nombre"].'\');"><div class="icon llave"></div></a></td>
                <td width="10%" align="center"><a href="#" onClick="javascript:sacarPenalty('.$jugador["id"].',\''.$jugador["nombre"].'\');"><div class="icon sacarPenalty"></div></a></td>
        </tr>';
    }
    echo "</table>";
}

$i = 0;
$j = 0;

if(count($convocatorias_incompletas)>0) {
    echo "<h2>Marcar Resultado de la Convocatoria</h2>";
    echo "<ul>";

    foreach($convocatorias_incompletas as $incompleta) {
        echo "<li>".$incompleta["fecha"].' ---> <a href="'._APP_URL.'/god/?act='.godActions::MARCAR_GANADOR.'&id='.$incompleta["id"].'&e=1">Equipo 1</a>';
        echo '| <a href="'._APP_URL.'/god/?act='.godActions::MARCAR_GANADOR.'&id='.$incompleta["id"].'&e=2">Equipo 2</a> ';
        echo '| <a href="'._APP_URL.'/god/?act='.godActions::MARCAR_EMPATE.'&id='.$incompleta["id"].'&e=2">Empate</a>';
        $i++;


        $jugadores_convocatoria = $c->getConvocados($incompleta["id"]);

        //-- Tabla para marcar las categorias
        echo '<form method="POST">';

        echo '<table>
                <tr>
                    <td>Fumbolista</td>
                    <td>JDP</td>';

        foreach($categorias as $cat) {
            echo '<td>'.$cat["icono"].'</td>';
        }

        echo '
                </tr>';
        foreach($jugadores_convocatoria as $jugador) {
            echo '<tr>
                    <td>('.$jugador["equipo"].") - ".$jugador["nombre"].'</td>
                    <td><input type="checkbox" name="chk_jdp[]" value="'.$jugador["id"].'" /></td>';
                    foreach($categorias as $cat) {
                        echo '<td><input type="checkbox" name="chk_cat_'.$cat["id"].'[]" id="chk_cat_'.$cat["id"].'[]" value="'.$jugador["id"].'" /></td>';
                    }

                    echo '</tr>';

        }
        echo '</table>';
        echo '<input type="submit" name="btnSubmit" value="Go" />';
        echo '<input type="hidden" name="act" value="'.godActions::MARCAR_CATEGORIAS.'" /></form>';

        echo "</li>";


    }

    echo "</ul>";

}

?>

<form method="POST">
    <h4>Agregar Fumbolista</h4>
    Nombre:<input type="text" size="30" name="nombre_jugador" /><input type="submit" value="Agregar"/>
    <input type="hidden" name="act" value="<?=godActions::AGREGAR_JUGADOR?>" />
</form>


</body>
</html>



<?php

class godActions {
    const SACAR_JUGADOR = 1;
    const MARCAR_GANADOR = 2;
    const SACAR_JUGADOR_Y_PENALIZAR =4;
    const AGREGAR_JUGADOR = 5;
    const MARCAR_EMPATE = 6;
    const MARCAR_CATEGORIAS = 7;
}
?>