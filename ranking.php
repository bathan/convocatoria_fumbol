<?php
require_once dirname(__FILE__) . '/include/config.php';
require_once _CONVOCATORIA_PATH;

$c = new convocatoria();
$fumbolistas = $c->getAllJugadores();
$categorias  = $c->getCategorias();
$jugadores_categorias = array();

foreach($c->getJugadoresCategorias() as $r) {
    $jugadores_categorias[$r["id_jugador"]][$r["id_categoria"]] = $r["valor"];
}

$colspan = count($categorias) + 6;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ranking de Fumbolistas</title>
    <link rel="stylesheet" type="text/css" href="bootstrap-1.4.0.min.css"/>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
</head>

<body style="padding-top: 10px;padding-left:10px;padding-right:10px">
                <div class="topbar clearfix">
                    <div class="topbar-inner">
                        <div class="container">
                            <h3><a href="#">Raking de Fumbolistas!</a></h3>
                        </div>
                    </div>
                </div>
<div style="padding-top: 30px;">
<table width="80% border="0" cellspacing="2" cellpadding="2">
                <tr>
                    <td align="center">Fumbolista</td>
                    <td align="center">PJ</td>
                    <td align="center">PG</td>
                    <td align="center">PG/PJ</td>
                    <td align="center">Concepto</td>
                    <td align="center">Puntaje</td>
                    <?php
                        foreach($categorias as $c) {
                            ?>
                            <td  align="center"><?=$c["icono"]?></td>
                            <?
                        }
                    ?>
                </tr>
                <tr>
                    <td colspan="<?=$colspan?>">&nbsp;</td>
                </tr>
                <?php
                foreach($fumbolistas as $j) {
                    ?>
                    <tr>
                        <td align="center"><?=$j["nombre"]?></td>
                        <td align="center"><?=$j["pj"]?></td>
                        <td align="center"><?=$j["pg"]?></td>
                        <td align="center"><?=$j["PG-PJ"]?></td>
                        <td align="center"><?=$j["concepto"]?></td>
                        <td align="center"><?=round($j["score"],2)?></td>
                        <?php
                        foreach($categorias as $c) {
                            $valor = isset($jugadores_categorias[$j["id"]][$c["id"]]) ? $jugadores_categorias[$j["id"]][$c["id"]] : 0;
                            ?>
                            <td  align="center"><?=$valor?></td>
                            <?
                        }
                        ?>
                    </tr>
                    <?
                }
                ?>
</table>

    <br>

    <table >
        <tr>
            <td colspan="2">Leyendas</td>
        </tr>
        <?php
        foreach($categorias as $cat) {
            ?>
            <tr>
                <td><?=$cat["icono"]?></td>
                <td><?=utf8_encode($cat["nombre"])?></td>
            </tr>
            <?
        }
        ?>
    </table>
</div>
</body>
</html>
