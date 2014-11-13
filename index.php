<?php

require_once("common.php");

$html = "";

$html .= <<<HTML
<div class="jumbotron">
	<div class="container">
		<h1>Warlight custom ladders</h1>
HTML;
if(!isLoggedIn()) {
	$html .= <<<HTML
		<p class="lead">
			Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ullamcorper ante nec maximus aliquet. In tincidunt, augue et varius commodo, erat tellus mattis libero, in porta est ipsum eu neque. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. In vel dapibus mi. Praesent nec ligula venenatis, tempor enim ut, aliquam lectus. Sed efficitur faucibus lobortis. Suspendisse eleifend lacinia tortor non porta.
		</p>
		<p><a class="btn btn-lg btn-primary" href="{$config["loginUrl"]}" role="button">Join today</a></p>

HTML;
} else {
	$html .= <<<HTML
		<p class="lead">
			Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ullamcorper ante nec maximus aliquet. In tincidunt, augue et varius commodo, erat tellus mattis libero, in porta est ipsum eu neque. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. In vel dapibus mi. Praesent nec ligula venenatis, tempor enim ut, aliquam lectus. Sed efficitur faucibus lobortis. Suspendisse eleifend lacinia tortor non porta.
		</p>

HTML;
}
$html .= <<<HTML
	</div>
</div>

HTML;

$openLaddersHtml = renderOpenLadders("Open ladders", 0, 10);

if(isLoggedIn()) {
	$myLaddersHtml = renderMyLadders(currentUserID(), "Your ladders", 0, 5);
	$myGamesHtml = renderGames(currentUserID(), null, "Recent games", 0, 5);
	$html .= <<<HTML
<div class="container">
	<div class="row">
		<div class="col-md-6">
			$openLaddersHtml
		</div>
		<div class="col-md-6">
			$myLaddersHtml
			$myGamesHtml
		</div>
	</div>
</div>

HTML;
} else {
	$html .= <<<HTML
<div class="container">
	$openLaddersHtml
</div>

HTML;
}

rawPage($html, "home");
