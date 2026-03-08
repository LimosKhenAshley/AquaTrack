

CREATE TABLE `api_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `api_tokens` VALUES ('1','5','9ae18ad12a1a4c6b4325cb9f723808f8452ffe7047896d927dd2a31fc4424984','2026-01-11 19:19:44','2026-01-12 19:19:44');


CREATE TABLE `areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `area_name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `areas` VALUES ('1','Centro',NULL);
INSERT INTO `areas` VALUES ('2','Laud',NULL);


CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=434 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `audit_logs` VALUES ('1',NULL,'LOGIN','User logged in','::1','2026-01-31 20:00:39');
INSERT INTO `audit_logs` VALUES ('2',NULL,'LOGIN','User logged in','::1','2026-01-31 20:01:30');
INSERT INTO `audit_logs` VALUES ('3',NULL,'LOGIN','User logged in','::1','2026-01-31 20:01:30');
INSERT INTO `audit_logs` VALUES ('4',NULL,'LOGIN','User logged in','::1','2026-01-31 20:01:39');
INSERT INTO `audit_logs` VALUES ('5',NULL,'LOGIN','User logged in','::1','2026-01-31 20:01:44');
INSERT INTO `audit_logs` VALUES ('6',NULL,'LOGIN','User logged in','::1','2026-01-31 20:01:49');
INSERT INTO `audit_logs` VALUES ('7',NULL,'LOGIN','User logged in','::1','2026-01-31 20:01:53');
INSERT INTO `audit_logs` VALUES ('8','24','ADD_READING','Customer ID: 12 | Reading: 90.54','::1','2026-01-31 20:03:16');
INSERT INTO `audit_logs` VALUES ('9',NULL,'LOGIN','User logged in','::1','2026-01-31 20:03:24');
INSERT INTO `audit_logs` VALUES ('10',NULL,'LOGIN','User logged in','::1','2026-01-31 20:03:32');
INSERT INTO `audit_logs` VALUES ('11',NULL,'LOGIN','User logged in','::1','2026-01-31 20:22:55');
INSERT INTO `audit_logs` VALUES ('12',NULL,'LOGIN','User logged in','::1','2026-01-31 20:23:01');
INSERT INTO `audit_logs` VALUES ('13',NULL,'LOGIN','User logged in','::1','2026-01-31 20:23:25');
INSERT INTO `audit_logs` VALUES ('14',NULL,'LOGIN','User logged in','::1','2026-02-01 09:28:07');
INSERT INTO `audit_logs` VALUES ('15',NULL,'LOGIN','User logged in','::1','2026-02-01 09:28:11');
INSERT INTO `audit_logs` VALUES ('16',NULL,'LOGIN','User logged in','::1','2026-02-01 09:28:13');
INSERT INTO `audit_logs` VALUES ('17',NULL,'LOGIN','User logged in','::1','2026-02-01 09:28:17');
INSERT INTO `audit_logs` VALUES ('18',NULL,'LOGIN','User logged in','::1','2026-02-01 10:14:40');
INSERT INTO `audit_logs` VALUES ('19','24','LOGIN','User logged in','::1','2026-02-01 10:47:39');
INSERT INTO `audit_logs` VALUES ('20','24','LOGIN','User logged in','::1','2026-02-01 10:54:46');
INSERT INTO `audit_logs` VALUES ('21','5','LOGIN','User logged in','::1','2026-02-01 10:56:03');
INSERT INTO `audit_logs` VALUES ('22','5','LOGIN','User logged in','::1','2026-02-01 10:56:06');
INSERT INTO `audit_logs` VALUES ('23',NULL,'LOGIN','User logged in','::1','2026-02-01 11:06:20');
INSERT INTO `audit_logs` VALUES ('24',NULL,'LOGIN','User logged in','::1','2026-02-01 11:06:25');
INSERT INTO `audit_logs` VALUES ('25',NULL,'LOGIN','User logged in','::1','2026-02-01 11:06:35');
INSERT INTO `audit_logs` VALUES ('26',NULL,'LOGIN','User logged in','::1','2026-02-01 11:07:00');
INSERT INTO `audit_logs` VALUES ('27',NULL,'LOGIN','User logged in','::1','2026-02-01 11:07:16');
INSERT INTO `audit_logs` VALUES ('28',NULL,'LOGIN','User logged in','::1','2026-02-01 11:07:22');
INSERT INTO `audit_logs` VALUES ('29',NULL,'LOGIN','User logged in','::1','2026-02-01 11:12:44');
INSERT INTO `audit_logs` VALUES ('30',NULL,'LOGIN','User logged in','::1','2026-02-01 11:13:18');
INSERT INTO `audit_logs` VALUES ('31',NULL,'LOGIN','User logged in','::1','2026-02-01 11:13:23');
INSERT INTO `audit_logs` VALUES ('32',NULL,'LOGIN','User logged in','::1','2026-02-01 11:13:33');
INSERT INTO `audit_logs` VALUES ('33',NULL,'LOGIN','User logged in','::1','2026-02-01 11:13:39');
INSERT INTO `audit_logs` VALUES ('34',NULL,'LOGIN','User logged in','::1','2026-02-01 11:20:22');
INSERT INTO `audit_logs` VALUES ('35',NULL,'LOGIN','User logged in','::1','2026-02-01 11:20:27');
INSERT INTO `audit_logs` VALUES ('36',NULL,'LOGIN','User logged in','::1','2026-02-01 11:20:33');
INSERT INTO `audit_logs` VALUES ('37',NULL,'LOGIN','User logged in','::1','2026-02-01 11:20:38');
INSERT INTO `audit_logs` VALUES ('38',NULL,'LOGIN','User logged in','::1','2026-02-01 11:23:44');
INSERT INTO `audit_logs` VALUES ('39',NULL,'LOGIN','User logged in','::1','2026-02-01 11:23:50');
INSERT INTO `audit_logs` VALUES ('40',NULL,'LOGIN','User logged in','::1','2026-02-01 11:24:03');
INSERT INTO `audit_logs` VALUES ('41',NULL,'LOGIN','User logged in','::1','2026-02-01 11:26:07');
INSERT INTO `audit_logs` VALUES ('42','24','ADD_READING','Customer ID: 13 | Reading: 80.05','::1','2026-02-01 11:26:59');
INSERT INTO `audit_logs` VALUES ('43',NULL,'LOGIN','User logged in','::1','2026-02-01 11:27:05');
INSERT INTO `audit_logs` VALUES ('44',NULL,'LOGIN','User logged in','::1','2026-02-01 11:27:10');
INSERT INTO `audit_logs` VALUES ('45',NULL,'LOGIN','User logged in','::1','2026-02-02 11:31:39');
INSERT INTO `audit_logs` VALUES ('46',NULL,'LOGIN','User logged in','::1','2026-02-02 11:31:46');
INSERT INTO `audit_logs` VALUES ('47',NULL,'LOGIN','User logged in','::1','2026-02-02 12:50:40');
INSERT INTO `audit_logs` VALUES ('48',NULL,'LOGIN','User logged in','::1','2026-02-02 12:50:46');
INSERT INTO `audit_logs` VALUES ('49',NULL,'LOGIN','User logged in','::1','2026-02-02 12:51:08');
INSERT INTO `audit_logs` VALUES ('50',NULL,'LOGIN','User logged in','::1','2026-02-02 12:51:21');
INSERT INTO `audit_logs` VALUES ('51',NULL,'LOGIN','User logged in','::1','2026-02-02 13:29:57');
INSERT INTO `audit_logs` VALUES ('52',NULL,'LOGIN','User logged in','::1','2026-02-02 13:30:01');
INSERT INTO `audit_logs` VALUES ('53',NULL,'LOGIN','User logged in','::1','2026-02-02 13:30:18');
INSERT INTO `audit_logs` VALUES ('54',NULL,'LOGIN','User logged in','::1','2026-02-02 13:30:23');
INSERT INTO `audit_logs` VALUES ('55',NULL,'LOGIN','User logged in','::1','2026-02-02 13:30:40');
INSERT INTO `audit_logs` VALUES ('56',NULL,'LOGIN','User logged in','::1','2026-02-02 13:42:05');
INSERT INTO `audit_logs` VALUES ('57',NULL,'LOGIN','User logged in','::1','2026-02-02 13:42:15');
INSERT INTO `audit_logs` VALUES ('58','24','ADD_READING','Customer ID: 2 | Reading: 200.25','::1','2026-02-02 13:43:01');
INSERT INTO `audit_logs` VALUES ('59',NULL,'LOGIN','User logged in','::1','2026-02-02 13:43:15');
INSERT INTO `audit_logs` VALUES ('60',NULL,'LOGIN','User logged in','::1','2026-02-02 13:43:22');
INSERT INTO `audit_logs` VALUES ('61',NULL,'LOGIN','User logged in','::1','2026-02-02 13:47:18');
INSERT INTO `audit_logs` VALUES ('62',NULL,'LOGIN','User logged in','::1','2026-02-02 13:47:26');
INSERT INTO `audit_logs` VALUES ('63',NULL,'LOGIN','User logged in','::1','2026-02-02 13:47:33');
INSERT INTO `audit_logs` VALUES ('64',NULL,'LOGIN','User logged in','::1','2026-02-02 13:47:45');
INSERT INTO `audit_logs` VALUES ('65',NULL,'LOGIN','User logged in','::1','2026-02-02 13:47:49');
INSERT INTO `audit_logs` VALUES ('66',NULL,'LOGIN','User logged in','::1','2026-02-02 13:47:56');
INSERT INTO `audit_logs` VALUES ('67',NULL,'LOGIN','User logged in','::1','2026-02-02 13:48:01');
INSERT INTO `audit_logs` VALUES ('68',NULL,'LOGIN','User logged in','::1','2026-02-02 13:51:49');
INSERT INTO `audit_logs` VALUES ('69',NULL,'LOGIN','User logged in','::1','2026-02-02 13:51:58');
INSERT INTO `audit_logs` VALUES ('70',NULL,'LOGIN','User logged in','::1','2026-02-02 13:54:13');
INSERT INTO `audit_logs` VALUES ('71',NULL,'LOGIN','User logged in','::1','2026-02-02 13:54:17');
INSERT INTO `audit_logs` VALUES ('72',NULL,'LOGIN','User logged in','::1','2026-02-02 14:02:25');
INSERT INTO `audit_logs` VALUES ('73',NULL,'LOGIN','User logged in','::1','2026-02-02 14:02:33');
INSERT INTO `audit_logs` VALUES ('74','24','ADD_READING','Customer ID: 10 | Reading: 100.25','::1','2026-02-02 14:03:08');
INSERT INTO `audit_logs` VALUES ('75',NULL,'LOGIN','User logged in','::1','2026-02-02 14:03:15');
INSERT INTO `audit_logs` VALUES ('76',NULL,'LOGIN','User logged in','::1','2026-02-02 14:03:25');
INSERT INTO `audit_logs` VALUES ('77',NULL,'LOGIN','User logged in','::1','2026-02-02 14:04:07');
INSERT INTO `audit_logs` VALUES ('78',NULL,'LOGIN','User logged in','::1','2026-02-02 14:04:12');
INSERT INTO `audit_logs` VALUES ('79',NULL,'LOGIN','User logged in','::1','2026-02-02 14:04:17');
INSERT INTO `audit_logs` VALUES ('80',NULL,'LOGIN','User logged in','::1','2026-02-02 14:10:51');
INSERT INTO `audit_logs` VALUES ('81',NULL,'LOGIN','User logged in','::1','2026-02-02 14:10:56');
INSERT INTO `audit_logs` VALUES ('82','24','ADD_READING','Customer ID: 10 | Reading: 100.25','::1','2026-02-02 14:11:24');
INSERT INTO `audit_logs` VALUES ('83',NULL,'LOGIN','User logged in','::1','2026-02-02 14:11:28');
INSERT INTO `audit_logs` VALUES ('84',NULL,'LOGIN','User logged in','::1','2026-02-02 14:11:35');
INSERT INTO `audit_logs` VALUES ('85',NULL,'LOGIN','User logged in','::1','2026-02-02 14:54:25');
INSERT INTO `audit_logs` VALUES ('86',NULL,'LOGIN','User logged in','::1','2026-02-02 14:54:30');
INSERT INTO `audit_logs` VALUES ('87','24','ADD_READING','Customer ID: 3 | Reading: 220.65','::1','2026-02-02 14:55:35');
INSERT INTO `audit_logs` VALUES ('88',NULL,'LOGIN','User logged in','::1','2026-02-02 14:55:38');
INSERT INTO `audit_logs` VALUES ('89',NULL,'LOGIN','User logged in','::1','2026-02-02 14:55:43');
INSERT INTO `audit_logs` VALUES ('90',NULL,'LOGIN','User logged in','::1','2026-02-02 14:56:02');
INSERT INTO `audit_logs` VALUES ('91',NULL,'LOGIN','User logged in','::1','2026-02-03 19:01:33');
INSERT INTO `audit_logs` VALUES ('92',NULL,'LOGIN','User logged in','::1','2026-02-03 19:01:41');
INSERT INTO `audit_logs` VALUES ('93',NULL,'LOGIN','User logged in','::1','2026-02-04 20:43:19');
INSERT INTO `audit_logs` VALUES ('94',NULL,'LOGIN','User logged in','::1','2026-02-04 20:43:25');
INSERT INTO `audit_logs` VALUES ('95',NULL,'LOGIN','User logged in','::1','2026-02-04 20:43:46');
INSERT INTO `audit_logs` VALUES ('96',NULL,'LOGIN','User logged in','::1','2026-02-04 20:43:51');
INSERT INTO `audit_logs` VALUES ('97',NULL,'LOGIN','User logged in','::1','2026-02-04 20:44:28');
INSERT INTO `audit_logs` VALUES ('98',NULL,'LOGIN','User logged in','::1','2026-02-04 20:44:32');
INSERT INTO `audit_logs` VALUES ('99',NULL,'LOGIN','User logged in','::1','2026-02-04 20:44:42');
INSERT INTO `audit_logs` VALUES ('100',NULL,'LOGIN','User logged in','::1','2026-02-04 20:44:47');
INSERT INTO `audit_logs` VALUES ('101','24','ADD_READING','Customer ID: 10 | Reading: 90.5','::1','2026-02-04 20:45:15');
INSERT INTO `audit_logs` VALUES ('102',NULL,'LOGIN','User logged in','::1','2026-02-04 20:46:45');
INSERT INTO `audit_logs` VALUES ('103',NULL,'LOGIN','User logged in','::1','2026-02-04 20:46:50');
INSERT INTO `audit_logs` VALUES ('104',NULL,'LOGIN','User logged in','::1','2026-02-07 19:09:52');
INSERT INTO `audit_logs` VALUES ('105',NULL,'LOGIN','User logged in','::1','2026-02-07 19:10:46');
INSERT INTO `audit_logs` VALUES ('106',NULL,'LOGIN','User logged in','::1','2026-02-07 19:10:52');
INSERT INTO `audit_logs` VALUES ('107',NULL,'LOGIN','User logged in','::1','2026-02-07 19:20:28');
INSERT INTO `audit_logs` VALUES ('108',NULL,'LOGIN','User logged in','::1','2026-02-07 19:54:50');
INSERT INTO `audit_logs` VALUES ('109',NULL,'LOGIN','User logged in','::1','2026-02-07 20:00:47');
INSERT INTO `audit_logs` VALUES ('110',NULL,'LOGIN','User logged in','::1','2026-02-07 20:01:51');
INSERT INTO `audit_logs` VALUES ('111',NULL,'LOGIN','User logged in','::1','2026-02-07 20:02:12');
INSERT INTO `audit_logs` VALUES ('112',NULL,'LOGIN','User logged in','::1','2026-02-07 20:02:47');
INSERT INTO `audit_logs` VALUES ('113',NULL,'LOGIN','User logged in','::1','2026-02-07 20:02:56');
INSERT INTO `audit_logs` VALUES ('114',NULL,'LOGIN','User logged in','::1','2026-02-07 20:03:14');
INSERT INTO `audit_logs` VALUES ('115',NULL,'LOGIN','User logged in','::1','2026-02-07 20:27:12');
INSERT INTO `audit_logs` VALUES ('116','5','LOGIN','User logged in','::1','2026-02-07 20:43:05');
INSERT INTO `audit_logs` VALUES ('117','5','LOGIN','User logged in','::1','2026-02-07 20:43:05');
INSERT INTO `audit_logs` VALUES ('118','5','LOGIN','User logged in','::1','2026-02-07 20:43:14');
INSERT INTO `audit_logs` VALUES ('119',NULL,'LOGIN','User logged in','::1','2026-02-09 18:53:15');
INSERT INTO `audit_logs` VALUES ('120',NULL,'LOGIN','User logged in','::1','2026-02-09 18:53:22');
INSERT INTO `audit_logs` VALUES ('121',NULL,'LOGIN','User logged in','::1','2026-02-09 18:57:09');
INSERT INTO `audit_logs` VALUES ('122',NULL,'LOGIN','User logged in','::1','2026-02-09 19:51:46');
INSERT INTO `audit_logs` VALUES ('123',NULL,'LOGIN','User logged in','::1','2026-02-09 19:51:46');
INSERT INTO `audit_logs` VALUES ('124',NULL,'LOGIN','User logged in','::1','2026-02-09 19:51:55');
INSERT INTO `audit_logs` VALUES ('125',NULL,'LOGIN','User logged in','::1','2026-02-09 19:53:20');
INSERT INTO `audit_logs` VALUES ('126',NULL,'LOGIN','User logged in','::1','2026-02-09 19:54:54');
INSERT INTO `audit_logs` VALUES ('127',NULL,'LOGIN','User logged in','::1','2026-02-09 20:12:08');
INSERT INTO `audit_logs` VALUES ('128',NULL,'LOGIN','User logged in','::1','2026-02-09 20:12:20');
INSERT INTO `audit_logs` VALUES ('129',NULL,'LOGIN','User logged in','::1','2026-02-09 20:12:26');
INSERT INTO `audit_logs` VALUES ('130',NULL,'LOGIN','User logged in','::1','2026-02-09 20:28:58');
INSERT INTO `audit_logs` VALUES ('131',NULL,'LOGIN','User logged in','::1','2026-02-10 14:02:09');
INSERT INTO `audit_logs` VALUES ('132',NULL,'LOGIN','User logged in','::1','2026-02-10 14:02:16');
INSERT INTO `audit_logs` VALUES ('133',NULL,'LOGIN','User logged in','::1','2026-02-10 14:10:40');
INSERT INTO `audit_logs` VALUES ('134',NULL,'LOGIN','User logged in','::1','2026-02-10 14:10:48');
INSERT INTO `audit_logs` VALUES ('135',NULL,'LOGIN','User logged in','::1','2026-02-10 14:11:06');
INSERT INTO `audit_logs` VALUES ('136',NULL,'LOGIN','User logged in','::1','2026-02-10 14:11:11');
INSERT INTO `audit_logs` VALUES ('137',NULL,'LOGIN','User logged in','::1','2026-02-10 14:11:36');
INSERT INTO `audit_logs` VALUES ('138',NULL,'LOGIN','User logged in','::1','2026-02-10 14:11:40');
INSERT INTO `audit_logs` VALUES ('139',NULL,'LOGIN','User logged in','::1','2026-02-10 15:46:14');
INSERT INTO `audit_logs` VALUES ('140',NULL,'LOGIN','User logged in','::1','2026-02-10 15:46:18');
INSERT INTO `audit_logs` VALUES ('141',NULL,'LOGIN','User logged in','::1','2026-02-10 15:47:49');
INSERT INTO `audit_logs` VALUES ('142',NULL,'LOGIN','User logged in','::1','2026-02-13 18:59:31');
INSERT INTO `audit_logs` VALUES ('143',NULL,'LOGIN','User logged in','::1','2026-02-13 19:02:10');
INSERT INTO `audit_logs` VALUES ('144',NULL,'LOGIN','User logged in','::1','2026-02-13 19:07:28');
INSERT INTO `audit_logs` VALUES ('145',NULL,'LOGIN','User logged in','::1','2026-02-13 19:07:34');
INSERT INTO `audit_logs` VALUES ('146',NULL,'LOGIN','User logged in','::1','2026-02-13 19:08:44');
INSERT INTO `audit_logs` VALUES ('147',NULL,'LOGIN','User logged in','::1','2026-02-13 19:08:49');
INSERT INTO `audit_logs` VALUES ('148',NULL,'LOGIN','User logged in','::1','2026-02-13 19:22:19');
INSERT INTO `audit_logs` VALUES ('149',NULL,'LOGIN','User logged in','::1','2026-02-13 19:22:25');
INSERT INTO `audit_logs` VALUES ('150',NULL,'LOGIN','User logged in','::1','2026-02-13 19:22:41');
INSERT INTO `audit_logs` VALUES ('151',NULL,'LOGIN','User logged in','::1','2026-02-13 19:22:46');
INSERT INTO `audit_logs` VALUES ('152',NULL,'LOGIN','User logged in','::1','2026-02-13 19:43:25');
INSERT INTO `audit_logs` VALUES ('153',NULL,'LOGIN','User logged in','::1','2026-02-13 19:43:28');
INSERT INTO `audit_logs` VALUES ('154',NULL,'LOGIN','User logged in','::1','2026-02-13 19:44:28');
INSERT INTO `audit_logs` VALUES ('155',NULL,'LOGIN','User logged in','::1','2026-02-13 19:44:33');
INSERT INTO `audit_logs` VALUES ('156',NULL,'LOGIN','User logged in','::1','2026-02-13 20:05:47');
INSERT INTO `audit_logs` VALUES ('157',NULL,'LOGIN','User logged in','::1','2026-02-13 20:05:53');
INSERT INTO `audit_logs` VALUES ('158',NULL,'LOGIN','User logged in','::1','2026-02-13 20:05:58');
INSERT INTO `audit_logs` VALUES ('159',NULL,'LOGIN','User logged in','::1','2026-02-13 20:07:33');
INSERT INTO `audit_logs` VALUES ('160','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 20:19:40');
INSERT INTO `audit_logs` VALUES ('161',NULL,'LOGIN','User logged in','::1','2026-02-13 20:19:50');
INSERT INTO `audit_logs` VALUES ('162',NULL,'LOGIN','User logged in','::1','2026-02-13 20:19:55');
INSERT INTO `audit_logs` VALUES ('163',NULL,'LOGIN','User logged in','::1','2026-02-13 20:22:25');
INSERT INTO `audit_logs` VALUES ('164',NULL,'LOGIN','User logged in','::1','2026-02-13 20:22:31');
INSERT INTO `audit_logs` VALUES ('165',NULL,'LOGIN','User logged in','::1','2026-02-13 20:22:56');
INSERT INTO `audit_logs` VALUES ('166',NULL,'LOGIN','User logged in','::1','2026-02-13 20:23:01');
INSERT INTO `audit_logs` VALUES ('167',NULL,'LOGIN','User logged in','::1','2026-02-13 20:31:26');
INSERT INTO `audit_logs` VALUES ('168',NULL,'LOGIN','User logged in','::1','2026-02-13 20:31:31');
INSERT INTO `audit_logs` VALUES ('169',NULL,'LOGIN','User logged in','::1','2026-02-13 20:33:47');
INSERT INTO `audit_logs` VALUES ('170',NULL,'LOGIN','User logged in','::1','2026-02-13 20:34:02');
INSERT INTO `audit_logs` VALUES ('171','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 20:42:48');
INSERT INTO `audit_logs` VALUES ('172','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 20:54:14');
INSERT INTO `audit_logs` VALUES ('173','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 21:02:00');
INSERT INTO `audit_logs` VALUES ('174','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 21:02:05');
INSERT INTO `audit_logs` VALUES ('175','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 21:02:15');
INSERT INTO `audit_logs` VALUES ('176','24','Updated service request #1 → open',NULL,NULL,'2026-02-13 21:02:29');
INSERT INTO `audit_logs` VALUES ('177',NULL,'LOGIN','User logged in','::1','2026-02-13 21:06:32');
INSERT INTO `audit_logs` VALUES ('178',NULL,'LOGIN','User logged in','::1','2026-02-13 21:06:36');
INSERT INTO `audit_logs` VALUES ('179',NULL,'LOGIN','User logged in','::1','2026-02-13 21:17:03');
INSERT INTO `audit_logs` VALUES ('180',NULL,'LOGIN','User logged in','::1','2026-02-16 14:53:40');
INSERT INTO `audit_logs` VALUES ('181',NULL,'LOGIN','User logged in','::1','2026-02-16 14:53:46');
INSERT INTO `audit_logs` VALUES ('182',NULL,'LOGIN','User logged in','::1','2026-02-16 15:51:28');
INSERT INTO `audit_logs` VALUES ('183',NULL,'LOGIN','User logged in','::1','2026-02-16 16:14:44');
INSERT INTO `audit_logs` VALUES ('184','24','ADD_READING','Customer ID: 15 | Reading: 50.52','::1','2026-02-16 16:15:13');
INSERT INTO `audit_logs` VALUES ('185',NULL,'LOGIN','User logged in','::1','2026-02-16 16:16:13');
INSERT INTO `audit_logs` VALUES ('186',NULL,'LOGIN','User logged in','::1','2026-02-16 16:16:22');
INSERT INTO `audit_logs` VALUES ('187',NULL,'LOGIN','User logged in','::1','2026-02-16 16:18:17');
INSERT INTO `audit_logs` VALUES ('188',NULL,'LOGIN','User logged in','::1','2026-02-16 16:18:21');
INSERT INTO `audit_logs` VALUES ('189',NULL,'LOGIN','User logged in','::1','2026-02-16 16:20:21');
INSERT INTO `audit_logs` VALUES ('190',NULL,'LOGIN','User logged in','::1','2026-02-16 19:19:33');
INSERT INTO `audit_logs` VALUES ('191',NULL,'LOGIN','User logged in','::1','2026-02-16 19:20:06');
INSERT INTO `audit_logs` VALUES ('192',NULL,'LOGIN','User logged in','::1','2026-02-16 19:25:02');
INSERT INTO `audit_logs` VALUES ('193',NULL,'LOGIN','User logged in','::1','2026-02-16 19:25:06');
INSERT INTO `audit_logs` VALUES ('194',NULL,'LOGIN','User logged in','::1','2026-02-16 19:30:07');
INSERT INTO `audit_logs` VALUES ('195',NULL,'LOGIN','User logged in','::1','2026-02-16 19:30:22');
INSERT INTO `audit_logs` VALUES ('196',NULL,'LOGIN','User logged in','::1','2026-02-16 19:30:42');
INSERT INTO `audit_logs` VALUES ('197',NULL,'LOGIN','User logged in','::1','2026-02-16 19:30:46');
INSERT INTO `audit_logs` VALUES ('198',NULL,'LOGIN','User logged in','::1','2026-02-16 20:06:53');
INSERT INTO `audit_logs` VALUES ('199',NULL,'LOGIN','User logged in','::1','2026-02-16 20:06:57');
INSERT INTO `audit_logs` VALUES ('200',NULL,'LOGIN','User logged in','::1','2026-02-16 20:07:03');
INSERT INTO `audit_logs` VALUES ('201',NULL,'LOGIN','User logged in','::1','2026-02-16 20:07:54');
INSERT INTO `audit_logs` VALUES ('202',NULL,'LOGIN','User logged in','::1','2026-02-17 19:01:25');
INSERT INTO `audit_logs` VALUES ('203',NULL,'LOGIN','User logged in','::1','2026-02-17 19:01:54');
INSERT INTO `audit_logs` VALUES ('204','24','ADD_READING','Customer ID: 14 | Reading: 50.25','::1','2026-02-17 19:02:26');
INSERT INTO `audit_logs` VALUES ('205',NULL,'LOGIN','User logged in','::1','2026-02-17 19:02:51');
INSERT INTO `audit_logs` VALUES ('206',NULL,'LOGIN','User logged in','::1','2026-02-17 19:02:56');
INSERT INTO `audit_logs` VALUES ('207',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:19');
INSERT INTO `audit_logs` VALUES ('208',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:24');
INSERT INTO `audit_logs` VALUES ('209',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:27');
INSERT INTO `audit_logs` VALUES ('210',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:31');
INSERT INTO `audit_logs` VALUES ('211',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:34');
INSERT INTO `audit_logs` VALUES ('212',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:41');
INSERT INTO `audit_logs` VALUES ('213',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:43');
INSERT INTO `audit_logs` VALUES ('214',NULL,'LOGIN','User logged in','::1','2026-02-17 19:03:55');
INSERT INTO `audit_logs` VALUES ('215',NULL,'LOGIN','User logged in','::1','2026-02-17 19:04:50');
INSERT INTO `audit_logs` VALUES ('216',NULL,'LOGIN','User logged in','::1','2026-02-18 19:00:01');
INSERT INTO `audit_logs` VALUES ('217',NULL,'LOGIN','User logged in','::1','2026-02-18 19:00:04');
INSERT INTO `audit_logs` VALUES ('218',NULL,'LOGIN','User logged in','::1','2026-02-18 19:00:10');
INSERT INTO `audit_logs` VALUES ('219',NULL,'LOGIN','User logged in','::1','2026-02-18 19:00:17');
INSERT INTO `audit_logs` VALUES ('220','3','LOGIN','User logged in','::1','2026-02-18 19:00:22');
INSERT INTO `audit_logs` VALUES ('221','5','LOGIN','User logged in','::1','2026-02-18 19:02:03');
INSERT INTO `audit_logs` VALUES ('222','5','LOGIN','User logged in','::1','2026-02-18 19:02:06');
INSERT INTO `audit_logs` VALUES ('223','5','LOGIN','User logged in','::1','2026-02-18 19:02:10');
INSERT INTO `audit_logs` VALUES ('224','24','Updated service request #1 → in_progress',NULL,NULL,'2026-02-18 19:02:33');
INSERT INTO `audit_logs` VALUES ('225','24','Updated service request #1 → open',NULL,NULL,'2026-02-18 19:03:09');
INSERT INTO `audit_logs` VALUES ('226','24','Updated service request #1 → open',NULL,NULL,'2026-02-18 19:03:12');
INSERT INTO `audit_logs` VALUES ('227','24','Updated service request #1 → resolved',NULL,NULL,'2026-02-18 19:03:23');
INSERT INTO `audit_logs` VALUES ('228',NULL,'LOGIN','User logged in','::1','2026-02-18 19:03:47');
INSERT INTO `audit_logs` VALUES ('229',NULL,'LOGIN','User logged in','::1','2026-02-18 19:03:51');
INSERT INTO `audit_logs` VALUES ('230',NULL,'LOGIN','User logged in','::1','2026-02-18 19:07:28');
INSERT INTO `audit_logs` VALUES ('231',NULL,'LOGIN','User logged in','::1','2026-02-18 19:07:32');
INSERT INTO `audit_logs` VALUES ('232',NULL,'LOGIN','User logged in','::1','2026-02-18 19:39:22');
INSERT INTO `audit_logs` VALUES ('233',NULL,'LOGIN','User logged in','::1','2026-02-18 19:39:25');
INSERT INTO `audit_logs` VALUES ('234',NULL,'LOGIN','User logged in','::1','2026-02-18 19:40:08');
INSERT INTO `audit_logs` VALUES ('235',NULL,'LOGIN','User logged in','::1','2026-02-18 19:40:08');
INSERT INTO `audit_logs` VALUES ('236',NULL,'LOGIN','User logged in','::1','2026-02-18 19:40:13');
INSERT INTO `audit_logs` VALUES ('237',NULL,'LOGIN','User logged in','::1','2026-02-18 19:42:02');
INSERT INTO `audit_logs` VALUES ('238',NULL,'LOGIN','User logged in','::1','2026-02-18 19:42:09');
INSERT INTO `audit_logs` VALUES ('239',NULL,'LOGIN','User logged in','::1','2026-02-18 19:42:12');
INSERT INTO `audit_logs` VALUES ('240',NULL,'LOGIN','User logged in','::1','2026-02-18 19:57:47');
INSERT INTO `audit_logs` VALUES ('241',NULL,'LOGIN','User logged in','::1','2026-02-18 20:45:02');
INSERT INTO `audit_logs` VALUES ('242',NULL,'LOGIN','User logged in','::1','2026-02-18 20:45:06');
INSERT INTO `audit_logs` VALUES ('243',NULL,'LOGIN','User logged in','::1','2026-02-18 20:46:18');
INSERT INTO `audit_logs` VALUES ('244',NULL,'LOGIN','User logged in','::1','2026-02-18 20:46:27');
INSERT INTO `audit_logs` VALUES ('245',NULL,'LOGIN','User logged in','::1','2026-02-18 20:47:46');
INSERT INTO `audit_logs` VALUES ('246',NULL,'LOGIN','User logged in','::1','2026-02-18 20:47:51');
INSERT INTO `audit_logs` VALUES ('247',NULL,'LOGIN','User logged in','::1','2026-02-18 20:47:55');
INSERT INTO `audit_logs` VALUES ('248',NULL,'LOGIN','User logged in','::1','2026-02-18 20:48:00');
INSERT INTO `audit_logs` VALUES ('249',NULL,'LOGIN','User logged in','::1','2026-02-18 21:04:57');
INSERT INTO `audit_logs` VALUES ('250',NULL,'LOGIN','User logged in','::1','2026-02-18 21:05:13');
INSERT INTO `audit_logs` VALUES ('251',NULL,'LOGIN','User logged in','::1','2026-02-18 21:05:20');
INSERT INTO `audit_logs` VALUES ('252',NULL,'LOGIN','User logged in','::1','2026-02-18 21:05:25');
INSERT INTO `audit_logs` VALUES ('253',NULL,'LOGIN','User logged in','::1','2026-02-18 21:05:52');
INSERT INTO `audit_logs` VALUES ('254',NULL,'LOGIN','User logged in','::1','2026-02-18 21:06:16');
INSERT INTO `audit_logs` VALUES ('255',NULL,'LOGIN','User logged in','::1','2026-02-18 21:06:29');
INSERT INTO `audit_logs` VALUES ('256',NULL,'LOGIN','User logged in','::1','2026-02-18 21:06:43');
INSERT INTO `audit_logs` VALUES ('257',NULL,'LOGIN','User logged in','::1','2026-02-18 21:07:10');
INSERT INTO `audit_logs` VALUES ('258',NULL,'LOGIN','User logged in','::1','2026-02-18 21:07:23');
INSERT INTO `audit_logs` VALUES ('259',NULL,'LOGIN','User logged in','::1','2026-02-18 21:08:05');
INSERT INTO `audit_logs` VALUES ('260',NULL,'LOGIN','User logged in','::1','2026-02-18 21:08:12');
INSERT INTO `audit_logs` VALUES ('261',NULL,'LOGIN','User logged in','::1','2026-02-18 21:08:39');
INSERT INTO `audit_logs` VALUES ('262',NULL,'LOGIN','User logged in','::1','2026-02-18 21:08:45');
INSERT INTO `audit_logs` VALUES ('263',NULL,'LOGIN','User logged in','::1','2026-02-18 21:09:04');
INSERT INTO `audit_logs` VALUES ('264',NULL,'LOGIN','User logged in','::1','2026-02-18 21:09:11');
INSERT INTO `audit_logs` VALUES ('265',NULL,'LOGIN','User logged in','::1','2026-02-18 21:10:42');
INSERT INTO `audit_logs` VALUES ('266',NULL,'LOGIN','User logged in','::1','2026-02-18 21:10:45');
INSERT INTO `audit_logs` VALUES ('267',NULL,'LOGIN','User logged in','::1','2026-02-18 21:10:50');
INSERT INTO `audit_logs` VALUES ('268',NULL,'LOGIN','User logged in','::1','2026-02-18 21:10:55');
INSERT INTO `audit_logs` VALUES ('269',NULL,'LOGIN','User logged in','::1','2026-02-18 21:11:35');
INSERT INTO `audit_logs` VALUES ('270',NULL,'LOGIN','User logged in','::1','2026-02-18 21:11:39');
INSERT INTO `audit_logs` VALUES ('271',NULL,'LOGIN','User logged in','::1','2026-02-19 18:59:02');
INSERT INTO `audit_logs` VALUES ('272',NULL,'LOGIN','User logged in','::1','2026-02-19 18:59:09');
INSERT INTO `audit_logs` VALUES ('273',NULL,'LOGIN','User logged in','::1','2026-02-19 19:11:35');
INSERT INTO `audit_logs` VALUES ('274',NULL,'LOGIN','User logged in','::1','2026-02-19 19:11:40');
INSERT INTO `audit_logs` VALUES ('275',NULL,'LOGIN','User logged in','::1','2026-02-19 19:11:57');
INSERT INTO `audit_logs` VALUES ('276',NULL,'LOGIN','User logged in','::1','2026-02-19 19:12:03');
INSERT INTO `audit_logs` VALUES ('277',NULL,'LOGIN','User logged in','::1','2026-02-19 19:13:11');
INSERT INTO `audit_logs` VALUES ('278',NULL,'LOGIN','User logged in','::1','2026-02-19 19:13:14');
INSERT INTO `audit_logs` VALUES ('279',NULL,'LOGIN','User logged in','::1','2026-02-19 19:13:31');
INSERT INTO `audit_logs` VALUES ('280',NULL,'LOGIN','User logged in','::1','2026-02-19 19:13:38');
INSERT INTO `audit_logs` VALUES ('281','24','PAYMENT','Bill ID: 10 marked as paid','::1','2026-02-19 19:17:34');
INSERT INTO `audit_logs` VALUES ('282','24','PAYMENT','Bill ID: 10 marked as paid. Total Paid: ₱2,376.68','::1','2026-02-19 19:27:20');
INSERT INTO `audit_logs` VALUES ('283','24','PAYMENT','Bill ID: 10 marked as paid. Total Paid: ₱2,376.68','::1','2026-02-19 19:30:08');
INSERT INTO `audit_logs` VALUES ('284','24','PAYMENT','Bill ID: 10 marked as paid. Total Paid: ₱2,376.68','::1','2026-02-19 19:34:50');
INSERT INTO `audit_logs` VALUES ('285','24','PAYMENT','Bill ID: 10 marked as paid. Total Paid: ₱2,376.68','::1','2026-02-19 19:38:24');
INSERT INTO `audit_logs` VALUES ('286',NULL,'LOGIN','User logged in','::1','2026-02-19 19:39:59');
INSERT INTO `audit_logs` VALUES ('287',NULL,'LOGIN','User logged in','::1','2026-02-19 19:40:05');
INSERT INTO `audit_logs` VALUES ('288',NULL,'LOGIN','User logged in','::1','2026-02-19 19:45:39');
INSERT INTO `audit_logs` VALUES ('289',NULL,'LOGIN','User logged in','::1','2026-02-19 19:45:44');
INSERT INTO `audit_logs` VALUES ('290',NULL,'LOGIN','User logged in','::1','2026-02-19 19:46:18');
INSERT INTO `audit_logs` VALUES ('291',NULL,'LOGIN','User logged in','::1','2026-02-19 19:46:24');
INSERT INTO `audit_logs` VALUES ('292',NULL,'LOGIN','User logged in','::1','2026-02-19 19:47:16');
INSERT INTO `audit_logs` VALUES ('293',NULL,'LOGIN','User logged in','::1','2026-02-19 19:47:24');
INSERT INTO `audit_logs` VALUES ('294',NULL,'LOGIN','User logged in','::1','2026-02-19 20:19:16');
INSERT INTO `audit_logs` VALUES ('295',NULL,'LOGIN','User logged in','::1','2026-02-19 20:19:22');
INSERT INTO `audit_logs` VALUES ('296',NULL,'LOGIN','User logged in','::1','2026-02-20 18:13:21');
INSERT INTO `audit_logs` VALUES ('297',NULL,'LOGIN','User logged in','::1','2026-02-20 18:13:27');
INSERT INTO `audit_logs` VALUES ('298',NULL,'LOGIN','User logged in','::1','2026-02-20 19:46:14');
INSERT INTO `audit_logs` VALUES ('299',NULL,'LOGIN','User logged in','::1','2026-02-20 19:46:21');
INSERT INTO `audit_logs` VALUES ('300',NULL,'LOGIN','User logged in','::1','2026-02-20 19:48:58');
INSERT INTO `audit_logs` VALUES ('301',NULL,'LOGIN','User logged in','::1','2026-02-20 19:49:06');
INSERT INTO `audit_logs` VALUES ('302',NULL,'LOGIN','User logged in','::1','2026-02-20 19:53:23');
INSERT INTO `audit_logs` VALUES ('303',NULL,'LOGIN','User logged in','::1','2026-02-20 19:53:29');
INSERT INTO `audit_logs` VALUES ('304',NULL,'LOGIN','User logged in','::1','2026-02-20 19:53:36');
INSERT INTO `audit_logs` VALUES ('305',NULL,'LOGIN','User logged in','::1','2026-02-20 19:53:41');
INSERT INTO `audit_logs` VALUES ('306','24','ADD_READING','Customer ID: 11 | Reading: 110','::1','2026-02-20 20:08:00');
INSERT INTO `audit_logs` VALUES ('307','24','ADD_READING','Customer ID: 11 | Reading: 110','::1','2026-02-20 20:14:26');
INSERT INTO `audit_logs` VALUES ('308',NULL,'LOGIN','User logged in','::1','2026-02-20 20:20:51');
INSERT INTO `audit_logs` VALUES ('309',NULL,'LOGIN','User logged in','::1','2026-02-20 20:20:56');
INSERT INTO `audit_logs` VALUES ('310',NULL,'LOGIN','User logged in','::1','2026-02-20 20:21:08');
INSERT INTO `audit_logs` VALUES ('311',NULL,'LOGIN','User logged in','::1','2026-02-20 20:21:08');
INSERT INTO `audit_logs` VALUES ('312',NULL,'LOGIN','User logged in','::1','2026-02-20 20:21:13');
INSERT INTO `audit_logs` VALUES ('313',NULL,'LOGIN','User logged in','::1','2026-02-20 20:27:51');
INSERT INTO `audit_logs` VALUES ('314',NULL,'LOGIN','User logged in','::1','2026-02-20 20:27:59');
INSERT INTO `audit_logs` VALUES ('315',NULL,'LOGIN','User logged in','::1','2026-02-20 20:29:14');
INSERT INTO `audit_logs` VALUES ('316',NULL,'LOGIN','User logged in','::1','2026-02-21 19:40:19');
INSERT INTO `audit_logs` VALUES ('317',NULL,'LOGIN','User logged in','::1','2026-02-21 19:40:25');
INSERT INTO `audit_logs` VALUES ('318',NULL,'LOGIN','User logged in','::1','2026-02-21 19:49:17');
INSERT INTO `audit_logs` VALUES ('319',NULL,'LOGIN','User logged in','::1','2026-02-21 19:49:23');
INSERT INTO `audit_logs` VALUES ('320',NULL,'LOGIN','User logged in','::1','2026-02-21 19:51:50');
INSERT INTO `audit_logs` VALUES ('321',NULL,'LOGIN','User logged in','::1','2026-02-21 19:51:56');
INSERT INTO `audit_logs` VALUES ('322',NULL,'LOGIN','User logged in','::1','2026-02-21 19:55:41');
INSERT INTO `audit_logs` VALUES ('323',NULL,'LOGIN','User logged in','::1','2026-02-21 19:55:46');
INSERT INTO `audit_logs` VALUES ('324',NULL,'LOGIN','User logged in','::1','2026-02-21 19:55:50');
INSERT INTO `audit_logs` VALUES ('325',NULL,'LOGIN','User logged in','::1','2026-02-22 10:13:52');
INSERT INTO `audit_logs` VALUES ('326',NULL,'LOGIN','User logged in','::1','2026-02-22 10:13:58');
INSERT INTO `audit_logs` VALUES ('327','24','Updated service request #1 → open',NULL,NULL,'2026-02-22 10:24:58');
INSERT INTO `audit_logs` VALUES ('328','24','Updated service request #1 → open',NULL,NULL,'2026-02-22 10:25:01');
INSERT INTO `audit_logs` VALUES ('329','24','Updated service request #1 → resolved',NULL,NULL,'2026-02-22 10:25:06');
INSERT INTO `audit_logs` VALUES ('330',NULL,'LOGIN','User logged in','::1','2026-02-22 10:26:02');
INSERT INTO `audit_logs` VALUES ('331',NULL,'LOGIN','User logged in','::1','2026-02-22 10:26:07');
INSERT INTO `audit_logs` VALUES ('332',NULL,'LOGIN','User logged in','::1','2026-02-22 10:37:04');
INSERT INTO `audit_logs` VALUES ('333',NULL,'LOGIN','User logged in','::1','2026-02-22 10:37:08');
INSERT INTO `audit_logs` VALUES ('334',NULL,'LOGIN','User logged in','::1','2026-03-02 18:15:34');
INSERT INTO `audit_logs` VALUES ('335',NULL,'LOGIN','User logged in','::1','2026-03-02 18:15:46');
INSERT INTO `audit_logs` VALUES ('336',NULL,'LOGIN','User logged in','::1','2026-03-02 19:21:47');
INSERT INTO `audit_logs` VALUES ('337',NULL,'LOGIN','User logged in','::1','2026-03-02 19:21:54');
INSERT INTO `audit_logs` VALUES ('338',NULL,'LOGIN','User logged in','::1','2026-03-02 19:22:00');
INSERT INTO `audit_logs` VALUES ('339',NULL,'LOGIN','User logged in','::1','2026-03-02 19:22:39');
INSERT INTO `audit_logs` VALUES ('340',NULL,'LOGIN','User logged in','::1','2026-03-02 19:22:43');
INSERT INTO `audit_logs` VALUES ('341',NULL,'LOGIN','User logged in','::1','2026-03-02 19:22:57');
INSERT INTO `audit_logs` VALUES ('342',NULL,'LOGIN','User logged in','::1','2026-03-02 19:23:06');
INSERT INTO `audit_logs` VALUES ('343',NULL,'LOGIN','User logged in','::1','2026-03-03 19:36:32');
INSERT INTO `audit_logs` VALUES ('344',NULL,'LOGIN','User logged in','::1','2026-03-03 19:36:40');
INSERT INTO `audit_logs` VALUES ('345',NULL,'LOGIN','User logged in','::1','2026-03-03 19:36:51');
INSERT INTO `audit_logs` VALUES ('346',NULL,'LOGIN','User logged in','::1','2026-03-03 19:36:57');
INSERT INTO `audit_logs` VALUES ('347',NULL,'LOGIN','User logged in','::1','2026-03-03 19:37:09');
INSERT INTO `audit_logs` VALUES ('348',NULL,'LOGIN','User logged in','::1','2026-03-03 19:37:14');
INSERT INTO `audit_logs` VALUES ('349',NULL,'LOGIN','User logged in','::1','2026-03-03 19:38:03');
INSERT INTO `audit_logs` VALUES ('350',NULL,'LOGIN','User logged in','::1','2026-03-03 19:38:09');
INSERT INTO `audit_logs` VALUES ('351',NULL,'LOGIN','User logged in','::1','2026-03-03 19:38:34');
INSERT INTO `audit_logs` VALUES ('352',NULL,'LOGIN','User logged in','::1','2026-03-03 19:38:39');
INSERT INTO `audit_logs` VALUES ('353','24','Updated service request #2 → open',NULL,NULL,'2026-03-03 19:38:49');
INSERT INTO `audit_logs` VALUES ('354','24','Updated service request #2 → open',NULL,NULL,'2026-03-03 20:05:27');
INSERT INTO `audit_logs` VALUES ('355',NULL,'LOGIN','User logged in','::1','2026-03-03 20:06:04');
INSERT INTO `audit_logs` VALUES ('356',NULL,'LOGIN','User logged in','::1','2026-03-03 20:06:12');
INSERT INTO `audit_logs` VALUES ('357',NULL,'LOGIN','User logged in','::1','2026-03-03 20:06:22');
INSERT INTO `audit_logs` VALUES ('358',NULL,'LOGIN','User logged in','::1','2026-03-03 20:06:32');
INSERT INTO `audit_logs` VALUES ('359','24','Updated service request #2 → open',NULL,NULL,'2026-03-03 20:06:58');
INSERT INTO `audit_logs` VALUES ('360',NULL,'LOGIN','User logged in','::1','2026-03-03 20:23:52');
INSERT INTO `audit_logs` VALUES ('361',NULL,'LOGIN','User logged in','::1','2026-03-03 20:24:01');
INSERT INTO `audit_logs` VALUES ('362',NULL,'LOGIN','User logged in','::1','2026-03-03 20:55:19');
INSERT INTO `audit_logs` VALUES ('363',NULL,'LOGIN','User logged in','::1','2026-03-03 20:55:25');
INSERT INTO `audit_logs` VALUES ('364',NULL,'LOGIN','User logged in','::1','2026-03-03 20:55:31');
INSERT INTO `audit_logs` VALUES ('365',NULL,'LOGIN','User logged in','::1','2026-03-03 20:55:36');
INSERT INTO `audit_logs` VALUES ('366',NULL,'LOGIN','User logged in','::1','2026-03-03 21:03:22');
INSERT INTO `audit_logs` VALUES ('367',NULL,'LOGIN','User logged in','::1','2026-03-03 21:03:28');
INSERT INTO `audit_logs` VALUES ('368',NULL,'LOGIN','User logged in','::1','2026-03-03 21:04:37');
INSERT INTO `audit_logs` VALUES ('369',NULL,'LOGIN','User logged in','::1','2026-03-03 21:04:41');
INSERT INTO `audit_logs` VALUES ('370',NULL,'LOGIN','User logged in','::1','2026-03-03 21:06:32');
INSERT INTO `audit_logs` VALUES ('371',NULL,'LOGIN','User logged in','::1','2026-03-03 21:06:37');
INSERT INTO `audit_logs` VALUES ('372',NULL,'LOGIN','User logged in','::1','2026-03-03 21:07:01');
INSERT INTO `audit_logs` VALUES ('373',NULL,'LOGIN','User logged in','::1','2026-03-03 21:07:08');
INSERT INTO `audit_logs` VALUES ('374',NULL,'LOGIN','User logged in','::1','2026-03-03 21:09:38');
INSERT INTO `audit_logs` VALUES ('375',NULL,'LOGIN','User logged in','::1','2026-03-03 21:09:43');
INSERT INTO `audit_logs` VALUES ('376',NULL,'LOGIN','User logged in','::1','2026-03-03 21:10:52');
INSERT INTO `audit_logs` VALUES ('377',NULL,'LOGIN','User logged in','::1','2026-03-03 21:10:56');
INSERT INTO `audit_logs` VALUES ('378',NULL,'LOGIN','User logged in','::1','2026-03-03 21:13:46');
INSERT INTO `audit_logs` VALUES ('379',NULL,'LOGIN','User logged in','::1','2026-03-03 21:13:51');
INSERT INTO `audit_logs` VALUES ('380',NULL,'LOGIN','User logged in','::1','2026-03-03 21:47:35');
INSERT INTO `audit_logs` VALUES ('381',NULL,'LOGIN','User logged in','::1','2026-03-03 21:47:40');
INSERT INTO `audit_logs` VALUES ('382',NULL,'LOGIN','User logged in','::1','2026-03-03 21:47:52');
INSERT INTO `audit_logs` VALUES ('383',NULL,'LOGIN','User logged in','::1','2026-03-03 21:49:20');
INSERT INTO `audit_logs` VALUES ('384',NULL,'LOGIN','User logged in','::1','2026-03-03 22:33:38');
INSERT INTO `audit_logs` VALUES ('385',NULL,'LOGIN','User logged in','::1','2026-03-03 22:33:44');
INSERT INTO `audit_logs` VALUES ('386',NULL,'LOGIN','User logged in','::1','2026-03-03 22:33:56');
INSERT INTO `audit_logs` VALUES ('387',NULL,'LOGIN','User logged in','::1','2026-03-03 22:34:00');
INSERT INTO `audit_logs` VALUES ('388','24','Updated service request #2 → in_progress',NULL,NULL,'2026-03-03 22:37:57');
INSERT INTO `audit_logs` VALUES ('389',NULL,'LOGIN','User logged in','::1','2026-03-03 22:39:30');
INSERT INTO `audit_logs` VALUES ('390',NULL,'LOGIN','User logged in','::1','2026-03-03 22:49:45');
INSERT INTO `audit_logs` VALUES ('391',NULL,'LOGIN','User logged in','::1','2026-03-04 19:16:26');
INSERT INTO `audit_logs` VALUES ('392',NULL,'LOGIN_FAILED','Failed login attempt: admin@aquatrack.com','::1','2026-03-04 19:24:19');
INSERT INTO `audit_logs` VALUES ('393','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-04 19:24:25');
INSERT INTO `audit_logs` VALUES ('394','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-04 19:27:49');
INSERT INTO `audit_logs` VALUES ('395','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-04 19:30:11');
INSERT INTO `audit_logs` VALUES ('396','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-04 19:53:44');
INSERT INTO `audit_logs` VALUES ('397','39','LOGIN_SUCCESS','User logged in: customer2@aquatrack.com','::1','2026-03-04 19:59:39');
INSERT INTO `audit_logs` VALUES ('398','43','LOGIN_SUCCESS','User logged in: customer2@aquatrack.com','::1','2026-03-04 20:18:57');
INSERT INTO `audit_logs` VALUES ('399','44','LOGIN_SUCCESS','User logged in: customer2@aquatrack.com','::1','2026-03-04 20:26:01');
INSERT INTO `audit_logs` VALUES ('400','47','LOGIN_SUCCESS','User logged in: customer3@aquatrack.com','::1','2026-03-04 20:46:22');
INSERT INTO `audit_logs` VALUES ('401','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-05 16:57:18');
INSERT INTO `audit_logs` VALUES ('402',NULL,'LOGIN_FAILED','Failed login attempt: customer1@aquatrack.com','::1','2026-03-05 16:59:04');
INSERT INTO `audit_logs` VALUES ('403','5','LOGIN_SUCCESS','User logged in: customer1@aquatrack.com','::1','2026-03-05 16:59:11');
INSERT INTO `audit_logs` VALUES ('404','6','LOGIN_SUCCESS','User logged in: owner@aquatrack.com','::1','2026-03-05 17:00:14');
INSERT INTO `audit_logs` VALUES ('405','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-05 17:00:34');
INSERT INTO `audit_logs` VALUES ('406','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-05 17:02:45');
INSERT INTO `audit_logs` VALUES ('407','6','LOGIN_SUCCESS','User logged in: owner@aquatrack.com','::1','2026-03-05 17:07:41');
INSERT INTO `audit_logs` VALUES ('408','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-05 17:09:01');
INSERT INTO `audit_logs` VALUES ('409','24','Updated service request #2 → resolved',NULL,NULL,'2026-03-05 17:23:45');
INSERT INTO `audit_logs` VALUES ('410','6','LOGIN_SUCCESS','User logged in: owner@aquatrack.com','::1','2026-03-05 17:24:05');
INSERT INTO `audit_logs` VALUES ('411','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-05 17:29:05');
INSERT INTO `audit_logs` VALUES ('412','24','Updated service request #2 → resolved','Service request updated by staff','::1','2026-03-05 17:29:28');
INSERT INTO `audit_logs` VALUES ('413','6','LOGIN_SUCCESS','User logged in: owner@aquatrack.com','::1','2026-03-05 17:29:41');
INSERT INTO `audit_logs` VALUES ('414','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-06 12:48:13');
INSERT INTO `audit_logs` VALUES ('415','37','LOGIN_SUCCESS','User logged in: kade.limos.up@phinmaed.com','::1','2026-03-06 12:48:25');
INSERT INTO `audit_logs` VALUES ('416',NULL,'LOGIN_FAILED','Failed login attempt: customer1@aquatrack.com','::1','2026-03-06 12:50:16');
INSERT INTO `audit_logs` VALUES ('417','5','LOGIN_SUCCESS','User logged in: customer1@aquatrack.com','::1','2026-03-06 12:50:21');
INSERT INTO `audit_logs` VALUES ('418','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-06 12:53:27');
INSERT INTO `audit_logs` VALUES ('419','5','LOGIN_SUCCESS','User logged in: customer1@aquatrack.com','::1','2026-03-06 12:55:09');
INSERT INTO `audit_logs` VALUES ('420','5','LOGIN_SUCCESS','User logged in: customer1@aquatrack.com','::1','2026-03-06 16:53:21');
INSERT INTO `audit_logs` VALUES ('421','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-06 16:54:10');
INSERT INTO `audit_logs` VALUES ('422','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-06 16:57:30');
INSERT INTO `audit_logs` VALUES ('423',NULL,'LOGIN_FAILED','Failed login attempt: customer1@aquatrack.com','::1','2026-03-06 19:59:33');
INSERT INTO `audit_logs` VALUES ('424','5','LOGIN_SUCCESS','User logged in: customer1@aquatrack.com','::1','2026-03-06 19:59:39');
INSERT INTO `audit_logs` VALUES ('425','48','LOGIN_SUCCESS','User logged in: sisonjohnpaul@gmail.com','::1','2026-03-06 20:47:31');
INSERT INTO `audit_logs` VALUES ('426','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-06 21:05:47');
INSERT INTO `audit_logs` VALUES ('427','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-08 18:39:14');
INSERT INTO `audit_logs` VALUES ('428','24','LOGIN_SUCCESS','User logged in: staff@aquatrack.com','::1','2026-03-08 18:39:27');
INSERT INTO `audit_logs` VALUES ('429','6','LOGIN_SUCCESS','User logged in: owner@aquatrack.com','::1','2026-03-08 18:39:41');
INSERT INTO `audit_logs` VALUES ('430','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-08 18:41:01');
INSERT INTO `audit_logs` VALUES ('431','6','LOGIN_SUCCESS','User logged in: owner@aquatrack.com','::1','2026-03-08 18:42:46');
INSERT INTO `audit_logs` VALUES ('432','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-08 18:43:02');
INSERT INTO `audit_logs` VALUES ('433','3','LOGIN_SUCCESS','User logged in: admin@aquatrack.com','::1','2026-03-08 18:44:27');


CREATE TABLE `backup_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `bills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `reading_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `penalty` decimal(10,2) DEFAULT '0.00',
  `status` enum('unpaid','paid','pending') DEFAULT 'unpaid',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `due_date` date DEFAULT NULL,
  `overdue_notified` tinyint DEFAULT '0',
  `rate_used` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `reading_id` (`reading_id`),
  KEY `idx_bills_status` (`status`),
  CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`reading_id`) REFERENCES `readings` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `bills` VALUES ('1','2','3','2015.00','0.00','paid','2026-01-11 18:38:03','2026-01-21','0',NULL);
