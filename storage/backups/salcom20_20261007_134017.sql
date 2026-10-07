-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: salcom20
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `abono_proveedor_documentos`
--

DROP TABLE IF EXISTS `abono_proveedor_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abono_proveedor_documentos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `abono_id` bigint(20) unsigned NOT NULL,
  `factura_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_doc` date DEFAULT NULL,
  `serie_doc` varchar(40) DEFAULT NULL,
  `folio_doc` varchar(80) DEFAULT NULL,
  `concepto_doc` varchar(255) DEFAULT 'Compra',
  `referencia` varchar(255) DEFAULT NULL,
  `importe_pago` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sistema_origen` varchar(40) NOT NULL DEFAULT 'SALCOM',
  `detalle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalle`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `abono_proveedor_documentos_factura_id_foreign` (`factura_id`),
  KEY `abono_proveedor_documentos_abono_id_factura_id_index` (`abono_id`,`factura_id`),
  CONSTRAINT `abono_proveedor_documentos_abono_id_foreign` FOREIGN KEY (`abono_id`) REFERENCES `abonos_proveedor` (`id`) ON DELETE CASCADE,
  CONSTRAINT `abono_proveedor_documentos_factura_id_foreign` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abono_proveedor_documentos`
--

LOCK TABLES `abono_proveedor_documentos` WRITE;
/*!40000 ALTER TABLE `abono_proveedor_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `abono_proveedor_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `abonos_proveedor`
--

DROP TABLE IF EXISTS `abonos_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abonos_proveedor` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poliza_key` varchar(40) NOT NULL,
  `serie` varchar(20) NOT NULL,
  `folio` int(10) unsigned NOT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `agente` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `proveedor_id` bigint(20) unsigned DEFAULT NULL,
  `codigo_proveedor` varchar(255) NOT NULL,
  `nombre_proveedor` varchar(255) DEFAULT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'MXN',
  `tipo_cambio` decimal(14,6) NOT NULL DEFAULT 1.000000,
  `cuenta_bancaria` varchar(255) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'borrador',
  `monto_pago` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abonos_proveedor_serie_folio_poliza_key_unique` (`serie`,`folio`,`poliza_key`),
  KEY `abonos_proveedor_proveedor_id_foreign` (`proveedor_id`),
  KEY `abonos_proveedor_estatus_fecha_index` (`estatus`,`fecha`),
  KEY `abonos_proveedor_poliza_key_index` (`poliza_key`),
  KEY `abonos_proveedor_serie_index` (`serie`),
  KEY `abonos_proveedor_folio_index` (`folio`),
  KEY `abonos_proveedor_codigo_proveedor_index` (`codigo_proveedor`),
  KEY `abonos_proveedor_agente_index` (`agente`),
  CONSTRAINT `abonos_proveedor_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abonos_proveedor`
--

LOCK TABLES `abonos_proveedor` WRITE;
/*!40000 ALTER TABLE `abonos_proveedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `abonos_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `rol` varchar(255) NOT NULL DEFAULT 'gerente',
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_usuario_unique` (`usuario`),
  KEY `admin_users_correo_index` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'Aneso Cominu','aneso.cominu@salcom.mx',NULL,'admin','aneso.cominu','$2y$12$8flXz5fqi02SmUJnELRGw.9E3jZiYd9YeQN6kTXiLjD05yY4VYaJa',1,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL);
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aduanas`
--

DROP TABLE IF EXISTS `aduanas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aduanas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(2) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `entidad` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aduanas_clave_unique` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aduanas`
--

