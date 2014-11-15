<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];

checkLadderMod($ladderID);

return page(renderAcceptList($ladderID, "Accept players", null, null, pageNumber()), "acceptlist");
