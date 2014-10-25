<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];
checkLadder($ladderID);

requireLogin();

if(!db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	// TODO: zinnige ranking / defaults invullen
	db()->stdNew("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID(), "mu"=>0, "sigma"=>0, "rating"=>0, "rank"=>9999, "active"=>1, "simultaneousGames"=>5, "joinTime"=>time()));
}

redirect("ladder.php?ladder=$ladderID");