INSERT INTO `bills` VALUES ('4','3','8','3265.00','0.00','paid','2026-01-17 15:33:39','2026-01-27','0',NULL);
INSERT INTO `bills` VALUES ('5','2','9','6.25','0.00','paid','2026-01-28 15:04:44','2026-02-07','0',NULL);
INSERT INTO `bills` VALUES ('6','10','10','1512.50','75.63','paid','2026-01-28 16:14:59','2026-02-07','1',NULL);
INSERT INTO `bills` VALUES ('7','3','11','491.25','0.00','paid','2026-01-30 20:42:54','2026-02-09','0',NULL);
INSERT INTO `bills` VALUES ('9','11','17','2513.00','125.65','paid','2026-01-30 21:24:33','2026-02-04','1',NULL);
INSERT INTO `bills` VALUES ('10','12','18','2263.50','113.18','paid','2026-01-31 20:03:16','2026-02-05','1',NULL);
INSERT INTO `bills` VALUES ('14','13','22','2001.25','100.06','paid','2026-02-01 11:26:59','2026-02-06','1',NULL);
INSERT INTO `bills` VALUES ('15','2','23','1750.00','87.50','paid','2026-02-02 13:43:01','2026-02-07','1',NULL);
INSERT INTO `bills` VALUES ('18','3','26','1760.00','88.00','paid','2026-02-02 14:55:35','2026-02-07','1',NULL);
INSERT INTO `bills` VALUES ('19','10','27','750.00','37.50','paid','2026-02-04 20:45:15','2026-02-09','1',NULL);
INSERT INTO `bills` VALUES ('20','15','28','1263.00','63.15','unpaid','2026-02-16 16:15:09','2026-02-21','1',NULL);
INSERT INTO `bills` VALUES ('21','14','29','1256.25','62.81','unpaid','2026-02-17 19:02:23','2026-02-22','1',NULL);
INSERT INTO `bills` VALUES ('23','11','31','170.64','8.53','unpaid','2026-02-20 20:14:25','2026-02-25','1','18.00');


CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `area_id` int NOT NULL,
  `meter_number` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `service_status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `meter_number` (`meter_number`),
  UNIQUE KEY `meter_number_2` (`meter_number`),
  KEY `user_id` (`user_id`),
  KEY `idx_customer_area` (`area_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `customers` VALUES ('2','5','1','MTR-0001','2026-01-11 18:33:24','active');
INSERT INTO `customers` VALUES ('3','15','1','MTR-0002','2026-01-17 15:24:58','active');
INSERT INTO `customers` VALUES ('10','32','1','MTR-0004','2026-01-28 16:13:52','active');
INSERT INTO `customers` VALUES ('11','33','2','MTR-0005','2026-01-30 21:23:59','active');
INSERT INTO `customers` VALUES ('12','34','1','MTR-0006','2026-01-31 20:01:26','active');
INSERT INTO `customers` VALUES ('13','35','1','MTR-0007','2026-02-01 11:13:17','active');
INSERT INTO `customers` VALUES ('14','36','2','MTR-0008','2026-02-07 20:02:44','active');
INSERT INTO `customers` VALUES ('15','37','2','MTR-0009','2026-02-10 14:10:26','disconnected');
INSERT INTO `customers` VALUES ('21','44','1','MTR-0010','2026-03-04 20:25:41','active');
INSERT INTO `customers` VALUES ('23','47','1','MTR-0011','2026-03-04 20:46:05','active');
INSERT INTO `customers` VALUES ('24','48','1','MTR-0012','2026-03-06 20:47:18','active');


