<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];
checkLadder($ladderID);

if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
	$page = $_GET["page"];
} else {
	$page = 1;
}

$ladderNameHtml = htmlentities(db()->stdGet("ladders", array("ladderID"=>$ladderID), "name"));

return page(renderRanking($ladderID, "Ranking for ladder $ladderNameHtml", null, null, null, $page), "ranking");

