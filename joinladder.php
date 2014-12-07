<?php

require_once("common.php");

$ladderID = get("ladder");
checkLadder($ladderID);

if (!isLoggedIn()) {
	requireAuthentication("joinladder.php?ladder=$ladderID");
}

if (isLoggedIn() && db()->stdExists("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>currentUserID()))) {
	redirect("ladder.php?ladder=$ladderID");
}

$ladder_error = "";

if (isLoggedIn()) {
	$warlightUserID = db()->stdGet("users", array("userID"=>currentUserID()), "warlightUserID");
} else {
	$warlightUserID = $_SESSION["token"];
}

$warlightTemplateIDs = db()->stdList("ladderTemplates", array("ladderID"=>$ladderID), "warlightTemplateID");
$playableTemplates = apiGetUserTemplates($warlightUserID, $warlightTemplateIDs);

if(count($playableTemplates) == 0) {
	$ladder_error .= formError("Your warlight level is too low to play on this ladder.");
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
		$values["simultaneousGames"] = $ladderInfo["maxSimultaneousGames"] === null ? $values["simultaneousGames"] : min($values["simultaneousGames"], $ladderInfo["maxSimultaneousGames"]);
		$values["simultaneousGames"] = $ladderInfo["minSimultaneousGames"] === null ? $values["simultaneousGames"] : max($values["simultaneousGames"], $ladderInfo["minSimultaneousGames"]);
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
			if (!isLoggedIn()) {
				$warlightUserID = $_SESSION["token"];
				$user = apiGetUser($warlightUserID);
				$_SESSION["userID"] = db()->stdNew("users", array("warlightUserID"=>$warlightUserID, "name"=>$user["name"], "color"=>$user["color"]));
				if (post("email") !== null) {
					initUserEmail($_SESSION["userID"], post("email"));
				}
			}
			$userID = currentUserID();
			$score = tsDefaultScore();
			
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
				$warlightTemplateID = db()->stdGet("ladderTemplates", array("templateID"=>$templateID), "warlightTemplateID");
				$canPlay = in_array($warlightTemplateID, $playableTemplates);
				if(db()->stdExists("playerLadderTemplates", $where)) {
					db()->stdSet("playerLadderTemplates", $where, array("score"=>$score, "canPlay"=>$canPlay));
				} else {
					db()->stdNew("playerLadderTemplates", array_merge($where, array("score"=>$score, "canPlay"=>$canPlay)));
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
	$templates[] = array("type"=>"checkbox", "name"=>"template-" . $template["templateID"], "label"=>$template["name"] . " <a href=\"http://warlight.net/MultiPlayer?TemplateID={$template["warlightTemplateID"]}\" target=\"_new\"><em>View on warlight</em></a>" . (in_array($template["warlightTemplateID"], $playableTemplates) ? "" : " (Your WarLight level is not high enough to play this template.)"));
	
	$ladder_values["template-" . $template["templateID"]] = 1;
}

if($ladderInfo["minSimultaneousGames"] !== null && $ladderInfo["maxSimultaneousGames"] !== null) {
	$htmlText = "<em>Choose between {$ladderInfo["minSimultaneousGames"]} and {$ladderInfo["maxSimultaneousGames"]}.</em>";
} else if($ladderInfo["minSimultaneousGames"] !== null && $ladderInfo["maxSimultaneousGames"] === null) {
	$htmlText = "<em>Choose more than {$ladderInfo["minSimultaneousGames"]}.</em>";
} else if($ladderInfo["minSimultaneousGames"] === null && $ladderInfo["maxSimultaneousGames"] !== null) {
	$htmlText = "<em>Choose up to {$ladderInfo["maxSimultaneousGames"]}.</em>";
} else {
	$htmlText = "<em>Choose any numer you like.</em>";
}

$html .= operationForm("joinladder.php?ladder=$ladderID", $ladder_error, "Ladder Settings", "Join", array(
	array("type"=>"hidden", "name"=>"action", "value"=>"ladder-settings"),
	(isLoggedIn() ? null : array("title"=>"Email", "type"=>"text", "name"=>"email")),
	(isLoggedIn() ? null : array("title"=>"", "type"=>"html", "html"=>"<p><em>Optional. We use this to mail you up-to-date ladder standings, if you enable them below.</em></p>")),
	($ladderInfo["minSimultaneousGames"] !== null && $ladderInfo["minSimultaneousGames"] == $ladderInfo["maxSimultaneousGames"] ?
		array("type"=>"hidden", "name"=>"simultaneousGames", "value"=>$ladderInfo["minSimultaneousGames"])
	:
		array("title"=>"Simultaneous games", "type"=>"colspan", "columns"=>array(
			array("type"=>"text", "name"=>"simultaneousGames", "cellclass"=>"stretch"),
			array("type"=>"html", "html"=>$htmlText, "cellclass"=>"nowrap"),
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