CREATE TABLE `disconnection_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `requested_by` int DEFAULT NULL,
  `action` enum('disconnect','reconnect') NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `reconnection_fee` decimal(10,2) DEFAULT '0.00',
  `completed_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `disconnection_requests_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `disconnection_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `disconnection_requests` VALUES ('2','12','Payment Not Settled','24','disconnect','completed','2026-02-19','0.00','2026-02-18 20:42:05','2026-02-18 20:15:42');
INSERT INTO `disconnection_requests` VALUES ('4','12','Will pay Bill','24','reconnect','completed','2026-02-19','0.00','2026-02-18 20:43:53','2026-02-18 20:43:38');
INSERT INTO `disconnection_requests` VALUES ('5','12','Payment not Settled','24','disconnect','completed','2026-02-18','0.00','2026-02-18 20:44:58','2026-02-18 20:44:53');
INSERT INTO `disconnection_requests` VALUES ('6','12','Will Pay Remaining Balanced and Continue Using the Sevices','24','reconnect','completed','2026-02-18','0.00','2026-02-18 20:48:17','2026-02-18 20:47:23');
INSERT INTO `disconnection_requests` VALUES ('7','13','Bill not Settled','24','disconnect','completed','2026-02-18','0.00','2026-02-18 21:09:17','2026-02-18 21:08:28');
INSERT INTO `disconnection_requests` VALUES ('9','13','Payment will be Settled','24','reconnect','completed','2026-02-18','0.00','2026-02-18 21:11:27','2026-02-18 21:10:35');
INSERT INTO `disconnection_requests` VALUES ('10','13','Payment Not Settled','24','disconnect','completed','2026-02-19','0.00','2026-02-19 19:11:12','2026-02-19 19:11:05');
INSERT INTO `disconnection_requests` VALUES ('11','13','Balance Settled','24','reconnect','completed','2026-02-19','150.00','2026-02-19 19:13:42','2026-02-19 19:12:45');
INSERT INTO `disconnection_requests` VALUES ('12','12','Balanced not Settled','24','disconnect','completed','2026-02-19','0.00','2026-02-19 19:16:09','2026-02-19 19:15:55');
INSERT INTO `disconnection_requests` VALUES ('13','12','Balance Settled','24','reconnect','completed','2026-02-19','150.00','2026-02-19 19:18:51','2026-02-19 19:18:46');
INSERT INTO `disconnection_requests` VALUES ('14','15','Bill not Settled','24','disconnect','completed','2026-03-02','0.00','2026-03-02 19:05:42','2026-03-02 19:05:32');


CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text,
  `type` enum('payment','warning','disconnect','system') DEFAULT 'system',
  `is_read` tinyint DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `notifications` VALUES ('3','35','New Meter Reading Recorded','New meter reading of 80.05 recorded. Bill amount: ₱2,001.25. Due date: 2026-02-06.','system','1','2026-02-01 11:26:59');
INSERT INTO `notifications` VALUES ('4','5','New Meter Reading Recorded','New meter reading of 200.25 recorded. Bill amount: ₱1,750.00. Due date: 2026-02-07.','system','1','2026-02-02 13:43:01');
INSERT INTO `notifications` VALUES ('6','32','New Meter Reading Recorded','New meter reading of 100.25 recorded. Bill amount: ₱993.75. Due date: 2026-02-07.','system','1','2026-02-02 14:11:24');
INSERT INTO `notifications` VALUES ('7','15','New Meter Reading Recorded','New meter reading of 220.65 recorded. Bill amount: ₱1,760.00. Due date: 2026-02-07.','system','1','2026-02-02 14:55:35');
INSERT INTO `notifications` VALUES ('8','32','New Meter Reading Recorded','New meter reading of 90.5 recorded. Bill amount: ₱750.00. Due date: 2026-02-09.','system','1','2026-02-04 20:45:15');
INSERT INTO `notifications` VALUES ('9','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:10:52');
INSERT INTO `notifications` VALUES ('10','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:10:52');
INSERT INTO `notifications` VALUES ('11','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:10:52');
INSERT INTO `notifications` VALUES ('12','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:10:59');
INSERT INTO `notifications` VALUES ('13','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:10:59');
INSERT INTO `notifications` VALUES ('14','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:10:59');
INSERT INTO `notifications` VALUES ('15','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:00');
INSERT INTO `notifications` VALUES ('16','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:00');
INSERT INTO `notifications` VALUES ('17','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:00');
INSERT INTO `notifications` VALUES ('18','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:04');
INSERT INTO `notifications` VALUES ('19','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:04');
INSERT INTO `notifications` VALUES ('20','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:04');
INSERT INTO `notifications` VALUES ('21','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:06');
INSERT INTO `notifications` VALUES ('22','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:06');
INSERT INTO `notifications` VALUES ('23','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:06');
INSERT INTO `notifications` VALUES ('24','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:07');
INSERT INTO `notifications` VALUES ('25','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:07');
INSERT INTO `notifications` VALUES ('26','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:07');
INSERT INTO `notifications` VALUES ('27','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:11');
INSERT INTO `notifications` VALUES ('28','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:11');
INSERT INTO `notifications` VALUES ('29','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:11:11');
INSERT INTO `notifications` VALUES ('30','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:20:25');
INSERT INTO `notifications` VALUES ('31','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:20:25');
INSERT INTO `notifications` VALUES ('32','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 19:20:25');
INSERT INTO `notifications` VALUES ('33','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:02:56');
INSERT INTO `notifications` VALUES ('34','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:02:56');
INSERT INTO `notifications` VALUES ('35','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:02:56');
INSERT INTO `notifications` VALUES ('36','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:10');
INSERT INTO `notifications` VALUES ('37','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:10');
INSERT INTO `notifications` VALUES ('38','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:10');
INSERT INTO `notifications` VALUES ('39','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:10');
INSERT INTO `notifications` VALUES ('40','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:10');
INSERT INTO `notifications` VALUES ('41','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:10');
INSERT INTO `notifications` VALUES ('42','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:12');
INSERT INTO `notifications` VALUES ('43','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:12');
INSERT INTO `notifications` VALUES ('44','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:03:12');
INSERT INTO `notifications` VALUES ('45','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:27:13');
INSERT INTO `notifications` VALUES ('46','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:27:13');
INSERT INTO `notifications` VALUES ('47','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:27:13');
INSERT INTO `notifications` VALUES ('48','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:42:42');
INSERT INTO `notifications` VALUES ('49','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:42:48');
INSERT INTO `notifications` VALUES ('50','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:42:52');
INSERT INTO `notifications` VALUES ('51','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:42:55');
INSERT INTO `notifications` VALUES ('52','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:42:59');
INSERT INTO `notifications` VALUES ('53','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:02');
INSERT INTO `notifications` VALUES ('54','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:14');
INSERT INTO `notifications` VALUES ('55','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:17');
INSERT INTO `notifications` VALUES ('56','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:21');
INSERT INTO `notifications` VALUES ('57','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:28');
INSERT INTO `notifications` VALUES ('58','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:32');
INSERT INTO `notifications` VALUES ('59','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:43:35');
INSERT INTO `notifications` VALUES ('60','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:44:09');
INSERT INTO `notifications` VALUES ('61','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:44:13');
INSERT INTO `notifications` VALUES ('62','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:44:16');
INSERT INTO `notifications` VALUES ('63','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:46:43');
INSERT INTO `notifications` VALUES ('64','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:46:46');
INSERT INTO `notifications` VALUES ('65','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-07 20:46:50');
INSERT INTO `notifications` VALUES ('66','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:57:09');
INSERT INTO `notifications` VALUES ('67','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:57:16');
INSERT INTO `notifications` VALUES ('68','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:57:20');
INSERT INTO `notifications` VALUES ('69','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:57:24');
INSERT INTO `notifications` VALUES ('70','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:57:28');
INSERT INTO `notifications` VALUES ('71','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:57:32');
INSERT INTO `notifications` VALUES ('72','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:59:47');
INSERT INTO `notifications` VALUES ('73','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:59:51');
INSERT INTO `notifications` VALUES ('74','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 18:59:55');
INSERT INTO `notifications` VALUES ('75','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:00');
INSERT INTO `notifications` VALUES ('76','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:04');
INSERT INTO `notifications` VALUES ('77','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:08');
INSERT INTO `notifications` VALUES ('78','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:32');
INSERT INTO `notifications` VALUES ('79','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:36');
INSERT INTO `notifications` VALUES ('80','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:41');
INSERT INTO `notifications` VALUES ('81','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:46');
INSERT INTO `notifications` VALUES ('82','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:51');
INSERT INTO `notifications` VALUES ('83','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:00:55');
INSERT INTO `notifications` VALUES ('84','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:01:30');
INSERT INTO `notifications` VALUES ('85','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:01:34');
INSERT INTO `notifications` VALUES ('86','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:01:38');
INSERT INTO `notifications` VALUES ('87','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:01:43');
INSERT INTO `notifications` VALUES ('88','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:01:46');
INSERT INTO `notifications` VALUES ('89','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:01:51');
INSERT INTO `notifications` VALUES ('90','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:02:50');
INSERT INTO `notifications` VALUES ('91','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:02:54');
INSERT INTO `notifications` VALUES ('92','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:02:59');
INSERT INTO `notifications` VALUES ('93','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:03:03');
INSERT INTO `notifications` VALUES ('94','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:03:06');
INSERT INTO `notifications` VALUES ('95','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:03:10');
INSERT INTO `notifications` VALUES ('96','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:04:46');
INSERT INTO `notifications` VALUES ('97','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:04:51');
INSERT INTO `notifications` VALUES ('98','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:04:55');
INSERT INTO `notifications` VALUES ('99','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:04:59');
INSERT INTO `notifications` VALUES ('100','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:03');
INSERT INTO `notifications` VALUES ('101','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:08');
INSERT INTO `notifications` VALUES ('102','5','Test Notification','This is a test alert from AquaTrack. If you received this, your notification channels are working.','system','1','2026-02-09 19:05:12');
INSERT INTO `notifications` VALUES ('103','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:18');
INSERT INTO `notifications` VALUES ('104','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:22');
INSERT INTO `notifications` VALUES ('105','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:28');
INSERT INTO `notifications` VALUES ('106','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:32');
INSERT INTO `notifications` VALUES ('107','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:36');
INSERT INTO `notifications` VALUES ('108','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:05:40');
INSERT INTO `notifications` VALUES ('109','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:16:51');
INSERT INTO `notifications` VALUES ('110','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:16:55');
INSERT INTO `notifications` VALUES ('111','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:17:01');
INSERT INTO `notifications` VALUES ('112','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:17:06');
INSERT INTO `notifications` VALUES ('113','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:17:11');
INSERT INTO `notifications` VALUES ('114','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:17:14');
INSERT INTO `notifications` VALUES ('115','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:21:35');
INSERT INTO `notifications` VALUES ('116','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:21:40');
INSERT INTO `notifications` VALUES ('117','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:21:45');
INSERT INTO `notifications` VALUES ('118','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:21:49');
INSERT INTO `notifications` VALUES ('119','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:21:54');
INSERT INTO `notifications` VALUES ('120','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:21:59');
INSERT INTO `notifications` VALUES ('121','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:23:45');
INSERT INTO `notifications` VALUES ('122','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:23:48');
INSERT INTO `notifications` VALUES ('123','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:23:52');
INSERT INTO `notifications` VALUES ('124','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:23:55');
INSERT INTO `notifications` VALUES ('125','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:24:00');
INSERT INTO `notifications` VALUES ('126','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:24:04');
INSERT INTO `notifications` VALUES ('127','32','Bill Overdue','Your water bill #6 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:25:13');
INSERT INTO `notifications` VALUES ('128','33','Bill Overdue','Your water bill #9 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:25:17');
INSERT INTO `notifications` VALUES ('129','34','Bill Overdue','Your water bill #10 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:25:21');
INSERT INTO `notifications` VALUES ('130','35','Bill Overdue','Your water bill #14 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:25:26');
INSERT INTO `notifications` VALUES ('131','5','Bill Overdue','Your water bill #15 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:25:30');
INSERT INTO `notifications` VALUES ('132','15','Bill Overdue','Your water bill #18 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-09 19:25:34');
INSERT INTO `notifications` VALUES ('133','32','Bill Overdue','Your water bill #19 is overdue. Please pay to avoid disconnection.','warning','1','2026-02-16 14:53:47');
INSERT INTO `notifications` VALUES ('134','37','New Meter Reading Recorded','New meter reading of 50.52 recorded. Bill amount: ₱1,263.00. Due date: 2026-02-21.','system','1','2026-02-16 16:15:09');
INSERT INTO `notifications` VALUES ('135','32','Payment Received','Your payment of ₱787.5 was successfully recorded.','payment','1','2026-02-16 19:49:27');
INSERT INTO `notifications` VALUES ('136','32','Payment Received','Your payment of ₱787.5 was successfully recorded.','payment','1','2026-02-16 19:57:05');
INSERT INTO `notifications` VALUES ('137','32','Payment Received','Your payment of ₱787.5 was successfully recorded.','payment','1','2026-02-16 20:01:16');
INSERT INTO `notifications` VALUES ('138','32','Payment Received','Your payment of ₱787.5 was successfully recorded.','payment','1','2026-02-16 20:02:04');
INSERT INTO `notifications` VALUES ('139','32','Payment Received','Your payment of ₱787.5 was successfully recorded.','payment','1','2026-02-16 20:04:35');
INSERT INTO `notifications` VALUES ('140','32','Payment Received','Your payment of ₱787.5 was successfully recorded.','payment','1','2026-02-16 20:05:56');
INSERT INTO `notifications` VALUES ('141','36','New Meter Reading Recorded','New meter reading of 50.25 recorded. Bill amount: ₱1,256.25. Due date: 2026-02-22.','system','1','2026-02-17 19:02:23');
INSERT INTO `notifications` VALUES ('142','5','Service Request Resolved','Your service request has been resolved by staff.','system','1','2026-02-18 19:03:23');
INSERT INTO `notifications` VALUES ('143','35','Payment Received','Your payment of ₱2101.31 was successfully recorded.','payment','1','2026-02-19 19:11:46');
INSERT INTO `notifications` VALUES ('144','33','New Meter Reading Recorded','New meter reading of 110 recorded. Bill amount: ₱237.00. Due date: 2026-02-25.','system','1','2026-02-20 20:07:58');
INSERT INTO `notifications` VALUES ('145','33','New Meter Reading Recorded','New meter reading of 110 recorded. Bill amount: ₱170.64. Due date: 2026-02-25.','system','1','2026-02-20 20:14:25');
INSERT INTO `notifications` VALUES ('146','5','Service Request Resolved','Your service request has been resolved by staff.','system','1','2026-02-22 10:25:06');
INSERT INTO `notifications` VALUES ('147','37','Bill Overdue','Your water bill #20 is overdue. Please pay to avoid disconnection.','warning','1','2026-03-02 18:15:46');
INSERT INTO `notifications` VALUES ('148','36','Bill Overdue','Your water bill #21 is overdue. Please pay to avoid disconnection.','warning','0','2026-03-02 18:15:49');
INSERT INTO `notifications` VALUES ('149','33','Bill Overdue','Your water bill #23 is overdue. Please pay to avoid disconnection.','warning','0','2026-03-03 19:36:40');
INSERT INTO `notifications` VALUES ('150','15','Service Request Resolved','Your service request has been resolved by staff.','system','0','2026-03-05 17:23:45');
INSERT INTO `notifications` VALUES ('151','15','Service Request Resolved','Your service request has been resolved by staff.','system','0','2026-03-05 17:29:28');


CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `method` enum('cash','card','online') DEFAULT 'cash',
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `payments` VALUES ('1','1','2015.00','2026-01-11 18:47:33','online');
INSERT INTO `payments` VALUES ('4','5','6.25','2026-01-28 15:05:18','online');
INSERT INTO `payments` VALUES ('5','4','3265.00','2026-01-30 20:23:28','online');
INSERT INTO `payments` VALUES ('6','7','491.25','2026-01-30 20:43:03','cash');
INSERT INTO `payments` VALUES ('11','9','2513.00','2026-02-10 15:47:18','cash');
INSERT INTO `payments` VALUES ('12','15','1750.00','2026-02-16 19:20:18','cash');
INSERT INTO `payments` VALUES ('13','18','1848.00','2026-02-16 19:27:15','cash');
INSERT INTO `payments` VALUES ('14','6','1588.13','2026-02-16 19:30:54','online');
INSERT INTO `payments` VALUES ('24','19','787.50','2026-02-16 20:05:56','online');
INSERT INTO `payments` VALUES ('25','14','2101.31','2026-02-19 19:11:46','cash');
INSERT INTO `payments` VALUES ('30','10','2376.68','2026-02-19 19:38:24','cash');


CREATE TABLE `penalty_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `monthly_rate` decimal(5,2) NOT NULL DEFAULT '5.00',
  `grace_days` int NOT NULL DEFAULT '0',
  `max_penalty_percent` decimal(5,2) NOT NULL DEFAULT '50.00',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `penalty_settings` VALUES ('1','5.00','5','50.00','1','2026-01-30 20:00:15');


CREATE TABLE `rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rate_per_unit` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `rates` VALUES ('1','15.50','2026-01-11','2026-01-11 18:35:52');
INSERT INTO `rates` VALUES ('2','18.00','2026-01-12','2026-01-12 12:55:58');


CREATE TABLE `readings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `reading_date` date NOT NULL,
  `reading_value` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`,`reading_date`),
  KEY `idx_readings_date` (`reading_date`),
  CONSTRAINT `readings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `readings` VALUES ('3','2','2026-01-11','130.00');
