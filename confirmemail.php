<?php

require_once("common.php");

$userID = get("player");
$confirmation = get("confirmation");

$email = db()->stdGetTry("users", array("userID"=>$userID), "email");

if ($email === false) {
	error404();
}

if (db()->stdExists("users", array("userID"=>$userID, "emailConfirmation"=>$confirmation))) {
	db()->stdSet("users", array("userID"=>$userID), array("emailConfirmation"=>null));
	$success = true;
} else {
	$success = false;
}

$html = "";
$emailHtml = htmlentities($email);

if ($success) {
	$html .= "<p class=\"alert alert-success\">Your email address $emailHtml is confirmed successfully.</p>\n";
} else {
	$html .= "<p class=\"alert alert-error\">Invalid confirmation code.</p>\n";
}

$html .= "<p><a href=\"index.php\" class=\"btn btn-primary\">Continue</a></p>\n";

page($html, "confirmemail", "Confirm email");