LOCK TABLES `aduanas` WRITE;
/*!40000 ALTER TABLE `aduanas` DISABLE KEYS */;
INSERT INTO `aduanas` VALUES (1,'01','Acapulco','Guerrero','ACAPULCO, ACAPULCO DE JUAREZ, GUERRERO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(2,'02','Agua Prieta','Sonora','AGUA PRIETA, AGUA PRIETA, SONORA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(3,'05','Subteniente Lopez','Quintana Roo','SUBTENIENTE LOPEZ, SUBTENIENTE LOPEZ, QUINTANA ROO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(4,'06','Ciudad Del Carmen','Campeche','CIUDAD DEL CARMEN, CIUDAD DEL CARMEN, CAMPECHE.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(5,'07','Ciudad Juarez','Chihuahua','CIUDAD JUAREZ, CIUDAD JUAREZ, CHIHUAHUA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(6,'08','Coatzacoalcos','Veracruz','COATZACOALCOS, COATZACOALCOS, VERACRUZ.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(7,'11','Ensenada','Baja California','ENSENADA, ENSENADA, BAJA CALIFORNIA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(8,'12','Guaymas','Sonora','GUAYMAS, GUAYMAS, SONORA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(9,'14','La Paz','Baja California Sur','LA PAZ, LA PAZ, BAJA CALIFORNIA SUR.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(10,'16','Manzanillo','Colima','MANZANILLO, MANZANILLO, COLIMA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(11,'17','Matamoros','Tamaulipas','MATAMOROS, MATAMOROS, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(12,'18','Mazatlan','Sinaloa','MAZATLAN, MAZATLAN, SINALOA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(13,'19','Mexicali','Baja California','MEXICALI, MEXICALI, BAJA CALIFORNIA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(14,'20','Mexico','Distrito Federal','MEXICO, DISTRITO FEDERAL.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(15,'22','Naco','Sonora','NACO, NACO, SONORA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(16,'23','Nogales','Sonora','NOGALES, NOGALES, SONORA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(17,'24','Nuevo Laredo','Tamaulipas','NUEVO LAREDO, NUEVO LAREDO, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(18,'25','Ojinaga','Chihuahua','OJINAGA, OJINAGA, CHIHUAHUA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(19,'26','Puerto Palomas','Chihuahua','PUERTO PALOMAS, PUERTO PALOMAS, CHIHUAHUA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(20,'27','Piedras Negras','Coahuila','PIEDRAS NEGRAS, PIEDRAS NEGRAS, COAHUILA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(21,'28','Progreso','Yucatan','PROGRESO, PROGRESO, YUCATAN.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(22,'30','Ciudad Reynosa','Tamaulipas','CIUDAD REYNOSA, CIUDAD REYNOSA, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(23,'31','Salina Cruz','Oaxaca','SALINA CRUZ, SALINA CRUZ, OAXACA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(24,'33','San Luis Rio Colorado','Sonora','SAN LUIS RIO COLORADO, SAN LUIS RIO COLORADO, SONORA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(25,'34','Ciudad Miguel Aleman','Tamaulipas','CIUDAD MIGUEL ALEMAN, CIUDAD MIGUEL ALEMAN, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(26,'37','Ciudad Hidalgo','Chiapas','CIUDAD HIDALGO, CIUDAD HIDALGO, CHIAPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(27,'38','Tampico','Tamaulipas','TAMPICO, TAMPICO, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(28,'39','Tecate','Baja California','TECATE, TECATE, BAJA CALIFORNIA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(29,'40','Tijuana','Baja California','TIJUANA, TIJUANA, BAJA CALIFORNIA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(30,'42','Tuxpan','Veracruz','TUXPAN, TUXPAN DE RODRIGUEZ CANO, VERACRUZ.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(31,'43','Veracruz','Veracruz','VERACRUZ, VERACRUZ, VERACRUZ.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(32,'44','Ciudad Acuña','Coahuila','CIUDAD ACUÑA, CIUDAD ACUÑA, COAHUILA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(33,'46','Torreon','Coahuila','TORREON, TORREON, COAHUILA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(34,'47','Aeropuerto Internacional De La Ciudad De Mexico',NULL,'AEROPUERTO INTERNACIONAL DE LA CIUDAD DE MEXICO,',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(35,'48','Guadalajara','Jalisco','GUADALAJARA, TLACOMULCO DE ZUÑIGA, JALISCO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(36,'50','Sonoyta','Sonora','SONOYTA, SONOYTA, SONORA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(37,'51','Lazaro Cardenas','Michoacan','LAZARO CARDENAS, LAZARO CARDENAS, MICHOACAN.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(38,'52','Monterrey','Nuevo Leon','MONTERREY, GENERAL MARIANO ESCOBEDO, NUEVO LEON.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(39,'53','Cancun','Quintana Roo','CANCUN, CANCUN, QUINTANA ROO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(40,'64','Querétaro','Querétaro','QUERÉTARO, EL MARQUÉS Y COLON, QUERÉTARO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(41,'65','Toluca','Estado De Mexico','TOLUCA, TOLUCA, ESTADO DE MEXICO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(42,'67','Chihuahua','Chihuahua','CHIHUAHUA, CHIHUAHUA, CHIHUAHUA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(43,'73','Aguascalientes','Aguascalientes','AGUASCALIENTES, AGUASCALIENTES, AGUASCALIENTES.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(44,'75','Puebla','Puebla','PUEBLA, HEROICA PUEBLA DE ZARAGOZA, PUEBLA.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(45,'80','Colombia','Nuevo Leon','COLOMBIA, COLOMBIA, NUEVO LEON.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(46,'81','Altamira','Tamaulipas','ALTAMIRA, ALTAMIRA, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(47,'82','Ciudad Camargo','Tamaulipas','CIUDAD CAMARGO, CIUDAD CAMARGO, TAMAULIPAS.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(48,'83','Dos Bocas','Tabasco','DOS BOCAS, PARAISO, TABASCO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(49,'84','Guanajuato','Guanajuato','GUANAJUATO, SILAO, GUANAJUATO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10'),(50,'85','Aeropuerto Internacional Felipe Ángeles','Estado De México','AEROPUERTO INTERNACIONAL FELIPE ÁNGELES, SANTA LUCÍA, ZUMPANGO, ESTADO DE MÉXICO.',1,'2026-10-07 19:24:10','2026-10-07 19:24:10');
/*!40000 ALTER TABLE `aduanas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agente_aduanal_aduana`
--

DROP TABLE IF EXISTS `agente_aduanal_aduana`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agente_aduanal_aduana` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agente_aduanal_id` bigint(20) unsigned NOT NULL,
  `aduana_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agente_aduanal_aduana_agente_aduanal_id_aduana_id_unique` (`agente_aduanal_id`,`aduana_id`),
  KEY `agente_aduanal_aduana_aduana_id_foreign` (`aduana_id`),
  CONSTRAINT `agente_aduanal_aduana_aduana_id_foreign` FOREIGN KEY (`aduana_id`) REFERENCES `aduanas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agente_aduanal_aduana_agente_aduanal_id_foreign` FOREIGN KEY (`agente_aduanal_id`) REFERENCES `agentes_aduanales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agente_aduanal_aduana`
--

LOCK TABLES `agente_aduanal_aduana` WRITE;
/*!40000 ALTER TABLE `agente_aduanal_aduana` DISABLE KEYS */;
/*!40000 ALTER TABLE `agente_aduanal_aduana` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agentes_aduanales`
--

DROP TABLE IF EXISTS `agentes_aduanales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentes_aduanales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `numero_patente` varchar(4) NOT NULL,
  `agencia` varchar(255) DEFAULT NULL,
  `tipo_operacion` varchar(20) NOT NULL,
  `contacto_nombre` varchar(255) DEFAULT NULL,
  `contacto_correo` varchar(255) DEFAULT NULL,
  `contacto_telefono` varchar(20) DEFAULT NULL,
  `contacto_celular` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `estado_verificacion` varchar(30) NOT NULL DEFAULT 'no_verificado',
  `fecha_ultima_verificacion` date DEFAULT NULL,
  `fuente_verificacion` varchar(30) DEFAULT NULL,
  `fuente_detalle` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agentes_aduanales_rfc_unique` (`rfc`),
  UNIQUE KEY `agentes_aduanales_numero_patente_unique` (`numero_patente`),
  KEY `agentes_aduanales_activo_index` (`activo`),
  KEY `agentes_aduanales_estado_verificacion_index` (`estado_verificacion`),
  KEY `agentes_aduanales_tipo_operacion_index` (`tipo_operacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agentes_aduanales`
--

LOCK TABLES `agentes_aduanales` WRITE;
/*!40000 ALTER TABLE `agentes_aduanales` DISABLE KEYS */;
/*!40000 ALTER TABLE `agentes_aduanales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alerta_configuracion`
--

DROP TABLE IF EXISTS `alerta_configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerta_configuracion` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alerta_configuracion_clave_unique` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerta_configuracion`
--

LOCK TABLES `alerta_configuracion` WRITE;
/*!40000 ALTER TABLE `alerta_configuracion` DISABLE KEYS */;
INSERT INTO `alerta_configuracion` VALUES (1,'umbral_critico_proveedor','60','Score mínimo aceptable para proveedores (%)',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06'),(2,'ddi_dias','90','Días de inventario (DDI) - política Salcom',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06'),(3,'dias_alerta_documento','7','Días antes del vencimiento para primera alerta',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06'),(4,'dias_urgente_documento','3','Días antes del vencimiento para alerta urgente',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06'),(5,'frecuencia_oc_trimestral','90','Cada cuántos días se genera OC trimestral',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06'),(6,'pico_demanda_porcentaje','20','Porcentaje de incremento para considerar pico de demanda',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06'),(7,'entregas_tardias_consecutivas','2','Entregas tardías consecutivas para alerta de patrón',NULL,'2026-10-07 19:24:06','2026-10-07 19:24:06');
/*!40000 ALTER TABLE `alerta_configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alertas`
--

DROP TABLE IF EXISTS `alertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alertas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL,
  `modulo` varchar(30) NOT NULL,
  `destinatario_tipo` varchar(20) DEFAULT NULL,
  `destinatario_id` bigint(20) unsigned DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `canal_enviado` varchar(20) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'pendiente',
  `nivel` varchar(10) NOT NULL DEFAULT 'info',
  `leida_at` timestamp NULL DEFAULT NULL,
  `accionada_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alertas_tipo_index` (`tipo`),
  KEY `alertas_destinatario_tipo_destinatario_id_index` (`destinatario_tipo`,`destinatario_id`),
  KEY `alertas_estatus_index` (`estatus`),
  KEY `alertas_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertas`
--

LOCK TABLES `alertas` WRITE;
/*!40000 ALTER TABLE `alertas` DISABLE KEYS */;
/*!40000 ALTER TABLE `alertas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `anticipos_proveedor`
--

DROP TABLE IF EXISTS `anticipos_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anticipos_proveedor` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) DEFAULT NULL,
  `proveedor_id` bigint(20) unsigned DEFAULT NULL,
  `codigo_proveedor` varchar(30) DEFAULT NULL,
  `nombre_proveedor` varchar(200) DEFAULT NULL,
  `rfc_proveedor` varchar(20) DEFAULT NULL,
  `banco` varchar(80) DEFAULT NULL,
  `cuenta_banco` varchar(30) DEFAULT NULL,
  `clabe` varchar(20) DEFAULT NULL,
  `importe` decimal(14,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_banco` decimal(14,2) NOT NULL DEFAULT 0.00,
  `folio_general` varchar(120) DEFAULT NULL,
  `uuid_cfdi` varchar(36) DEFAULT NULL,
  `departamento` varchar(60) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `concepto` text DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'pendiente',
  `monto_aplicado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `factura_id` bigint(20) unsigned DEFAULT NULL,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `anticipos_proveedor_proveedor_id_foreign` (`proveedor_id`),
  KEY `anticipos_proveedor_codigo_proveedor_index` (`codigo_proveedor`),
  KEY `anticipos_proveedor_uuid_cfdi_index` (`uuid_cfdi`),
  CONSTRAINT `anticipos_proveedor_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anticipos_proveedor`
--

LOCK TABLES `anticipos_proveedor` WRITE;
/*!40000 ALTER TABLE `anticipos_proveedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `anticipos_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accion` varchar(255) NOT NULL,
  `modulo` varchar(255) NOT NULL,
  `usuario_tipo` varchar(255) NOT NULL,
  `usuario_id` bigint(20) unsigned DEFAULT NULL,
  `usuario_nombre` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `datos_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_antes`)),
  `datos_despues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_despues`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `nivel` varchar(255) NOT NULL DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_log_accion_index` (`accion`),
  KEY `audit_log_modulo_index` (`modulo`),
  KEY `audit_log_usuario_tipo_index` (`usuario_tipo`),
  KEY `audit_log_usuario_id_index` (`usuario_id`),
  KEY `audit_log_nivel_index` (`nivel`),
  KEY `audit_log_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes_users`
--

DROP TABLE IF EXISTS `clientes_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `rfc` varchar(255) DEFAULT NULL,
  `tipo_persona` varchar(255) DEFAULT NULL,
  `codigo_cliente` varchar(255) DEFAULT NULL,
  `tipo_cliente` varchar(255) DEFAULT NULL,
  `credito_autorizado` tinyint(1) NOT NULL DEFAULT 0,
  `limite_credito` decimal(12,2) DEFAULT NULL,
  `dias_credito` smallint(5) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_users_usuario_unique` (`usuario`),
  KEY `clientes_users_correo_index` (`correo`),
  KEY `clientes_users_codigo_cliente_index` (`codigo_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes_users`
--

LOCK TABLES `clientes_users` WRITE;
/*!40000 ALTER TABLE `clientes_users` DISABLE KEYS */;
INSERT INTO `clientes_users` VALUES (1,'Comercializadora del Norte SA de CV','compras@comnorte.com','CLI001','$2y$12$oQga/1p0CXEbuHMlyIf32uUidlMHBpO5n7XCBbsmdTO0d4I9Kv5N6','8112345678','CNO260101AAA','Persona Moral','CLI-2026-001','mayorista',0,NULL,NULL,1,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(2,'Ferretería López','contacto@ferrelopez.com','CLI002','$2y$12$kmL/HgrCNoNwdnrormr5z.0dPx3A3YTRdL8CbptAX3TENkYhFSkyO','3387654321','LOPJ900101BBB','Persona Física','CLI-2026-002','minorista',0,NULL,NULL,1,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL);
/*!40000 ALTER TABLE `clientes_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contactos_proveedor`
--

DROP TABLE IF EXISTS `contactos_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contactos_proveedor` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `rol` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contactos_proveedor_proveedor_id_index` (`proveedor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactos_proveedor`
--

LOCK TABLES `contactos_proveedor` WRITE;
/*!40000 ALTER TABLE `contactos_proveedor` DISABLE KEYS */;
INSERT INTO `contactos_proveedor` VALUES (1,7,'Ana Ventas','ventas','3311111199','ventas.sinonboarding@test.local','2026-10-07 19:39:07','2026-10-07 19:39:07'),(2,7,'Luis Compras','compras','3322222299','compras.sinonboarding@test.local','2026-10-07 19:39:07','2026-10-07 19:39:07');
/*!40000 ALTER TABLE `contactos_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_bancarias`
--

DROP TABLE IF EXISTS `cuentas_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_bancarias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `banco` varchar(60) NOT NULL,
  `clave_corta` varchar(20) DEFAULT NULL,
  `concepto_contpaqi` varchar(20) DEFAULT NULL,
  `consecutivo_actual` bigint(20) unsigned NOT NULL DEFAULT 0,
  `saldo_actual` decimal(18,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cuentas_bancarias_activo_index` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_bancarias`
--

LOCK TABLES `cuentas_bancarias` WRITE;
/*!40000 ALTER TABLE `cuentas_bancarias` DISABLE KEYS */;
INSERT INTO `cuentas_bancarias` VALUES (1,'BBVA 969 SALCOM PESOS','BBVA','8969','28',80194,0.00,1,'2026-10-07 19:24:11','2026-10-07 19:24:11');
/*!40000 ALTER TABLE `cuentas_bancarias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos_agente_aduanal`
--

DROP TABLE IF EXISTS `documentos_agente_aduanal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos_agente_aduanal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agente_aduanal_id` bigint(20) unsigned NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `fecha_carga` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `subido_por` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documentos_agente_aduanal_agente_aduanal_id_foreign` (`agente_aduanal_id`),
  KEY `documentos_agente_aduanal_subido_por_foreign` (`subido_por`),
  KEY `documentos_agente_aduanal_tipo_index` (`tipo`),
  KEY `documentos_agente_aduanal_fecha_vencimiento_index` (`fecha_vencimiento`),
  CONSTRAINT `documentos_agente_aduanal_agente_aduanal_id_foreign` FOREIGN KEY (`agente_aduanal_id`) REFERENCES `agentes_aduanales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_agente_aduanal_subido_por_foreign` FOREIGN KEY (`subido_por`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos_agente_aduanal`
--

LOCK TABLES `documentos_agente_aduanal` WRITE;
/*!40000 ALTER TABLE `documentos_agente_aduanal` DISABLE KEYS */;
/*!40000 ALTER TABLE `documentos_agente_aduanal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos_proveedor`
--

DROP TABLE IF EXISTS `documentos_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos_proveedor` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` bigint(20) unsigned NOT NULL,
  `tipo` varchar(255) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `estatus` varchar(255) NOT NULL DEFAULT 'pendiente',
  `resultado_validacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resultado_validacion`)),
  `notas_revision` text DEFAULT NULL,
  `revisado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documentos_proveedor_proveedor_id_index` (`proveedor_id`),
  KEY `documentos_proveedor_estatus_index` (`estatus`),
  KEY `documentos_proveedor_tipo_index` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos_proveedor`
--

LOCK TABLES `documentos_proveedor` WRITE;
/*!40000 ALTER TABLE `documentos_proveedor` DISABLE KEYS */;
INSERT INTO `documentos_proveedor` VALUES (1,1,'cif','cif/prov001_cif.pdf','aprobado',NULL,'RFC válido, documento vigente','2026-03-20 06:00:00','2026-10-07 19:39:09','2026-10-07 19:39:09'),(2,1,'opinion','opiniones/prov001_opinion.pdf','aprobado',NULL,'Opinión positiva abril 2026','2026-04-05 06:00:00','2026-10-07 19:39:09','2026-10-07 19:39:09'),(3,2,'cif','cif/prov002_cif.pdf','pendiente',NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09'),(4,2,'caratula_banco','caratula_banco/prov002_banco.pdf','pendiente',NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09'),(5,3,'cif','cif/prov003_cif.pdf','rechazado',NULL,'RFC no coincide con nombre','2026-04-10 06:00:00','2026-10-07 19:39:09','2026-10-07 19:39:09'),(6,3,'opinion','opiniones/prov003_opinion.pdf','pendiente',NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09');
/*!40000 ALTER TABLE `documentos_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_empleado` varchar(50) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `departamento` varchar(255) DEFAULT NULL,
  `requiere_gasolina` tinyint(1) NOT NULL DEFAULT 0,
  `correo` varchar(255) DEFAULT NULL,
  `numero_cuenta` varchar(30) DEFAULT NULL,
  `titular_cuenta` varchar(255) DEFAULT NULL,
  `banco_tarjeta` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleados_numero_empleado_unique` (`numero_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleados`
--

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encargos_conferidos`
--

DROP TABLE IF EXISTS `encargos_conferidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encargos_conferidos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agente_aduanal_id` bigint(20) unsigned NOT NULL,
  `numero_patente` varchar(4) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_termino` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `numero_acuse` varchar(80) DEFAULT NULL,
  `nombre_acuse` varchar(255) DEFAULT NULL,
  `ruta_acuse` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encargos_conferidos_agente_aduanal_id_foreign` (`agente_aduanal_id`),
  KEY `encargos_conferidos_estado_index` (`estado`),
  KEY `encargos_conferidos_fecha_termino_index` (`fecha_termino`),
  CONSTRAINT `encargos_conferidos_agente_aduanal_id_foreign` FOREIGN KEY (`agente_aduanal_id`) REFERENCES `agentes_aduanales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encargos_conferidos`
--

LOCK TABLES `encargos_conferidos` WRITE;
/*!40000 ALTER TABLE `encargos_conferidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `encargos_conferidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encuestas`
--

DROP TABLE IF EXISTS `encuestas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encuestas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo_cliente` varchar(255) NOT NULL,
  `pedido_id` bigint(20) unsigned DEFAULT NULL,
  `calificacion` tinyint(4) NOT NULL,
  `tiempo_entrega` tinyint(4) NOT NULL,
  `calidad_producto` tinyint(4) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encuestas_codigo_cliente_index` (`codigo_cliente`),
  KEY `encuestas_pedido_id_index` (`pedido_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encuestas`
--

LOCK TABLES `encuestas` WRITE;
/*!40000 ALTER TABLE `encuestas` DISABLE KEYS */;
INSERT INTO `encuestas` VALUES (1,'CLI-2026-001',NULL,5,5,5,'Excelente servicio y calidad, siempre puntuales','2026-10-07 19:39:09','2026-10-07 19:39:09'),(2,'CLI-2026-001',NULL,4,4,5,'Buen producto, entrega un poco lenta esta vez','2026-10-07 19:39:09','2026-10-07 19:39:09'),(3,'CLI-2026-001',NULL,5,5,4,'Muy satisfecho con el servicio','2026-10-07 19:39:09','2026-10-07 19:39:09'),(4,'CLI-2026-002',NULL,4,3,4,'Todo bien, pero la entrega tardó más de lo esperado','2026-10-07 19:39:09','2026-10-07 19:39:09'),(5,'CLI-2026-002',NULL,3,2,4,'La entrega tardó demasiado, producto OK','2026-10-07 19:39:09','2026-10-07 19:39:09'),(6,'CLI-2026-002',NULL,5,5,5,'Perfecto, nada que mejorar','2026-10-07 19:39:09','2026-10-07 19:39:09');
/*!40000 ALTER TABLE `encuestas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `excel_validaciones`
--

DROP TABLE IF EXISTS `excel_validaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `excel_validaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` bigint(20) unsigned NOT NULL,
  `archivo_path` varchar(255) NOT NULL,
  `total_productos` int(11) NOT NULL DEFAULT 0,
  `productos_validos` int(11) NOT NULL DEFAULT 0,
  `productos_con_error` int(11) NOT NULL DEFAULT 0,
  `errores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errores`)),
  `estatus` varchar(20) NOT NULL DEFAULT 'procesando',
  `aprobado_por` bigint(20) unsigned DEFAULT NULL,
  `aprobado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `excel_validaciones_proveedor_id_index` (`proveedor_id`),
  KEY `excel_validaciones_estatus_index` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `excel_validaciones`
--

LOCK TABLES `excel_validaciones` WRITE;
/*!40000 ALTER TABLE `excel_validaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `excel_validaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facturas`
--

DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `folio_cfdi` varchar(255) NOT NULL,
  `uuid_cfdi` varchar(36) DEFAULT NULL,
  `codigo_cliente` varchar(255) DEFAULT NULL,
  `codigo_proveedor` varchar(255) DEFAULT NULL,
  `regimen_fiscal` varchar(10) DEFAULT NULL,
  `es_fletera` tinyint(1) NOT NULL DEFAULT 0,
  `pedido_id` bigint(20) unsigned DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `monto_iva` decimal(12,2) NOT NULL,
  `retencion_iva` decimal(12,2) DEFAULT NULL,
  `retencion_isr` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `monto_pagado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estatus` varchar(255) NOT NULL DEFAULT 'pendiente',
  `fecha_vencimiento` date DEFAULT NULL,
  `dias_plazo` smallint(5) unsigned DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `archivo_xml` varchar(255) DEFAULT NULL,
  `archivo_oc` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `validacion_detalle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validacion_detalle`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facturas_folio_cfdi_unique` (`folio_cfdi`),
  UNIQUE KEY `facturas_uuid_cfdi_unique` (`uuid_cfdi`),
  KEY `facturas_codigo_cliente_index` (`codigo_cliente`),
  KEY `facturas_codigo_proveedor_index` (`codigo_proveedor`),
  KEY `facturas_estatus_index` (`estatus`),
  KEY `facturas_pedido_id_index` (`pedido_id`),
  KEY `facturas_es_fletera_index` (`es_fletera`),
  KEY `facturas_regimen_fiscal_index` (`regimen_fiscal`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facturas`
--

LOCK TABLES `facturas` WRITE;
/*!40000 ALTER TABLE `facturas` DISABLE KEYS */;
INSERT INTO `facturas` VALUES (1,'CFDI-A-001230',NULL,'CLI-2026-001',NULL,NULL,0,NULL,36206.90,5793.10,NULL,NULL,42000.00,0.00,'pagada','2025-12-15',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(2,'CFDI-A-001231',NULL,'CLI-2026-002',NULL,NULL,0,NULL,7327.59,1172.41,NULL,NULL,8500.00,0.00,'pagada','2026-01-03',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(3,'CFDI-A-001235',NULL,'CLI-2026-001',NULL,NULL,0,NULL,54310.34,8689.66,NULL,NULL,63000.00,0.00,'pendiente','2026-02-10',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(4,'CFDI-A-001236',NULL,'CLI-2026-001',NULL,NULL,0,NULL,65948.28,10551.72,NULL,NULL,76500.00,0.00,'pendiente','2026-04-12',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(5,'CFDI-A-001240',NULL,'CLI-2026-001',NULL,NULL,0,NULL,58620.69,9379.31,NULL,NULL,68000.00,0.00,'pendiente','2026-05-02',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(6,'CFDI-A-001241',NULL,'CLI-2026-002',NULL,NULL,0,NULL,8275.86,1324.14,NULL,NULL,9600.00,0.00,'pendiente','2026-05-15',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(7,'CFDI-P-000501',NULL,NULL,'102003240',NULL,0,NULL,12500.00,2000.00,NULL,NULL,14500.00,0.00,'pagada','2026-03-01',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL),(8,'CFDI-P-000502',NULL,NULL,'102003241',NULL,0,NULL,8200.00,1312.00,NULL,NULL,9512.00,0.00,'pendiente','2026-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09',NULL);
/*!40000 ALTER TABLE `facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migraciones_masivas`
--

DROP TABLE IF EXISTS `migraciones_masivas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migraciones_masivas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL COMMENT 'Admin que inició la migración',
  `archivo_path` varchar(255) NOT NULL COMMENT 'Ruta del Excel subido',
  `total_productos` int(11) NOT NULL DEFAULT 0 COMMENT 'Total de productos en el Excel',
  `productos_procesados` int(11) NOT NULL DEFAULT 0 COMMENT 'Productos procesados exitosamente',
  `productos_error` int(11) NOT NULL DEFAULT 0 COMMENT 'Productos con error de procesamiento',
  `lotes_total` int(11) NOT NULL DEFAULT 0 COMMENT 'Total de lotes (batches de 50)',
  `lotes_completados` int(11) NOT NULL DEFAULT 0 COMMENT 'Lotes procesados',
  `estatus` enum('pendiente','procesando','completado','error') NOT NULL DEFAULT 'pendiente',
  `resultado_path` varchar(255) DEFAULT NULL COMMENT 'Ruta del Excel de resultados',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `migraciones_masivas_admin_id_foreign` (`admin_id`),
  CONSTRAINT `migraciones_masivas_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migraciones_masivas`
--

LOCK TABLES `migraciones_masivas` WRITE;
/*!40000 ALTER TABLE `migraciones_masivas` DISABLE KEYS */;
/*!40000 ALTER TABLE `migraciones_masivas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_25_203638_create_proveedores_users_table',1),(5,'2026_04_08_000000_add_indexes_to_proveedores_users_table',1),(6,'2026_04_08_000001_add_soft_deletes_to_proveedores_users_table',1),(7,'2026_04_11_000000_create_muestras_table',1),(8,'2026_04_13_000000_create_clientes_users_table',1),(9,'2026_04_14_000001_create_pedidos_table',1),(10,'2026_04_14_000002_create_productos_table',1),(11,'2026_04_14_000003_create_facturas_table',1),(12,'2026_04_14_000004_create_notificaciones_table',1),(13,'2026_04_14_000005_create_tracking_pedidos_table',1),(14,'2026_04_14_000006_create_encuestas_table',1),(15,'2026_04_16_000000_create_admin_users_table',1),(16,'2026_04_20_000000_create_documentos_proveedor_table',1),(17,'2026_04_20_000001_add_score_to_proveedores_users_table',1),(18,'2026_04_20_000002_create_contactos_proveedor_table',1),(19,'2026_05_08_000000_create_audit_log_table',1),(20,'2026_05_09_194130_add_rol_to_admin_users_table',1),(21,'2026_05_11_000001_add_dias_credito_to_clientes_users_table',1),(22,'2026_05_12_000001_create_alertas_table',1),(23,'2026_05_12_000002_create_alerta_configuracion_table',1),(24,'2026_05_12_000003_create_pronosticos_table',1),(25,'2026_05_12_000004_create_oc_borradores_table',1),(26,'2026_05_12_000005_create_excel_validaciones_table',1),(27,'2026_05_14_220000_add_planta_fields_to_productos_table',1),(28,'2026_05_19_230000_add_rol_to_admin_users_table',1),(29,'2026_05_21_000001_add_proveedor_fields_to_pedidos_table',1),(30,'2026_05_21_000002_backfill_pedidos_proveedor',1),(31,'2026_06_03_201811_add_proveedor_to_productos',1),(32,'2026_06_03_222743_add_proveedor_tipo_to_productos',1),(33,'2026_06_04_172210_fix_producto_mpi0536_proveedor_nombre',1),(34,'2026_06_05_174227_add_foto_to_proveedores_users',1),(35,'2026_06_05_174741_add_foto_to_admin_users',1),(36,'2026_06_06_000001_create_migraciones_masivas_table',1),(37,'2026_06_26_160650_add_clasificaciones_to_productos',1),(38,'2026_07_01_000001_add_nombre_desglose_to_productos_table',1),(39,'2026_07_07_000001_create_producto_proveedor_precios_table',1),(40,'2026_07_09_000001_rename_codigo_compras_to_id_proveedor',1),(41,'2026_07_15_000001_add_datos_identificacion_to_proveedores_users',1),(42,'2026_07_16_000001_create_solicitudes_alta_table',1),(43,'2026_07_17_000001_add_alta_factura_fields_to_facturas_table',1),(44,'2026_07_22_000001_add_solicitud_alta_estatus_to_proveedores_users',1),(45,'2026_07_23_000001_add_solicitud_alta_intentos_to_proveedores_users',1),(46,'2026_07_28_120000_create_pagos_proveedor_tables',1),(47,'2026_07_29_180000_add_comprobantes_to_pagos_proveedor',1),(48,'2026_07_29_184500_add_datos_confirmacion_to_pagos_proveedor',1),(49,'2026_07_30_000001_add_stock_minimo_lead_time_to_productos_table',1),(50,'2026_07_30_000002_add_historial_modificaciones_to_oc_borradores_table',1),(51,'2026_08_03_120000_add_codigo_sap_to_proveedores_users',1),(52,'2026_08_07_140000_add_correo_verified_at_to_proveedores_users',1),(53,'2026_08_07_184000_add_correo_verified_at_to_proveedores_users',1),(54,'2026_08_09_210000_create_solicitudes_modificacion_datos_table',1),(55,'2026_08_11_120000_add_rfc_to_proveedores_users',1),(56,'2026_08_11_120000_create_abonos_proveedor_tables',1),(57,'2026_08_11_130000_add_moneda_to_proveedores_users',1),(58,'2026_08_11_140000_add_agente_to_abonos_proveedor',1),(59,'2026_08_11_160000_add_monto_pagado_to_facturas',1),(60,'2026_08_14_140000_add_dias_plazo_to_facturas',1),(61,'2026_08_17_122442_actualizar_tipos_producto_wiese',1),(62,'2026_08_17_124735_mapear_familias_wiese_clasif2',1),(63,'2026_08_17_125245_asignar_familia_por_tipo_producto',1),(64,'2026_08_20_create_reembolsos_viaje_table',1),(65,'2026_08_24_120422_create_anticipos_proveedor_table',1),(66,'2026_09_02_100000_add_uuid_cfdi_to_anticipos_proveedor_table',1),(67,'2026_09_02_add_fechas_viaje_to_reembolsos_viaje',1),(68,'2026_09_03_create_empleados_table',1),(69,'2026_09_04_120000_add_es_repse_to_proveedores_users_table',1),(70,'2026_09_04_add_tarjeta_to_empleados_table',1),(71,'2026_09_05_add_requiere_gasolina_to_empleados',1),(72,'2026_09_14_120000_add_expediente_pago_to_pagos_proveedor',1),(73,'2026_09_30_add_banco_tarjeta_to_empleados_table',1),(74,'2026_09_30_make_numero_empleado_nullable',1),(75,'2026_10_05_140000_create_agentes_aduanales_tables',1),(76,'2026_10_06_120000_create_wiese_banco_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_bancarios`
--

DROP TABLE IF EXISTS `movimientos_bancarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_bancarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_id` bigint(20) unsigned NOT NULL,
  `fecha` date NOT NULL,
  `num` bigint(20) unsigned DEFAULT NULL,
  `payee` varchar(255) DEFAULT NULL,
  `categoria` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `payment` decimal(18,2) NOT NULL DEFAULT 0.00,
  `deposit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `iddocumento_contpaqi` bigint(20) unsigned DEFAULT NULL,
  `codigo_proveedor` varchar(40) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_bancarios_cuenta_id_index` (`cuenta_id`),
  KEY `movimientos_bancarios_fecha_index` (`fecha`),
  KEY `movimientos_bancarios_num_index` (`num`),
  KEY `movimientos_bancarios_estatus_index` (`estatus`),
  CONSTRAINT `movimientos_bancarios_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_bancarios`
--

LOCK TABLES `movimientos_bancarios` WRITE;
/*!40000 ALTER TABLE `movimientos_bancarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimientos_bancarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `muestras`
--

DROP TABLE IF EXISTS `muestras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `muestras` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lote` varchar(50) NOT NULL,
  `producto` varchar(255) NOT NULL,
  `proveedor` varchar(255) NOT NULL,
  `proveedor_contacto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `unidad` varchar(30) NOT NULL DEFAULT 'piezas',
  `etapa` enum('registro','recepcion','validacion','laboratorio','piso','estabilidad','aprobado','rechazado') NOT NULL DEFAULT 'registro',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_recepcion` timestamp NULL DEFAULT NULL,
  `fecha_validacion` timestamp NULL DEFAULT NULL,
  `fecha_laboratorio` timestamp NULL DEFAULT NULL,
  `fecha_piso` timestamp NULL DEFAULT NULL,
  `fecha_estabilidad` timestamp NULL DEFAULT NULL,
  `fecha_resolucion` timestamp NULL DEFAULT NULL,
  `dias_validacion` int(11) NOT NULL DEFAULT 15,
  `notas` text DEFAULT NULL,
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `muestras`
--

LOCK TABLES `muestras` WRITE;
/*!40000 ALTER TABLE `muestras` DISABLE KEYS */;
INSERT INTO `muestras` VALUES (1,'LOTE-2026-001','Resina epóxica premium','Distribuidora Nacional SA de CV',NULL,'Nueva formulación de resina con mayor resistencia',5,'kg','laboratorio','2026-03-15 06:00:00','2026-03-16 06:00:00',NULL,'2026-03-20 06:00:00',NULL,NULL,NULL,15,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09'),(2,'LOTE-2026-002','Solvente ecológico','Materiales Industriales del Bajío',NULL,'Solvente base agua biodegradable',10,'lt','piso','2026-03-01 06:00:00','2026-03-02 06:00:00',NULL,'2026-03-05 06:00:00','2026-03-20 06:00:00',NULL,NULL,15,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09'),(3,'LOTE-2026-003','Pigmento orgánico','Juan Pérez López',NULL,'Pigmento natural para recubrimientos',3,'kg','registro','2026-04-10 06:00:00',NULL,NULL,NULL,NULL,NULL,NULL,15,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09'),(4,'LOTE-2026-004','Catalizador UV','Distribuidora Nacional SA de CV',NULL,'Catalizador de curado por luz UV',2,'kg','estabilidad','2026-02-10 06:00:00','2026-02-11 06:00:00',NULL,'2026-02-15 06:00:00','2026-03-05 06:00:00','2026-03-15 06:00:00',NULL,15,NULL,NULL,'2026-10-07 19:39:09','2026-10-07 19:39:09');
/*!40000 ALTER TABLE `muestras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_usuario` varchar(255) NOT NULL,
  `codigo_usuario` varchar(255) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `tipo` varchar(255) NOT NULL DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notificaciones_tipo_usuario_codigo_usuario_index` (`tipo_usuario`,`codigo_usuario`),
  KEY `notificaciones_leida_index` (`leida`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,'cliente','CLI-2026-001','Pedido PED-2026-035 — Enviado','Tu pedido PED-2026-035 ha sido enviado vía Estafeta.',0,'pedido_estatus','2026-10-07 19:39:09','2026-10-07 19:39:09'),(2,'cliente','CLI-2026-001','Pedido PED-2026-048 — Procesando','Tu pedido PED-2026-048 está en producción.',0,'pedido_estatus','2026-10-07 19:39:09','2026-10-07 19:39:09'),(3,'cliente','CLI-2026-001','Factura CFDI-A-001235 vencida','Tu factura CFDI-A-001235 por $63,000 venció el 10/02/2026.',1,'factura','2026-10-07 19:39:09','2026-10-07 19:39:09'),(4,'cliente','CLI-2026-002','Pedido PED-2026-055 — En validación','Tu pedido PED-2026-055 está siendo validado.',0,'pedido_estatus','2026-10-07 19:39:09','2026-10-07 19:39:09'),(5,'proveedor','102003241','Documento pendiente','Tu CIF está pendiente de revisión. Sube el documento actualizado.',0,'documento','2026-10-07 19:39:09','2026-10-07 19:39:09');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oc_borradores`
--

DROP TABLE IF EXISTS `oc_borradores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oc_borradores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(20) NOT NULL,
  `proveedor_id` bigint(20) unsigned NOT NULL,
  `productos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`productos`)),
  `monto_estimado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `motivo` varchar(255) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'pendiente',
  `aprobada_por` bigint(20) unsigned DEFAULT NULL,
  `aprobada_at` timestamp NULL DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `historial_modificaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`historial_modificaciones`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oc_borradores_proveedor_id_index` (`proveedor_id`),
  KEY `oc_borradores_estatus_index` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oc_borradores`
--

LOCK TABLES `oc_borradores` WRITE;
/*!40000 ALTER TABLE `oc_borradores` DISABLE KEYS */;
/*!40000 ALTER TABLE `oc_borradores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operaciones_comercio_exterior`
--

DROP TABLE IF EXISTS `operaciones_comercio_exterior`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operaciones_comercio_exterior` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agente_aduanal_id` bigint(20) unsigned DEFAULT NULL,
  `aduana_id` bigint(20) unsigned DEFAULT NULL,
  `numero_patente` varchar(4) DEFAULT NULL,
  `numero_pedimento` varchar(30) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `contraparte` varchar(255) DEFAULT NULL,
  `mercancia` varchar(255) DEFAULT NULL,
  `tipo_operacion` varchar(20) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operaciones_comercio_exterior_agente_aduanal_id_foreign` (`agente_aduanal_id`),
  KEY `operaciones_comercio_exterior_aduana_id_foreign` (`aduana_id`),
  KEY `operaciones_comercio_exterior_numero_pedimento_index` (`numero_pedimento`),
  KEY `operaciones_comercio_exterior_tipo_operacion_index` (`tipo_operacion`),
  KEY `operaciones_comercio_exterior_fecha_index` (`fecha`),
  CONSTRAINT `operaciones_comercio_exterior_aduana_id_foreign` FOREIGN KEY (`aduana_id`) REFERENCES `aduanas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operaciones_comercio_exterior_agente_aduanal_id_foreign` FOREIGN KEY (`agente_aduanal_id`) REFERENCES `agentes_aduanales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operaciones_comercio_exterior`
--

LOCK TABLES `operaciones_comercio_exterior` WRITE;
/*!40000 ALTER TABLE `operaciones_comercio_exterior` DISABLE KEYS */;
/*!40000 ALTER TABLE `operaciones_comercio_exterior` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_proveedor_facturas`
--

DROP TABLE IF EXISTS `pago_proveedor_facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago_proveedor_facturas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pago_id` bigint(20) unsigned NOT NULL,
  `factura_id` bigint(20) unsigned NOT NULL,
  `folio_cfdi` varchar(255) DEFAULT NULL,
  `uuid_cfdi` varchar(36) DEFAULT NULL,
  `es_fletera` tinyint(1) NOT NULL DEFAULT 0,
  `regimen_fiscal` varchar(10) DEFAULT NULL,
  `monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_iva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `retencion_iva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `retencion_isr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `neto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `avisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`avisos`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pago_proveedor_facturas_pago_id_factura_id_unique` (`pago_id`,`factura_id`),
  KEY `pago_proveedor_facturas_factura_id_foreign` (`factura_id`),
  CONSTRAINT `pago_proveedor_facturas_factura_id_foreign` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`),
  CONSTRAINT `pago_proveedor_facturas_pago_id_foreign` FOREIGN KEY (`pago_id`) REFERENCES `pagos_proveedor` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_proveedor_facturas`
--

LOCK TABLES `pago_proveedor_facturas` WRITE;
/*!40000 ALTER TABLE `pago_proveedor_facturas` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_proveedor_facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos_proveedor`
--

DROP TABLE IF EXISTS `pagos_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos_proveedor` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` bigint(20) unsigned DEFAULT NULL,
  `codigo_proveedor` varchar(255) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'facturas',
  `estatus` varchar(20) NOT NULL DEFAULT 'borrador',
  `fecha_pago` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `comprobantes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`comprobantes`)),
  `datos_confirmacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_confirmacion`)),
  `num_facturas` int(10) unsigned NOT NULL DEFAULT 0,
  `monto_subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_iva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_retencion_iva` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_retencion_isr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_neto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `confirmado_por` bigint(20) unsigned DEFAULT NULL,
  `confirmado_at` timestamp NULL DEFAULT NULL,
  `estatus_autorizacion` varchar(255) NOT NULL DEFAULT 'pendiente',
  `autorizado_por` bigint(20) unsigned DEFAULT NULL,
  `autorizado_por_nombre` varchar(255) DEFAULT NULL,
  `autorizado_at` timestamp NULL DEFAULT NULL,
  `notas_autorizacion` text DEFAULT NULL,
  `documentos_adjuntos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documentos_adjuntos`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_proveedor_proveedor_id_foreign` (`proveedor_id`),
  KEY `pagos_proveedor_estatus_created_at_index` (`estatus`,`created_at`),
  KEY `pagos_proveedor_codigo_proveedor_index` (`codigo_proveedor`),
  CONSTRAINT `pagos_proveedor_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_proveedor`
--

LOCK TABLES `pagos_proveedor` WRITE;
/*!40000 ALTER TABLE `pagos_proveedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedidos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(255) NOT NULL,
  `codigo_cliente` varchar(255) NOT NULL,
  `codigo_proveedor` varchar(255) DEFAULT NULL,
  `nombre_cliente` varchar(255) NOT NULL,
  `nombre_proveedor` varchar(255) DEFAULT NULL,
  `productos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`productos`)),
  `total` decimal(12,2) NOT NULL,
  `tipo_pago` varchar(255) NOT NULL DEFAULT 'contado',
  `estatus` varchar(255) NOT NULL DEFAULT 'validacion',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pedidos_folio_unique` (`folio`),
  KEY `pedidos_codigo_cliente_index` (`codigo_cliente`),
  KEY `pedidos_estatus_index` (`estatus`),
  KEY `pedidos_codigo_proveedor_index` (`codigo_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,'PED-2025-089','CLI-2026-001','102003240','Comercializadora del Norte SA de CV','Distribuidora Nacional SA de CV','[{\"sku\":\"SAL-001\",\"nombre\":\"Resina ep\\u00f3xica\",\"cantidad\":500,\"precio\":85}]',42500.00,'credito','entregado','Entregado sin novedad','2025-11-15 06:00:00','2026-10-07 19:39:08',NULL),(2,'PED-2025-102','CLI-2026-002','102003241','Ferretería López','Materiales Industriales del Bajío','[{\"sku\":\"SAL-003\",\"nombre\":\"Solvente t\\u00e9cnico\",\"cantidad\":200,\"precio\":42.5}]',8500.00,'contado','entregado',NULL,'2025-12-03 06:00:00','2026-10-07 19:39:08',NULL),(3,'PED-2026-008','CLI-2026-001','102003240','Comercializadora del Norte SA de CV','Distribuidora Nacional SA de CV','[{\"sku\":\"SAL-001\",\"nombre\":\"Resina ep\\u00f3xica\",\"cantidad\":600,\"precio\":85},{\"sku\":\"SAL-005\",\"nombre\":\"Pigmento\",\"cantidad\":100,\"precio\":120}]',63000.00,'credito','entregado',NULL,'2026-01-10 06:00:00','2026-10-07 19:39:08',NULL),(4,'PED-2026-021','CLI-2026-002','102003241','Ferretería López','Materiales Industriales del Bajío','[{\"sku\":\"SAL-007\",\"nombre\":\"Catalizador\",\"cantidad\":50,\"precio\":210}]',10500.00,'contado','entregado','Recogido en planta','2026-02-05 06:00:00','2026-10-07 19:39:08',NULL),(5,'PED-2026-035','CLI-2026-001','102003240','Comercializadora del Norte SA de CV','Distribuidora Nacional SA de CV','[{\"sku\":\"SAL-001\",\"nombre\":\"Resina ep\\u00f3xica\",\"cantidad\":750,\"precio\":85},{\"sku\":\"SAL-003\",\"nombre\":\"Solvente\",\"cantidad\":300,\"precio\":42.5}]',76500.00,'credito','enviado','Guía Estafeta: 6024958372615','2026-03-12 06:00:00','2026-10-07 19:39:08',NULL),(6,'PED-2026-048','CLI-2026-001','102003240','Comercializadora del Norte SA de CV','Distribuidora Nacional SA de CV','[{\"sku\":\"SAL-001\",\"nombre\":\"Resina ep\\u00f3xica\",\"cantidad\":800,\"precio\":85}]',68000.00,'credito','procesando','En producción lote #4521','2026-04-02 06:00:00','2026-10-07 19:39:08',NULL),(7,'PED-2026-055','CLI-2026-002','102003242','Ferretería López','Juan Pérez López','[{\"sku\":\"SAL-005\",\"nombre\":\"Pigmento\",\"cantidad\":80,\"precio\":120}]',9600.00,'contado','validacion','Pendiente verificar stock','2026-04-15 06:00:00','2026-10-07 19:39:08',NULL),(8,'PED-2026-061','CLI-2026-001','102003240','Comercializadora del Norte SA de CV','Distribuidora Nacional SA de CV','[{\"sku\":\"SAL-011\",\"nombre\":\"Fibra de refuerzo\",\"cantidad\":20,\"precio\":320},{\"sku\":\"SAL-009\",\"nombre\":\"Aditivo antioxidante\",\"cantidad\":100,\"precio\":55}]',11900.00,'credito','procesando',NULL,'2026-04-20 06:00:00','2026-10-07 19:39:08',NULL),(9,'PED-2026-068','CLI-2026-002','102003241','Ferretería López','Materiales Industriales del Bajío','[{\"sku\":\"SAL-015\",\"nombre\":\"Sellador industrial\",\"cantidad\":30,\"precio\":95}]',2850.00,'contado','validacion',NULL,'2026-04-25 06:00:00','2026-10-07 19:39:08',NULL),(10,'PED-2025-075','CLI-2026-001','102003240','Comercializadora del Norte SA de CV','Distribuidora Nacional SA de CV','[{\"sku\":\"SAL-013\",\"nombre\":\"Adhesivo estructural\",\"cantidad\":40,\"precio\":180}]',7200.00,'credito','cancelado','Cancelado — cambio de especificación','2025-10-20 06:00:00','2026-10-07 19:39:08',NULL);
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_proveedor_precios`
--

DROP TABLE IF EXISTS `producto_proveedor_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_proveedor_precios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint(20) unsigned NOT NULL,
  `proveedor_id` bigint(20) unsigned NOT NULL,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moq` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `producto_proveedor_precios_producto_id_proveedor_id_unique` (`producto_id`,`proveedor_id`),
  KEY `producto_proveedor_precios_proveedor_id_foreign` (`proveedor_id`),
  CONSTRAINT `producto_proveedor_precios_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `producto_proveedor_precios_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_proveedor_precios`
--

LOCK TABLES `producto_proveedor_precios` WRITE;
/*!40000 ALTER TABLE `producto_proveedor_precios` DISABLE KEYS */;
/*!40000 ALTER TABLE `producto_proveedor_precios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) NOT NULL,
  `codigo_alterno` varchar(255) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `nombre_tipo` varchar(255) DEFAULT NULL,
  `nombre_marca` varchar(255) DEFAULT NULL,
  `nombre_modelo` varchar(255) DEFAULT NULL,
  `nombre_medida` varchar(255) DEFAULT NULL,
  `nombre_especificacion` varchar(255) DEFAULT NULL,
  `nombre_alterno` varchar(255) DEFAULT NULL,
  `clave_sat` varchar(255) DEFAULT NULL,
  `descripcion_corta` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(255) DEFAULT NULL,
  `familia` varchar(255) DEFAULT NULL,
  `subfamilia` varchar(255) DEFAULT NULL,
  `segmento_mercado` varchar(255) DEFAULT NULL,
  `tipo_producto` varchar(255) NOT NULL DEFAULT 'General',
  `precio` decimal(12,2) NOT NULL,
  `unidad_venta` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` decimal(12,2) DEFAULT NULL,
  `lead_time_dias` smallint(5) unsigned DEFAULT NULL,
  `cajas_por_tarima` int(11) DEFAULT NULL,
  `peso_bruto_caja` decimal(10,4) DEFAULT NULL,
  `peso_bruto` decimal(10,4) DEFAULT NULL,
  `piezas_por_caja` decimal(10,2) DEFAULT NULL,
  `volumen` decimal(12,7) DEFAULT NULL,
  `maneja_lotes` tinyint(1) NOT NULL DEFAULT 0,
  `unidad_xml` varchar(255) DEFAULT NULL,
  `iva` decimal(5,2) NOT NULL DEFAULT 16.00,
  `ieps` decimal(5,2) NOT NULL DEFAULT 0.00,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor_nombre` varchar(255) DEFAULT NULL,
  `proveedor_tipo` varchar(20) DEFAULT NULL,
  `departamento` varchar(255) DEFAULT NULL,
  `linea` varchar(255) DEFAULT NULL,
  `subfamilia_pt` varchar(255) DEFAULT NULL,
  `canal` varchar(255) DEFAULT NULL,
  `vendedor` varchar(255) DEFAULT NULL,
  `modulo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productos_codigo_unique` (`codigo`),
  KEY `productos_categoria_index` (`categoria`),
  KEY `productos_activo_index` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'SAL-001',NULL,'Resina epóxica industrial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Resina de alta viscosidad para uso industrial','Materia prima',NULL,NULL,NULL,'General',85.00,'kg',1200,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(2,'SAL-003',NULL,'Solvente grado técnico',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Solvente de alta pureza','Materia prima',NULL,NULL,NULL,'General',42.50,'lt',150,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(3,'SAL-005',NULL,'Pigmento base agua',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Pigmento ecológico base agua','Materia prima',NULL,NULL,NULL,'General',120.00,'kg',300,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(4,'SAL-007',NULL,'Catalizador rápido',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Catalizador de curado rápido','Consumible',NULL,NULL,NULL,'General',210.00,'kg',25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(5,'SAL-009',NULL,'Aditivo antioxidante',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Aditivo para prevenir oxidación','Consumible',NULL,NULL,NULL,'General',55.00,'kg',500,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(6,'SAL-011',NULL,'Fibra de refuerzo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fibra de vidrio para refuerzo estructural','Materia prima',NULL,NULL,NULL,'General',320.00,'rollo',80,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(7,'SAL-013',NULL,'Adhesivo estructural',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Adhesivo de alta resistencia','Producto terminado',NULL,NULL,NULL,'General',180.00,'kg',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL),(8,'SAL-015',NULL,'Sellador industrial',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Sellador para juntas industriales','Producto terminado',NULL,NULL,NULL,'General',95.00,'lt',45,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,16.00,0.00,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 19:39:08','2026-10-07 19:39:08',NULL);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pronosticos`
--

DROP TABLE IF EXISTS `pronosticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pronosticos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL,
  `referencia_tipo` varchar(20) DEFAULT NULL,
  `referencia_id` bigint(20) unsigned DEFAULT NULL,
  `codigo_referencia` varchar(50) DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `confianza` varchar(10) NOT NULL DEFAULT 'media',
  `generado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pronosticos_tipo_referencia_tipo_referencia_id_index` (`tipo`,`referencia_tipo`,`referencia_id`),
  KEY `pronosticos_generado_at_index` (`generado_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pronosticos`
--

LOCK TABLES `pronosticos` WRITE;
/*!40000 ALTER TABLE `pronosticos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pronosticos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores_users`
--

DROP TABLE IF EXISTS `proveedores_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_proveedor` varchar(255) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'MXN',
  `tipo_persona` varchar(255) DEFAULT NULL,
  `es_repse` tinyint(1) NOT NULL DEFAULT 0,
  `rfc` varchar(13) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `datos_identificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_identificacion`)),
  `correo` varchar(255) DEFAULT NULL,
  `correo_verified_at` timestamp NULL DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `solicitud_alta_estatus` varchar(30) DEFAULT NULL,
  `solicitud_alta_intentos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `score_entrega` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_puntualidad` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_total` decimal(5,2) NOT NULL DEFAULT 0.00,
  `aviso_privacidad_aceptado` tinyint(1) NOT NULL DEFAULT 0,
  `aviso_privacidad_fecha` timestamp NULL DEFAULT NULL,
  `codigo` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proveedores_users_usuario_unique` (`usuario`),
  UNIQUE KEY `proveedores_users_rfc_unique` (`rfc`),
  KEY `proveedores_users_correo_index` (`correo`),
  KEY `proveedores_users_id_proveedor_index` (`id_proveedor`),
  KEY `proveedores_users_solicitud_alta_estatus_index` (`solicitud_alta_estatus`),
  KEY `proveedores_users_codigo_index` (`codigo`),
  KEY `proveedores_users_moneda_index` (`moneda`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores_users`
--

LOCK TABLES `proveedores_users` WRITE;
/*!40000 ALTER TABLE `proveedores_users` DISABLE KEYS */;
INSERT INTO `proveedores_users` VALUES (1,'PROV001','$2y$12$p9BKAL1j1wYMXS/0Ttd/MOIdWmcHiF/mv/uXCBYpwf2DjsCAVDKsC','102003240','Distribuidora Nacional SA de CV','MXN','Persona Moral',0,NULL,'3312345678',NULL,'contacto@distribuidora.com',NULL,NULL,1,NULL,0,94.00,88.00,91.00,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:09',NULL),(2,'PROV002','$2y$12$ExO3EpkmGXX0BqVS9ASllOWiVpWCdX7eddINus1.eWPzzXM9ln9M2','102003241','Materiales Industriales del Bajío','MXN','Persona Moral',0,NULL,'4771234567',NULL,'ventas@mibajio.com',NULL,NULL,1,NULL,0,78.00,82.00,80.00,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:09',NULL),(3,'PROV003','$2y$12$fLsVFdi836nWqUtcUvYhTeOq.LaTZFAqJPwaxll0KBOQqje6E3.v.','102003242','Juan Pérez López','MXN','Persona Física',0,NULL,'5551234567',NULL,'juan.perez@correo.com',NULL,NULL,1,NULL,0,65.00,70.00,67.50,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:09',NULL),(4,'said','$2y$12$hvfYVokMsPiSuqOhxzB2juzjdgZ8UYaj8/6zZrqRXMX.CFBXpi0gW','DEMO-SAID','Proveedor Demo Said','MXN','Persona Moral',0,NULL,'3300000001',NULL,'said@demo.salcom',NULL,NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:07',NULL),(5,'demo','$2y$12$.Lj.ETfXxLbl7neEp.d1tOmF3lv4xc1Tu.jfY2p5pZEKMMhW.mV7u','DEMO-001','Proveedor Demo','MXN','Persona Moral',0,NULL,'3300000002',NULL,'demo@demo.salcom',NULL,NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:07',NULL),(6,'Rebeca','$2y$12$xFY7wDAKjPQ3mQuG5uJjCOB2AK6a87UYs40dinLyAltgvRAQ1qeRK','DEMO-REBECA','Rebeca (Test)','MXN','Persona Moral',0,NULL,'3300000003',NULL,'rebeca.leon@framfoods.com.mx',NULL,NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:07',NULL),(7,'sinonboarding','$2y$12$k95xuP5gzgM0RdVB1SKGHOpevjct/dbQmszykuHthNQlwyUXyMzRC','DEMO-SINONB','Proveedor Sin Onboarding S.A. de C.V.','MXN','Persona Moral',0,'PSO010101XX1','3310000099',NULL,'sinonboarding@test.local',NULL,NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-10-07 19:39:07','2026-10-07 19:39:07',NULL);
/*!40000 ALTER TABLE `proveedores_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reembolsos_viaje`
--

DROP TABLE IF EXISTS `reembolsos_viaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reembolsos_viaje` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo_empleado` varchar(50) NOT NULL,
  `nombre_empleado` varchar(255) NOT NULL,
  `departamento` varchar(255) DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `fecha_regreso` date DEFAULT NULL,
  `pais_destino` varchar(255) NOT NULL,
  `moneda_destino` varchar(10) NOT NULL,
  `tipo_cambio` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `moneda_base` varchar(10) NOT NULL DEFAULT 'MXN',
  `gastos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`gastos`)),
  `total_moneda_local` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_moneda_base` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estatus` varchar(30) NOT NULL DEFAULT 'borrador',
  `archivo_comprobantes` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `notas_revision` text DEFAULT NULL,
  `enviado_at` timestamp NULL DEFAULT NULL,
  `aprobado_at` timestamp NULL DEFAULT NULL,
  `aprobado_por` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reembolsos_viaje_codigo_empleado_index` (`codigo_empleado`),
  KEY `reembolsos_viaje_estatus_index` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reembolsos_viaje`
--

LOCK TABLES `reembolsos_viaje` WRITE;
/*!40000 ALTER TABLE `reembolsos_viaje` DISABLE KEYS */;
/*!40000 ALTER TABLE `reembolsos_viaje` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_alta`
--

DROP TABLE IF EXISTS `solicitudes_alta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitudes_alta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` bigint(20) unsigned DEFAULT NULL,
  `tipo_persona` varchar(255) NOT NULL,
  `nombre_completo` varchar(255) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `apellido_paterno` varchar(255) DEFAULT NULL,
  `apellido_materno` varchar(255) DEFAULT NULL,
  `nombres` varchar(255) DEFAULT NULL,
  `calle` varchar(255) DEFAULT NULL,
  `num_exterior` varchar(255) DEFAULT NULL,
  `num_interior` varchar(255) DEFAULT NULL,
  `colonia` varchar(255) DEFAULT NULL,
  `municipio` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `telefono2` varchar(30) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `clabe` varchar(18) DEFAULT NULL,
  `cuenta` varchar(30) DEFAULT NULL,
  `banco` varchar(255) DEFAULT NULL,
  `docs_marcados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`docs_marcados`)),
  `nombre_firma` varchar(255) DEFAULT NULL,
  `estatus` varchar(255) NOT NULL DEFAULT 'pendiente',
  `notas_admin` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `solicitudes_alta_proveedor_id_index` (`proveedor_id`),
  KEY `solicitudes_alta_estatus_index` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_alta`
--

LOCK TABLES `solicitudes_alta` WRITE;
/*!40000 ALTER TABLE `solicitudes_alta` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_alta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_modificacion_datos`
--

DROP TABLE IF EXISTS `solicitudes_modificacion_datos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitudes_modificacion_datos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` bigint(20) unsigned NOT NULL,
  `campo` varchar(40) NOT NULL DEFAULT 'nombre',
  `valor_actual` varchar(255) DEFAULT NULL,
  `valor_propuesto` varchar(255) NOT NULL,
  `tipo_persona` varchar(40) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `estatus` varchar(30) NOT NULL DEFAULT 'pendiente',
  `archivo_cif` varchar(255) DEFAULT NULL,
  `archivo_acta` varchar(255) DEFAULT NULL,
  `resultado_ia` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resultado_ia`)),
  `notas` text DEFAULT NULL,
  `revisado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `solicitudes_modificacion_datos_proveedor_id_estatus_index` (`proveedor_id`,`estatus`),
  KEY `solicitudes_modificacion_datos_proveedor_id_index` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_modificacion_datos`
--

LOCK TABLES `solicitudes_modificacion_datos` WRITE;
/*!40000 ALTER TABLE `solicitudes_modificacion_datos` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_modificacion_datos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tracking_pedidos`
--

DROP TABLE IF EXISTS `tracking_pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracking_pedidos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint(20) unsigned NOT NULL,
  `estatus` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `usuario_responsable` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tracking_pedidos_pedido_id_index` (`pedido_id`),
  KEY `tracking_pedidos_estatus_index` (`estatus`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tracking_pedidos`
--

LOCK TABLES `tracking_pedidos` WRITE;
/*!40000 ALTER TABLE `tracking_pedidos` DISABLE KEYS */;
INSERT INTO `tracking_pedidos` VALUES (1,5,'validacion','Pedido recibido y en validación','2026-03-12 09:00:00','Sistema','2026-10-07 19:39:09','2026-10-07 19:39:09'),(2,5,'procesando','Pedido aprobado, en producción','2026-03-13 11:30:00','ADMIN001','2026-10-07 19:39:09','2026-10-07 19:39:09'),(3,5,'enviado','Enviado vía Estafeta — Guía: 6024958372615','2026-03-18 16:00:00','ADMIN001','2026-10-07 19:39:09','2026-10-07 19:39:09'),(4,6,'validacion','Pedido recibido','2026-04-02 10:00:00','Sistema','2026-10-07 19:39:09','2026-10-07 19:39:09'),(5,6,'procesando','En producción — lote #4521','2026-04-03 08:15:00','ADMIN001','2026-10-07 19:39:09','2026-10-07 19:39:09');
/*!40000 ALTER TABLE `tracking_pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Test User','test@example.com','2026-10-07 19:39:04','$2y$12$7xwbMshCF5JcYddlsagPeO4P7DBbsFLRuywpnsUY0y..pNeOO6ela','C2lyEbn2B2','2026-10-07 19:39:05','2026-10-07 19:39:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'salcom20'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-07 13:40:19
