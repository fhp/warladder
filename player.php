<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
if (!isset($_GET["player"])) error404();
$ladderID = $_GET["ladder"];
$userID = $_GET["player"];
checkLadder($ladderID);
if(!db()->stdExists("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID))) {
	error404();
}

$html = "";

$playerNameHtml = htmlentities(db()->stdGet("users", array("userID"=>$userID), "name"));
$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));

$playerRank = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), "rank");
$rankingHtml = renderRanking($ladderID, "Ranking", $userID, max($playerRank - 6, 0), 11);
$recentGamesHtml = renderGames($userID, $ladderID, "Recent games", 0, 10);

$html .= <<<HTML
<div class="panel panel-default">
  <div class="panel-heading"><h1 class="panel-title">$playerNameHtml on $ladderNameHtml</h1></div>
</div>

$rankingHtml
$recentGamesHtml

HTML;

page($html, "player");
