<?php
require_once dirname(__FILE__) . '/../config.php';
require_once _DBCONNECTION_PATH;

class convocatoria {

    private $id;
    private $fecha;
    private $sede;
    private $convocados;
    private $completo;

	private $convocatoria_status;
	private $dias_convocatoria = array();
	private $sedes_convocatoria = array();
    private $abierta_convocatoria;

    const STATUS_CERRADA = 1;
    const STATUS_HAY_FUMBOL_CERRADA_CONVOCATORIA = 2;
    const STATUS_HAY_FUMBOL_ABIERTA_CONVOCATORIA = 3;
    const STATUS_CUPO_COMPLETO =4;

    public function estaCompleto() {
        return $this->completo;
    }

	public function getStatus() {
		return $this->convocatoria_status;
	}

    public function getFecha() {
        return $this->fecha;
    }

	public function getDias_convocatoria() {
		return $this->dias_convocatoria;
	}
    public function __construct() {

        $this->dias_convocatoria = json_decode(_DIAS_CONVOCATORIA,true);
        $this->convocatoria_status = self::STATUS_CERRADA;

        if (isset($this->dias_convocatoria[date('w')])) {

            $cd = $this->dias_convocatoria[date("w")];
            $sc = json_decode(_SEDES_CONVOCATORIA,true);

		    $this->sede = $cd["sede"];
            $this->fecha = date("Y-m-d ".$sc[$this->sede]["horario"]);

            $this->populateConvocatoria();

            $this->convocatoria_status = self::STATUS_HAY_FUMBOL_CERRADA_CONVOCATORIA;

            if(_HORARIO_INICIO_RANDOM) {
                //-- Truco para habilitar o no la convocatoria
                $hora  = floor((intval(date("j")) * intval(date("n")))%8);
                if ((intval(date("G"))>=$hora)) {
                    $this->convocatoria_status = self::STATUS_HAY_FUMBOL_ABIERTA_CONVOCATORIA;
                }
            }else{
                $this->convocatoria_status = self::STATUS_HAY_FUMBOL_ABIERTA_CONVOCATORIA;
            }
        }


        if($this->estaCompleto()) {
            $this->convocatoria_status = self::STATUS_CUPO_COMPLETO;
        }

    }

    private function populateConvocatoria() {

        $c = $this->getConvocatoria();

        if(is_null($c)) {
            $db = DBConnection::getDataAccess();
            //-- No Hay convocatoria para hoy. Crearla
            $q = "insert into convocatoria (fecha,convocados,sede) values ('".$this->fecha."',0,'".$this->sede."')";
            $db->execute($q);
        }
        $c = $this->getConvocatoria();
        $this->id = $c["id"];
        $this->convocados = $c["convocados"];
        $this->completo = $c["completo"] == 0 ? false : true;
    }

    private function getConvocatoria() {

        $db = DBConnection::getDataAccess();
        $q = "select * from convocatoria where fecha='".$this->fecha."'";
        $res = $db->executeAndFetchSingle($q);

        return $res;
    }

    public function addJugador($id_jugador,$mensaje) {
        if(!$this->completo) {

            $db = DBConnection::getDataAccess();

            //-- Agregar este jugador a la convocatoria
            $q = " insert into convocatoria_jugadores (id_jugador,ts,mensaje,id_convocatoria) VALUES ";
            $q .= "(".$id_jugador.",CURRENT_TIMESTAMP,'".$db->escape($mensaje)."',".$this->id.")";
            $db->execute($q);

            $q = "select count(*) as players from convocatoria_jugadores where id_convocatoria=".$this->id;
            $r = $db->executeAndFetchSingle($q);

            $this->convocados = $r["players"];
            $this->completo = $this->convocados >= _MAX_PLAYERS ? true : false;

            $q = " update convocatoria set convocados=".$this->convocados.",completo=".($this->completo ? 1 : 0)." where id=".$this->id;
            $db->execute($q);


            if($this->completo) {
                $this->asignarCapitan();

                //$this->generarEquipos();
                $this->convocatoria_status = self::STATUS_CUPO_COMPLETO;
                $this->generarEquiposInteligente();
            }

            $this->sendEmail();
        }
    }

