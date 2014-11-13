<?php

require_once("common.php");

$ladderID = get("ladder");
$userID = get("player");

checkLadderPlayer($ladderID, $userID);

$html = "";

$playerNameHtml = htmlentities(db()->stdGet("users", array("userID"=>$userID), "name"));
$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));

$playerRank = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), "rank");
$rankingHtml = renderRanking($ladderID, "$playerNameHtml's rank", "There don't seem to be any players on this ladder.", $userID, max($playerRank - 6, 0), 10);
$recentGamesHtml = renderGames($userID, $ladderID, "Recent games", 0, 10);

$html .= <<<HTML
<div class="panel panel-default">
  <div class="panel-heading"><h1 class="panel-title">$playerNameHtml on $ladderNameHtml</h1></div>
</div>

$rankingHtml
$recentGamesHtml

HTML;

page($html, "player");
