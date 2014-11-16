<?php

require_once("common.php");

$ladderID = get("ladder");
checkLadder($ladderID);

$html = "";

$ladder = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("name", "summary", "message"));

$ladderNameHtml = htmlentities($ladder["name"]);
$ladderSummaryHtml = htmlentities($ladder["summary"]);
$ladderMessageHtml = nl2br(htmlentities($ladder["message"]));

if(isLoggedIn() && !db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	$joinLadderHtml = <<<HTML
<a href="joinladder.php?ladder={$ladderID}" class="btn btn-lg btn-primary">Join this ladder</a>
HTML;
} else {
	$joinLadderHtml = "";
}

$html .= <<<HTML
<div class="jumbotron">
	<div class="container">
		<h1>$ladderNameHtml<br /><small>$ladderSummaryHtml</small></h1>
		<p>$ladderMessageHtml</p>
		$joinLadderHtml
	</div>
</div>

<div class="container">

HTML;

$topRankingHtml = renderRanking($ladderID, "Top players", "There don't seem to be any players on this ladder.", null, 0, 10);
$recentGamesHtml = renderGames(null, $ladderID, "Recent games", "No games have been played on this ladder yet.", 0, 10);

if(isLoggedIn() && db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	$myRank = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()), "rank");
	$myRankingHtml = renderRanking($ladderID, "Your rank", "There don't seem to be any players on this ladder.", currentUserID(), max($myRank - 6, 0), 10);
	$myRecentGamesHtml = renderGames(currentUserID(), $ladderID, "Your recent games", "You have not played any games on this ladder yet.", 0, 10);
	
	$html .= <<<HTML
<div class="row">
	<div class="col-md-6">
		$topRankingHtml
	</div>
	<div class="col-md-6">
		$myRankingHtml
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		$recentGamesHtml
	</div>
	<div class="col-md-6">
		$myRecentGamesHtml
	</div>
</div>

HTML;
} else {
	$html .= $topRankingHtml . $recentGamesHtml;
}

$html .= <<<HTML
<div class="panel panel-default chat">
	<div class="panel-heading">Ladder Chat</div>
	<table class="table table-condensed" id="chatLines"></table>
	<div class="panel-footer enterChat">
		<form id="chatForm"><table><tr>
			<td class="stretch newChatLinetd"><input type="text" name="newChatLine" id="newChatLine" class="form-control"></td>
			<td><input type="submit" name="newChatLineSubmit" id="newChatLineSubmit" class="btn btn-default" value="Send"></td>
			<td><input type="button" name="chatShowAll" id="chatShowAll" class="btn" value="Show all"></td>
		</tr></table></form>
	</div>
</div>
<script>new LadderChat($ladderID);</script>

HTML;

$html .= "</div>\n";

rawPage($html, "ladder");
