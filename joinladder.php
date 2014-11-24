<?php

require_once("common.php");

$ladderID = get("ladder");
checkLadder($ladderID);

requireLogin();

if(db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	redirect("ladder.php?ladder=$ladderID");
}

$ladder_error = "";

$warlightUserID = db()->stdGet("users", array("userID"=>currentUserID()), "warlightUserID");
$warlightTemplateIDs = db()->stdList("ladderTemplates", array("ladderID"=>$ladderID), "warlightTemplateID");

if(count(apiGetUserTemplates($warlightUserID, $warlightTemplateIDs)) == 0) {
	$ladder_error .= formError("You're warlight level is too low to play on this ladder.");
}

if(($action = post("action")) !== null) {
	if($action == "ladder-settings") {
		$ladderID = get("ladder");
		checkLadder($ladderID);
		
		$ladderInfo = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("minSimultaneousGames", "maxSimultaneousGames", "accessibility"));
		
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
		foreach(db()->stdList("ladderTemplates", array("ladderID"=>$ladderID), "templateID") as $templateID) {
			$score = post("template-" . $templateID);
			if($score === null || $score = 0) {
				$templateScores[$templateID] = 0;
			} else {
				$templateScores[$templateID] = 1;
			}
		}
		
		if($ladder_error == "") {
			$score = tsDefaultScore();
			$userID = currentUserID();
			
			$values["ladderID"] = $ladderID;
			$values["userID"] = currentUserID();
			$values["mu"] = $score["mu"];
			$values["sigma"] = $score["sigma"];
			$values["rating"] = $score["rating"];
			$values["rank"] = 0;
			$values["active"] = 1;
			$values["joinTime"] = time();
			if($ladderInfo["accessibility"] == "PUBLIC") {
				$values["joinStatus"] = "JOINED";
			} else {
				$values["joinStatus"] = "SIGNEDUP";
			}
			
			db()->stdNew("ladderPlayers", $values);
			if($ladderInfo["accessibility"] == "PUBLIC") {
				initPlayerRank($ladderID, $userID, $score["rating"]);
			}
			
			foreach($templateScores as $templateID=>$score) {
				$where = array("userID"=>$userID, "ladderID"=>$ladderID, "templateID"=>$templateID);
				if(db()->stdExists("playerLadderTemplates", $where)) {
					db()->stdSet("playerLadderTemplates", $where, array("score"=>$score));
				} else {
					db()->stdNew("playerLadderTemplates", array_merge($where, array("score"=>$score)));
				}
			}
			
			redirect("ladder.php?ladder=$ladderID");
		}
	}
}


$html = "";

$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
$ladderNameHtml = htmlentities($ladderName);


$ladderInfo = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("minSimultaneousGames", "maxSimultaneousGames"));

$ladder_values = array();

$templates = array(array("type"=>"html", "html"=>"Select your preferred templates. If possible, created games will use one of those templates."));
foreach(db()->stdList("ladderTemplates", array("ladderID"=>$ladderID), array("templateID", "name", "warlightTemplateID")) as $template) {
	$templates[] = array("type"=>"checkbox", "name"=>"template-" . $template["templateID"], "label"=>$template["name"] . " <a href=\"http://warlight.net/MultiPlayer?TemplateID={$template["warlightTemplateID"]}\" target=\"_new\"><em>View on warlight</em></a>");
	
	$ladder_values["template-" . $template["templateID"]] = 1;
}

$html .= operationForm("joinladder.php?ladder=$ladderID", $ladder_error, "Ladder Settings", "Join", array(
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
), $ladder_values);

page($html, "joinladder", "Join the ladder", "<a href=\"ladder.php?ladder=$ladderID\">$ladderNameHtml</a>", null, "Preferences");

