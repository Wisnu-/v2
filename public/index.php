<?php
session_start();
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'boot.php';

$request 	= str_replace($_SERVER['SCRIPT_NAME'],"",$_SERVER['PHP_SELF']);
$url 		= $_SERVER['REQUEST_URI'];

if ($request === '') {
	$request = '/views/index';
}

ambilFile($request); 