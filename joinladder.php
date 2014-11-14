<?php

require_once("common.php");

$ladderID = get("ladder");
checkLadder($ladderID);

requireLogin();

if(!db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	$score = tsDefaultScore();
	db()->startTransaction();
	$ladderIDSql = db()->addSlashes($ladderID);
	$rankRecord = db()->query("
		SELECT MAX(rank) as newRank
		FROM ladderPlayers
		WHERE ladderID='$ladderIDSql'
		AND rating > {$score["rating"]}
	")->fetchArray();
	$rank = $rankRecord["newRank"];
	
	db()->setQuery("UPDATE ladderPlayers SET rank = rank + 1 WHERE ladderID = '$ladderID' AND rank > $rank");
	
	// TODO: pas een ladderPlayer maken nadat settings zijn doorgegeven.
	db()->stdNew("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID(), "mu"=>$score["mu"], "sigma"=>$score["sigma"], "rating"=>$score["rating"], "rank"=>$rank + 1, "active"=>1, "simultaneousGames"=>5, "joinTime"=>time()));
	db()->commitTransaction();
}

redirect("ladder.php?ladder=$ladderID");


SELECT MAX(rating) as newRating
		FROM ladderPlayers
		WHERE ladderID='1'
		AND rating > 500