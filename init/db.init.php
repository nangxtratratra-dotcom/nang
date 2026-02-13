<?php
$db_host = '127.0.0.1'; // localhost
$db_name = 'g19bcsy3_nang';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;

$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($db->connect_error) {
    echo $db->connect_error;
    die();
}
