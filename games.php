<?php

require_once("common.php");

$ladderID = get("ladder");
$userID = get("player");

if ($ladderID !== null && $userID !== null) {
	checkLadder($ladderID);
	
	$title = "Recent games on " . db()->stdGet("ladders", array("ladderID"=>$ladderID), "name") . " played by " . db()->stdGet("users", array("userID"=>$userID), "name");
	$pageType = "ladderplayergames";
} else if($userID !== null) {
	requireLogin();
	if(currentUserID() != $userID) {
		error404();
	}
	
	$title = "My recent games";
	$pageType = "mygames";
} else if($ladderID !== null) {
	checkLadder($ladderID);
	
	$title = "Recent games on " . db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
	$pageType = "laddergames";
} else {
	error404();
}

return page(renderGames($userID, $ladderID, $title, null, null, pageNumber()), $pageType);
