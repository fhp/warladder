<?php

require_once("common.php");


$joinLink = "";
if(!isLoggedIn()) {
	$joinLink .= "<p><a class=\"btn btn-lg btn-primary\" href=\"{$config["loginUrl"]}\" role=\"button\">Join today</a></p>";
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

$subtitle = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ullamcorper ante nec maximus aliquet. In tincidunt, augue et varius commodo, erat tellus mattis libero, in porta est ipsum eu neque. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. In vel dapibus mi. Praesent nec ligula venenatis, tempor enim ut, aliquam lectus. Sed efficitur faucibus lobortis. Suspendisse eleifend lacinia tortor non porta.";

page($html, "home", "Warlight custom ladders", null, "<p>$subtitle</p>$joinLink");
