<?php

require_once("common.php");

$ladderID = get("ladder");
$userID = get("player");

if ($ladderID !== null && $userID !== null) {
	checkLadder($ladderID);
	
	$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));
	if ($userID == currentUserID()) {
		$title = "Your recent games on $ladderNameHtml";
		$emptyMessage = "You have not played any games on $ladderNameHtml yet.";
	} else {
		$userNameHtml = htmlentities(db()->stdGet("users", array("userID"=>$userID), "name"));
		
		$title = "$userNameHtml's recent games on $ladderNameHtml";
		$emptyMessage = "$userNameHtml has not played any games on $ladderNameHtml yet.";
	}
	
	$pageType = "ladderplayergames";
} else if($userID !== null) {
	requireLogin();
	if(currentUserID() != $userID) {
		error404();
	}
	
	$title = "Your recent games";
	$emptyMessage = "You have not played any ladder games yet.";
	$pageType = "mygames";
} else if($ladderID !== null) {
	checkLadder($ladderID);
	
	$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));
	$title = "Recent games on $ladderNameHtml";
	$emptyMessage = "No games have been played on $ladderNameHtml yet.";
	$pageType = "laddergames";
} else {
	error404();
}

return page(renderGames($userID, $ladderID, $title, $emptyMessage, null, null, pageNumber()), $pageType);
