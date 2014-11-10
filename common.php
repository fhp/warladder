<?php

session_start();

require_once("config.php");
require_once("trueskill.php");
require_once("ui.php");
require_once("warlightapi.php");
require_once("widgets.php");
require_once("/usr/lib/phpdatabase/database.php");

$database = new MysqlConnection();
$database->open($config["dbHost"], $config["dbUser"], $config["dbPass"], $config["dbName"]);

function db()
{
	return $GLOBALS["database"];
}

function redirect($url)
{
	header("location: $url");
	die();
}

function isLoggedIn()
{
	return isset($_SESSION["userID"]);
}

function currentUserID()
{
	if(!isLoggedIn()) {
		return null;
	}
	return $_SESSION["userID"];
}

function requireLogin()
{
	if(!isLoggedIn()) {
		redirect("index.php");
	}
}

function error404()
{
	header("HTTP/1.1 404 Not Found");
	die("The requested page could not be found.");
}

function checkLadder($ladderID)
{
	if (!db()->stdExists("ladders", array("ladderID"=>$ladderID))) error404();
	
	// TODO: check visibility and accessibility
}
