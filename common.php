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

function get($key)
{
	if (isset($_GET[$key])) {
		return $_GET[$key];
	}
	return null;
}

function post($key)
{
	if (isset($_POST[$key])) {
		return $_POST[$key];
	}
	return null;
}

function pageNumber()
{
	$page = get("page");
	if ($page !== null && ctype_digit($page)) {
		return $page;
	} else {
		return 1;
	}
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
	$settings = db()->stdGetTry("ladders", array("ladderID"=>$ladderID), array("accessibility", "visibility"));
	if ($settings === null) error404();
	
	if ($settings["visibility"] == "PUBLIC") {
		return;
	}
	
	if ($settings["accessibility"] == "PUBLIC" || $settings["accessibility"] == "MODERATED") {
		return;
	}
	
	$userID = currentUserID();
	if ($userID === null) {
		error404();
	}
	
	if (db()->stdExists("ladderAdmins", array("ladderID"=>$ladderID, "userID"=>$userID))) {
		return;
	}
	
	$status = db()->stdGetTry("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), "joinStatus");
	if ($status === null) {
		error404();
	}
	if ($status == "BOOTED") {
		error404();
	}
	
	return;
}

function checkLadderPlayer($ladderID, $userID)
{
	checkLadder($ladderID);
	if(!db()->stdExists("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID))) {
		error404();
	}
}
