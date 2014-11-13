<?php

require_once("common.php");

if(!apiCheckLogin(get("token"), get("clotpass"))) {
	redirect("index.php");
}

$warlightUserID = get("token");

if(!db()->stdExists("users", array("warlightUserID"=>$warlightUserID))) {
	$user = apiGetUser($warlightUserID);
	db()->stdNew("users", array("warlightUserID"=>$warlightUserID, "name"=>$user["name"], "color"=>$user["color"]));
}

$_SESSION["userID"] = db()->stdGet("users", array("warlightUserID"=>$warlightUserID), "userID");

redirect("index.php");
