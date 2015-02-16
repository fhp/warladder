<?php

require_once(dirname(__FILE__) . "/../common.php");

$ladderTemplates = array();
$ladders = db()->stdList("ladders", array(), "ladderID");
foreach($ladders as $ladderID) {
	$ladderTemplates[$ladderID] = db()->stdMap("ladderTemplates", array("ladderID"=>$ladderID), "templateID", "warlightTemplateID");
}

$users = db()->stdList("users", null, array("userID", "warlightUserID", "name", "color"));
foreach($users as $user) {
	$player = apiGetUser($user["warlightUserID"]);
	if ($player !== null && ($player["name"] != $user["name"] || $player["color"] != $user["color"])) {
		db()->stdSet("users", array("userID"=>$user["userID"]), array("name"=>$player["name"], "color"=>$player["color"]));
	}
	
	$playerTemplates = db()->stdMap("playerLadderTemplates", array("userID"=>$user["userID"]), "templateID", array("ladderID", "templateID", "canPlay"));
	
	$playerLadders = db()->stdList("ladderPlayers", array("userID"=>$user["userID"]), array("ladderID", "newTemplateScore"));
	$allTemplates = array();
	foreach($playerLadders as $ladder) {
		$allTemplates = array_merge($allTemplates, $ladderTemplates[$ladder["ladderID"]]);
	}
	
	$usableTemplates = apiGetUserTemplates($user["warlightUserID"], array_unique($allTemplates));
	if($usableTemplates === null) {
		continue;
	}
	
	foreach($playerLadders as $ladder) {
		foreach($ladderTemplates[$ladder["ladderID"]] as $templateID=>$warlightTemplateID) {
			if(!isset($playerTemplates[$templateID])) {
				db()->stdNew("playerLadderTemplates", array("userID"=>$user["userID"], "ladderID"=>$ladder["ladderID"], "templateID"=>$templateID, "score"=>$ladder["newTemplateScore"], "canPlay"=>in_array($warlightTemplateID, $usableTemplates)));
			} else if($playerTemplates[$templateID]["canPlay"] != in_array($warlightTemplateID, $usableTemplates)) {
				db()->stdSet("playerLadderTemplates", array("userID"=>$user["userID"], "ladderID"=>$ladder["ladderID"], "templateID"=>$templateID), array("canPlay"=>in_array($warlightTemplateID, $usableTemplates)));
			}
		}
	}
	foreach($playerTemplates as $templateID=>$template) {
		$stillInUse = false;
		foreach($playerLadders as $ladder) {
			if(isset($ladderTemplates[$ladder["ladderID"]][$templateID])) {
				$stillInUse = true;
				break;
			}
		}
		if(!$stillInUse) {
			db()->stdDel("playerLadderTemplates", array("userID"=>$user["userID"], "ladderID"=>$ladder["ladderID"], "templateID"=>$templateID));
		}
	}
}
