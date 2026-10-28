<?php
require_once 'helpers.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