    public function asignarCapitan() {

        $db = DBConnection::getDataAccess();

        //-- Por las dudas, resetear el capitan
        $q = "update convocatoria_jugadores set capitan=0 where id_convocatoria=".$this->id;
        $db->execute($q);

        //-- Traigo todos los jugadores de esta convocatoria
        $convocados = $this->getConvocados($this->id);
        $posibles_capitanes = [];

        foreach($convocados as $fumbolista) {
            if($fumbolista["capitan_flag"]==0) {
                $posibles_capitanes[]=$fumbolista["id"];
            }
        }
        shuffle($posibles_capitanes);
        $capitan_id = reset($posibles_capitanes);

        $q = "update convocatoria_jugadores set capitan=1 where id_convocatoria=".$this->id." and id_jugador=".$capitan_id;
        $db->execute($q);

        //-- Ahora actualizamos la flag para el capitan actual y todos los que ya fueron capitanes alguna vez
        $q = "update jugadores set capitan_flag=".(_MAX_FECHAS_CAPITANES * -1)." where id=".$capitan_id;
        $db->execute($q);

        $q = "update jugadores set capitan_flag = capitan_flag + 1 where capitan_flag < 0 and id !=".$capitan_id;
        $db->execute($q);


    }

    public function removeJugador($id_jugador,$penalty=false) {

        $db = DBConnection::getDataAccess();

        //-- Agregar este jugador a la convocatoria
        $q = " delete from convocatoria_jugadores where id_convocatoria=".$this->id." and id_jugador=".$id_jugador;
        $db->execute($q);

        $q = "select count(*) as players from convocatoria_jugadores where id_convocatoria=".$this->id;
        $r = $db->executeAndFetchSingle($q);

        $this->convocados = $r["players"];
        $this->completo = $this->convocados >= _MAX_PLAYERS ? true : false;

        $q = " update convocatoria set convocados=".$this->convocados.",completo=".($this->completo ? 1 : 0)." where id=".$this->id;
        $db->execute($q);

        $q = " update convocatoria_jugadores set equipo=NULL where id_convocatoria=".$this->id;
        $db->execute($q);

        if($penalty) {
            $q = " update jugadores set concepto = concepto - 1 where id=".$id_jugador;
            $db->execute($q);
        }

        $this->calcularScore();

    }
    public function marcarEstrellita($id_jugadores) {
        $db = DBConnection::getDataAccess();

        $q = " update jugadores set concepto = 0 where concepto IS NULL ";
        $db->execute($q);

        $q = " update jugadores set concepto = concepto + 1 where id in (".implode(',',$id_jugadores).")";
        $db->execute($q);

        $this->calcularScore();
    }

    public function marcarGanador($equipo,$id_convocatoria) {
        $db = DBConnection::getDataAccess();
        $q = "update convocatoria set ganador=".$equipo." where id=".$id_convocatoria;
        $db->execute($q);

        //-- Actualizar Partidos Jugados
        $q = "update jugadores set pj = pj + 1 where id in (select id_jugador from convocatoria_jugadores where id_convocatoria=".$id_convocatoria.")";
        $db->execute($q);

        //-- Actualizar Partidos Ganados
        $q = "update jugadores set pg = pg + 1 where id in (select id_jugador from convocatoria_jugadores where id_convocatoria=".$id_convocatoria." and equipo=".$equipo.")";
        $db->execute($q);

        $this->calcularScore();
    }

    public function marcarEmpate($id_convocatoria) {

        $db = DBConnection::getDataAccess();
        $q = "update convocatoria set empate=1 where id=".$id_convocatoria;
        $db->execute($q);

        //-- Actualizar Partidos Jugados
        $q = "update jugadores set pj = pj + 1 where id in (select id_jugador from convocatoria_jugadores where id_convocatoria=".$id_convocatoria.")";
        $db->execute($q);

        //-- Actualizar Partidos Empatados
        $q = "update jugadores set pe = pe + 1 where id in (select id_jugador from convocatoria_jugadores where id_convocatoria=".$id_convocatoria.")";
        $db->execute($q);

        $this->calcularScore();
    }

