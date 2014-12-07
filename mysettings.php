<?php

require_once("common.php");

requireLogin();

$general_messages = "";
$general_error = "";

$ladder_messages = "";
$ladder_error = "";


if(($action = post("action")) !== null) {
	if($action == "general-settings") {
		if(post("email") !== null) {
			db()->stdSet("users", array("userID"=>currentUserID()), array("email"=>post("email") == "" ? null : post("email")));
			$general_messages .= "<div class=\"alert alert-success\" role=\"alert\"><strong>Success!</strong> Your preferences are saved.</div>";
		}
	}
	if($action == "ladder-settings") {
		$ladderID = get("ladder");
		checkLadder($ladderID);
		
		$ladderInfo = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("minSimultaneousGames", "maxSimultaneousGames"));
		
		$values = array();
		$values["simultaneousGames"] = post("simultaneousGames");
		if(!ctype_digit($values["simultaneousGames"])) {
			$ladder_error .= formError("Please enter a number for the simultaneous games.");
		}
		$values["simultaneousGames"] = min($values["simultaneousGames"], $ladderInfo["maxSimultaneousGames"]);
		$values["simultaneousGames"] = max($values["simultaneousGames"], $ladderInfo["minSimultaneousGames"]);
		$values["emailInterval"] = post("emailInterval");
		if(!in_array($values["emailInterval"], array("NEVER", "DAILY", "WEEKLY", "MONTHLY"))) {
			$ladder_error .= formError("Invalid email interval.");
		}
		$values["active"] = post("active");
		if(!in_array($values["active"], array("0", "1"))) {
			$ladder_error .= formError("Invalid currently playing value.");
		}
		foreach(db()->stdList("ladderTemplates", array("ladderID"=>$ladderID), "templateID") as $templateID) {
			$score = post("template-" . $templateID);
			if($score === null || $score = 0) {
				$templateScores[$templateID] = 0;
			} else {
				$templateScores[$templateID] = 1;
			}
		}
		
		if($ladder_error == "") {
			$userID = currentUserID();
			db()->stdSet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), $values);
			foreach($templateScores as $templateID=>$score) {
				$where = array("userID"=>$userID, "ladderID"=>$ladderID, "templateID"=>$templateID);
				if(db()->stdExists("playerLadderTemplates", $where)) {
					db()->stdSet("playerLadderTemplates", $where, array("score"=>$score));
				} else {
					db()->stdNew("playerLadderTemplates", array_merge($where, array("score"=>$score)));
				}
			}
			
			$ladder_messages = "<div class=\"alert alert-success\" role=\"alert\">Settings saved.</div>";
		}
	}
}
$html = "";

$general_values = db()->stdGet("users", array("userID"=>currentUserID()), array("email"));

$html .= operationForm("mysettings.php", $general_error, "Email settings", "Save", array(
	array("type"=>"hidden", "name"=>"action", "value"=>"general-settings"),
	array("title"=>"Email", "type"=>"text", "name"=>"email"),
), $general_values, array("custom"=>"{$general_messages}<p>Optional. We use this to mail you up-to-date ladder standings, if you enable them.</p>"));



$ladderID = get("ladder");

if ($ladderID !== null) {
	checkLadder($ladderID);
	
	$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
	$ladderNameHtml = htmlentities($ladderName);
	
	$ladder_values = db()->stdGet("ladderPlayers", array("userID"=>currentUserID(), "ladderID"=>$ladderID), array("simultaneousGames", "active", "emailInterval"));
	
	$ladderInfo = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("minSimultaneousGames", "maxSimultaneousGames"));
	
	// TODO: melden welke templates je kan spelen.
	$templates = array(array("type"=>"html", "html"=>"Select your preferred templates. If possible, created games will use one of those templates."));
	foreach(db()->stdList("ladderTemplates", array("ladderID"=>$ladderID), array("templateID", "name", "warlightTemplateID")) as $template) {
		$templates[] = array("type"=>"checkbox", "name"=>"template-" . $template["templateID"], "label"=>$template["name"] . " <a href=\"http://warlight.net/MultiPlayer?TemplateID={$template["warlightTemplateID"]}\" target=\"_new\"><em>View on warlight</em></a>" . (db()->stdGet("playerLadderTemplates", array("templateID"=>$template["templateID"]), "canPlay") ? "" : " (Your WarLight level is not high enough to play this template.)"));
		
		$ladder_values["template-" . $template["templateID"]] = db()->stdGetTry("playerLadderTemplates", array("userID"=>currentUserID(), "ladderID"=>$ladderID, "templateID"=>$template["templateID"]), "score") == 1 ? 1 : null;
	}
	
	$html .= operationForm("mysettings.php?ladder=$ladderID", $ladder_error, "Ladder Settings - $ladderNameHtml", "Save", array(
		array("type"=>"hidden", "name"=>"action", "value"=>"ladder-settings"),
		($ladderInfo["minSimultaneousGames"] == $ladderInfo["maxSimultaneousGames"] ?
			array("type"=>"hidden", "name"=>"simultaneousGames", "value"=>$ladderInfo["minSimultaneousGames"])
		:
			array("title"=>"Simultaneous games", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"simultaneousGames", "cellclass"=>"stretch"),
				array("type"=>"html", "html"=>"<em>Choose between {$ladderInfo["minSimultaneousGames"]} and {$ladderInfo["maxSimultaneousGames"]}.</em>", "cellclass"=>"nowrap"),
			))
		),
		array("title"=>"Receive ladder standings email", "type"=>"dropdown", "name"=>"emailInterval", "options"=>array(
			array("value"=>"NEVER", "label"=>"Never"),
			array("value"=>"DAILY", "label"=>"Every day"),
			array("value"=>"WEEKLY", "label"=>"Every week"),
			array("value"=>"MONTHLY", "label"=>"Every month"),
		)),
		array("title"=>"Preferred templates", "type"=>"rowspan", "name"=>"templates", "rows"=>$templates),
		array("title"=>"Currently playing", "type"=>"dropdown", "name"=>"active", "options"=>array(
			array("value"=>"1", "label"=>"Yes, create games for me."),
			array("value"=>"0", "label"=>"No, do not create any further games. This will disable your rank until you resume playing.")
		)),
	), $ladder_values, array("custom"=>$ladder_messages == "" ? null : $ladder_messages));
}



if ($ladderID === null) {
	$html .= "<h2>Ladder settings</h2>\n";
} else {
	$html .= "<h2>Other ladders</h2>\n";
}
$html .= renderMyLadderSettings(currentUserID());


page($html, "mysettings", "Your preferences", null, null, "Preferences");
