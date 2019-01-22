<?php
require_once '../vendor/autoload.php';

use \PdAuth\Auth;

$auth = new Auth(require '../config/auth.php');
$auth->choose('erp');
exec('open '.$auth->connect('/'));