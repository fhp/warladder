<?php

require_once("common.php");

$html = "";

if(!isLoggedIn()) {
	$html .= <<<HTML
<div class="jumbotron">
	<div class="container">
		<h1>Warlight custom ladders</h1>
		<p class="lead">
			Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ullamcorper ante nec maximus aliquet. In tincidunt, augue et varius commodo, erat tellus mattis libero, in porta est ipsum eu neque. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. In vel dapibus mi. Praesent nec ligula venenatis, tempor enim ut, aliquam lectus. Sed efficitur faucibus lobortis. Suspendisse eleifend lacinia tortor non porta.
		</p>
		<p><a class="btn btn-lg btn-primary" href="{$config["loginUrl"]}" role="button">Join today</a></p>
	</div>
</div>

HTML;
}

$html .= "<div class=\"container\">" . renderRanking(1, "rankings", 4, 0, 10) . "</div>";

// Lijst met ladders

rawPage($html, "home");

?>