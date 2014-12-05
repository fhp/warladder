<?php

require_once("common.php");

if(!apiCheckLogin(get("token"), get("clotpass"))) {
	redirect("index.php");
}

$warlightUserID = get("token");
$target = get("state");

if(!db()->stdExists("users", array("warlightUserID"=>$warlightUserID))) {
	$_SESSION["token"] = $warlightUserID;
	if ($target !== null && startsWith($target, "joinladder.php")) {
		redirect($target);
	}
	redirect("register.php");
}

$_SESSION["userID"] = db()->stdGet("users", array("warlightUserID"=>$warlightUserID), "userID");

if ($target !== null) {
	redirect($target);
} else {
	redirect("index.php");
}
