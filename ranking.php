<?php

require_once("common.php");

$ladderID = get("ladder");
$userID = get("player");

checkLadder($ladderID);
if ($userID !== null) {
	checkLadderPlayer($ladderID, $userID);
}

$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));

if (get("page") === null && $userID !== null) {
	$rank = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), "rank");
	$page = 1 + (int)(($rank - 1) / 50);
} else {
	$page = pageNumber();
}


if ($userID === null) {
	$title = "$ladderNameHtml top players";
} else if ($userID == currentUserID()) {
	$title = "Your rank";
} else {
	$userNameHtml = htmlentities(db()->stdGet("users", array("userID"=>$userID), "name"));
	$title = "$userNameHtml's rank";
}

return page(renderRanking($ladderID, $title, "There don't seem to be any players on this ladder.", $userID, null, null, $page), "ranking");
