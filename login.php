<?php

require_once("common.php");

if(!isset($_GET["token"]) || !isset($_GET["clotpass"])) {
	redirect("index.php");
}

if(!apiCheckLogin($_GET["token"], $_GET["clotpass"])) {
	redirect("index.php");
}

$warlightUserID = $_GET["token"];

if(!db()->stdExists("users", array("warlightUserID"=>$warlightUserID))) {
	$user = apiGetUser($userID);
	db()->stdNew("users", array("warlightUserID"=>$warlightUserID, "name"=>$user["name"], "color"=>$user["color"]));
}

$_SESSION["userID"] = db()->stdGet("users", array("warlightUserID"=>$warlightUserID), "userID");

redirect("index.php");

?>