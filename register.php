<?php

require_once("common.php");

$general_messages = "";
$general_error = "";

if(($action = post("action")) !== null) {
	if($action == "general-settings") {
		if(post("email") !== null) {
			$warlightUserID = $_SESSION["token"];
			$user = apiGetUser($warlightUserID);
			$_SESSION["userID"] = db()->stdNew("users", array("warlightUserID"=>$warlightUserID, "name"=>$user["name"], "color"=>$user["color"]));
			if (post("email") !== null) {
				initUserEmail($_SESSION["userID"], post("email"));
			}
			
			redirect("index.php");
		}
	}
}
$html = "";

$html .= operationForm("register.php", $general_error, "Email settings", "Save", array(
	array("type"=>"hidden", "name"=>"action", "value"=>"general-settings"),
	array("title"=>"Email", "type"=>"text", "name"=>"email"),
), null, array("custom"=>"{$general_messages}<p>Optional. We use this to mail you up-to-date ladder standings, if you enable them.</p>"));


page($html, "register", "Registration", "Please enter your information");
