<?php

require_once("common.php");

if(isset($_GET["ladder"]) && isset($_GET["player"])) {
	$ladderID = $_GET["ladder"];
	$userID = $_GET["player"];
	checkLadder($ladderID);
	
	$title = "Recent games on " . db()->stdGet("ladders", array("ladderID"=>$ladderID), "name") . " played by " . db()->stdGet("users", array("userID"=>$userID), "name");
	$pageType = "ladderplayergames";
} else if(isset($_GET["player"])) {
	requireLogin();
	if(currentUserID() != $_GET["player"]) {
		error404();
	}
	$ladderID = null;
	$userID = $_GET["player"];
	
	$title = "My recent games";
	$pageType = "mygames";
} else if(isset($_GET["ladder"])) {
	$ladderID = $_GET["ladder"];
	$userID = null;
	checkLadder($ladderID);
	
	$title = "Recent games on " . db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
	$pageType = "laddergames";
} else {
	error404();
}

if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
	$page = $_GET["page"];
} else {
	$page = 1;
}

return page(renderGames($userID, $ladderID, $title, null, null, $page), $pageType);