    private function calcularScore() {

        $db = DBConnection::getDataAccess();

        //-- Sanity Check
        $q="update jugadores set pj=0,pg=0,pe=0 where pj IS NULL and pg is NULL and pe is NULL";
        $db->execute($q);

        $q = "select * from jugadores ";
        $res = $db->executeAndFetch($q);

        foreach($res as $jugador) {

            try {
                if($jugador["pj"]>0) {
                    //-- Usando Puntos en vez de Partidos para tener en cuenta los empates
                    $puntos = (floatval($jugador["pg"]) * 3) + floatval($jugador["pe"]);
                    $score = floatval($puntos / floatval($jugador["pj"]) * 0.7);
                }else{
                    $score = 0;
                }
            }catch(Exception $e) {
                $score = 0;
            }
            try{
                $concepto = floatval(is_null($jugador["concepto"]) ? 0 : $jugador["concepto"]) * 0.3;
            }catch(Exception $e) {
                $concepto= 0;
            }

            $final = $score + $concepto;
            $q = "update jugadores set score=".$final." where id=".$jugador["id"];
            $db->execute($q);
        }


    }

    public function getConvocados($id=0) {

        $db = DBConnection::getDataAccess();
        $q = "select j.nombre,cj.mensaje,j.id,cj.equipo,j.score,cj.capitan,j.capitan_flag from jugadores j
                inner join convocatoria_jugadores cj on j.id = cj.id_jugador
                where cj.id_convocatoria=".($id>0 ? $id : $this->id)." order by cj.ts";
        $r = $db->executeAndFetch($q);
        return $r;
    }

    public function jugadorEnConvocatoria($id) {
        $db = DBConnection::getDataAccess();
        $q = "select * from convocatoria_jugadores where id_convocatoria=".$this->id." and id_jugador=".$id;
        $r = $db->executeAndFetch($q);

        return count($r) > 0;
    }

    public function getJugadoresByName($name,$strict=false) {

        $db = DBConnection::getDataAccess();
        $q = "select * from jugadores where ";
        if($strict) {
            $q .= "RTRIM(LTRIM(nombre))='".trim($name)."'";
            $r = $db->executeAndFetchSingle($q);
        }else{
            $q .= "nombre like '".strtolower($name)."%'";
            $q .= " and id not in (select id_jugador from convocatoria_jugadores where id_convocatoria=".$this->id.")";
            $r = $db->executeAndFetch($q);
        }

        return $r;
    }


    public function getJugadoresByEmail($email,$strict=false) {

        $db = DBConnection::getDataAccess();
        $q = "select * from jugadores where ";
        if($strict) {
            $q .= "email='".$email."'";
            $r = $db->executeAndFetchSingle($q);
        }else{
            $q .= "email like '%".strtolower($email)."%'";
            $r = $db->executeAndFetch($q);
        }

        return $r;
    }

    public function generarEquipos() {
        $db = DBConnection::getDataAccess();

        $convocados = $this->getConvocados();
        $raw = array();

        foreach($convocados as $c) {
                $raw[] = array("nombre"=>$c["nombre"],"id"=>$c["id"]);
        }

        shuffle($raw);
        $equipos = array_chunk($raw,(_MAX_PLAYERS/2));

        foreach($equipos as $equipo=>$jugadores) {
            $v = array();
            foreach($jugadores as $j) {
                $v[] = $j["id"];
            }
            $q = "update convocatoria_jugadores set equipo=".($equipo+1)." where id_convocatoria=".$this->id." and id_jugador in (".implode(',',$v).")";
            $db->execute($q);
        }
    }


    public function sendEmail() {

        $headers  = "From: Convocatoria Fumbol <convocatoria@fumbol.com.ar>\r\n";
        $headers .= "Reply-To: seleccion@fumbol.com.ar\r\n";
        $headers .= "Return-Path: convocatoria@fumbol.com.ar\r\n";
        $headers .= "X-Mailer: BathanEmailer\n";
        $headers .= 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        $body = $this->getEquiposHTML();
        $dias_semana = explode(',',_DIAS_SEMANA);

        mail(_SELECCION_EMAIL, "Fumbol de ".$dias_semana[date("w",strtotime($this->fecha))], $body, $headers);
    }

