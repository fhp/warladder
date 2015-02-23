<?php

require_once(dirname(__FILE__) . "/../common.php");

$action = get("action");
$ladderID = get("ladder");

checkLadder($ladderID);
$ladderIDSql = db()->addSlashes($ladderID);

if($action == "get") {
	if(get("from") === null) {
		$result = db()->query("SELECT userID, name, message, timestamp FROM ladderChat LEFT JOIN users USING(userID) WHERE ladderID='$ladderIDSql' ORDER BY timestamp DESC LIMIT 10")->fetchList();
		$result = array_reverse($result);
	} else {
		$fromSql = db()->addSlashes(get("from"));
		$result = db()->query("SELECT userID, name, message, timestamp FROM ladderChat LEFT JOIN users USING(userID) WHERE ladderID='$ladderIDSql' AND timestamp > $fromSql ORDER BY timestamp")->fetchList();
	}
	$output = array();
	foreach($result as $line) {
		$output[] = array("userID"=>(int)$line["userID"], "name"=>$line["name"], "message"=>htmlentities($line["message"]), "timestamp"=>(int)$line["timestamp"]);
	}
	echo json_encode($output);
	die();
} else if($action == "send") {
	if(!isLoggedIn()) {
		error404();
	}
	$message = post("message");
	if($message === null || $message == "" || strlen($message) > 255) {
		error404();
	}
	$message = urldecode($message);
	db()->stdNew("ladderChat", array("ladderID"=>$ladderID, "userID"=>currentUserID(), "timestamp"=>time(), "message"=>$message));
} else {
	error404();
}

?>