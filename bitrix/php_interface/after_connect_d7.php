<?php
/* Ansible managed: /etc/ansible/roles/web/templates/after_connect_d7.php.j2 modified on 2015-06-15 14:31:17 by root on server */
$connection = \Bitrix\Main\Application::getConnection();

$connection->queryExecute("SET NAMES 'utf8'");
$connection->queryExecute("SET collation_connection = 'utf8_unicode_ci'");