    public function getEquiposHTML($showLogo=true) {

        $convocados = $this->getConvocados();

        $body='';

	    if($showLogo) {
            $body = '<img src="'._APP_URL.'/img/'.$this->getLogo().'" >';
        }

        $body .= "<h3>Listado de Convocados ".($showLogo ? "para el ".$this->getFecha_string()." en el ".$this->getSede_string() : "")."</h3>";

        $i= 1;
        $equipos = array();

	    //$body .= "<ul>";\
        $il_capitano = '';
        foreach($convocados as $c) {

            $body .= "<strong>".$i.". ".$c["nombre"]."</strong>";
            if($c["mensaje"] != '') {
                $body .= " (<i>".$c["mensaje"]."</i>)";
            }
            $body .= "<br>";
            $i++;
            $equipos[$c["equipo"]][] = $c;

            if($c["capitan"]==1) {
                $il_capitano = $c["nombre"];
            }
        }

	    //$body .= "</ul>";

        if($this->completo) {
            $body .= "<h3>Equipos Automaticos</h3>";

            $body .= '<table width="50%" border="0" cellspacing="2" cellpadding="2">';
            $body .= '<tr>';
            $body .= '<td align="center">Equipo 1 <br><img src="http://www.fumbol.com.ar/convocatoria/img/remera1.jpg" ></td>';
            $body .= '<td align="center">Equipo 2 <br><img src="http://www.fumbol.com.ar/convocatoria/img/remera2.jpg" ></td>';
            $body .= '</tr>';

            $body .= '<tr>';
            $body .= '<td colspan="2">&nbsp;</td>';
            $body .= '</tr>';

            for ($i = 0; $i < (_MAX_PLAYERS/2); $i++) {
                $body .= '<tr>';
                $body .= '<td>'.$equipos[1][$i]["nombre"].'</td>';
                $body .= '<td>'.$equipos[2][$i]["nombre"].'</td>';
                $body .= '</tr>';
            }

            $body .= '</table>';

            $body .="<hr/>";
            $body .= '<h3> El Dios del Fumbol ha seleccionado a <font color="red">'.$il_capitano.'</font> como Capit&aacute;n del encuentro y deber&aacute; apersonarse una ofrenda</h3>';

        }

        $body .= '<br/><br/>';
        //$body .= '<div class="clearfix" align="center"><label>Para convocarte , ingresa en <a href="'._APP_URL.'">'._APP_URL.'</a></label></div><br/>';
        if($showLogo && !$this->completo) {
            $body .= '<h3>Para convocarte , <a href="'._APP_URL.'">hac&eacute; click aca</a></h3><br/>';
        }

        return $body;
    }

    public function getIncompleteConvocatorias() {

        $db = DBConnection::getDataAccess();

        $q = "select * from convocatoria where ganador is null and empate is null and completo=1";
        $r = $db->executeAndFetch($q);

        return $r;
    }

    public function agregarJugador($nombre) {

        $db = DBConnection::getDataAccess();

        $q = "insert into jugadores (nombre,pj,pg,score,concepto) values ('".$db->escape($nombre)."',0,0,0,0)";
        $db->execute($q);

        $this->calcularScore();
    }

    public function generarEquiposInteligente() {

        //-- Lista de los Convocados
        $convocados = $this->getConvocados();

        $players = array();
        foreach($convocados as $c) {
            $players[$c["nombre"]] = array("score"=>floatval($c["score"]),"id"=>$c["id"]);
        }

        //-- Get the Max Players and the Array of Player Names
        $max_players = count($players)/2;
        $p = array_keys($players);

        for($i=0;$i<=(count($p));$i++){
            $tmp = array();
            $t=0;

            //-- Loop and start placing players into a team. When the max is reached, start placing on the other team.
            foreach($p as $player) {
                //-- Store player on Team
                $tmp[$t][] = $player;
                if(count($tmp[$t])==$max_players) {
                    $t++;
                }
            }
            //-- Get the second player and move it to the bottom of the list.
            $second = $p[1];
            unset($p[1]);
            $p[] = $second;
            $p = array_values($p);

            //-- Loop thru teams and add the score of each player
            foreach($tmp as $key=>$eq) {
                $score = 0 ;
                foreach($eq as $jug) {
                    //-- Add Score for each player
                    $score +=  floatval($players[$jug]["score"]);
                }
                //-- Store the sum of scores of all players in team
                $tmp[$key]["score"] = $score;
            }
            //-- Store the Difference between team scores in this "team set"
            $tmp["diff"] = abs(round($tmp[0]["score"]-$tmp[1]["score"],2));
            $teams[] = $tmp;
        }

        $lowest_diff = 100;
        $final_team = null;


        foreach($teams as $k=>$team) {
            //echo " Comparando (".$k.") ".$team["diff"]."<=". $lowest_diff." <br> ";
            if($team["diff"]<= $lowest_diff) {
                //echo " ENTRO con ".$team["diff"]."<br>";
                $lowest_diff = floatval($team["diff"]);
                $final_team = $team;
            }
        }
        //echo "FINAL TEAM";
        //var_dump($final_team);

        $db = DBConnection::getDataAccess();

        $equipos[1] = $final_team[0];
        $equipos[2] = $final_team[1];

        foreach($equipos as $equipo=>$juga) {
            $v = array();
            foreach($juga as $j) {
                if(isset($players[$j]["id"])) {
                    $v[] = $players[$j]["id"];
                }
            }
            $q = "update convocatoria_jugadores set equipo=".$equipo." where id_convocatoria=".$this->id." and id_jugador in (".implode(',',$v).")";
            //var_dump($q);
            $db->execute($q);
        }
    }

