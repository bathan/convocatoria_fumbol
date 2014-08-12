<?php
require_once dirname(__FILE__) . '/include/config.php';
require_once _CONVOCATORIA_PATH;


$x = array("1"=>array("nombre"=>"25 de Mayo","horario"=>"22:00"),"2"=>array("nombre"=>"BANADE","horario"=>"23:00"));


$dias_semana = explode(',',_DIAS_SEMANA);
$errors = null;
$c = new convocatoria();

$convocados = array();
$fecha_str = $dias_semana[date("w",strtotime($c->getFecha()))]." ".date("d",strtotime($c->getFecha()))." (".date("H:i",strtotime($c->getFecha()))." hs)";
$nombre_jugador = '';
$showForm = true;

if($_POST){
	if (trim($_POST["nombre_jugador"]) != ""){

        $showForm = false;
	    $nombre_jugador = trim($_POST["nombre_jugador"]);
	    $mensaje = trim($_POST["mensaje"]);

	    //-- Verificar que haya cupo en la convocatoria
	    try {
            if(!$c->estaCompleto()) {
                $jugador = $c->getJugadoresByName($nombre_jugador,true);

                if(is_null($jugador)) {
                    $errors['nombre_jugador'] = "Y este?? Quien te conoce??";
                }else{
                    //-- Verificar si no esta ya anotado
                    if(!$c->jugadorEnConvocatoria($jugador["id"])) {
                        $c->addJugador($jugador["id"],$mensaje);
                    }else{
                        $errors['nombre_jugador'] = $jugador["nombre"]." ya te convocaste ... ";
                    }

                }
            }

	    }catch(Exception $ex) {
		    $errors = $ex->getMessage();
	    }
    }else {
		$errors['nombre_jugador'] = "Algo falta...";
    }
    $showForm = count($errors) > 0;
}else{
    $showForm = $c->getStatus() == convocatoria::STATUS_HAY_FUMBOL_ABIERTA_CONVOCATORIA;
}

$redBackground = ($c->getStatus() == convocatoria::STATUS_HAY_FUMBOL_CERRADA_CONVOCATORIA) || ($c->getStatus() == convocatoria::STATUS_CERRADA);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Convocatoria Fumbolistica</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <link rel="stylesheet" type="text/css" href="bootstrap-1.4.0.min.css"/>
    <link rel="stylesheet" type="text/css" href="jquery.autocomplete.css" />
    <script type="text/javascript" src="jquery.js"></script>
    <script type='text/javascript' src='jquery.autocomplete.js'></script>
    <script type="text/javascript">
        $().ready(function() {
            $("#nombre_jugador").autocomplete("get_players.php", {
                width: 260,
                matchContains: true,
                //mustMatch: true,
                //minChars: 0,
                //multiple: true,
                //highlight: false,
                //multipleSeparator: ",",
                selectFirst: false
            });
        });
    </script>
    <style>
        { margin: 0; padding: 0; }

        <?php
        if($redBackground) {
            ?>
                html {
                    background: url('<?=_APP_URL."/img/bck.jpg"; ?>');
                }
            <?
        }else{
            ?>
                html {
                    background: url('<?=_APP_URL."/img/ghana_back.jpg"; ?>');
                    -webkit-background-size: cover;
                    -moz-background-size: cover;
                    -o-background-size: cover;
                    background-size: cover;
                }
            <?
        }
        ?>

        .Cronica{position: absolute;left: 50%;top: 50%;z-index: 100;height: 400px;margin-top: -250px;width: 600px;margin-left: -300px; }
        a:link
        {color:#e9322d;
        }

        a:hover
        {
            color:#3d773d;
        }
    </style>

</head>

