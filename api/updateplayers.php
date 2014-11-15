<?php

require_once("../common.php");

$users = db()->stdList("users", null, array("userID", "warlightUserID", "name", "color"));
foreach($users as $user) {
	$player = apiGetUser($users["warlightUserID"]);
	if ($player["name"] != $user["name"] || $player["color"] != $user["color"]) {
		db()->stdSet("users", array("userID"=>$user["userID"]), array("name"=>$player["name"], "color"=>$player["color"]));
	}
}
