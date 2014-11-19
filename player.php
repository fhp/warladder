<?php

require_once("common.php");

$ladderID = get("ladder");
$userID = get("player");

checkLadderPlayer($ladderID, $userID);

$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
$playerName = db()->stdGet("users", array("userID"=>$userID), "name");

$playerNameHtml = htmlentities($playerName);
$ladderNameHtml = htmlentities($ladderName);

$html = "";

$playerRank = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), "rank");
$html .= renderRanking($ladderID, "$playerNameHtml's rank", "There don't seem to be any players on this ladder.", $userID, max($playerRank - 6, 0), 10);

$html .= renderGames($userID, $ladderID, "$playerNameHtml's recent games", "$playerNameHtml has not played any games on this ladder yet.", 0, 10);

if(isMod($ladderID)) {
	$html .= operationForm("modladder.php?ladder=$ladderID", "", "Ban player from ladder", "ban", array(
		array("type"=>"hidden", "name"=>"action", "value"=>"ban-player"),
		array("type"=>"hidden", "name"=>"userID", "value"=>$userID),
	), null);
}

$titleHtml = "$playerNameHtml's activity on <a href=\"ladder.php?ladder=$ladderID\">$ladderNameHtml</a>";

page($html, "player", "$playerNameHtml's activity", "<a href=\"ladder.php?ladder=$ladderID\">$ladderNameHtml</a>", null, "$playerName's activity - $ladderName");
