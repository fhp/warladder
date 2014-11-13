<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];

checkLadderMod($ladderID);

$change_settings_messages = array();
$change_settings_error = "";

if(($action = post("action")) !== null) {
	if($action == "change-settings") {
		$values["name"] = post("name");
		if($values["name"] === null || $values["name"] == "") {
			$change_settings_error .= formError("Please specify a name.");
		}
		$values["description"] = post("description");
		$values["accessibility"] = post("accessibility");
		if($values["accessibility"] != "PUBLIC" && $values["accessibility"] != "MODERATED" && $values["accessibility"] != "PRIVATE") {
			$change_settings_error .= formError("Invalid joining policy.");
		}
		$values["visibility"] = post("visibility");
		if($values["visibility"] != "PUBLIC" && $values["visibility"] != "PRIVATE") {
			$change_settings_error .= formError("Show on front page answer invalid.");
		}
		if($values["accessibility"] == "PRIVATE" && $values["visibility"] != "PRIVATE") {
			$change_settings_error .= formError("Invite only ladders can't be shown on the front page.");
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
}

// mod ladder (modladder.php?ladder=X)
// 	invite knop
// 	invitation lijst [top10]
// 	accept lijst [top10]
// 	start knop [met confirm]
// 	stop knop [met confirm]
// 	admin lijst



$html = <<<HTML
<h1>Ladder Configuration</h1>
<p>Change the settings for the ladder here!</p>

HTML;

if($change_settings_error == "") {
	$values = db()->stdGet("ladders", array("ladderID"=>$ladderID), array("name", "description", "accessibility", "visibility", "minSimultaneousGames", "maxSimultaneousGames"));
}

$html .= operationForm("modladder.php?ladder=$ladderID", $change_settings_error, "Ladder Settings", "save", array(
	array("type"=>"hidden", "name"=>"action", "value"=>"change-settings"),
	array("title"=>"Name", "type"=>"text", "name"=>"name"),
	array("title"=>"Description", "type"=>"textarea", "name"=>"description"),
	array("title"=>"Joining policy", "type"=>"dropdown", "name"=>"accessibility", "options"=>array(array("value"=>"PUBLIC", "label"=>"Everyone can join"), array("value"=>"MODERATED", "label"=>"Moderator must approve"), array("value"=>"PRIVATE", "label"=>"Invite only"))),
	array("title"=>"Show on front page", "type"=>"dropdown", "name"=>"visibility", "options"=>array(array("value"=>"PUBLIC", "label"=>"Yes"), array("value"=>"PRIVATE", "label"=>"No"))),
	array("title"=>"Number of simultainius games", "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>_("Minimal")),
		array("type"=>"text", "name"=>"minSimultaneousGames", "cellclass"=>"stretch-50"),
		array("type"=>"html", "html"=>_("Maximal")),
		array("type"=>"text", "name"=>"maxSimultaneousGames", "cellclass"=>"stretch-50")
	)),
	), $values, $change_settings_messages);
// TODO: Show on front page should be restricted to NO if Joining policy is set to invite only.

page($html, "modladder");

?>