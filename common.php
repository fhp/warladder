<?php

session_start();

require_once("config.php");
require_once("trueskill.php");
require_once("libui.php");
require_once("ui.php");
require_once("warlightapi.php");
require_once("widgets.php");
require_once("/usr/lib/phpdatabase/database.php");
require_once("/usr/lib/phpmail/mimemail.php");

$database = new MysqlConnection();
$database->open($config["dbHost"], $config["dbUser"], $config["dbPass"], $config["dbName"]);

if (isset($_SESSION["userID"])) {
	if (!db()->stdExists("users", array("userID"=>$_SESSION["userID"]))) {
		unset($_SESSION["userID"]);
	}
}

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

function requireLogin($target = null)
{
	if(!isLoggedIn()) {
		if ($target === null) {
			redirect("index.php");
		} else {
			redirect($GLOBALS["config"]["loginUrl"] . "&state=" . urlencode($target));
		}
	}
}

function requireAuthentication($target)
{
	if (!isset($_SESSION["token"])) {
		if ($target === null) {
			redirect($GLOBALS["config"]["loginUrl"]);
		} else {
			redirect($GLOBALS["config"]["loginUrl"] . "&state=" . urlencode($target));
		}
	}
}

function startsWith($string, $prefix)
{
	return substr($string, 0, strlen($prefix)) === $prefix;
}

function error404()
{
	header("HTTP/1.1 404 Not Found");
	die("The requested page could not be found.");
}

function checkLadder($ladderID)
{
	if (!db()->stdExists("ladders", array("ladderID"=>$ladderID))) {
		error404();
	}
}

function checkLadderPlayer($ladderID, $userID)
{
	checkLadder($ladderID);
	if(!db()->stdExists("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID))) {
		error404();
	}
}

function checkLadderMod($ladderID)
{
	checkLadder($ladderID);
	if (!isMod($ladderID)) error404();
}

function isMod($ladderID, $userID = null) {
	if($userID === null) {
		$userID = currentUserID();
	}
	if($userID === null) {
		return false;
	}
	return db()->stdExists("ladderAdmins", array("ladderID"=>$ladderID, "userID"=>$userID));
}

function initPlayerRank($ladderID, $userID, $rating = null)
{
	db()->startTransaction();
	if($rating === null) {
		$rating = db()->stdGet("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID), "rating");
	}
	
	$ladderIDSql = db()->addSlashes($ladderID);
	$rankRecord = db()->query("
		SELECT MAX(rank) as newRank
		FROM ladderPlayers
		WHERE ladderID='$ladderIDSql'
		AND rating > {$rating}
	")->fetchArray();
	$rank = $rankRecord["newRank"];
	if($rank === null) {
		$rank = 0;
	}
	db()->setQuery("UPDATE ladderPlayers SET rank = rank + 1 WHERE ladderID = '$ladderID' AND rank > $rank");
	
	db()->stdSet("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID), array("rank"=>$rank + 1));
	db()->commitTransaction();
}

function removePlayerRank($ladderID, $userID)
{
	db()->startTransaction();
	$rank = db()->stdGet("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID), "rank");
	
	$ladderIDSql = db()->addSlashes($ladderID);
	db()->setQuery("UPDATE ladderPlayers SET rank = rank - 1 WHERE ladderID = '$ladderID' AND rank > $rank");
	
	db()->stdSet("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID), array("rank"=>0));
	db()->commitTransaction();
}

function sendConfirmationEmail($userID)
{
	$userData = db()->stdGet("users", array("userID"=>$userID), array("name", "email", "emailConfirmation"));
	$name = $userData["name"];
	$email = $userData["email"];
	$confirmation = $userData["emailConfirmation"];
	if ($email === null || $confirmation === null) {
		return;
	}
	
	$nameHtml = htmlentities($name);
	$emailHtml = htmlentities($email);
	$link = "{$GLOBALS["config"]["baseUrl"]}confirmemail.php?player=$userID&confirmation=$confirmation";
	$linkHtml = htmlentities($link);
	
	$mail = new mimemail();
	$mail->addReceiver($email, $name);
	$mail->setSender($GLOBALS["config"]["email"], "warladder.net");
	$mail->setSubject("Confirm your address for warladder.net");
	$mail->setHtmlMessage(<<<MESSAGE
<p>Hi $nameHtml,</p>

<p>Somebody (and we hope it was you) configured your email address &lt;$emailHtml&gt; for the warladder.net registration of $nameHtml. Please click <a href="$linkHtml">this link</a> to confirm that you want this.</p>

<p>If you do not want to do this, simply ignore this message.</p>

MESSAGE
,
<<<MESSAGE
Hi $name,

Somebody (and we hope it was you) configured your email address <$email> for the warladder.net registration of $name. Please visit $link to confirm that you want this.

If you do not want to do this, simply ignore this message.

MESSAGE
	);
	$mail->send();
}

function initUserEmail($userID, $email)
{
	if ($email === "") {
		$email = null;
	}
	if ($email === null) {
		$confirmation = null;
	} else {
		$characters = "0123456789qwertyuioplkjhgfdsazxcvbnm";
		while (true) {
			$confirmation = "";
			for ($i = 0; $i < 50; $i++) {
				$confirmation .= $characters[rand(0, strlen($characters) - 1)];
			}
			if (!db()->stdExists("users", array("userID"=>$userID, "emailConfirmation"=>$confirmation))) {
				break;
			}
		}
	}
	db()->stdSet("users", array("userID"=>$userID), array("email"=>$email, "emailConfirmation"=>$confirmation));
	if ($confirmation !== null) {
		sendConfirmationEmail($userID);
	}
}
