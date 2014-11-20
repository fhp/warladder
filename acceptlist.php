<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];

checkLadderMod($ladderID);

$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
$ladderNameHtml = htmlentities($ladderName);


return page(renderAcceptList($ladderID, null, null, null, pageNumber()), "acceptlist", "New players", "<a href=\"ladder.php?ladder=$ladderID\">$ladderNameHtml</a>", "<p>These players want to join this ladder. As a moderator, you can either accept or reject them.</p>", "New players - $ladderNameHtml");
