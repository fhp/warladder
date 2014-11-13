<?php

require_once("common.php");

requireLogin();

$messages = array();
if(post("email") !== null) {
	db()->stdSet("users", array("userID"=>currentUserID()), array("email"=>post("email")));
	$messages["custom"] = "<div class=\"alert alert-success\" role=\"alert\"><strong>Success!</strong> Your preferences are saved.</div>";
}

$html = <<<HTML
<h1>Your preferences</h1>
<p>Change your email settings here. Change your ladder settings at the <a href="myladders.php">ladder page</a>.</p>

HTML;

$values["email"] = db()->stdGet("users", array("userID"=>currentUserID()), "email");

$html .= operationForm("mysettings.php", "", "Email settings", "save", array(
	array("title"=>"Email", "type"=>"text", "name"=>"email"),
), $values, $messages);


page($html, "mysettings");
