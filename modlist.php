<?php

require_once("common.php");

if (!isset($_GET["ladder"])) error404();
$ladderID = $_GET["ladder"];

checkLadderMod($ladderID);

return page(renderLadderMods($ladderID, "Ladder moderators", null, null, pageNumber()), "laddermodlist");