<body style="padding-top: 10px;padding-left:10px;padding-right:10px;">
<?php
    $status = $c->getStatus();

    switch($status) {
        case convocatoria::STATUS_CERRADA:{
            ShowPlacasRojas($c);
            break;
        }
        case convocatoria::STATUS_HAY_FUMBOL_CERRADA_CONVOCATORIA:{
            //-- Status Nuevo
            ShowPlacasRojas($c,true);
            break;
        }
        case convocatoria::STATUS_HAY_FUMBOL_ABIERTA_CONVOCATORIA:{
            if($showForm) {
                $convocados = $c->getConvocados();
                ShowConvocatoriaForm($errors,$convocados,$nombre_jugador,$c);
            }
            ShowEquipos($c,!$showForm);

            break;
        }
        case convocatoria::STATUS_CUPO_COMPLETO: {
            $sede_str = $c->getSede_string();
            ShowCupoCompleto($fecha_str,$sede_str);
            ShowEquipos($c);
            break;
        }
    }
?>
</body>
</html>

<?php
//-- Funciones con Formularios HTML

function ShowConvocatoriaForm($errors,$convocados,$nombre_jugador,$c) {
    ?>
        <div class="container">
            <section id="form">
                <form id="convocatoria" method="post">
                        <div class="clearfix" align="center"><img src="<?=_APP_URL?>/img/<?php echo $c->getLogo();?>" ></div>
                    <fieldset>
                        <legend>Ingresa a la convocatoria para el <?php echo $c->getFecha_string();?> en el <?php echo $c->getSede_string();?>,<br> todav&iacute;a quedan <?php echo _MAX_PLAYERS - count($convocados);?> vacantes!</legend>
                        <div class="clearfix<?= isset($errors['nombre_jugador']) ? ' error' : '' ?>">
                            <label for="nombre_jugador">Nombre</label>
                            <div class="input">
                                <input type="text" class="large<?= isset($errors['nombre_jugador']) ? ' error' : '' ?>" id="nombre_jugador" name="nombre_jugador" value="<?=$nombre_jugador?>" />
                                <?= isset($errors['nombre_jugador']) ? '<span class="help-inline">'.$errors['nombre_jugador'].'</span>' : '<span class="help-inline"><strong><font color="#ffffff">Comenzá a tipear y aparecerá tu nombre</font></strong></span>' ?>
                            </div>

                        </div>
                        <div class="clearfix">
                            <label for="mensaje">Mensaje</label>
                            <div class="input">
                                <textarea class="xxlarge" id="mensaje" name="mensaje"></textarea>
                            </div>
                        </div>
                        <div class="clearfix error">
                            <font color="red">
                                <?php
                                //echo $errors;
                                ?>
                            </font>
                        </div>
                        <div class="actions" align="center">
                            <input type="submit" class="btn primary" value="Enviar Convocatoria" />
                            <button type="reset" class="btn">Borrar todo porque soy un Nabo</button>
                        </div>
                    </fieldset>
                </form>
            </section>
        </div>
    <?
}

function ShowPlacasRojas(convocatoria $c,$lta=false) {
    ?>
        <div class="Cronica">
            <?php
            if($lta) {
                echo " <h2 style='color:#FFF;text-align: center'>Volv&eacute; m&aacute;s tarde, aun no se abri&oacute; la convocatoria! LA TENES ADENTRO !!!</h2>";
            }
            ?>
            <img src="<?=_APP_URL?>/img/<?=$c->diasHastaProxima()?>_dias.jpg">
        </div>
    <?
}

function ShowCupoCompleto($fecha_str,$sede_str) {
    ?>
        <div class="clearfix">
            <div class="topbar-inner">
                <div class="container">
                    <h3><a href="#">Convocatoria al Fumbol del dia <?php echo $fecha_str; ?> en <?php echo $sede_str;?> COMPLETA!!!</a></h3>
                </div>
            </div>
        </div>

    <?
}

function ShowEquipos(convocatoria $c,$showLogo=true){
    ?>
    <div class="clearfix" align="center" style="color:#FFFFFF;">
        <?php echo $c->getEquiposHTML($showLogo); ?>
    </div>
    <?
}
?>
