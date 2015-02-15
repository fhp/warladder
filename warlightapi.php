<?php

require_once("common.php");

function apiPost($url, $get = null, $post = null, $addAuth = true)
{
	if ($get === null) {
		$get = array();
	}
	if ($post === null) {
		$post = array();
	}
	
	foreach($get as $key=>$value) {
		if (strpos($url, "?") === false) {
			$url .= "?";
		} else {
			$url .= "&";
		}
		$url .= urlencode($key);
		$url .= "=";
		$url .= urlencode($value);
	}
	
	if (is_array($post)) {
		if ($addAuth) {
			$post["Email"] = $GLOBALS["config"]["email"];
			$post["APIToken"] = $GLOBALS["config"]["apiKey"];
		}
		
		$postdata = "";
		foreach($post as $key=>$value) {
			if ($postdata != "") {
				$postdata .= "&";
			}
			$postdata .= urlencode($key);
			$postdata .= "=";
			$postdata .= urlencode($value);
		}
	} else {
		$postdata = $post;
	}
	
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_POST, 1);
	curl_setopt($c, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	
	return curl_exec($c);
}

function apiCheckLogin($warlightUserID, $clotPassword)
{
	if ($warlightUserID === null || $clotPassword === null) {
		return false;
	}
	$json = apiPost("http://warlight.net/API/ValidateInviteToken", array("Token"=>$warlightUserID));
	$response = json_decode($json);
	if ($response === null) {
		return null;
	}
	if (isset($response->error)) {
		return false;
	}
	if (!isset($response->tokenIsValid)) {
		return false;
	}
	if (!isset($response->clotpass)) {
		return false;
	}
	return $response->clotpass == $clotPassword;
}

function apiGetUser($warlightUserID)
{
	$json = apiPost("http://warlight.net/API/ValidateInviteToken", array("Token"=>$warlightUserID));
	$response = json_decode($json);
	if ($response === null) {
		return null;
	}
	if (isset($response->error)) {
		return null;
	}
	if (!isset($response->tokenIsValid)) {
		return null;
	}
	return array(
		"name"=>$response->name,
		"color"=>$response->color
	);
}

function apiGetUserTemplates($warlightUserID, $warlightTemplateIDs)
{
	$output = array();
	if(!is_array($warlightTemplateIDs)) {
		$warlightTemplateIDs = array($warlightTemplateIDs);
	}
	$chunks = array_chunk($warlightTemplateIDs, 20);
	foreach($chunks as $chunk) {
		$json = apiPost("http://warlight.net/API/ValidateInviteToken", array("Token"=>$warlightUserID, "TemplateIDs"=>implode(",", $chunk)));
		$response = json_decode($json);
		if ($response === null) {
			return null;
		}
		if (isset($response->error)) {
			return null;
		}
		if (!isset($response->tokenIsValid)) {
			return null;
		}
		foreach($chunk as $templateID) {
			$variable = "template$templateID";
			if (!isset($response->$variable)) {
				return null;
			}
			if ($response->$variable->result == "CanUseTemplate") {
				$output[] = $templateID;
			}
		}
	}
	return $output;
}

function apiGetGame($gameID)
{
	$json = apiPost("http://warlight.net/API/GameFeed", array("GameID"=>$gameID));
	$response = json_decode($json);
	if ($response === null) {
		return null;
	}
	if (isset($response->error)) {
		return null;
	}
	if (!isset($response->state)) {
		return null;
	}
	if (!isset($response->players)) {
		return null;
	}
	if ($response->state == "DistributingTerritories" || $response->state == "Playing") {
		return array("state"=>"playing");
	} else if ($response->state == "Finished") {
		$winners = array();
		foreach($response->players as $player) {
			if (!isset($player->id)) {
				return null;
			}
			if (!isset($player->state)) {
				return null;
			}
			if ($player->state == "VotedToEnd") {
				return array("state"=>"votedtoend");
			}
			if ($player->state == "Won") {
				$winners[] = $player->id;
			}
		}
		return array("state"=>"finished", "winners"=>$winners);
	} else if ($response->state == "WaitingForPlayers") {
		foreach ($response->players as $player) {
			if (!isset($player->state)) {
				return null;
			}
			if ($player->state == "Declined") {
				return array("state"=>"rejected");
			}
		}
		return array("state"=>"playing");
	} else {
		return null;
	}
}

function apiGetGameEndTime($gameID)
{
	$json = apiPost("http://warlight.net/API/GameFeed", array("GameID"=>$gameID, "GetHistory"=>"true"));
	$response = json_decode($json);
	if ($response === null) {
		return null;
	}
	if (isset($response->error)) {
		return null;
	}
	if (!isset($response->state)) {
		return null;
	}
	if ($response->state != "Finished") {
		return false;
	}
	if (!isset($response->players)) {
		return null;
	}
	if (!isset($response->numberOfTurns)) {
		return null;
	}
	$turnName = "turn" . ($response->numberOfTurns - 1);
	if (!isset($response->$turnName)) {
		return null;
	}
	$turn = $response->$turnName;
	if (!isset($turn->date)) {
		return null;
	}
	return date_create_from_format("m/d/Y H:i:s", $turn->date)->format("U");
}

function apiCreateGame($templateID, $gameName, $personalMessage, $players)
{
	$playersJson = array();
	foreach($players as $player => $team) {
		if ($team === null) {
			$team = "None";
		}
		$playersJson[] = array("token" => "$player", "team" => "$team");
	}
	
	$json = array();
	$json["hostEmail"] = $GLOBALS["config"]["email"];
	$json["hostAPIToken"] = $GLOBALS["config"]["apiKey"];
	$json["templateID"] = $templateID;
	$json["gameName"] = substr($gameName, 0, 50);
	$json["personalMessage"] = substr($personalMessage, 0, 1024);
	$json["players"] = $playersJson;
	
	$responseJson = apiPost("http://warlight.net/API/CreateGame", array(), json_encode($json), false);
	$response = json_decode($responseJson);
	if ($response === null) {
		return null;
	}
	if (isset($response->error)) {
		return null;
	}
	if (!isset($response->gameID)) {
		return null;
	}
	return $response->gameID;
}

function apiDeleteGame($gameID)
{
	$json = array();
	$json["Email"] = $GLOBALS["config"]["email"];
	$json["APIToken"] = $GLOBALS["config"]["apiKey"];
	$json["gameID"] = (int)$gameID;
	
	$responseJson = apiPost("http://warlight.net/API/DeleteLobbyGame", array(), json_encode($json), false);
	var_dump($responseJson);
	$response = json_decode($responseJson);
	if ($response === null) {
		return false;
	}
	if (isset($response->error)) {
		return false;
	}
	return true;
}


//var_dump(apiGetGame(7275224));

//var_dump(apiCreateGame(535412, "game name", "hippe beschrijving", array(158790373=>null, 408790419=>null)));

//var_dump(apiGetGameEndTime(1212978));

//var_dump(apiGetUser("1220997122"));

//var_dump(apiCheckLogin("158790374", "rmjYedAQzJMpfan6"));

//var_dump(apiGetUserTemplates("158790373", array(546673, 546680, 546690, 546697, 546700)));

