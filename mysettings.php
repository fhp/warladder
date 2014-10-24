<?php

require_once("common.php");

requireLogin();

$message = "";
if(isset($_POST["email"])) {
	db()->stdSet("users", array("userID"=>currentUserID()), array("email"=>$_POST["email"]));
	$message = "<div class=\"alert alert-success\" role=\"alert\"><strong>Success!</strong> Your settings are saved.</div>";
}

$html = "";

$emailHtml = htmlentities(db()->stdGet("users", array("userID"=>currentUserID()), "email"));

$html .= <<<HTML
<h1>My Settings</h1>
{$message}
<form role="form" method="post">
	<div class="form-group">
		<label for="settings-email">Email address</label>
		<input name="email" type="email" class="form-control" id="settings-email" placeholder="Enter email" value="{$emailHtml}">
	</div>
	<button type="submit" class="btn btn-default">Save settings</button>
</form>
<br>

<p class="alert alert-info"><strong>Ladder settings</strong> Change your ladder settings at the <a href="myladders.php">ladder page</a>.</p>

HTML;

page($html, "mysettings");

?>