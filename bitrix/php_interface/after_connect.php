<?php
/* Ansible managed: /etc/ansible/roles/web/templates/after_connect.php.j2 modified on 2015-06-15 14:31:17 by root on server */
$DB->Query("SET NAMES 'utf8'");
$DB->Query('SET collation_connection = "utf8_unicode_ci"');
