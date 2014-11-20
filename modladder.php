<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];

checkLadderMod($ladderID);

$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
$ladderNameHtml = htmlentities($ladderName);

$removeModeratorError = "";

$change_settings_messages = array();
$change_settings_error = "";

$addTemplateError = "";

$html = "";

if (($removeTemplate = post("remove-template")) !== null) {
	// TODO: confirm
	db()->stdDel("ladderTemplates", array("ladderID"=>$ladderID, "templateID"=>$removeTemplate));
} else if (($removeAdmin = post("remove-admin")) !== null) {
	if ($removeAdmin == currentUserID()) {
		$removeModeratorError = formError("You cannot remove yourself as a moderator.");
	} else if (isMod($ladderID, $removeAdmin)) {
		db()->stdDel("ladderAdmins", array("userID"=>$removeAdmin, "ladderID"=>$ladderID));
	}
} else if(($action = post("action")) !== null) {
	if($action == "change-settings") {
		$values["name"] = post("name");
		if($values["name"] === null || $values["name"] == "") {
			$change_settings_error .= formError("Please specify a name.");
		}
		$values["summary"] = post("summary");
		if($values["summary"] === null || $values["summary"] == "") {
			$change_settings_error .= formError("Please specify a summary.");
		}
		if(strlen($values["summary"]) > 255) {
			$change_settings_error .= formError("Please make your summary shorter.");
		}
		$values["message"] = post("message");
		$values["accessibility"] = post("accessibility");
		if($values["accessibility"] != "PUBLIC" && $values["accessibility"] != "MODERATED") {
			$change_settings_error .= formError("Invalid joining policy.");
		}
		$values["visibility"] = post("visibility");
		if($values["visibility"] != "PUBLIC" && $values["visibility"] != "PRIVATE") {
			$change_settings_error .= formError("Show on front page answer invalid.");
		}
		$values["minSimultaneousGames"] = post("minSimultaneousGames");
		if(!ctype_digit($values["minSimultaneousGames"])) {
			$change_settings_error .= formError("Please enter a number for the minimal simultaneous games.");
		}
		$values["maxSimultaneousGames"] = post("maxSimultaneousGames");
		if(!ctype_digit($values["maxSimultaneousGames"])) {
			$change_settings_error .= formError("Please enter a number for the maximal simultaneous games.");
		}
		if($values["minSimultaneousGames"] > $values["maxSimultaneousGames"]) {
			$change_settings_error .= formError("The minimal number of simultaneous games must be smaller than the maximal number of simultaneous games.");
		}
		if($change_settings_error == "") {
			db()->stdSet("ladders", array("ladderID"=>$ladderID), $values);
			$change_settings_messages["custom"] = "<div class=\"alert alert-success\" role=\"alert\">Settings saved.</div>";
		}
	}
	if($action == "start-ladder") {
		if(post("confirm") == 1) {
			db()->stdSet("ladders", array("ladderID"=>$ladderID), array("active"=>1));
		} else {
			$html .= operationForm("modladder.php?ladder=$ladderID", null, "Start Ladder", "Start", array(
				array("type"=>"hidden", "name"=>"action", "value"=>"start-ladder"),
			), null, array("custom"=>"<p>This will activate the ladder, allowing players to join and play games.</p>"));
			page($html, "modladder");
			die();
		}
	}
	if($action == "stop-ladder") {
		if(post("confirm") == 1) {
			db()->stdSet("ladders", array("ladderID"=>$ladderID), array("active"=>0));
		} else {
			$html .= operationForm("modladder.php?ladder=$ladderID", null, "Deactivate Ladder", "Deactivate", array(
				array("type"=>"hidden", "name"=>"action", "value"=>"stop-ladder"),
			), null, array("custom"=>"<p>This will deactivate the ladder. New games will no longer be created, and players cannot join the ladder anymore.</p>"));
			page($html, "modladder");
			die();
		}
	}
	if($action == "add-moderator") {
		$newModID = post("userID");
		if(!isMod($ladderID, $newModID)) {
			db()->stdNew("ladderAdmins", array("userID"=>$newModID, "ladderID"=>$ladderID));$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
$ladderNameHtml = htmlentities($ladderName);

		}
	}
	if($action == "ban-player") {
		$userID = post("userID");
		checkLadderPlayer($ladderID, $userID);
		if(post("confirm") == 1) {
			removePlayerRank($ladderID, $userID);
			db()->stdSet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), array("joinStatus"=>"BOOTED"));
			redirect("ladder.php?ladder=" . $ladderID);
		} else {
			$name = db()->stdGet("users", array("userID"=>$userID), "name");
			$html .= operationForm("modladder.php?ladder=$ladderID", null, "Ban player from ladder", "Yes, ban this player!", array(
				array("type"=>"hidden", "name"=>"action", "value"=>"ban-player"),
				array("type"=>"hidden", "name"=>"userID", "value"=>$userID),
			), null, array("custom"=>"<p class=\"alert alert-warning\"><b>Warning:</b> Are you sure you want to ban <b>$name</b> from this ladder?</p>"));
			page($html, "modladder", null);
			die();
		}
	}
	if($action == "add-template") {
		$template = post("template");
		$name = post("templateName");
		if(strpos($template, "TemplateID=") === false) {
			$templateID = (int)$template;
		} else {
			$templateID = (int)substr($template, strrpos($template, "=") + 1);
		}
		if ($templateID == 0) {
			$addTemplateError = formError("Invalid template ID.");
		} else if ($name == "") {
			// do nothing
		} else if (db()->stdExists("ladderTemplates", array("ladderID"=>$ladderID, "warlightTemplateID"=>$templateID))) {
			$addTemplateError = formError("This template is already in use on this ladder.");
		} else {
			db()->stdNew("ladderTemplates", array("ladderID"=>$ladderID, "warlightTemplateID"=>$templateID, "name"=>$name));
		}
	}
}