INSERT INTO `readings` VALUES ('8','3','2026-01-17','130.60');
INSERT INTO `readings` VALUES ('9','2','2026-01-28','130.25');
INSERT INTO `readings` VALUES ('10','10','2026-01-28','60.50');
INSERT INTO `readings` VALUES ('11','3','2026-01-30','150.25');
INSERT INTO `readings` VALUES ('17','11','2026-01-30','100.52');
INSERT INTO `readings` VALUES ('18','12','2026-01-31','90.54');
INSERT INTO `readings` VALUES ('22','13','2026-02-01','80.05');
INSERT INTO `readings` VALUES ('23','2','2026-02-02','200.25');
INSERT INTO `readings` VALUES ('26','3','2026-02-02','220.65');
INSERT INTO `readings` VALUES ('27','10','2026-02-04','90.50');
INSERT INTO `readings` VALUES ('28','15','2026-02-16','50.52');
INSERT INTO `readings` VALUES ('29','14','2026-02-17','50.25');
INSERT INTO `readings` VALUES ('31','11','2026-02-20','110.00');


CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `roles` VALUES ('1','admin');
INSERT INTO `roles` VALUES ('3','customer');
INSERT INTO `roles` VALUES ('4','owner');
INSERT INTO `roles` VALUES ('2','staff');


