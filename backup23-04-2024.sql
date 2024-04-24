SET foreign_key_checks = 0;
#
# TABLE STRUCTURE FOR: anexos
#

DROP TABLE IF EXISTS `anexos`;

CREATE TABLE `anexos` (
  `idAnexos` int(11) NOT NULL AUTO_INCREMENT,
  `anexo` varchar(45) DEFAULT NULL,
  `thumb` varchar(45) DEFAULT NULL,
  `url` varchar(300) DEFAULT NULL,
  `path` varchar(300) DEFAULT NULL,
  `os_id` int(11) NOT NULL,
  PRIMARY KEY (`idAnexos`),
  KEY `fk_anexos_os1` (`os_id`),
  CONSTRAINT `fk_anexos_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: anotacoes_os
#

DROP TABLE IF EXISTS `anotacoes_os`;

CREATE TABLE `anotacoes_os` (
  `idAnotacoes` int(11) NOT NULL AUTO_INCREMENT,
  `anotacao` varchar(255) NOT NULL,
  `data_hora` datetime NOT NULL,
  `os_id` int(11) NOT NULL,
  PRIMARY KEY (`idAnotacoes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: categorias
#

DROP TABLE IF EXISTS `categorias`;

CREATE TABLE `categorias` (
  `idCategorias` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(80) DEFAULT NULL,
  `cadastro` date DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `tipo` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`idCategorias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: ci_sessions
#

DROP TABLE IF EXISTS `ci_sessions`;

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('sbsr23pi9mo5ce3trq97dke1jcc69rcu', '::1', 1713387461, '__ci_last_regenerate|i:1713387420;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('e4e4dif4iuefvvo0lig9svqd6o4b9l1c', '::1', 1713395378, '__ci_last_regenerate|i:1713395367;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('05pl66ter2r4ro493o4o67vc8gq74a8j', '::1', 1713434797, '__ci_last_regenerate|i:1713434797;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('4kh6kdum1bjbf7p1q6ueb8vt2gosm5lm', '::1', 1713434800, '__ci_last_regenerate|i:1713434797;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('9p26t9i4gbhdi6rtddn23ustr8isnjh7', '::1', 1713470053, '__ci_last_regenerate|i:1713470053;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('cl8hr8n9edj7bi245q9fh7o9ang958ud', '::1', 1713470397, '__ci_last_regenerate|i:1713470397;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('vlku97toj94nsratndvdsjm934pec1i8', '::1', 1713471594, '__ci_last_regenerate|i:1713471594;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('2b3de5566lfrr11vhvm31ltn1g6fncvq', '::1', 1713471960, '__ci_last_regenerate|i:1713471960;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('it1qnii8d2lfkel2b74vava02rdtdue7', '::1', 1713472263, '__ci_last_regenerate|i:1713472263;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('c1j1rik3356qkbond598hovhh64tq53v', '::1', 1713472668, '__ci_last_regenerate|i:1713472668;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('11o4h6gtb7gb0a9k0te5e12hv7i59g7g', '::1', 1713473063, '__ci_last_regenerate|i:1713473063;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('no10evrn1spc7q7k983as1ejns9jl9e7', '::1', 1713473377, '__ci_last_regenerate|i:1713473377;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('b4592qt1r3l841jbfc12j890nitt0r9q', '::1', 1713473652, '__ci_last_regenerate|i:1713473607;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('rsa8ldss2cp3l6p3t0cpj3gc1996im42', '::1', 1713563237, '__ci_last_regenerate|i:1713563220;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('q6n3q0ccbo7tlqaunmp88jf9aok10omf', '::1', 1713565064, '__ci_last_regenerate|i:1713565064;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;url_image_user|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('u51rmggqcriaasmuon9js45r3h9iorjh', '::1', 1713565451, '__ci_last_regenerate|i:1713565451;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;url_image_user|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('f1jm74n8oulrbnfrargo4qvheilaaam0', '::1', 1713565992, '__ci_last_regenerate|i:1713565992;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;url_image_user|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('n0g00n7jcf46deqcsr4jeh9cvvftcnev', '::1', 1713566023, '__ci_last_regenerate|i:1713565992;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|N;id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;url_image_user|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ti7aatab1cjk7jrmk6j129f2b5kri0u2', '::1', 1713574507, '__ci_last_regenerate|i:1713574507;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('d16upfmv60g3ejj4g58er7a2krqceigf', '::1', 1713577236, '__ci_last_regenerate|i:1713577236;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7gpkcid3aok37c3vi4a0alrvu5b1mogc', '::1', 1713575406, '__ci_last_regenerate|i:1713575406;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('3vfa36kt67j19ac4n0s57c6iokelo42o', '::1', 1713575465, '__ci_last_regenerate|i:1713575406;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('m1s4dfrur07q445uucu4ng6bjfn7hftp', '::1', 1713578072, '__ci_last_regenerate|i:1713578072;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('tebfmrgspkff6juucfj9ld8glqtiru6a', '::1', 1713579078, '__ci_last_regenerate|i:1713579078;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('aprdajq2lpbael5mugmkuaktjmbat8d8', '::1', 1713579381, '__ci_last_regenerate|i:1713579381;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('hif45o852d5obd1s1nnotk1rp2lnjtse', '::1', 1713579702, '__ci_last_regenerate|i:1713579702;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('dvqt999aprd0fneg1287d076u811j3mp', '::1', 1713580089, '__ci_last_regenerate|i:1713580089;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('miiqt2e6ue8mso09looschkveki9acbd', '::1', 1713581810, '__ci_last_regenerate|i:1713581810;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('vqi522k603gug6tv6l92nugi36i1j7ab', '::1', 1713582206, '__ci_last_regenerate|i:1713582206;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('bkklo7nvr186q0nbkqkrte77h2tt9tsc', '::1', 1713582531, '__ci_last_regenerate|i:1713582531;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('i9jpnraq661hovtet6gui3vcbh74k26b', '::1', 1713582918, '__ci_last_regenerate|i:1713582918;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('vpvdkco1tjlsof1t5jj15jqku3sqcm7g', '::1', 1713583235, '__ci_last_regenerate|i:1713583235;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:28:\"Produto editado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('0dih2n0n2mv50m4ood5eifnl3qghg4jh', '::1', 1713583237, '__ci_last_regenerate|i:1713583235;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('q1qkrgank014v705dpd9s6695hqka7ho', '::1', 1713608893, '__ci_last_regenerate|i:1713608893;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('42ourvdv86r6vhq0l4a550j8jsm9kdee', '::1', 1713609249, '__ci_last_regenerate|i:1713609249;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7r27i59se768qfj3p48gpoi3f8ca94u5', '::1', 1713609608, '__ci_last_regenerate|i:1713609608;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('13uq5s77dk64nva83ksoocturcjj0k5i', '::1', 1713609951, '__ci_last_regenerate|i:1713609951;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('vjkas29qn554496ab8qqcsn6s3anf278', '::1', 1713610464, '__ci_last_regenerate|i:1713610464;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('lehdq6o0tkqe1mbs34c5o81bin4k90qr', '::1', 1713610829, '__ci_last_regenerate|i:1713610829;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('kcmgb13dop9bp4d51i7mnqmhefb6cjt7', '::1', 1713611293, '__ci_last_regenerate|i:1713611293;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('36bg2domtlj9jj2h0vigjeqlt1t8hs3j', '::1', 1713611595, '__ci_last_regenerate|i:1713611595;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('am59vqsqlgc3knh119r8bohqimddd2jo', '::1', 1713611898, '__ci_last_regenerate|i:1713611898;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7tvqta69p74ja77m2n17e5ks0sr3knd8', '::1', 1713612199, '__ci_last_regenerate|i:1713612199;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7ce8obr6eau8o4o4m4kjtjs1das844u1', '::1', 1713612500, '__ci_last_regenerate|i:1713612500;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7d9bb4gmisfq5mbsarm1vbjckr9niah7', '::1', 1713612881, '__ci_last_regenerate|i:1713612881;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('2fprrvbrba76sjmis090aifs1sl1ua7o', '::1', 1713613284, '__ci_last_regenerate|i:1713613284;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('47asnsu7gvbpir9hvbptmpkqtlgc6mpn', '::1', 1713613603, '__ci_last_regenerate|i:1713613603;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('94gm145ldn6ki1kr98u70ujp28c8h44k', '::1', 1713613915, '__ci_last_regenerate|i:1713613915;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7649l092dtihqg05vjv2hj7ovfjlon6f', '::1', 1713614217, '__ci_last_regenerate|i:1713614217;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('6adl5hqto0o0b5hu3elctf5q1je04972', '::1', 1713614532, '__ci_last_regenerate|i:1713614532;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('11ddo1ud058hsl29c57554ubse65lkb8', '::1', 1713618015, '__ci_last_regenerate|i:1713618015;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('kuk5ja39b8cvft2gon21jlcfrhq6m91e', '::1', 1713618553, '__ci_last_regenerate|i:1713618553;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('nk0jf7tu62u5d16n1m3ku05pkh3nuspg', '::1', 1713618966, '__ci_last_regenerate|i:1713618966;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('vluck66ma0ddobarrr9mtd8vqop5pafu', '::1', 1713619285, '__ci_last_regenerate|i:1713619285;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('3t0rn55i4tf5c0ip7c4ilj7mc4enctgj', '::1', 1713619597, '__ci_last_regenerate|i:1713619597;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('9m2kp1i8id4dt43agarenaq4l03m5at7', '::1', 1713619998, '__ci_last_regenerate|i:1713619998;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('cckbrktvcnae4al5rfsbigphr06h4cko', '::1', 1713619999, '__ci_last_regenerate|i:1713619998;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:31:\"Produto adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('dcceu3m8cgg71ljp6n6cc9th4rb4qqvh', '::1', 1713633353, '__ci_last_regenerate|i:1713633353;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('3pkql0sb5t984ms00vb3naue8nhs94nv', '::1', 1713634900, '__ci_last_regenerate|i:1713634900;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('4nbot3mltc5umeesa1nkialb6guql79f', '::1', 1713635471, '__ci_last_regenerate|i:1713635471;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('f3mrcvkglnqv0c58kou9eekafdb9gtq5', '::1', 1713635804, '__ci_last_regenerate|i:1713635804;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:35:\"Lançamento adicionado com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('5ma2qfoktoih7r78th1j6n9mtseuigup', '::1', 1713635804, '__ci_last_regenerate|i:1713635804;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ajibdt8tcifoedldqgk1svb52i8uccdj', '::1', 1713649402, '__ci_last_regenerate|i:1713649402;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('8jis0ad36mert2cheb6jejsgul8s1d11', '::1', 1713654946, '__ci_last_regenerate|i:1713654946;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('grk5a9gjnn98jk1t8jsm0mlpn85lee4u', '::1', 1713654948, '__ci_last_regenerate|i:1713654946;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('313d3l41tjsbrev0i94ujbf8v8ho8miv', '::1', 1713702821, '__ci_last_regenerate|i:1713702821;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('8l5v3dbh1rc7gbr8ed5hi2vdd8p0oh9p', '::1', 1713706913, '__ci_last_regenerate|i:1713706913;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('lbglgfn11a78femsgg0aeqoch02ev2f2', '::1', 1713707256, '__ci_last_regenerate|i:1713707256;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('jhf6aq9oalgoi9f3h6l5itumvmfmi5us', '::1', 1713707595, '__ci_last_regenerate|i:1713707595;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('l35r8g86m4otbp9a8h60msgfohl0mm6j', '::1', 1713707844, '__ci_last_regenerate|i:1713707595;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('6krovgt0sn5lflsmseplbc3b91ukdcfk', '::1', 1713737402, '__ci_last_regenerate|i:1713737367;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('9trtvvn24rt9itl12m5igo9gd18aj0va', '::1', 1713750366, '__ci_last_regenerate|i:1713750366;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('h6sma85ee301fgv94qnf88iajkds7h06', '::1', 1713750695, '__ci_last_regenerate|i:1713750695;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('93m4q9druj9fnui23mrrp1djjnke5rfg', '::1', 1713750997, '__ci_last_regenerate|i:1713750997;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;success|s:28:\"Venda excluída com sucesso!\";__ci_vars|a:1:{s:7:\"success\";s:3:\"old\";}');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('12qpda2ri8009jovf2ersmete71van4o', '::1', 1713751342, '__ci_last_regenerate|i:1713751342;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('h3rvupef4bjl4bb0b6oe4kbvuudbj0lr', '::1', 1713751654, '__ci_last_regenerate|i:1713751654;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('5v3g1pdiguu4tnsfu1t2r1dm4ksb3skl', '::1', 1713752101, '__ci_last_regenerate|i:1713752101;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('9cie67tm74vr2vb9nc991oenkbev69i3', '::1', 1713752486, '__ci_last_regenerate|i:1713752486;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('cdknqri1hg7on8mcsrl2d81r0hm7cjrj', '::1', 1713753398, '__ci_last_regenerate|i:1713753398;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('rja9hohjdcfefgcpb0j2m2t8ep6e3mbd', '::1', 1713753844, '__ci_last_regenerate|i:1713753844;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('679dn9muf107hl8m1brjl098sigqjpnq', '::1', 1713754364, '__ci_last_regenerate|i:1713754364;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ungs3lh6slsrorifp2qvjn43echndne4', '::1', 1713755473, '__ci_last_regenerate|i:1713755473;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ca5ktutps6b3ofev2858fgpkm6h8jgeo', '::1', 1713755473, '__ci_last_regenerate|i:1713755473;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('e8iienuvtddjj7umigprd3nqa8ppgcsv', '::1', 1713782071, '__ci_last_regenerate|i:1713782071;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('0gt1ugjr65hlhia91ivbum1bod935v9q', '::1', 1713782572, '__ci_last_regenerate|i:1713782572;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('m9uso3aojip1ndfdvmrk4tbs1q88dcvf', '::1', 1713783550, '__ci_last_regenerate|i:1713783550;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('7pnt4fk7h03lu0mdt4e9pfa9q0o4ulih', '::1', 1713783579, '__ci_last_regenerate|i:1713783550;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('6srr5pieaglefrd1n6kcujapfhiurla5', '::1', 1713809093, '__ci_last_regenerate|i:1713809093;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('d4v4nvlhlujpmhaij6kdkfdaltild7ev', '::1', 1713806891, '__ci_last_regenerate|i:1713806891;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('3hmcv52d5vq4u0d7sgn7951uo1fnogda', '::1', 1713810441, '__ci_last_regenerate|i:1713810441;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ba2c8kd1t68sovhda8pqufmscok862kj', '::1', 1713810523, '__ci_last_regenerate|i:1713810441;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('absn0oinfg8ur4fr9c8d5lhl9q5675q5', '::1', 1713824374, '__ci_last_regenerate|i:1713824374;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('lum3tph8gdq5mpqgq2f0hth13f5995s8', '::1', 1713824418, '__ci_last_regenerate|i:1713824374;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('6t78dmikmt9nekd2u35u9hrdi760ep8e', '::1', 1713838491, '__ci_last_regenerate|i:1713838453;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('18pkcqu2eusd6kdj3fb5rpumkfijl7po', '::1', 1713861247, '__ci_last_regenerate|i:1713861049;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('u6dfn2pamh8gnf57gefd53fkel9c3u3k', '::1', 1713861050, '__ci_last_regenerate|i:1713861049;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('itjil47uk7rup1ftgfv80buh39eves0g', '::1', 1713912440, '__ci_last_regenerate|i:1713912440;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('60o462l5nptr2ssmff1tu8unbonmjrdp', '::1', 1713917618, '__ci_last_regenerate|i:1713917618;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('qt8oplq55p6os378g99i3t8h94lbm7o3', '::1', 1713923210, '__ci_last_regenerate|i:1713923210;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('gq4eeqpnp75oi6gas52jepm6umutfrir', '::1', 1713923215, '__ci_last_regenerate|i:1713923210;nome_admin|s:8:\"EDNILSON\";email_admin|s:16:\"ed8156@gmail.com\";url_image_user_admin|s:36:\"ded6355c9b13ae4b079234d53b19aeaa.png\";id_admin|s:1:\"1\";permissao|s:1:\"1\";logado|b:1;');


#
# TABLE STRUCTURE FOR: clientes
#

DROP TABLE IF EXISTS `clientes`;

CREATE TABLE `clientes` (
  `idClientes` int(11) NOT NULL AUTO_INCREMENT,
  `asaas_id` varchar(255) DEFAULT NULL,
  `nomeCliente` varchar(255) NOT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `pessoa_fisica` tinyint(1) NOT NULL DEFAULT 1,
  `documento` varchar(20) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(200) NOT NULL,
  `dataCadastro` date DEFAULT NULL,
  `rua` varchar(70) DEFAULT NULL,
  `numero` varchar(15) DEFAULT NULL,
  `bairro` varchar(45) DEFAULT NULL,
  `cidade` varchar(45) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `cep` varchar(20) DEFAULT NULL,
  `contato` varchar(45) DEFAULT NULL,
  `complemento` varchar(45) DEFAULT NULL,
  `fornecedor` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idClientes`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `clientes` (`idClientes`, `asaas_id`, `nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`, `complemento`, `fornecedor`) VALUES (1, NULL, 'SPJ MOLLDURAS', NULL, 0, '', '', '', 'ed8156@gmail.com', '$2y$10$Ua.pij2WZjTYTwnm4VXsn.jj0MGPeknIo8JwNrllksqKjfcuWSoze', '2024-04-18', '', '', '', 'SANTA CATARINA', 'SC', '', '', '', 0);
INSERT INTO `clientes` (`idClientes`, `asaas_id`, `nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`, `complemento`, `fornecedor`) VALUES (2, NULL, 'JERONIMO VIDROS', NULL, 0, '', '', '', 'ed8156@gmail.com', '$2y$10$2FTen1RjWd1htMvMy4ZkRuMVMZglXr8WAYc5IDiJvQqlJWM1NCXnK', '2024-04-18', '', '', '', '', '', '', '', '', 1);
INSERT INTO `clientes` (`idClientes`, `asaas_id`, `nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`, `complemento`, `fornecedor`) VALUES (3, NULL, 'MOLDURARTE MDF', NULL, 0, '', '', '', 'ed8156@gmail.com', '$2y$10$dhImfhv3SRm50eMWuTxOneyjcQdY6uAB/7d.XmGu.EU1m5KzXvjUa', '2024-04-18', '', '', '', '', '', '', '', '', 0);
INSERT INTO `clientes` (`idClientes`, `asaas_id`, `nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`, `complemento`, `fornecedor`) VALUES (4, NULL, 'DEUSIMARA DE OLIVEIRA ALVES', NULL, 0, '493.026.711-00', '(61)98212-4377', '(61)98212-4377', 'stylofotostudio@gmail.com', '$2y$10$mt0sKAO8k3mYi4NOWg5lOufGyDAp6JMgXnmYKLVDuoQHfdrUVeDCq', '2024-04-18', 'QNQ 4 Conjunto 1', '23', 'Ceilândia Norte (Ceilândia)', 'Brasília', 'DF', '72270-401', 'Deusimar', '', 0);
INSERT INTO `clientes` (`idClientes`, `asaas_id`, `nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`, `complemento`, `fornecedor`) VALUES (5, NULL, 'TEREZA', NULL, 0, '', '', '', 'ed8156@gmail.com', '$2y$10$uAcxZyd7YLpQaq5/P4E7heraHwjdrtxQaml7oOSu0mdec4ocl/CQ6', '2024-04-18', 'QNQ 5', '', '', 'Ceilandia', 'DF', '', '', '', 0);
INSERT INTO `clientes` (`idClientes`, `asaas_id`, `nomeCliente`, `sexo`, `pessoa_fisica`, `documento`, `telefone`, `celular`, `email`, `senha`, `dataCadastro`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `contato`, `complemento`, `fornecedor`) VALUES (6, NULL, 'JUCILENE DA SILVA LIMA', NULL, 1, '057.566.143-70', '(61)99562-6425', '(61)99562-6425', 'jucilenesilvafotografia@gmail.com', '$2y$10$yeNBjO/XCaM/33ivnw4KEOnXqBr18r13sq92x2LsXTIO1VaF33wIm', '2024-04-21', 'QNQ 4 Conjunto 1', '23', 'Ceilândia Norte (Ceilândia)', 'Brasília', 'DF', '72270-401', 'Jucilene', '', 0);


#
# TABLE STRUCTURE FOR: cobrancas
#

DROP TABLE IF EXISTS `cobrancas`;

CREATE TABLE `cobrancas` (
  `idCobranca` int(11) NOT NULL AUTO_INCREMENT,
  `charge_id` varchar(255) DEFAULT NULL,
  `conditional_discount_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `custom_id` int(11) DEFAULT NULL,
  `expire_at` date NOT NULL,
  `message` varchar(255) NOT NULL,
  `payment_method` varchar(11) DEFAULT NULL,
  `payment_url` varchar(255) DEFAULT NULL,
  `request_delivery_address` varchar(64) DEFAULT NULL,
  `status` varchar(36) NOT NULL,
  `total` decimal(10,2) DEFAULT 0.00,
  `barcode` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `payment_gateway` varchar(255) DEFAULT NULL,
  `payment` varchar(64) NOT NULL,
  `pdf` varchar(255) DEFAULT NULL,
  `vendas_id` int(11) DEFAULT NULL,
  `os_id` int(11) DEFAULT NULL,
  `clientes_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idCobranca`),
  KEY `fk_cobrancas_os1` (`os_id`),
  KEY `fk_cobrancas_vendas1` (`vendas_id`),
  KEY `fk_cobrancas_clientes1` (`clientes_id`),
  CONSTRAINT `fk_cobrancas_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_cobrancas_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_cobrancas_vendas1` FOREIGN KEY (`vendas_id`) REFERENCES `vendas` (`idVendas`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: configuracoes
#

DROP TABLE IF EXISTS `configuracoes`;

CREATE TABLE `configuracoes` (
  `idConfig` int(11) NOT NULL AUTO_INCREMENT,
  `config` varchar(20) NOT NULL,
  `valor` text DEFAULT NULL,
  PRIMARY KEY (`idConfig`),
  UNIQUE KEY `config` (`config`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (2, 'app_name', 'DECORARTE MOLDURAS');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (3, 'app_theme', 'default');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (4, 'per_page', '10');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (5, 'os_notification', 'cliente');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (6, 'control_estoque', '1');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (7, 'notifica_whats', 'Prezado(a), {CLIENTE_NOME} a OS de nº {NUMERO_OS} teve o status alterado para: {STATUS_OS} segue a descrição {DESCRI_PRODUTOS} com valor total de {VALOR_OS}! Para mais informações entre em contato conosco. Atenciosamente, {EMITENTE} {TELEFONE_EMITENTE}.');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (8, 'control_baixa', '0');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (9, 'control_editos', '1');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (10, 'control_datatable', '1');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (11, 'pix_key', '83137335515');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (12, 'os_status_list', '[\"Aberto\",\"Faturado\",\"Negocia\\u00e7\\u00e3o\",\"Em Andamento\",\"Or\\u00e7amento\",\"Finalizado\",\"Cancelado\",\"Aguardando Pe\\u00e7as\",\"Aprovado\"]');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (13, 'control_edit_vendas', '1');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (14, 'email_automatico', '1');
INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (15, 'control_2vias', '1');


#
# TABLE STRUCTURE FOR: contas
#

DROP TABLE IF EXISTS `contas`;

CREATE TABLE `contas` (
  `idContas` int(11) NOT NULL AUTO_INCREMENT,
  `conta` varchar(45) DEFAULT NULL,
  `banco` varchar(45) DEFAULT NULL,
  `numero` varchar(45) DEFAULT NULL,
  `saldo` decimal(10,2) DEFAULT NULL,
  `cadastro` date DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `tipo` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`idContas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: documentos
#

DROP TABLE IF EXISTS `documentos`;

CREATE TABLE `documentos` (
  `idDocumentos` int(11) NOT NULL AUTO_INCREMENT,
  `documento` varchar(70) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `file` varchar(100) DEFAULT NULL,
  `path` varchar(300) DEFAULT NULL,
  `url` varchar(300) DEFAULT NULL,
  `cadastro` date DEFAULT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `tipo` varchar(15) DEFAULT NULL,
  `tamanho` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idDocumentos`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `documentos` (`idDocumentos`, `documento`, `descricao`, `file`, `path`, `url`, `cadastro`, `categoria`, `tipo`, `tamanho`) VALUES (1, 'Nota Fiscal', '', '8add63f118b4baa38c53eee6a3410c58.pdf', 'C:/xampp/htdocs/mapos/assets/arquivos/21-04-2024/8add63f118b4baa38c53eee6a3410c58.pdf', 'http://localhost/mapos/assets/arquivos/21-04-2024/8add63f118b4baa38c53eee6a3410c58.pdf', '2024-04-17', NULL, '.pdf', '149.76');


#
# TABLE STRUCTURE FOR: email_queue
#

DROP TABLE IF EXISTS `email_queue`;

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to` varchar(255) NOT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sending','sent','failed') DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `headers` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: emitente
#

DROP TABLE IF EXISTS `emitente`;

CREATE TABLE `emitente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `cnpj` varchar(45) DEFAULT NULL,
  `ie` varchar(50) DEFAULT NULL,
  `rua` varchar(70) DEFAULT NULL,
  `numero` varchar(15) DEFAULT NULL,
  `bairro` varchar(45) DEFAULT NULL,
  `cidade` varchar(45) DEFAULT NULL,
  `uf` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `url_logo` varchar(225) DEFAULT NULL,
  `cep` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `emitente` (`id`, `nome`, `cnpj`, `ie`, `rua`, `numero`, `bairro`, `cidade`, `uf`, `telefone`, `email`, `url_logo`, `cep`) VALUES (1, 'DECORARTE MOLDURAS', '45.113.424/0001-09', '0811278900131', 'Quadra 8', '51', 'Setor Industrial (Ceilândia)', 'Brasília', 'DF', '(61)99211-5930', 'ed8156@gmail.com', 'http://localhost/mapos/assets/uploads/c42ebe2112c4bfc66a1691294b868a0c.png', '72265-080');


#
# TABLE STRUCTURE FOR: equipamentos
#

DROP TABLE IF EXISTS `equipamentos`;

CREATE TABLE `equipamentos` (
  `idEquipamentos` int(11) NOT NULL AUTO_INCREMENT,
  `equipamento` varchar(150) NOT NULL,
  `num_serie` varchar(80) DEFAULT NULL,
  `modelo` varchar(80) DEFAULT NULL,
  `cor` varchar(45) DEFAULT NULL,
  `descricao` varchar(150) DEFAULT NULL,
  `tensao` varchar(45) DEFAULT NULL,
  `potencia` varchar(45) DEFAULT NULL,
  `voltagem` varchar(45) DEFAULT NULL,
  `data_fabricacao` date DEFAULT NULL,
  `marcas_id` int(11) DEFAULT NULL,
  `clientes_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idEquipamentos`),
  KEY `fk_equipanentos_marcas1_idx` (`marcas_id`),
  KEY `fk_equipanentos_clientes1_idx` (`clientes_id`),
  CONSTRAINT `fk_equipanentos_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_equipanentos_marcas1` FOREIGN KEY (`marcas_id`) REFERENCES `marcas` (`idMarcas`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: equipamentos_os
#

DROP TABLE IF EXISTS `equipamentos_os`;

CREATE TABLE `equipamentos_os` (
  `idEquipamentos_os` int(11) NOT NULL AUTO_INCREMENT,
  `defeito_declarado` varchar(200) DEFAULT NULL,
  `defeito_encontrado` varchar(200) DEFAULT NULL,
  `solucao` varchar(45) DEFAULT NULL,
  `equipamentos_id` int(11) DEFAULT NULL,
  `os_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idEquipamentos_os`),
  KEY `fk_equipamentos_os_equipanentos1_idx` (`equipamentos_id`),
  KEY `fk_equipamentos_os_os1_idx` (`os_id`),
  CONSTRAINT `fk_equipamentos_os_equipanentos1` FOREIGN KEY (`equipamentos_id`) REFERENCES `equipamentos` (`idEquipamentos`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_equipamentos_os_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: garantias
#

DROP TABLE IF EXISTS `garantias`;

CREATE TABLE `garantias` (
  `idGarantias` int(11) NOT NULL AUTO_INCREMENT,
  `dataGarantia` date DEFAULT NULL,
  `refGarantia` varchar(15) DEFAULT NULL,
  `textoGarantia` text DEFAULT NULL,
  `usuarios_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idGarantias`),
  KEY `fk_garantias_usuarios1` (`usuarios_id`),
  CONSTRAINT `fk_garantias_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: itens_de_vendas
#

DROP TABLE IF EXISTS `itens_de_vendas`;

CREATE TABLE `itens_de_vendas` (
  `idItens` int(11) NOT NULL AUTO_INCREMENT,
  `subTotal` decimal(10,2) DEFAULT 0.00,
  `quantidade` int(11) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT 0.00,
  `vendas_id` int(11) NOT NULL,
  `produtos_id` int(11) NOT NULL,
  PRIMARY KEY (`idItens`),
  KEY `fk_itens_de_vendas_vendas1` (`vendas_id`),
  KEY `fk_itens_de_vendas_produtos1` (`produtos_id`),
  CONSTRAINT `fk_itens_de_vendas_produtos1` FOREIGN KEY (`produtos_id`) REFERENCES `produtos` (`idProdutos`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_itens_de_vendas_vendas1` FOREIGN KEY (`vendas_id`) REFERENCES `vendas` (`idVendas`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `itens_de_vendas` (`idItens`, `subTotal`, `quantidade`, `preco`, `vendas_id`, `produtos_id`) VALUES (7, '120.00', 10, '12.00', 6, 58);
INSERT INTO `itens_de_vendas` (`idItens`, `subTotal`, `quantidade`, `preco`, `vendas_id`, `produtos_id`) VALUES (8, '60.00', 10, '6.00', 7, 1);


#
# TABLE STRUCTURE FOR: lancamentos
#

DROP TABLE IF EXISTS `lancamentos`;

CREATE TABLE `lancamentos` (
  `idLancamentos` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `valor_desconto` decimal(10,2) DEFAULT 0.00,
  `tipo_desconto` varchar(8) DEFAULT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `baixado` tinyint(1) DEFAULT 0,
  `cliente_fornecedor` varchar(255) DEFAULT NULL,
  `forma_pgto` varchar(100) DEFAULT NULL,
  `tipo` varchar(45) DEFAULT NULL,
  `anexo` varchar(250) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `clientes_id` int(11) DEFAULT NULL,
  `categorias_id` int(11) DEFAULT NULL,
  `contas_id` int(11) DEFAULT NULL,
  `vendas_id` int(11) DEFAULT NULL,
  `usuarios_id` int(11) NOT NULL,
  PRIMARY KEY (`idLancamentos`),
  KEY `fk_lancamentos_clientes1` (`clientes_id`),
  KEY `fk_lancamentos_categorias1_idx` (`categorias_id`),
  KEY `fk_lancamentos_contas1_idx` (`contas_id`),
  KEY `fk_lancamentos_usuarios1` (`usuarios_id`),
  CONSTRAINT `fk_lancamentos_categorias1` FOREIGN KEY (`categorias_id`) REFERENCES `categorias` (`idCategorias`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_lancamentos_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_lancamentos_contas1` FOREIGN KEY (`contas_id`) REFERENCES `contas` (`idContas`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_lancamentos_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `lancamentos` (`idLancamentos`, `descricao`, `valor`, `desconto`, `valor_desconto`, `tipo_desconto`, `data_vencimento`, `data_pagamento`, `baixado`, `cliente_fornecedor`, `forma_pgto`, `tipo`, `anexo`, `observacoes`, `clientes_id`, `categorias_id`, `contas_id`, `vendas_id`, `usuarios_id`) VALUES (1, 'DUPLICADA 5628/02 - Entrada do parc. de R$1301.09', '1000.00', '0.00', '1000.00', 'real', '2024-05-17', '2024-04-23', 1, 'SPJ MOLLDURAS', 'Boleto', 'despesa', NULL, '', 1, NULL, NULL, NULL, 1);
INSERT INTO `lancamentos` (`idLancamentos`, `descricao`, `valor`, `desconto`, `valor_desconto`, `tipo_desconto`, `data_vencimento`, `data_pagamento`, `baixado`, `cliente_fornecedor`, `forma_pgto`, `tipo`, `anexo`, `observacoes`, `clientes_id`, `categorias_id`, `contas_id`, `vendas_id`, `usuarios_id`) VALUES (2, 'DUPLICADA 5628/02 - Parcelamento de R$1301.09 [1/1]', '301.09', '0.00', '301.09', 'real', '2024-05-17', '2024-05-17', 0, 'SPJ MOLLDURAS', 'Boleto', 'despesa', NULL, '', NULL, NULL, NULL, NULL, 1);
INSERT INTO `lancamentos` (`idLancamentos`, `descricao`, `valor`, `desconto`, `valor_desconto`, `tipo_desconto`, `data_vencimento`, `data_pagamento`, `baixado`, `cliente_fornecedor`, `forma_pgto`, `tipo`, `anexo`, `observacoes`, `clientes_id`, `categorias_id`, `contas_id`, `vendas_id`, `usuarios_id`) VALUES (3, '5628/03 - Parcelamento de R$991.48  [1/1]', '991.48', '0.00', '991.48', 'real', '2024-06-16', '2024-06-16', 0, 'SPJ MOLLDURAS', 'Boleto', 'despesa', NULL, '', 1, NULL, NULL, NULL, 1);
INSERT INTO `lancamentos` (`idLancamentos`, `descricao`, `valor`, `desconto`, `valor_desconto`, `tipo_desconto`, `data_vencimento`, `data_pagamento`, `baixado`, `cliente_fornecedor`, `forma_pgto`, `tipo`, `anexo`, `observacoes`, `clientes_id`, `categorias_id`, `contas_id`, `vendas_id`, `usuarios_id`) VALUES (4, '5628/04 - Parcelamento de R$991.48  [1/1]', '991.48', '0.00', '991.48', 'real', '2024-07-16', '2024-07-16', 0, 'SPJ MOLLDURAS', 'Boleto', 'despesa', NULL, '', 1, NULL, NULL, NULL, 1);
INSERT INTO `lancamentos` (`idLancamentos`, `descricao`, `valor`, `desconto`, `valor_desconto`, `tipo_desconto`, `data_vencimento`, `data_pagamento`, `baixado`, `cliente_fornecedor`, `forma_pgto`, `tipo`, `anexo`, `observacoes`, `clientes_id`, `categorias_id`, `contas_id`, `vendas_id`, `usuarios_id`) VALUES (5, '5628/05 - Parcelamento de R$991.49  [1/1]', '991.49', '0.00', '991.49', 'real', '2024-08-16', '2024-08-16', 0, 'SPJ MOLLDURAS', 'Boleto', 'despesa', NULL, '', 1, NULL, NULL, NULL, 1);
INSERT INTO `lancamentos` (`idLancamentos`, `descricao`, `valor`, `desconto`, `valor_desconto`, `tipo_desconto`, `data_vencimento`, `data_pagamento`, `baixado`, `cliente_fornecedor`, `forma_pgto`, `tipo`, `anexo`, `observacoes`, `clientes_id`, `categorias_id`, `contas_id`, `vendas_id`, `usuarios_id`) VALUES (6, 'Fatura de Venda Nº: 6', '120.00', '0.00', '0.00', NULL, '2024-04-23', '0000-00-00', 0, 'JUCILENE DA SILVA LIMA', 'Dinheiro', 'receita', NULL, NULL, 6, NULL, NULL, 6, 1);


#
# TABLE STRUCTURE FOR: logs
#

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  `idLogs` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(80) DEFAULT NULL,
  `tarefa` varchar(100) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idLogs`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (1, 'EDNILSON', 'Efetuou login no sistema', '2024-04-17', '17:57:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (2, 'EDNILSON', 'Efetuou login no sistema', '2024-04-17', '20:09:37', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (3, 'EDNILSON', 'Efetuou login no sistema', '2024-04-18', '07:06:39', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (4, 'EDNILSON', 'Efetuou login no sistema', '2024-04-18', '16:49:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (5, 'EDNILSON', 'Adicionou informações de emitente.', '2024-04-18', '16:51:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (6, 'EDNILSON', 'Adicionou um cliente.', '2024-04-18', '16:54:13', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (7, 'EDNILSON', 'Adicionou um cliente.', '2024-04-18', '16:54:30', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (8, 'EDNILSON', 'Alterou um cliente. ID2', '2024-04-18', '16:54:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (9, 'EDNILSON', 'Adicionou um cliente.', '2024-04-18', '16:55:15', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (10, 'EDNILSON', 'Adicionou um cliente.', '2024-04-18', '16:56:20', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (11, 'EDNILSON', 'Adicionou um cliente.', '2024-04-18', '16:56:51', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (12, 'EDNILSON', 'Adicionou um produto', '2024-04-18', '16:59:57', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (13, 'EDNILSON', 'Adicionou um produto', '2024-04-18', '17:00:30', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (14, 'EDNILSON', 'Adicionou um produto', '2024-04-18', '17:01:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (15, 'EDNILSON', 'Adicionou um produto', '2024-04-18', '17:19:54', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (16, 'EDNILSON', 'Adicionou uma venda.', '2024-04-18', '17:20:33', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (17, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-18', '17:21:06', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (18, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-18', '17:21:21', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (19, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-18', '17:21:38', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (20, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-18', '17:21:47', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (21, 'EDNILSON', 'Alterou um cliente. ID5', '2024-04-18', '17:23:34', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (22, 'EDNILSON', 'Adicionou uma venda.', '2024-04-18', '17:34:54', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (23, 'EDNILSON', 'Adicionou um produto', '2024-04-18', '17:38:58', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (24, 'EDNILSON', 'Adicionou uma venda.', '2024-04-18', '17:39:19', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (25, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-18', '17:39:34', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (26, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-18', '17:39:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (27, 'EDNILSON', 'Efetuou login no sistema', '2024-04-18', '17:53:29', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (28, 'EDNILSON', 'Efetuou login no sistema', '2024-04-19', '18:47:04', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (29, 'EDNILSON', 'Efetuou login no sistema', '2024-04-19', '19:13:12', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (30, 'EDNILSON', 'Alterou a Imagem do Usuario.', '2024-04-19', '19:15:42', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (31, 'EDNILSON', 'Alterou informações de emitente.', '2024-04-19', '19:22:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (32, 'EDNILSON', 'Adicionou um usuário.', '2024-04-19', '19:33:13', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (33, 'EDNILSON', 'Efetuou login no sistema', '2024-04-19', '21:40:30', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (34, 'EDNILSON', 'Adicionou uma permissão', '2024-04-19', '21:41:28', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (35, 'EDNILSON', 'Alterou um usuário. ID: 2', '2024-04-19', '21:41:47', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (36, 'EDNILSON', 'Alterou um usuário. ID: 2', '2024-04-19', '21:41:47', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (37, 'EDNILSON', 'Alterou um usuário. ID: 2', '2024-04-19', '21:42:08', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (38, 'EDNILSON', 'Efetuou login no sistema', '2024-04-19', '21:48:21', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (39, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:11:18', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (40, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:13:39', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (41, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:15:32', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (42, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:15:50', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (43, 'EDNILSON', 'Alterou um produto. ID: 8', '2024-04-19', '23:17:22', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (44, 'EDNILSON', 'Alterou um produto. ID: 8', '2024-04-19', '23:18:25', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (45, 'EDNILSON', 'Alterou um produto. ID: 8', '2024-04-19', '23:19:30', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (46, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:21:42', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (47, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:23:46', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (48, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:25:42', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (49, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:28:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (50, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:56:50', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (51, 'EDNILSON', 'Adicionou um produto', '2024-04-19', '23:59:48', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (52, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '00:00:59', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (53, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '00:03:26', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (54, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '00:07:01', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (55, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '00:08:51', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (56, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '00:12:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (57, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '00:15:18', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (58, 'EDNILSON', 'Alterou um produto. ID: 21', '2024-04-20', '00:19:35', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (59, 'EDNILSON', 'Efetuou login no sistema', '2024-04-20', '07:23:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (60, 'EDNILSON', 'Removeu um produto. ID: 16', '2024-04-20', '07:28:13', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (61, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:31:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (62, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:32:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (63, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:34:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (64, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:35:12', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (65, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:36:39', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (66, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:37:51', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (67, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:37:51', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (68, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:40:08', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (69, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:41:22', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (70, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:43:25', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (71, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:44:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (72, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:45:07', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (73, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:45:51', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (74, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:46:44', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (75, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:55:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (76, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:56:56', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (77, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:57:24', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (78, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '07:59:00', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (79, 'EDNILSON', 'Removeu uma venda. ID: 1', '2024-04-20', '08:08:20', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (80, 'EDNILSON', 'Removeu uma venda. ID: 2', '2024-04-20', '08:08:25', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (81, 'EDNILSON', 'Removeu uma venda. ID: 3', '2024-04-20', '08:08:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (82, 'EDNILSON', 'Adicionou uma venda.', '2024-04-20', '08:09:16', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (83, 'EDNILSON', 'Alterou um produto. ID: 35', '2024-04-20', '08:17:12', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (84, 'EDNILSON', 'Alterou um produto. ID: 34', '2024-04-20', '08:17:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (85, 'EDNILSON', 'Alterou um produto. ID: 33', '2024-04-20', '08:17:57', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (86, 'EDNILSON', 'Alterou um produto. ID: 32', '2024-04-20', '08:18:18', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (87, 'EDNILSON', 'Alterou um produto. ID: 31', '2024-04-20', '08:18:51', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (88, 'EDNILSON', 'Alterou um produto. ID: 30', '2024-04-20', '08:19:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (89, 'EDNILSON', 'Alterou um produto. ID: 29', '2024-04-20', '08:19:31', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (90, 'EDNILSON', 'Alterou um produto. ID: 28', '2024-04-20', '08:19:52', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (91, 'EDNILSON', 'Alterou um produto. ID: 27', '2024-04-20', '08:20:19', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (92, 'EDNILSON', 'Alterou um produto. ID: 26', '2024-04-20', '08:20:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (93, 'EDNILSON', 'Alterou um produto. ID: 25', '2024-04-20', '08:21:03', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (94, 'EDNILSON', 'Alterou um produto. ID: 24', '2024-04-20', '08:21:20', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (95, 'EDNILSON', 'Alterou um produto. ID: 23', '2024-04-20', '08:21:38', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (96, 'EDNILSON', 'Alterou um produto. ID: 22', '2024-04-20', '08:22:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (97, 'EDNILSON', 'Alterou um produto. ID: 21', '2024-04-20', '08:22:35', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (98, 'EDNILSON', 'Alterou um produto. ID: 20', '2024-04-20', '08:22:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (99, 'EDNILSON', 'Alterou um produto. ID: 19', '2024-04-20', '08:23:19', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (100, 'EDNILSON', 'Alterou um produto. ID: 18', '2024-04-20', '08:23:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (101, 'EDNILSON', 'Alterou um produto. ID: 17', '2024-04-20', '08:23:58', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (102, 'EDNILSON', 'Alterou um produto. ID: 15', '2024-04-20', '08:24:16', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (103, 'EDNILSON', 'Alterou um produto. ID: 14', '2024-04-20', '08:24:44', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (104, 'EDNILSON', 'Alterou um produto. ID: 13', '2024-04-20', '08:25:06', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (105, 'EDNILSON', 'Alterou um produto. ID: 12', '2024-04-20', '08:25:27', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (106, 'EDNILSON', 'Alterou um produto. ID: 11', '2024-04-20', '08:25:44', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (107, 'EDNILSON', 'Alterou um produto. ID: 10', '2024-04-20', '08:27:47', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (108, 'EDNILSON', 'Alterou um produto. ID: 9', '2024-04-20', '08:28:14', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (109, 'EDNILSON', 'Alterou um produto. ID: 8', '2024-04-20', '08:28:33', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (110, 'EDNILSON', 'Alterou um produto. ID: 7', '2024-04-20', '08:29:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (111, 'EDNILSON', 'Alterou um produto. ID: 6', '2024-04-20', '08:30:13', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (112, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:43:27', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (113, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:44:42', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (114, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:47:08', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (115, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:48:27', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (116, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:49:55', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (117, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:51:00', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (118, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:52:17', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (119, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:53:24', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (120, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:54:32', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (121, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:56:57', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (122, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:58:00', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (123, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '08:59:25', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (124, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '09:00:29', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (125, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '09:01:31', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (126, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '09:02:32', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (127, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '09:04:17', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (128, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:16:06', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (129, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:17:59', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (130, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:19:47', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (131, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:21:25', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (132, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:23:06', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (133, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:23:59', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (134, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:25:44', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (135, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:26:37', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (136, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:27:43', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (137, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:28:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (138, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:29:39', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (139, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:30:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (140, 'EDNILSON', 'Adicionou um produto', '2024-04-20', '10:33:18', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (141, 'EDNILSON', 'Efetuou login no sistema', '2024-04-20', '12:50:41', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (142, 'EDNILSON', 'Efetuou login no sistema', '2024-04-20', '14:16:16', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (143, 'EDNILSON', 'Efetuou login no sistema', '2024-04-20', '14:41:46', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (144, 'EDNILSON', 'Adicionou um lançamento em Financeiro', '2024-04-20', '14:51:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (145, 'EDNILSON', 'Adicionou um lançamento em Financeiro', '2024-04-20', '14:53:01', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (146, 'EDNILSON', 'Adicionou um lançamento em Financeiro', '2024-04-20', '14:54:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (147, 'EDNILSON', 'Adicionou um lançamento em Financeiro', '2024-04-20', '14:55:19', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (148, 'EDNILSON', 'Efetuou login no sistema', '2024-04-20', '18:43:22', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (149, 'EDNILSON', 'Efetuou login no sistema', '2024-04-21', '09:21:48', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (150, 'EDNILSON', 'Adicionou um arquivo', '2024-04-21', '09:34:48', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (151, 'EDNILSON', 'Alterou um produto. ID: 29', '2024-04-21', '10:53:15', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (152, 'EDNILSON', 'Alterou um produto. ID: 29', '2024-04-21', '10:56:27', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (153, 'EDNILSON', 'Efetuou login no sistema', '2024-04-21', '19:09:30', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (154, 'EDNILSON', 'Efetuou login no sistema', '2024-04-21', '22:22:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (155, 'EDNILSON', 'Efetuou backup do banco de dados.', '2024-04-21', '22:22:16', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (156, 'EDNILSON', 'Adicionou um cliente.', '2024-04-21', '22:51:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (157, 'EDNILSON', 'Alterou um cliente. ID4', '2024-04-21', '22:53:27', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (158, 'EDNILSON', 'Adicionou uma venda.', '2024-04-21', '22:54:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (159, 'EDNILSON', 'Adicionou uma venda.', '2024-04-21', '22:54:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (160, 'EDNILSON', 'Atualizou estoque de um produto. ID: 58', '2024-04-21', '22:56:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (161, 'EDNILSON', 'Removeu uma venda. ID: 5', '2024-04-21', '22:56:32', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (162, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-21', '22:57:36', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (163, 'EDNILSON', 'Alterou um cliente. ID6', '2024-04-21', '23:02:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (164, 'EDNILSON', 'Alterou um usuário. ID: 1', '2024-04-21', '23:03:40', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (165, 'EDNILSON', 'Alterou um cliente. ID6', '2024-04-21', '23:05:44', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (166, 'EDNILSON', 'Alterou um cliente. ID6', '2024-04-21', '23:06:10', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (167, 'EDNILSON', 'Alterou um cliente. ID6', '2024-04-21', '23:07:34', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (168, 'EDNILSON', 'Faturou uma venda.', '2024-04-21', '23:11:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (169, 'EDNILSON', 'Efetuou login no sistema', '2024-04-21', '23:44:09', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (170, 'EDNILSON', 'Efetuou login no sistema', '2024-04-21', '23:52:58', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (171, 'EDNILSON', 'Efetuou login no sistema', '2024-04-22', '07:28:59', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (172, 'EDNILSON', 'Efetuou login no sistema', '2024-04-22', '07:59:16', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (173, 'EDNILSON', 'Efetuou login no sistema', '2024-04-22', '14:28:13', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (174, 'EDNILSON', 'Efetuou backup do banco de dados.', '2024-04-22', '15:05:45', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (175, 'EDNILSON', 'Efetuou login no sistema', '2024-04-22', '18:47:29', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (176, 'EDNILSON', 'Alterou um cliente. ID4', '2024-04-22', '18:49:00', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (177, 'EDNILSON', 'Adicionou uma venda.', '2024-04-22', '18:49:19', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (178, 'EDNILSON', 'Adicionou produto a uma venda.', '2024-04-22', '18:49:35', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (179, 'EDNILSON', 'Efetuou login no sistema', '2024-04-22', '23:14:20', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (180, 'EDNILSON', 'Efetuou login no sistema', '2024-04-23', '03:43:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (181, 'EDNILSON', 'Alterou um lançamento no financeiro. ID1', '2024-04-23', '05:33:11', '::1');
INSERT INTO `logs` (`idLogs`, `usuario`, `tarefa`, `data`, `hora`, `ip`) VALUES (182, 'EDNILSON', 'Efetuou login no sistema', '2024-04-23', '18:10:50', '::1');


#
# TABLE STRUCTURE FOR: marcas
#

DROP TABLE IF EXISTS `marcas`;

CREATE TABLE `marcas` (
  `idMarcas` int(11) NOT NULL AUTO_INCREMENT,
  `marca` varchar(100) DEFAULT NULL,
  `cadastro` date DEFAULT NULL,
  `situacao` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`idMarcas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: migrations
#

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `version` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `migrations` (`version`) VALUES ('20230428110810');


#
# TABLE STRUCTURE FOR: os
#

DROP TABLE IF EXISTS `os`;

CREATE TABLE `os` (
  `idOs` int(11) NOT NULL AUTO_INCREMENT,
  `dataInicial` date DEFAULT NULL,
  `dataFinal` date DEFAULT NULL,
  `garantia` varchar(45) DEFAULT NULL,
  `descricaoProduto` text DEFAULT NULL,
  `defeito` text DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `laudoTecnico` text DEFAULT NULL,
  `valorTotal` decimal(10,2) DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `valor_desconto` decimal(10,2) DEFAULT 0.00,
  `tipo_desconto` varchar(8) DEFAULT NULL,
  `clientes_id` int(11) NOT NULL,
  `usuarios_id` int(11) NOT NULL,
  `lancamento` int(11) DEFAULT NULL,
  `faturado` tinyint(1) NOT NULL,
  `garantias_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idOs`),
  KEY `fk_os_clientes1` (`clientes_id`),
  KEY `fk_os_usuarios1` (`usuarios_id`),
  KEY `fk_os_lancamentos1` (`lancamento`),
  KEY `fk_os_garantias1` (`garantias_id`),
  CONSTRAINT `fk_os_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_os_lancamentos1` FOREIGN KEY (`lancamento`) REFERENCES `lancamentos` (`idLancamentos`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_os_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: permissoes
#

DROP TABLE IF EXISTS `permissoes`;

CREATE TABLE `permissoes` (
  `idPermissao` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(80) NOT NULL,
  `permissoes` text DEFAULT NULL,
  `situacao` tinyint(1) DEFAULT NULL,
  `data` date DEFAULT NULL,
  PRIMARY KEY (`idPermissao`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `permissoes` (`idPermissao`, `nome`, `permissoes`, `situacao`, `data`) VALUES (1, 'Administrador', 'a:53:{s:8:\"aCliente\";s:1:\"1\";s:8:\"eCliente\";s:1:\"1\";s:8:\"dCliente\";s:1:\"1\";s:8:\"vCliente\";s:1:\"1\";s:8:\"aProduto\";s:1:\"1\";s:8:\"eProduto\";s:1:\"1\";s:8:\"dProduto\";s:1:\"1\";s:8:\"vProduto\";s:1:\"1\";s:8:\"aServico\";s:1:\"1\";s:8:\"eServico\";s:1:\"1\";s:8:\"dServico\";s:1:\"1\";s:8:\"vServico\";s:1:\"1\";s:3:\"aOs\";s:1:\"1\";s:3:\"eOs\";s:1:\"1\";s:3:\"dOs\";s:1:\"1\";s:3:\"vOs\";s:1:\"1\";s:6:\"aVenda\";s:1:\"1\";s:6:\"eVenda\";s:1:\"1\";s:6:\"dVenda\";s:1:\"1\";s:6:\"vVenda\";s:1:\"1\";s:9:\"aGarantia\";s:1:\"1\";s:9:\"eGarantia\";s:1:\"1\";s:9:\"dGarantia\";s:1:\"1\";s:9:\"vGarantia\";s:1:\"1\";s:8:\"aArquivo\";s:1:\"1\";s:8:\"eArquivo\";s:1:\"1\";s:8:\"dArquivo\";s:1:\"1\";s:8:\"vArquivo\";s:1:\"1\";s:10:\"aPagamento\";N;s:10:\"ePagamento\";N;s:10:\"dPagamento\";N;s:10:\"vPagamento\";N;s:11:\"aLancamento\";s:1:\"1\";s:11:\"eLancamento\";s:1:\"1\";s:11:\"dLancamento\";s:1:\"1\";s:11:\"vLancamento\";s:1:\"1\";s:8:\"cUsuario\";s:1:\"1\";s:9:\"cEmitente\";s:1:\"1\";s:10:\"cPermissao\";s:1:\"1\";s:7:\"cBackup\";s:1:\"1\";s:10:\"cAuditoria\";s:1:\"1\";s:6:\"cEmail\";s:1:\"1\";s:8:\"cSistema\";s:1:\"1\";s:8:\"rCliente\";s:1:\"1\";s:8:\"rProduto\";s:1:\"1\";s:8:\"rServico\";s:1:\"1\";s:3:\"rOs\";s:1:\"1\";s:6:\"rVenda\";s:1:\"1\";s:11:\"rFinanceiro\";s:1:\"1\";s:9:\"aCobranca\";s:1:\"1\";s:9:\"eCobranca\";s:1:\"1\";s:9:\"dCobranca\";s:1:\"1\";s:9:\"vCobranca\";s:1:\"1\";}', 1, '2024-04-17');
INSERT INTO `permissoes` (`idPermissao`, `nome`, `permissoes`, `situacao`, `data`) VALUES (2, 'Vendedor', 'a:53:{s:8:\"aCliente\";s:1:\"1\";s:8:\"eCliente\";N;s:8:\"dCliente\";N;s:8:\"vCliente\";s:1:\"1\";s:8:\"aProduto\";s:1:\"1\";s:8:\"eProduto\";s:1:\"1\";s:8:\"dProduto\";N;s:8:\"vProduto\";s:1:\"1\";s:8:\"aServico\";s:1:\"1\";s:8:\"eServico\";s:1:\"1\";s:8:\"dServico\";N;s:8:\"vServico\";s:1:\"1\";s:3:\"aOs\";N;s:3:\"eOs\";N;s:3:\"dOs\";N;s:3:\"vOs\";s:1:\"1\";s:6:\"aVenda\";s:1:\"1\";s:6:\"eVenda\";s:1:\"1\";s:6:\"dVenda\";N;s:6:\"vVenda\";s:1:\"1\";s:9:\"aGarantia\";N;s:9:\"eGarantia\";N;s:9:\"dGarantia\";N;s:9:\"vGarantia\";s:1:\"1\";s:8:\"aArquivo\";N;s:8:\"eArquivo\";N;s:8:\"dArquivo\";N;s:8:\"vArquivo\";s:1:\"1\";s:10:\"aPagamento\";s:1:\"1\";s:10:\"ePagamento\";s:1:\"1\";s:10:\"dPagamento\";N;s:10:\"vPagamento\";s:1:\"1\";s:11:\"aLancamento\";s:1:\"1\";s:11:\"eLancamento\";s:1:\"1\";s:11:\"dLancamento\";N;s:11:\"vLancamento\";s:1:\"1\";s:8:\"cUsuario\";N;s:9:\"cEmitente\";N;s:10:\"cPermissao\";N;s:7:\"cBackup\";N;s:10:\"cAuditoria\";N;s:6:\"cEmail\";N;s:8:\"cSistema\";N;s:8:\"rCliente\";N;s:8:\"rProduto\";N;s:8:\"rServico\";N;s:3:\"rOs\";N;s:6:\"rVenda\";N;s:11:\"rFinanceiro\";N;s:9:\"aCobranca\";s:1:\"1\";s:9:\"eCobranca\";s:1:\"1\";s:9:\"dCobranca\";N;s:9:\"vCobranca\";s:1:\"1\";}', 1, '2024-04-19');


#
# TABLE STRUCTURE FOR: produtos
#

DROP TABLE IF EXISTS `produtos`;

CREATE TABLE `produtos` (
  `idProdutos` int(11) NOT NULL AUTO_INCREMENT,
  `codDeBarra` varchar(70) NOT NULL,
  `descricao` varchar(80) NOT NULL,
  `unidade` varchar(10) DEFAULT NULL,
  `precoCompra` decimal(10,2) DEFAULT NULL,
  `precoVenda` decimal(10,2) NOT NULL,
  `estoque` int(11) NOT NULL,
  `estoqueMinimo` int(11) DEFAULT NULL,
  `saida` tinyint(1) DEFAULT NULL,
  `entrada` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`idProdutos`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (1, '001', 'MOLDURA 10X15CM', 'UNID', '3.00', '6.00', 82, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (2, '002', 'MOLDURA 15X21CM', 'UNID', '4.50', '9.00', 93, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (3, '03', 'MOLDURA  20X30', 'UNID', '4.50', '9.00', 98, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (4, '03', 'MOLDURA  30X40', 'UNID', '12.00', '24.00', 98, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (5, '004', 'MOLDURA 002 10X15CM', 'UNID', '3.00', '6.00', 98, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (6, '166/1105 ', 'MOLDURA 30X40 166/1105 OURO 60X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (7, '168/002 ', 'MOLDURA 30X40 168/002  GRAVADA PRETA 63X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (8, '168/1105', 'MOLDURA 30X40 168/1105 OURO HS 63X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (9, '168/1110', 'MOLDURA 30X40 168/1110 PRATA HS 63X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (10, '102/001', 'MOLDURA 10X15CM 102/001 BRANCA 20x10', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (11, '168/001', 'MOLDURA 30X40 168/001 GRAVADA BRANCO 63X12', 'UNID', '24.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (12, '167/110', 'MOLDURA 30X40 167/110 GRAVADA 60X12', 'UNID', '24.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (13, '132/001', 'MOLDURA 20X30 CM 132/001 BRANCO 34X12', 'UNID', '24.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (14, '139/1105', 'MOLDURA 15x21cm 139/1105 OURO 34x12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (15, '144/001', 'MOLDURA 30X40 144/001 BRANCA 40X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (17, '030/106', 'MOLDURA 15X21CM 030/106 VERMELHA LISA 30X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (18, '030/1110', 'MOLDURA 20x30 CM 030/1110 OURO H 30X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (19, '030/1105', 'MOLDURA 20x30 CM 030/1105 PRATA HS 30X12', 'UNID', '6.00', '12.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (20, '031/001', 'MOLDURA GRAVADA 031/001 10X15CM 27X10', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (21, '144/002', 'MOLDURA 30x40 144/002 PRETA 40X12', 'UNID', '12.00', '24.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (22, '020/112', 'MOLDURA 10X15CM 020/112 ROSA LISA 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (23, '020/106', 'MOLDURA 10X15CM 020/106 VERMELHA LISA 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (24, '020/121', 'MOLDURA 10X15CM 020/121 ROZE LISA 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (25, '020/118', 'MOLDURA 10X15CM 020/118 AZUL BEBE LISA 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (26, '022/172', 'MOLDURA 10X15CM 022/172 LARANJA GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (27, '020/004', 'MOLDURA 10X15CM 020/004 TABACO LISA. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (28, '020/004', 'MOLDURA 10X15CM 020/004 TABACO LISA. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (29, '020/001', 'MOLDURA 10X15CM 020/001 BRANCA LISA. 20X12', 'UNID', '2.20', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (30, '020/119', 'MOLDURA 10X15CM 020/119 VERDE BAN.LISA. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (31, '169/001', 'MOLDURA 10X15CM 169/001 BRANCO GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (32, '169/002', 'MOLDURA 10X15CM 169/002 PRETA GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (33, '169/120', 'MOLDURA 10X15CM 169/120 AZUL GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (34, '169/112', 'MOLDURA 10X15CM 169/112 ROSA GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (35, '169/1105', 'MOLDURA 10X15CM 169/1105 OURO GRAV. 20X12', 'UNID', '3.00', '3.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (36, '170/1105', 'MOLDURA 10X15CM 170/1105 OURO GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (37, '170/002', 'MOLDURA 10X15CM 170/002 PRETA GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (38, '170/001', 'MOLDURA 10X15CM 170/001 BRANCO GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (39, '170/101', 'MOLDURA 10X15CM 170/101 B.SUJO GRAV. 20X12', 'UNID', '3.00', '6.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (40, '15211', 'MOLDURA 15x21CM 020/001 BRANCA LISA. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (41, '15212', 'MOLDURA 15x21CM 020/004 TABACO LISA', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (42, '15213', 'MOLDURA 15x21CM 020/106 VERMELHA LISA 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (43, '15214', 'MOLDURA 15x21CM 020/112 ROSA LISA 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (44, '15215', 'MOLDURA 15x21CM 020/118 AZUL BEBE LISA', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (45, '15216', 'MOLDURA 15x21CM 020/119 VERDE BAN.LISA. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (46, '15217', 'MOLDURA 15x21CM 020/121 ROZE LISA 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (47, '15218', 'MOLDURA 15x21CM 022/172 LARANJA GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (48, '15219', 'MOLDURA 15x21CM 102/001 BRANCA 20x10', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (49, '152110', 'MOLDURA 15x21CM 169/001 BRANCO GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (50, '152111', 'MOLDURA 15x21CM 169/002 PRETA GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (51, '152112', 'MOLDURA 15x21CM 169/1105 OURO GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (52, '152113', 'MOLDURA 15x21CM 170/001 BRANCO GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (53, '152114', 'MOLDURA 15x21CM 170/002 PRETA GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (54, '152115', 'MOLDURA 15x21CM 170/101 B.SUJO GRAV. 20X12', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (55, '152116', 'MOLDURA 15x21CM 170/1105 OURO GRAV.', 'UNID', '4.50', '9.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (56, '20301', 'MOLDURA 20x30CM 030/106 VERMELHA LISA 30X12', 'UNID', '6.00', '12.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (57, '20251', 'MOLDURA 20x25cm 139/1105 OURO 34x12', 'UNID', '6.00', '12.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (58, '20302', 'MOLDURA 20x30cm 139/1105 OURO 34x12', 'UNID', '6.00', '12.00', 6, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (59, '152117', 'MOLDURA 15x21 CM 030/1105 PRATA HS 30X12', 'UNID', '6.00', '12.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (60, '152118', 'MOLDURA 15x21 CM 030/1110 OURO H 30X12', 'UNID', '6.00', '12.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (61, '20252', 'MOLDURA 20x25CM 030/1110 OURO H 30X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (62, '152118', 'MOLDURA 15x21CM 132/001 BRANCO 34X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (63, '20253', 'MOLDURA 20x25CM 132/001 BRANCO 34X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (64, '20x301', 'MOLDURA 20x30 144/001 BRANCA 40X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (65, '20254', 'MOLDURA 20x25 144/001 BRANCA 40X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (66, '20303', 'MOLDURA 20x30 144/002 PRETA 40X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (67, '20255', 'MOLDURA 20x25 144/002 PRETA 40X12', 'UNID', '7.00', '14.00', 1, 10, 1, 1);
INSERT INTO `produtos` (`idProdutos`, `codDeBarra`, `descricao`, `unidade`, `precoCompra`, `precoVenda`, `estoque`, `estoqueMinimo`, `saida`, `entrada`) VALUES (68, '152119', 'MOLDURA 15x21 GRAV. 031/001 CM 27X10', 'UNID', '7.00', '14.00', 1, 10, 1, 1);


#
# TABLE STRUCTURE FOR: produtos_os
#

DROP TABLE IF EXISTS `produtos_os`;

CREATE TABLE `produtos_os` (
  `idProdutos_os` int(11) NOT NULL AUTO_INCREMENT,
  `quantidade` int(11) NOT NULL,
  `descricao` varchar(80) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT 0.00,
  `os_id` int(11) NOT NULL,
  `produtos_id` int(11) NOT NULL,
  `subTotal` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`idProdutos_os`),
  KEY `fk_produtos_os_os1` (`os_id`),
  KEY `fk_produtos_os_produtos1` (`produtos_id`),
  CONSTRAINT `fk_produtos_os_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_produtos_os_produtos1` FOREIGN KEY (`produtos_id`) REFERENCES `produtos` (`idProdutos`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: resets_de_senha
#

DROP TABLE IF EXISTS `resets_de_senha`;

CREATE TABLE `resets_de_senha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `token` varchar(255) NOT NULL,
  `data_expiracao` datetime NOT NULL,
  `token_utilizado` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: servicos
#

DROP TABLE IF EXISTS `servicos`;

CREATE TABLE `servicos` (
  `idServicos` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(45) NOT NULL,
  `descricao` varchar(45) DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  PRIMARY KEY (`idServicos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: servicos_os
#

DROP TABLE IF EXISTS `servicos_os`;

CREATE TABLE `servicos_os` (
  `idServicos_os` int(11) NOT NULL AUTO_INCREMENT,
  `servico` varchar(80) DEFAULT NULL,
  `quantidade` double DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT 0.00,
  `os_id` int(11) NOT NULL,
  `servicos_id` int(11) NOT NULL,
  `subTotal` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`idServicos_os`),
  KEY `fk_servicos_os_os1` (`os_id`),
  KEY `fk_servicos_os_servicos1` (`servicos_id`),
  CONSTRAINT `fk_servicos_os_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_servicos_os_servicos1` FOREIGN KEY (`servicos_id`) REFERENCES `servicos` (`idServicos`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

#
# TABLE STRUCTURE FOR: usuarios
#

DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `idUsuarios` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(80) NOT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `cpf` varchar(20) NOT NULL,
  `cep` varchar(9) NOT NULL,
  `rua` varchar(70) DEFAULT NULL,
  `numero` varchar(15) DEFAULT NULL,
  `bairro` varchar(45) DEFAULT NULL,
  `cidade` varchar(45) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `email` varchar(80) NOT NULL,
  `senha` varchar(200) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `situacao` tinyint(1) NOT NULL,
  `dataCadastro` date NOT NULL,
  `permissoes_id` int(11) NOT NULL,
  `dataExpiracao` date DEFAULT NULL,
  `url_image_user` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idUsuarios`),
  KEY `fk_usuarios_permissoes1_idx` (`permissoes_id`),
  CONSTRAINT `fk_usuarios_permissoes1` FOREIGN KEY (`permissoes_id`) REFERENCES `permissoes` (`idPermissao`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`idUsuarios`, `nome`, `rg`, `cpf`, `cep`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `email`, `senha`, `telefone`, `celular`, `situacao`, `dataCadastro`, `permissoes_id`, `dataExpiracao`, `url_image_user`) VALUES (1, 'EDNILSON', 'MG-25.502.560', '600.021.520-87', '70005-115', 'Rua Acima', '12', 'Alvorada', 'Teste', 'MG', 'ed8156@gmail.com', '$2y$10$QUWr7SdJCtHZvNGGHu6RKuFs1iNILVOKnpMTUourFKWlsi./ns2b6', '(61)99211-5930', '(61)99211-5930', 1, '2024-04-17', 1, '3000-01-01', 'ded6355c9b13ae4b079234d53b19aeaa.png');
INSERT INTO `usuarios` (`idUsuarios`, `nome`, `rg`, `cpf`, `cep`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `email`, `senha`, `telefone`, `celular`, `situacao`, `dataCadastro`, `permissoes_id`, `dataExpiracao`, `url_image_user`) VALUES (2, 'JOAQUIM', '1609401', '041.141.100-46', '72270-400', 'QNQ 4', '28', 'Ceilândia Norte (Ceilândia)', 'Brasília', 'DF', 'joaquim@gmail.com', '$2y$10$VtZuV7PITwFnd1Jg5sSBkuO2UeJdusoeq0A6tfAbJZBcLffJpfM2q', '(61)99646-9683', '', 1, '2024-04-19', 2, '2026-09-19', NULL);


#
# TABLE STRUCTURE FOR: vendas
#

DROP TABLE IF EXISTS `vendas`;

CREATE TABLE `vendas` (
  `idVendas` int(11) NOT NULL AUTO_INCREMENT,
  `dataVenda` date DEFAULT NULL,
  `valorTotal` decimal(10,2) DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `valor_desconto` decimal(10,2) DEFAULT 0.00,
  `tipo_desconto` varchar(8) DEFAULT NULL,
  `faturado` tinyint(1) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `observacoes_cliente` text DEFAULT NULL,
  `clientes_id` int(11) NOT NULL,
  `usuarios_id` int(11) DEFAULT NULL,
  `lancamentos_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idVendas`),
  KEY `fk_vendas_clientes1` (`clientes_id`),
  KEY `fk_vendas_usuarios1` (`usuarios_id`),
  KEY `fk_vendas_lancamentos1` (`lancamentos_id`),
  CONSTRAINT `fk_vendas_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_vendas_lancamentos1` FOREIGN KEY (`lancamentos_id`) REFERENCES `lancamentos` (`idLancamentos`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_vendas_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `vendas` (`idVendas`, `dataVenda`, `valorTotal`, `desconto`, `valor_desconto`, `tipo_desconto`, `faturado`, `observacoes`, `observacoes_cliente`, `clientes_id`, `usuarios_id`, `lancamentos_id`) VALUES (4, '2024-04-20', '0.00', '0.00', '0.00', NULL, 0, '', '', 5, 1, NULL);
INSERT INTO `vendas` (`idVendas`, `dataVenda`, `valorTotal`, `desconto`, `valor_desconto`, `tipo_desconto`, `faturado`, `observacoes`, `observacoes_cliente`, `clientes_id`, `usuarios_id`, `lancamentos_id`) VALUES (6, '2024-04-19', '120.00', '0.00', '0.00', NULL, 1, '', '', 6, 1, NULL);
INSERT INTO `vendas` (`idVendas`, `dataVenda`, `valorTotal`, `desconto`, `valor_desconto`, `tipo_desconto`, `faturado`, `observacoes`, `observacoes_cliente`, `clientes_id`, `usuarios_id`, `lancamentos_id`) VALUES (7, '2024-04-22', '0.00', '0.00', '0.00', NULL, 0, '', '', 4, 1, NULL);


SET foreign_key_checks = 1;
