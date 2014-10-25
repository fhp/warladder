<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];
checkLadder($ladderID);

$html = "";

$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));
$ladderDescriptionHtml = nl2br(htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "description")));

$html .= <<<HTML
<div class="panel panel-default">
  <div class="panel-heading"><h1 class="panel-title">$ladderNameHtml</h1></div>
  <div class="panel-body">
    $ladderDescriptionHtml
  </div>
</div>
HTML;

$topRankingHtml = renderRanking($ladderID, "Top rankings", null, 0, 10);
$recentGamesHtml = renderGames(null, $ladderID, "Recent games", 0, 10);

if(isLoggedIn() && db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	$myRank = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()), "rank");
	$myRankingHtml = renderRanking($ladderID, "My rankings", currentUserID(), $myRank - 6, 11);
	$myRecentGamesHtml = renderGames(currentUserID(), $ladderID, "My recent games", 0, 10);
	
	$html .= <<<HTML
<div class="row">
	<div class="col-md-6">
		$topRankingHtml
		$recentGamesHtml
	</div>
	<div class="col-md-6">
		$myRankingHtml
		$myRecentGamesHtml
	</div>
</div>

HTML;
} else if(isLoggedIn() && !db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	// TODO: checken of de ladder te joinen is!
	$joinLadderHtml = <<<HTML
<a href="joinladder.php?ladder={$ladderID}" class="btn btn-default">Join this ladder</a>
HTML;
	
	$html .= $joinLadderHtml . $topRankingHtml . $recentGamesHtml;
} else {
	$html .= $topRankingHtml . $recentGamesHtml;
}

page($html, "ladder");
