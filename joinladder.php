<?php

require_once("common.php");

$ladderID = get("ladder");
checkLadder($ladderID);

requireLogin();

if(!db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	$score = tsDefaultScore();
	
	$userID = db()->stdNew("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID(), "mu"=>$score["mu"], "sigma"=>$score["sigma"], "rating"=>$score["rating"], "rank"=>0, "active"=>1, "simultaneousGames"=>5, "joinTime"=>time()));
	// TODO: pas een ladderPlayer maken nadat settings zijn doorgegeven.
	
	initPlayerRank($ladderID, $userID);
}

redirect("ladder.php?ladder=$ladderID");
