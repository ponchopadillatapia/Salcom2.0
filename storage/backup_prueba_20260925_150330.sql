-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: salcom20
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
INSERT INTO `facturas` VALUES (1,'CFDI-A-001230',NULL,'CLI-2026-001',NULL,NULL,0,NULL,36206.90,5793.10,NULL,NULL,42000.00,0.00,'pagada','2025-12-15',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(2,'CFDI-A-001231',NULL,'CLI-2026-002',NULL,NULL,0,NULL,7327.59,1172.41,NULL,NULL,8500.00,0.00,'pagada','2026-01-03',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(3,'CFDI-A-001235',NULL,'CLI-2026-001',NULL,NULL,0,NULL,54310.34,8689.66,NULL,NULL,63000.00,0.00,'pendiente','2026-02-10',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(4,'CFDI-A-001236',NULL,'CLI-2026-001',NULL,NULL,0,NULL,65948.28,10551.72,NULL,NULL,76500.00,0.00,'pendiente','2026-04-12',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(5,'CFDI-A-001240',NULL,'CLI-2026-001',NULL,NULL,0,NULL,58620.69,9379.31,NULL,NULL,68000.00,0.00,'pendiente','2026-05-02',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(6,'CFDI-A-001241',NULL,'CLI-2026-002',NULL,NULL,0,NULL,8275.86,1324.14,NULL,NULL,9600.00,0.00,'pendiente','2026-05-15',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(7,'CFDI-P-000501',NULL,NULL,'102003240',NULL,0,NULL,12500.00,2000.00,NULL,NULL,14500.00,0.00,'pagada','2026-03-01',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL),(8,'CFDI-P-000502',NULL,NULL,'102003241',NULL,0,NULL,8200.00,1312.00,NULL,NULL,9512.00,0.00,'pendiente','2026-04-20',NULL,NULL,NULL,NULL,NULL,'2026-04-28 23:52:21','2026-04-28 23:52:21',NULL);
/*!40000 ALTER TABLE `facturas` ENABLE KEYS */;
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
  `moneda` varchar(20) DEFAULT NULL,
  `tipo_persona` varchar(255) DEFAULT NULL,
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
  KEY `proveedores_users_codigo_index` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores_users`
--

LOCK TABLES `proveedores_users` WRITE;
/*!40000 ALTER TABLE `proveedores_users` DISABLE KEYS */;
INSERT INTO `proveedores_users` VALUES (1,'alfonsopadilla391@gmail.com','$2y$12$m2pnKIETGDiyqxjvBwYE0.1mpE7caRgcoicqYOlOTd8lvdH9LVdEy',NULL,'Alfonso Yahir',NULL,'Fisica',NULL,'3334574344',NULL,'alfonsopadilla391@gmail.com','2026-04-01 02:21:33',NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-04-01 02:21:33','2026-04-28 01:14:19','2026-04-28 01:14:19'),(2,'alfonsop@gmail.com','$2y$12$Sw.EetHUQONBoNXDY0etoemRgR9JCLtPTxfa2AcS9eakau0kvVT6a',NULL,'Alfonso Yahir',NULL,'Fisica',NULL,'3334574333',NULL,'alfonsop@gmail.com','2026-04-09 02:19:50',NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-04-09 02:19:50','2026-04-09 02:19:50',NULL),(3,'alfonso@gmail.com','$2y$12$/JhgyIc.jv3B.8vT.QYk0u4.scmLGGjKgNNxqL4LC5BCigijox8Ey',NULL,'Alfonso Yahir',NULL,'Persona F├¡sica',NULL,'3334574333',NULL,'alfonso@gmail.com','2026-04-13 10:29:32',NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-04-13 10:29:32','2026-04-28 23:56:47','2026-04-28 23:56:47'),(4,'ponchopad7@gmail.com','$2y$12$vaa2yx4G0tFp7IligDwGF.T/f2VDqc6g2b93ZpnRY.C9yUIlNxgWO',NULL,'poncho',NULL,'Persona F├¡sica',NULL,'3334574347',NULL,'ponchopad7@gmail.com','2026-04-16 23:05:30',NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-04-16 23:05:30','2026-04-28 23:56:41','2026-04-28 23:56:41'),(5,'PROV001','$2y$12$KsxF4jhu1D56QITLeocjwOtQSMfpzjzp.pB2PC.BpjoWbe.Pbz6B.','102003240','Distribuidora Nacional SA de CV',NULL,'Persona Moral',NULL,'3312345678',NULL,'contacto@distribuidora.com','2026-04-28 23:54:32',NULL,1,NULL,0,94.00,88.00,91.00,0,NULL,NULL,'2026-04-28 23:54:32','2026-04-28 23:54:44',NULL),(6,'PROV002','$2y$12$UbZxcRfkcw/6h.2qKYZf1ezAga7djuWp.UxyWKZHaMa55AM0mKTry','102003241','Materiales Industriales del Baj├¡o',NULL,'Persona Moral',NULL,'4771234567',NULL,'ventas@mibajio.com','2026-04-28 23:54:32',NULL,1,NULL,0,78.00,82.00,80.00,0,NULL,NULL,'2026-04-28 23:54:32','2026-04-28 23:54:44',NULL),(7,'PROV003','$2y$12$AIZAc/wVxnQmADPpQ16GQ.jUL62JG2/uRjeLyA2RbcozX9KDusTve','102003242','Juan P├®rez L├│pez',NULL,'Persona F├¡sica',NULL,'5551234567',NULL,'juan.perez@correo.com','2026-04-28 23:54:32',NULL,1,NULL,0,65.00,70.00,67.50,0,NULL,NULL,'2026-04-28 23:54:32','2026-04-28 23:54:44',NULL),(8,'poncho@salcom.com','$2y$12$oQtwe4nbsdwuSFZXrICree19JI8lsGFf.5QQtKe39kp..mtzniffG',NULL,'poncho',NULL,'Persona F├¡sica',NULL,'3334574347','{\"fecha\":\"2026-07-16\",\"tipo_persona\":\"Persona F\\u00edsica\",\"calle\":\"Loma Central\",\"num_exterior\":\"102\",\"num_interior\":null,\"colonia\":null,\"municipio\":null,\"estado\":\"Jalisco\",\"ciudad\":null,\"pais\":\"M\\u00e9xico\",\"cp\":\"45645\",\"telefono\":\"3334574347\",\"celular\":null,\"telefono2\":null,\"extension\":null,\"correo\":\"poncho@salcom.com\",\"clabe\":\"201321643545613469\",\"cuenta\":\"618468846848494\",\"banco\":\"Padilla Tapia\",\"docs\":[\"id_contribuyente\",\"constancia_fiscal\",\"opinion_cumplimiento\",\"caratula_banco\"],\"nombre_firma\":\"alfonso padilla\",\"apellido_paterno\":\"Padilla\",\"apellido_materno\":\"Tapia\",\"nombres\":\"Alfonso Yahir\",\"razon_social\":null,\"tipo_clave\":\"fisica\",\"nombre_esperado\":\"Padilla Tapia Alfonso Yahir\"}','poncho@salcom.com','2026-07-09 19:27:37',NULL,1,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-07-09 19:27:37','2026-07-16 21:10:03',NULL),(9,'alfonso.padilla.22s@utzmg.edu.mx','$2y$12$DXeM00Ssj0Irvg8Ko97el.A6mkOxZ9oHTm2rOJIDvjl0sTQE4NVOK',NULL,'Alfonso Padilla',NULL,'Persona F├¡sica',NULL,'3334574347','{\"fecha\":\"2026-07-18\",\"tipo_persona\":\"Persona F\\u00edsica\",\"calle\":\"Loma Central\",\"num_exterior\":\"102\",\"num_interior\":\"102\",\"colonia\":\"Santa Anita\",\"municipio\":\"Santa Anita\",\"estado\":\"Jalisco\",\"ciudad\":\"Santa Anita\",\"pais\":\"M\\u00e9xico\",\"cp\":\"45645\",\"telefono\":\"3334574347\",\"celular\":null,\"telefono2\":null,\"extension\":null,\"correo\":\"alfonso.padilla.22s@utzmg.edu.mx\",\"clabe\":\"201321643545613469\",\"cuenta\":\"618468846848494\",\"banco\":\"Banregio\",\"docs\":[\"id_contribuyente\",\"constancia_fiscal\",\"opinion_cumplimiento\",\"caratula_banco\"],\"nombre_firma\":\"alfonso padilla\",\"apellido_paterno\":\"Padilla\",\"apellido_materno\":\"Tapia\",\"nombres\":\"Alfonso Yahir\",\"razon_social\":null,\"tipo_clave\":\"fisica\",\"nombre_esperado\":\"Padilla Tapia Alfonso Yahir\"}','alfonso.padilla.22s@utzmg.edu.mx','2026-07-18 17:56:57',NULL,0,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-07-18 17:56:57','2026-07-18 18:04:01',NULL),(10,'danmonsenior@gmail.com','$2y$12$.Gv77l.c5p9Zr0LckUBmJuW2XYem39ZiLjMp82LkDUGYEZE/iviyW',NULL,'dan',NULL,'Persona F├¡sica',NULL,'3334574333','{\"fecha\":\"2026-07-21\",\"tipo_persona\":\"Persona F\\u00edsica\",\"calle\":\"Loma Central\",\"num_exterior\":\"102\",\"num_interior\":\"102\",\"colonia\":\"San Agustin\",\"municipio\":\"Santa Anita\",\"estado\":\"Jalisco\",\"ciudad\":\"Santa Anita\",\"pais\":\"M\\u00e9xico\",\"cp\":\"45645\",\"telefono\":\"3334574347\",\"celular\":\"3344958503\",\"telefono2\":null,\"extension\":\"52\",\"correo\":\"danmonsenior@gmail.com\",\"clabe\":\"014180655097399069\",\"cuenta\":\"65509739906\",\"banco\":\"Santander\",\"docs\":[\"id_rep_legal\",\"id_contribuyente\",\"constancia_fiscal\",\"opinion_cumplimiento\",\"caratula_banco\"],\"nombre_firma\":\"DAN monsenior\",\"apellido_paterno\":\"Tellez\",\"apellido_materno\":\"Gonzalez\",\"nombres\":\"Carlos Isaac\",\"razon_social\":null,\"tipo_clave\":\"fisica\",\"nombre_esperado\":\"Tellez Gonzalez Carlos Isaac\"}','danmonsenior@gmail.com','2026-07-18 19:01:34',NULL,0,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-07-18 19:01:34','2026-07-27 18:50:52',NULL),(11,'proveedor.test','$2y$12$rm2AF5UzCfvRfscUqAdCZO.2ItOA/9QDlNRu7lpu1H38lvK6MsBO2','PROV-TEST-001','Proveedor de Prueba S.A. de C.V.',NULL,'Persona Moral',NULL,'3312345678',NULL,'proveedor@test.com','2026-08-07 21:47:06',NULL,1,NULL,0,85.00,90.00,87.50,0,NULL,NULL,'2026-08-07 21:47:06','2026-08-07 21:47:06',NULL),(12,'alfonso.padilla','$2y$12$zgjUmtpvwzBwp6v33BTrnOASG4Wi5xIAqEyvJu1ULk17f2mGphcbC',NULL,'Padilla Tapia Alfonso Yahir','DOLLAR','Persona F├¡sica',NULL,'3334574333','{\"fecha\":\"2026-08-11\",\"tipo_persona\":\"Persona F\\u00edsica\",\"calle\":\"Loma Central\",\"num_exterior\":\"102\",\"num_interior\":\"102\",\"colonia\":\"El Capulin\",\"municipio\":\"Tlajomulco de Z\\u00fa\\u00f1iga\",\"estado\":\"Jalisco\",\"ciudad\":\"Tlajomulco de Z\\u00fa\\u00f1iga\",\"pais\":\"M\\u00e9xico\",\"cp\":\"45665\",\"telefono\":\"3334574333\",\"celular\":\"3334574347\",\"telefono2\":null,\"extension\":\"52\",\"correo\":\"alfonsopadil@outlook.com\",\"rfc\":\"PATA030412U01\",\"clabe\":\"014180655097399069\",\"cuenta\":\"65509739906\",\"banco\":\"Santander\",\"docs\":[\"id_rep_legal\",\"id_contribuyente\",\"constancia_fiscal\",\"opinion_cumplimiento\",\"caratula_banco\"],\"nombre_firma\":\"Carlos Isaac Tellez Gonzalez\",\"clabe_usd\":null,\"cuenta_usd\":null,\"banco_usd\":null,\"apellido_paterno\":\"Padilla\",\"apellido_materno\":\"Tapia\",\"nombres\":\"Alfonso Yahir\",\"razon_social\":null,\"tipo_clave\":\"fisica\",\"nombre_esperado\":\"Padilla Tapia Alfonso Yahir\"}','alfonsopadil@outlook.com','2026-08-11 22:26:13',NULL,0,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-08-11 22:26:13','2026-08-13 21:06:27',NULL),(13,'yahir.padilla','$2y$12$x/OABqgWUXjBZtXTq7AEG.GfUtwPpnUgQ6GGtmZmv3EXjNdA5pYf2',NULL,'Yahir Padilla Tapia',NULL,'Persona F├¡sica',NULL,'3334574347','{\"rfc\":\"PATA030412U01\",\"nombres\":\"Yahir\",\"apellido_paterno\":\"Padilla\",\"apellido_materno\":\"Tapia\",\"tipo_persona\":\"Persona F├¡sica\",\"tipo_clave\":\"fisica\"}','y141126.05@gmail.com','2026-08-14 18:31:46',NULL,0,NULL,0,0.00,0.00,0.00,0,NULL,NULL,'2026-08-14 18:31:46','2026-08-14 18:31:46',NULL);
/*!40000 ALTER TABLE `proveedores_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-25 15:03:30
