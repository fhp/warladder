<?php

require_once(dirname(__FILE__) . "/../common.php");

db()->setQuery("DROP TABLE IF EXISTS gamePlayers;");
db()->setQuery("DROP TABLE IF EXISTS ladderAdmins;");
db()->setQuery("DROP TABLE IF EXISTS ladderChat;");
db()->setQuery("DROP TABLE IF EXISTS ladderGames;");
db()->setQuery("DROP TABLE IF EXISTS ladderPlayers;");
db()->setQuery("DROP TABLE IF EXISTS ladders;");
db()->setQuery("DROP TABLE IF EXISTS ladderTemplates;");
db()->setQuery("DROP TABLE IF EXISTS playerLadderTemplates;");
db()->setQuery("DROP TABLE IF EXISTS users;");

$sql = file_get_contents(dirname(__FILE__) . "/../database.sql");

foreach(explode(";", $sql) as $query) {
	try {
		db()->query($query);
	} catch(DatabaseException $e) {
		// Jammer dan...
	}
}

?>