    public function getConvocadosCategorias() {

        $db = DBConnection::getDataAccess();
        $q = " select * from jugadores_categorias jc where jc.id_jugador in (select id_jugador from convocatoria_jugadores where id_convocatoria=".$this->id.")";

        return $db->executeAndFetch($q);

    }

    public function getCategorias() {
        $db = DBConnection::getDataAccess();
        $q = " select * from categorias order by id";
        return $db->executeAndFetch($q);
    }

    public function getAllJugadores() {

        $db = DBConnection::getDataAccess();

        $q = "Select * from jugadores order by score DESC ";
        $res = $db->executeAndFetch($q);

        $fumbolistas = array();
        foreach($res as $r) {

            $tmp = $r;

            try {
                if(floatval($tmp["pj"])==0){
                    $tmp["PG-PJ"] = 0;
                }else{
                    $tmp["PG-PJ"] = round(floatval($tmp["pg"])/floatval($tmp["pj"]),2);
                }
            }catch(Exception $e) {
                $tmp["PG-PJ"] = 0 ;
            }

            $fumbolistas[]=$tmp;
        }

        return $fumbolistas;
    }

    public function getJugadoresCategorias() {
        $db = DBConnection::getDataAccess();
        $q = " select * from jugadores_categorias order by id_jugador ";
        return $db->executeAndFetch($q);
    }

    public function marcarCategoriasJugadores($data) {
        $db = DBConnection::getDataAccess();

        foreach($data as $id_categoria=>$d) {
            foreach($d as $id_jugador) {
                $q = "INSERT INTO jugadores_categorias (id_jugador,id_categoria,valor) values (".$id_jugador.",".$id_categoria.",1) ON DUPLICATE KEY UPDATE valor=valor+1";
                $db->execute($q);
            }
        }
    }

    public function diasHastaProxima() {

        for($i=1;$i<=7;$i++){
            $semana[$i] = isset($this->dias_convocatoria[$i]);
        }

        $resto_semana = array_slice($semana,intval(date("N"))-1,(8-(date("N"))),true);

        $x = 0;
        foreach($resto_semana as $rs) {
            if($rs!=1) {
                $x++;
            }else{
                break;
            }
        }

        return $x;
    }

	public function getSede(){
		return $this->sede;
	}

    public function getBackground() {
        $sedes_convocatoria = json_decode(_SEDES_CONVOCATORIA,true);
        return $sedes_convocatoria[$this->sede]["fondo"];
    }

    public function getLogo() {
        $sedes_convocatoria = json_decode(_SEDES_CONVOCATORIA,true);
        return $sedes_convocatoria[$this->sede]["logo"];
    }

	public function getSede_string(){
	    $sedes_convocatoria = json_decode(_SEDES_CONVOCATORIA,true);
		return $sedes_convocatoria[$this->sede]["nombre"];
	}

	public function getFecha_string(){
		$dias_semana = explode(',',_DIAS_SEMANA);
		return $dias_semana[date("w",strtotime($this->fecha))]." ".date('d (H:i)',strtotime($this->fecha));
	}

}
