
# Dump of table categorias
# ------------------------------------------------------------

DROP TABLE IF EXISTS `categorias`;

CREATE TABLE `categorias` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) DEFAULT NULL,
  `icono` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;

INSERT INTO `categorias` (`id`, `nombre`, `icono`)
VALUES
	(1,'Caños Recibidos','CR'),
	(2,'Caños Realizados','CRE'),
	(3,'Jugador Mierda','JM'),
	(4,'Goleador','GOL');

/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table convocatoria
# ------------------------------------------------------------

DROP TABLE IF EXISTS `convocatoria`;

CREATE TABLE `convocatoria` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `fecha` datetime DEFAULT NULL,
  `sede` int(11) DEFAULT NULL,
  `convocados` int(11) DEFAULT NULL,
  `completo` tinyint(4) NOT NULL,
  `ganador` tinyint(1) DEFAULT NULL,
  `empate` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table convocatoria_jugadores
# ------------------------------------------------------------

DROP TABLE IF EXISTS `convocatoria_jugadores`;

CREATE TABLE `convocatoria_jugadores` (
  `id_convocatoria` int(11) NOT NULL,
  `id_jugador` int(11) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mensaje` text,
  `equipo` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id_convocatoria`,`id_jugador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table jugadores
# ------------------------------------------------------------

DROP TABLE IF EXISTS `jugadores`;

CREATE TABLE `jugadores` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(250) NOT NULL DEFAULT '',
  `email` varchar(320) NOT NULL DEFAULT '',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pj` int(11) DEFAULT NULL,
  `pg` int(11) DEFAULT NULL,
  `pe` int(11) DEFAULT NULL,
  `score` decimal(11,2) DEFAULT NULL,
  `concepto` decimal(11,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `jugadores` WRITE;
/*!40000 ALTER TABLE `jugadores` DISABLE KEYS */;

INSERT INTO `jugadores` (`id`, `nombre`, `email`, `ts`, `pj`, `pg`, `pe`, `score`, `concepto`)
VALUES
	(45,'Chapi','anastassiadesf@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(46,'Dipa','armyofhammer@hotmail.com','2013-03-13 18:45:48',2,1,0,1.05,0.00),
	(48,'Toti','bathan@gmail.com','2013-03-13 18:45:48',5,4,0,2.28,2.00),
	(49,'Berny','bernardo_montana@hotmail.com','2013-03-13 18:45:48',2,0,0,0.30,1.00),
	(50,'Diego','diegodd@gmail.com','2013-03-13 18:45:48',4,0,0,0.00,0.00),
	(51,'Nico','digiacomo.nico@gmail.com','2013-03-13 18:45:48',1,0,0,0.00,0.00),
	(52,'Wini','elcuis@gmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(53,'Rata','ezequielmateu@yahoo.com.ar','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(54,'Fer','fstonehenge@gmail.com','2013-03-13 18:45:48',4,2,0,1.05,0.00),
	(55,'German','germanakash@gmail.com','2013-03-13 18:45:48',1,1,0,2.10,0.00),
	(56,'Gori','gori07@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(57,'odrog lE','granchimi@yahoo.com','2013-03-13 18:45:48',3,1,0,0.70,0.00),
	(58,'Bolsa','guardiandark@hotmail.com','2013-03-13 18:45:48',5,4,0,1.68,0.00),
	(59,'Hernan','hanskait@gmail.com','2013-03-13 18:45:48',4,3,0,1.58,0.00),
	(60,'Humo','ignacioshanahan@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(61,'Javi','japisepto@hotmail.com','2013-03-13 18:45:48',1,1,0,2.10,0.00),
	(62,'Maicena','javier_salina@hotmail.com','2013-03-13 18:45:48',4,3,0,1.58,0.00),
	(63,'Joaco','Joaco_bacrc1994@hotmail.com','2013-03-13 18:45:48',2,0,0,0.00,0.00),
	(65,'Juanma','juanmaleufu@yahoo.com.ar','2013-03-13 18:45:48',3,2,0,1.40,0.00),
	(66,'Chicho','lalzitza25@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(67,'Chapas','mabalo@hotmail.com','2013-03-13 18:45:48',1,0,0,0.00,0.00),
	(68,'Mantis','mantismathews@gmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(69,'Spiderman','marcosmramos@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(70,'Mariano','mariano.guidobono@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(71,'Vikingo','martinbaleztena@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(72,'Bombita','mateudaniel@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(73,'Maese','maximilianogvazquez@gmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(74,'Monti','montesdeoca-hd@live.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(76,'Pol','pabloferrante@yahoo.com.ar','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(77,'Pedro','pedrofried@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(78,'Bocha','pedrogilmore@gmail.com','2013-03-13 18:45:48',1,1,0,2.10,0.00),
	(79,'Peter','petersepto@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(80,'Querol','querolezequiel@gmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(82,'Frydel','sebastianfrydel@hotmail.com','2013-03-13 18:45:48',4,0,0,0.00,0.00),
	(83,'Apolo','sgatto@hotmail.com','2013-03-13 18:45:48',1,0,0,0.00,0.00),
	(86,'Cesar','zhetapch@hotmail.com','2013-03-13 18:45:48',0,0,0,0.00,0.00),
	(87,'Luchito','','2013-03-14 15:28:45',1,1,NULL,2.10,0.00),
	(88,'Pablinksy','','2013-03-28 10:05:19',1,1,NULL,2.10,0.00),
	(89,'El Tio','','2013-03-28 10:09:05',0,0,NULL,0.00,0.00);

/*!40000 ALTER TABLE `jugadores` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table jugadores_categorias
# ------------------------------------------------------------

DROP TABLE IF EXISTS `jugadores_categorias`;

CREATE TABLE `jugadores_categorias` (
  `id_jugador` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `valor` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_jugador`,`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


