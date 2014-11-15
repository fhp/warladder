<?php

require_once("common.php");

// TODO: mass accept / reject.

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];
$userID = $_GET["player"];
$accept = $_GET["accept"];

if($accept != 1 && $accept != 0) {
	error404();
}
checkLadderMod($ladderID);

if(!db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID, "joinStatus"=>"SIGNEDUP"))) {
	error404();
}

db()->stdSet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), array("joinStatus"=>($accept == 1 ? "JOINED" : "REJECTED")));

if($accept == 1) {
	initPlayerRank($ladderID, $userID);
}

redirect("modladder.php?ladder=$ladderID");
