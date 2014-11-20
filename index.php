<?php

require_once("common.php");


$subtext = "";
if(!isLoggedIn()) {
	$subtext = <<<TEXT
<p>On this platform, you can play and organize warlight ladders.</p>

TEXT;

	$subtext .= "<p><a class=\"btn btn-lg btn-primary\" href=\"{$config["loginUrl"]}\" role=\"button\">Join today</a></p>";
}

$openLaddersHtml = renderOpenLadders("Open ladders", 0, 10);

$html = "";
if(isLoggedIn()) {
	$myLaddersHtml = renderMyLadders(currentUserID(), "Your ladders", 0, 5);
	$myGamesHtml = renderGames(currentUserID(), null, "Your recent games", "You have not played any ladder games yet.", 0, 5);
	$html .= <<<HTML
<div class="row">
	<div class="col-md-6">
		$openLaddersHtml
	</div>
	<div class="col-md-6">
		$myLaddersHtml
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		$myGamesHtml
	</div>
</div>

HTML;
} else {
	$html .= $openLaddersHtml;
}


page($html, "home", "Warladder.net", "Warlight ladder platform", $subtext);
