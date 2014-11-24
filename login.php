<?php

require_once("common.php");

if(!apiCheckLogin(get("token"), get("clotpass"))) {
	redirect("index.php");
}

$warlightUserID = get("token");

if(!db()->stdExists("users", array("warlightUserID"=>$warlightUserID))) {
	$_SESSION["token"] = $warlightUserID;
	redirect("register.php");
}

$_SESSION["userID"] = db()->stdGet("users", array("warlightUserID"=>$warlightUserID), "userID");

redirect("index.php");
