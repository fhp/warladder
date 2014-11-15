<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];

checkLadderMod($ladderID);

$change_settings_messages = array();
$change_settings_error = "";

$html = <<<HTML
<h1>Ladder Configuration</h1>
<p>Change the settings for the ladder here!</p>

HTML;

if(($action = post("action")) !== null) {
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
			$change_settings_error .= formError("Please enter a number for the minimal simultainius games.");
		}
		$values["maxSimultaneousGames"] = post("maxSimultaneousGames");
		if(!ctype_digit($values["maxSimultaneousGames"])) {
			$change_settings_error .= formError("Please enter a number for the maximal simultainius games.");
		}
		if($values["minSimultaneousGames"] > $values["maxSimultaneousGames"]) {
			$change_settings_error .= formError("The minimal number of simultainius games must be smaller than the maximal number of simultainius games.");
		}
		if($change_settings_error == "") {
			db()->stdSet("ladders", array("ladderID"=>$ladderID), $values);
			$change_settings_messages["custom"] = "<div class=\"alert alert-success\" role=\"alert\"><strong>Success!</strong> The preferences are saved.</div>";
		}
	}
	if($action == "start-ladder") {
		if(post("confirm") == 1) {
			db()->stdSet("ladders", array("ladderID"=>$ladderID), array("active"=>1));
		} else {
			$html .= operationForm("modladder.php?ladder=$ladderID", null, "Start Ladder", "start", array(
				array("type"=>"hidden", "name"=>"action", "value"=>"start-ladder"),
			), null);
			page($html, "modladder");
			die();
		}
	}
	if($action == "stop-ladder") {
		// TODO: uitleg met implicaties van aan / uit zetten toevoegen.
		if(post("confirm") == 1) {
			db()->stdSet("ladders", array("ladderID"=>$ladderID), array("active"=>0));
		} else {
			$html .= operationForm("modladder.php?ladder=$ladderID", null, "Stop Ladder", "stop", array(
				array("type"=>"hidden", "name"=>"action", "value"=>"stop-ladder"),
			), null);
			page($html, "modladder");
			die();
		}
	}
}

// mod ladder (modladder.php?ladder=X)
// 	mod lijst

if(db()->stdGet("ladders", array("ladderID"=>$ladderID), "accessibility") == "MODERATED") {
	$html .= renderAcceptList($ladderID, "Accept players", 0, 10);
}


if($change_settings_error == "") {
	$values = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("name", "summary", "message", "accessibility", "visibility", "minSimultaneousGames", "maxSimultaneousGames"));
}

$html .= operationForm("modladder.php?ladder=$ladderID", $change_settings_error, "Ladder Settings", "save", array(
	array("type"=>"hidden", "name"=>"action", "value"=>"change-settings"),
	array("title"=>"Name", "type"=>"text", "name"=>"name"),
	array("title"=>"Summary", "type"=>"text", "name"=>"summary"),
	array("title"=>"Message", "type"=>"textarea", "name"=>"message"),
	array("title"=>"Joining policy", "type"=>"dropdown", "name"=>"accessibility", "options"=>array(array("value"=>"PUBLIC", "label"=>"Everyone can join"), array("value"=>"MODERATED", "label"=>"Moderator must approve"))),
	array("title"=>"Show on front page", "type"=>"dropdown", "name"=>"visibility", "options"=>array(array("value"=>"PUBLIC", "label"=>"Yes"), array("value"=>"PRIVATE", "label"=>"No"))),
	array("title"=>"Number of simultainius games", "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>_("Minimal")),
		array("type"=>"text", "name"=>"minSimultaneousGames", "cellclass"=>"stretch-50"),
		array("type"=>"html", "html"=>_("Maximal")),
		array("type"=>"text", "name"=>"maxSimultaneousGames", "cellclass"=>"stretch-50")
	)),
), $values, $change_settings_messages);

$html .= renderLadderMods($ladderID, "Ladder moderators", 0, 10);

// TODO: betere namen, mogelijk start-knop bovenaan de pagina.
if(db()->stdGet("ladders", array("ladderID"=>$ladderID), "active") == 0) {
	$html .= operationForm("modladder.php?ladder=$ladderID", "", "Start Ladder", "start", array(
		array("type"=>"hidden", "name"=>"action", "value"=>"start-ladder"),
	), null);
} else {
	$html .= operationForm("modladder.php?ladder=$ladderID", "", "Stop Ladder", "stop", array(
		array("type"=>"hidden", "name"=>"action", "value"=>"stop-ladder"),
	), null);
}

page($html, "modladder");

?>