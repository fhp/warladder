<?php

require_once("common.php");

requireLogin();

if (isset($_GET["page"]) && ctype_digit($_GET["page"])) {
	$page = $_GET["page"];
} else {
	$page = 1;
}

return page(renderMyLadders(currentUserID(), "My ladders", null, null, $page), "myladders");
