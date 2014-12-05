<?php

require_once(dirname(__FILE__) . "/../common.php");

db()->setQuery("TRUNCATE gamePlayers;");
db()->setQuery("TRUNCATE ladderAdmins;");
db()->setQuery("TRUNCATE ladderChat;");
db()->setQuery("TRUNCATE ladderGames;");
db()->setQuery("TRUNCATE ladderPlayers;");
db()->setQuery("TRUNCATE ladders;");
db()->setQuery("TRUNCATE ladderTemplates;");
db()->setQuery("TRUNCATE playerLadderTemplates;");
db()->setQuery("TRUNCATE users;");

$ladderID = 1337;
db()->stdNew("ladders", array("ladderID"=>$ladderID, "name"=>"Test ladder", "summary"=>"Alleen om te testen", "message"=>"Doe mee met deze ladder als je warladder wilt testen.\n\nDat is heel erg leuk namelijk!", "accessibility"=>"PUBLIC", "visibility"=>"PUBLIC", "active"=>1, "minSimultaneousGames"=>1, "maxSimultaneousGames"=>5));
$templateID = db()->stdNew("ladderTemplates", array("ladderID"=>$ladderID, "warlightTemplateID"=>"546673", "name"=>"Demo template"));

$score = tsDefaultScore();
for($i = 1; $i <= 100; $i++) {
	$userID = db()->stdNew("users", array("warlightUserID"=>$i, "name"=>"Player #$i", "color"=>"#000000", "email"=>null));
	db()->stdNew("ladderPlayers", array("userID"=>$userID, "ladderID"=>$ladderID, "mu"=>$score["mu"], "sigma"=>$score["sigma"], "rating"=>$score["rating"], "rank"=>$i, "joinStatus"=>"JOINED", "active"=>1, "simultaneousGames"=>($i % 5) + 1, "joinTime"=>time()));
	db()->stdNew("playerLadderTemplates", array("userID"=>$userID, "ladderID"=>$ladderID, "templateID"=>$templateID, "score"=>1));
}

?>
