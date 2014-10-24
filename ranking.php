<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
checkLadder($_GET["ladder"]);

if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
	$page = $_GET["page"];
} else {
	$page = 1;
}

return page(renderRanking($_GET["ladder"], "Ranking for ladder #{$_GET["ladder"]}", null, null, null, $page), "ranking");

