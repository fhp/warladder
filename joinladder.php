<?php

require_once("common.php");

$ladderID = get("ladder");
checkLadder($ladderID);

requireAuthentication("joinladder.php?ladder=$ladderID");

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

if(count(apiGetUserTemplates($warlightUserID, $warlightTemplateIDs)) == 0) {
	$ladder_error .= formError("Your warlight level is too low to play on this ladder.");
}

if(($action = post("action")) !== null) {
	if($action == "ladder-settings") {
		$ladderID = get("ladder");
		checkLadder($ladderID);
		
		$ladderInfo = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("name", "minSimultaneousGames", "maxSimultaneousGames", "accessibility"));
		
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
				if(db()->stdExists("playerLadderTemplates", $where)) {
					db()->stdSet("playerLadderTemplates", $where, array("score"=>$score));
				} else {
					db()->stdNew("playerLadderTemplates", array_merge($where, array("score"=>$score)));
				}
			}
			
			if($ladderInfo["accessibility"] != "PUBLIC") {
				$ladderIDSql = db()->addSlashes($ladderID);
				$mods = db()->query("SELECT email, emailConfirmation FROM ladderAdmins INNER JOIN users USING(userID) WHERE ladderID='$ladderIDSql'")->fetchList();
				
				$userName = db()->stdGet("users", array("userID"=>currentUserID()), "name");
				$userNameHtml = htmlentities($userName);
				$ladderNameHtml = htmlentities($ladderInfo["name"]);
				$modladderHtml = htmlentities($GLOBALS["config"]["baseUrl"] . "modladder.php?ladder=$ladderID");
				
				foreach($mods as $mod) {
					if ($mod["email"] !== null && $mod["emailConfirmation"] === null) {
						$mail = new mimemail();
						$mail->addReceiver($mod["email"]);
						$mail->setSender($GLOBALS["config"]["email"], "warladder.net");
						$mail->setSubject("New player for your ladder {$ladderInfo["name"]}");
						$mail->setHtmlMessage(<<<MESSAGE
<p>Dear $ladderNameHtml moderator,</p>

<p>A new player, $userNameHtml, signed up for your ladder $ladderNameHtml. Please visit <a href="$modladderHtml">the moderator page</a> to accept or reject this player.</p>

MESSAGE
						);
						$mail->send();
					}
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
	(isLoggedIn() ? null : array("title"=>"Email", "type"=>"text", "name"=>"email")),
	(isLoggedIn() ? null : array("title"=>"", "type"=>"html", "html"=>"<p><em>Optional. We use this to mail you up-to-date ladder standings, if you enable them below.</em></p>")),
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
