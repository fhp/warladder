<?php

require_once("common.php");

if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
	$page = $_GET["page"];
} else {
	$page = 1;
}

return page(renderOpenLadders("Open ladders", null, null, $page), "ladders");
