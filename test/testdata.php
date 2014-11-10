<?php

require_once("common.php");

db()->setQuery("TRUNCATE gamePlayers;");
db()->setQuery("TRUNCATE ladderAdmins;");
db()->setQuery("TRUNCATE ladderGames;");
db()->setQuery("TRUNCATE ladderPlayers;");
db()->setQuery("TRUNCATE ladders;");
db()->setQuery("TRUNCATE ladderTemplates;");
db()->setQuery("TRUNCATE playerLadderTemplates;");
db()->setQuery("TRUNCATE templates;");
db()->setQuery("TRUNCATE users;");

$ladderID = db()->stdNew("ladders", array("name"=>"Test ladder", "description"=>"test ladder description", "accessibility"=>"PUBLIC", "visibility"=>"PUBLIC", "active"=>1, "minSimultaneousGames"=>1, "maxSimultaneousGames"=>5));
$templateID = db()->stdNew("templates", array("name"=>"Test template", "warlightTemplateID"=>0));
db()->stdNew("ladderTemplates", array("ladderID"=>$ladderID, "templateID"=>$templateID));

$score = tsDefaultScore();
for($i = 1; $i <= 100; $i++) {
	$userID = db()->stdNew("users", array("warlightUserID"=>$i, "name"=>"Player #$i", "color"=>"#000000", "email"=>null));
	db()->stdNew("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID, "mu"=>$score["mu"], "sigma"=>$score["sigma"], "rating"=>$score["rating"], "rank"=>$i, "joinStatus"=>"JOINED", "active"=>1, "simultaneousGames"=>($i % 5) + 1, "joinTime"=>time()));
	db()->stdNew("playerLadderTemplates", array("userID"=>$userID, "ladderID"=>$ladderID, "templateID"=>$templateID, "score"=>1));
}

?>