if(db()->stdGet("ladders", array("ladderID"=>$ladderID), "active") == 0) {
	$html .= operationForm("modladder.php?ladder=$ladderID", "", "Start Ladder", "Start", array(
		array("type"=>"hidden", "name"=>"action", "value"=>"start-ladder"),
	), null, array("custom"=>"<p>This will activate the ladder, allowing players to join and play games.</p>"));
}



if(db()->stdGet("ladders", array("ladderID"=>$ladderID), "accessibility") == "MODERATED") {
	$html .= "<div class=\"accept-players\">\n";
	$html .= "<h2>Joining Players</h2>\n";
	$html .= "<p>These players want to join this ladder. As a moderator, you can either accept or reject them.</p>";
	$html .= renderAcceptList($ladderID, "New players", 0, 10);
	$html .= "</div>\n";
}



if($change_settings_error == "") {
	$values = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("name", "summary", "message", "accessibility", "visibility", "minSimultaneousGames", "maxSimultaneousGames"));
}

$html .= operationForm("modladder.php?ladder=$ladderID", $change_settings_error, "Ladder Settings", "Save", array(
	array("type"=>"hidden", "name"=>"action", "value"=>"change-settings"),
	array("title"=>"Name", "type"=>"text", "name"=>"name"),
	array("title"=>"Summary", "type"=>"text", "name"=>"summary"),
	array("title"=>"Message", "type"=>"textarea", "name"=>"message"),
	array("title"=>"Joining policy", "type"=>"dropdown", "name"=>"accessibility", "options"=>array(array("value"=>"PUBLIC", "label"=>"Everyone can join"), array("value"=>"MODERATED", "label"=>"Moderator must approve"))),
	array("title"=>"Show on front page", "type"=>"dropdown", "name"=>"visibility", "options"=>array(array("value"=>"PUBLIC", "label"=>"Yes"), array("value"=>"PRIVATE", "label"=>"No"))),
	array("title"=>"Number of simultaneous games", "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>_("Minimal")),
		array("type"=>"text", "name"=>"minSimultaneousGames", "cellclass"=>"stretch-50"),
		array("type"=>"html", "html"=>_("Maximal")),
		array("type"=>"text", "name"=>"maxSimultaneousGames", "cellclass"=>"stretch-50")
	)),
), $values, $change_settings_messages);



$html .= "<div class=\"ladder-templates\">\n";
$html .= "<h2>Templates</h2>\n";
$html .= $addTemplateError;
$html .= "<p>Those are the games that can be played on this ladder. Players can choose which of these templates they like to play.</p>";
$html .= renderLadderTemplates($ladderID, "modladder.php?ladder=$ladderID", "templateName", "template", "add-template");
$html .= "</div>\n";



$html .= "<div class=\"ladder-moderators\">\n";
$html .= "<h2>Moderators</h2>\n";
$html .= $removeModeratorError;
$html .= "<p>Moderators can change ladder settings, ban players, and accept new recruits.</p>\n";
$html .= renderLadderMods($ladderID, "modladder.php?ladder=$ladderID", "userID", "add-moderator");
$html .= "</div>\n";



if(!db()->stdGet("ladders", array("ladderID"=>$ladderID), "active") == 0) {
	$html .= operationForm("modladder.php?ladder=$ladderID", "", "Deactivate Ladder", "Deactivate", array(
		array("type"=>"hidden", "name"=>"action", "value"=>"stop-ladder"),
	), null, array("custom"=>"<p>This will deactivate the ladder. New games will no longer be created, and players cannot join the ladder anymore.</p>"));
}



page($html, "modladder", "Ladder configuration", "<a href=\"ladder.php?ladder=$ladderID\">$ladderNameHtml</a>", null, "Ladder configuration - $ladderNameHtml");