CREATE TABLE `service_request_updates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `notes` text,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `service_request_updates` VALUES ('1','2','open','open','','1','2026-03-03 20:05:27');
INSERT INTO `service_request_updates` VALUES ('2','2','open','open','','1','2026-03-03 20:06:58');
INSERT INTO `service_request_updates` VALUES ('3','2','open','in_progress','Sending Technician Tomorrow','1','2026-03-03 22:37:57');
INSERT INTO `service_request_updates` VALUES ('4','2','in_progress','resolved','Technician Reseolved the Issue','1','2026-03-05 17:23:45');
INSERT INTO `service_request_updates` VALUES ('5','2','resolved','resolved','Technician Solved the Issue','1','2026-03-05 17:29:28');


CREATE TABLE `service_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('billing','meter','leak','connection','other') DEFAULT 'other',
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `status` enum('open','in_progress','resolved','rejected','cancelled') DEFAULT 'open',
  `admin_note` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `assigned_staff_id` int DEFAULT NULL,
  `staff_notes` text,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_sr_assigned` (`assigned_staff_id`),
  KEY `idx_sr_status` (`status`),
  KEY `idx_sr_created` (`created_at`),
  CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_staff_id`) REFERENCES `staffs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `service_requests` VALUES ('1','2','Meter Error','My Water Meter is going haywire.','meter','high','resolved','Needs Worker to check','2026-02-13 19:44:21','2026-02-22 10:25:06','1','');
INSERT INTO `service_requests` VALUES ('2','3','Hose Error','I think a leak has been getting my meter haywire','leak','high','resolved','Send a technician to check','2026-03-03 19:37:58','2026-03-05 17:29:28','1','Technician Solved the Issue');
INSERT INTO `service_requests` VALUES ('3','2','Meter Error','Meter not showing reading','meter','high','cancelled',NULL,'2026-03-03 20:51:22','2026-03-03 20:55:12',NULL,NULL);


CREATE TABLE `staffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `area_id` int DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `area_id` (`area_id`),
  CONSTRAINT `staffs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staffs_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `staffs` VALUES ('1','24',NULL,NULL,'2026-01-18 11:26:16');
INSERT INTO `staffs` VALUES ('3','38',NULL,NULL,'2026-03-03 21:04:32');


CREATE TABLE `user_contact_preferences` (
  `user_id` int NOT NULL,
  `email_enabled` tinyint DEFAULT '1',
  `sms_enabled` tinyint DEFAULT '1',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_contact_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `user_contact_preferences` VALUES ('5','1','1','2026-02-07 20:31:06');
INSERT INTO `user_contact_preferences` VALUES ('33','1','1','2026-02-10 15:46:56');
INSERT INTO `user_contact_preferences` VALUES ('37','1','1','2026-02-10 14:10:55');
INSERT INTO `user_contact_preferences` VALUES ('44','1','1','2026-03-04 20:25:41');
INSERT INTO `user_contact_preferences` VALUES ('47','1','1','2026-03-04 20:46:05');
INSERT INTO `user_contact_preferences` VALUES ('48','1','1','2026-03-06 20:47:18');


CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `role_id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `failed_attempts` int DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `email_2` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` VALUES ('3','Khen Ashley Limos','Seselangen, Sual, Pangasinan','1','admin@aquatrack.com',NULL,'$2y$10$l/2AdTWYi0vWDpxWlf2Zju9aFogelsGieF63LiPjIwnOqygkdas8.','2026-01-11 15:20:53','0',NULL,'active','e685755534f161853230701e0d4b3a35a597aad26c128fa62f48757ae3fbf8b5','2026-03-08 18:44:27','0',NULL);
INSERT INTO `users` VALUES ('5','Khen Ashley Limos','Seselangen, Sual, Pangasinan','3','customer1@aquatrack.com','+639610527055','$2y$10$bNEsUzRBL1d21qVmHZy2QOSuanxoIu.KYT8zXOsDeSwfVY2Edg3ni','2026-01-11 18:30:42','0',NULL,'active',NULL,'2026-03-06 19:59:39','0',NULL);
INSERT INTO `users` VALUES ('6','Ashie Lim','Seselangen, Sual, Pangasinan','4','owner@aquatrack.com',NULL,'$2y$10$Bg8s7bEg5llgIEKMOlxdLOvZgufCyC4REsEYEFcTJd5D50ss0QqHK','2026-01-11 18:53:01','0',NULL,'active',NULL,'2026-03-08 18:42:46','0',NULL);
INSERT INTO `users` VALUES ('15','Kurt Ashton Limos','Seselangen, Sual, Pangasinan','3','trojankiller06@gmail.com',NULL,'$2y$10$IImSMY4ecJXbxkQ0RZ4yOu.fInFDHAFeSBe5Qhm9fZ4Z3dfSt2IUO','2026-01-17 15:24:58','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('24','Ashie','Seselangen, Sual, Pangasinan','2','staff@aquatrack.com',NULL,'$2y$10$VY6zt3Ti0UeGvvuQuMxw1OqcUGUBryLp5YfmHMd6e9ZR4XxlilDxK','2026-01-18 11:26:16','0',NULL,'active',NULL,'2026-03-08 18:39:27','0',NULL);
INSERT INTO `users` VALUES ('32','Khen','Centro, Seselangen','3','limoskhen06@gmail.com',NULL,'$2y$10$762B3h3iB/Bp47klCvl8sOptCKHs/GmDTLHtU0/ps.h/3DlHyPlQK','2026-01-28 16:13:52','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('33','Ashie','Laud, Seselangen, Sual, Pangasinan','3','ashleylimos06@gmail.com',NULL,'$2y$10$7aoC.Ido9Em9CWABl/9Zw.XW2VHZ.bxBYBNf750Ta2gZOzP2SE2PW','2026-01-30 21:23:59','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('34','Ashley','Poblacion East, Sual, Pangasinan','3','sample@gmail.com',NULL,'$2y$10$2RIgb8aui0qwJP0Pb4Rwo.K2i6zWT5ICDfXZxnmz1k5Kh04Gsd.qW','2026-01-31 20:01:26','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('35','Reyna','Caoayan, Sual, Pangasinan','3','reyna@gmail.com',NULL,'$2y$10$Aou9MFnKKo4Pjk3W2Oo97.tS/EuR/PCgSNHz6F5vHgDmh9/ZEBr3e','2026-02-01 11:13:17','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('36','Kiara Allyn Limos','Paitan West, Sual, Pangasinan','3','khenashley22@gmail.com','+639918722254','$2y$10$8o/GcSI3pAx3w6ROyhL.8O8xbdrP9OMcGDr1MZWwE5BgI.UkcdUsa','2026-02-07 20:02:44','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('37','Xiao','Seselangen, Sual Pangasinan','3','kade.limos.up@phinmaed.com','+639610527056','$2y$10$CzBTwnYruKIbkZGPMDk6luel3Rv11rl5XdFVEIaROY2FCdEVWGNsq','2026-02-10 14:10:26','0',NULL,'active',NULL,'2026-03-06 12:48:25','0',NULL);
INSERT INTO `users` VALUES ('38','Khen','Seselangen, Sual Pangasinan','2','staff2@aquatrack.com','+639208452137','$2y$10$9aNgqASB0wsUFTqtoHhUPed4D6ldNbwevwHsSURHDeYw3mqeDPRPC','2026-03-03 21:04:32','0',NULL,'active',NULL,NULL,'0',NULL);
INSERT INTO `users` VALUES ('44','Reynaline Meru','Tangcarang, Alaminos City, Pangasinan','3','customer2@aquatrack.com','+639151335006','$2y$10$oftWnWb70UC7FQi1uMGhxeMzM0EXziW/sSqDQ8Qwyt35QUaUv4tZK','2026-03-04 20:25:41','0',NULL,'active',NULL,'2026-03-04 20:26:01','0',NULL);
INSERT INTO `users` VALUES ('47','Merlyn Limos','Seselangen, Sual, Pangasinan','3','customer3@aquatrack.com','+639219480654','$2y$10$DFItSVbgN/DJRDa5TbRsneT6nVnUjffN7FmM0qo3KteVF6t/NJqk.','2026-03-04 20:46:05','0',NULL,'active',NULL,'2026-03-04 20:46:22','0',NULL);
INSERT INTO `users` VALUES ('48','John Paul Sison','Uyong, Labrador, Pangasinan','3','sisonjohnpaul@gmail.com','+639064750087','$2y$10$1IU3ORvqaLG.dV4FO3Ar1OcZSpgpm0/DSr14n4JtmKZJBdGxe2c.y','2026-03-06 20:47:18','0',NULL,'active',NULL,'2026-03-06 20:47:31','0',NULL);
