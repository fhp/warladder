<?php

require_once(dirname(__FILE__) . "/../common.php");

// TODO: per speler berekenen op basis van maximum haalbare score
define('ALWAYS_ACCEPTABLE_MATCH_QUALITY', 0.9);
define('ACCEPTABLE_MATCH_QUALITY_SLACK_PER_MISSING_GAME', 0.1);


function finishGames($ladderID)
{
	$results = array();
	
	$runningGames = db()->stdList("ladderGames", array("ladderID"=>$ladderID, "status"=>"RUNNING"), array("gameID", "warlightGameID"));
	foreach ($runningGames as $game) {
		$status = apiGetGame($game["warlightGameID"]);
		if (!$status["finished"]) {
			continue;
		}
		
		$players = db()->stdList("gamePlayers", array("gameID"=>$game["gameID"]), "userID");
		
		if (count($status["winners"]) == 0) {
			$winner = null;
		} else {
			$winner = db()->stdGet("users", array("warlightUserID"=>$status["winners"][0]), "userID");
		}
		
		$endTime = apiGetGameEndTime($game["warlightGameID"]);
		
		db()->stdSet("ladderGames", array("gameID"=>$game["gameID"]), array("status"=>"FINISHED", "winningUserID"=>$winner, "endTime"=>$endTime));
		
		$teams = array();
		$rankings = array();
		foreach ($players as $userID) {
			$teams[] = array($userID);
			if ($winner == $userID) {
				$rankings[] = 0;
			} else {
				$rankings[] = 1;
			}
		}
		
		$results[] = array("teams"=>$teams, "rankings"=>$rankings);
	}
	
	$players = db()->stdList("ladderPlayers", array("ladderID"=>$ladderID, "joinStatus"=>"JOINED", "active"=>1), array("userID", "mu", "sigma"));
	$newScores = tsProcessMatchResults($players, $results);
	
	$ladderIDSql = db()->addSlashes($ladderID);
	db()->setQuery("UPDATE ladderPlayers SET rank = 0 WHERE ladderID='$ladderIDSql' AND (active = 0 OR joinStatus <> 'JOINED')");
	foreach($newScores as $userID => $score) {
		db()->stdSet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID), $score);
	}
}





function describeRank($rank)
{
	switch ($rank) {
		case 1: return "1st";
		case 2: return "2nd";
		case 3: return "3rd";
		default: return "{$rank}th";
	}
}

function createGame($ladderID, $userID1, $userID2)
{
	$user1WarlightID = db()->stdGet("users", array("userID"=>$userID1), "warlightUserID");
	$user2WarlightID = db()->stdGet("users", array("userID"=>$userID2), "warlightUserID");
	
	$templates = db()->stdMap("ladderTemplates", array("ladderID"=>$ladderID), "templateID", "warlightTemplateID");
	
	$user1Templates = db()->stdMap("playerLadderTemplates", array("userID"=>$userID1, "ladderID"=>$ladderID, "canPlay"=>1), "templateID", "score");
	$user2Templates = db()->stdMap("playerLadderTemplates", array("userID"=>$userID2, "ladderID"=>$ladderID, "canPlay"=>1), "templateID", "score");
	
	$usableTemplates = array();
	foreach($templates as $templateID => $warlightTemplateID) {
		if (isset($user1Templates[$templateID]) && 
		    isset($user2Templates[$templateID]))
		{
			$usableTemplates[$templateID] = $warlightTemplateID;
		}
	}
	
	$scores = array();
	foreach($usableTemplates as $templateID => $warlightTemplateID) {
		$scores[$templateID] = $user1Templates[$templateID] + $user2Templates[$templateID];
	}
	
	arsort($scores);
	reset($scores);
	list($templateID, $maxScore) = each($scores);
	
	$bestScores = array();
	foreach($scores as $templateID => $score) {
		if ($score == $maxScore) {
			$bestScores[] = $templateID;
		} else {
			break;
		}
	}
	$templateID = $bestScores[rand(0, count($bestScores) - 1)];
	
	$templateName = db()->stdGet("ladderTemplates", array("templateID"=>$templateID), "name");
	
	
	
	db()->startTransaction();
	$gameID = db()->stdNew("ladderGames", array("ladderID"=>$ladderID, "templateID"=>$templateID, "warlightGameID"=>null, "name"=>null, "status"=>"RUNNING", "winningUserID"=>null, "startTime"=>time(), "endTime"=>null));
	db()->stdNew("gamePlayers", array("gameID"=>$gameID, "userID"=>$userID1));
	db()->stdNew("gamePlayers", array("gameID"=>$gameID, "userID"=>$userID2));
	
	$ladderName = db()->stdGet("ladders", array("ladderID"=>$ladderID), "name");
	$userName1 = db()->stdGet("users", array("userID"=>$userID1), "name");
	$userName2 = db()->stdGet("users", array("userID"=>$userID2), "name");
	$user1 = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID1), array("rating", "rank"));
	$user2 = db()->stdGet("ladderPlayers", array("ladderID"=>$ladderID, "userID"=>$userID2), array("rating", "rank"));
	$rank1 = describeRank($user1["rank"]);
	$rank2 = describeRank($user2["rank"]);
	
	$name = "$ladderName game #$gameID: $userName1 vs $userName2";
	
	$description = <<<DESC
This game is part of ladder $ladderName. To see up-to-date ladder ratings, visit the ladder page at {$GLOBALS["config"]["baseUrl"]}/ladder.php?ladder=$ladderID
Contender 1: $userName1 (Ranked $rank1 with a rating of {$user1["rating"]})
Contender 2: $userName2 (Ranked $rank2 with a rating of {$user2["rating"]})

Settings: $templateName
DESC;
	
	$warlightGameID = apiCreateGame($templates[$templateID], $name, $description, array($user1WarlightID=>null, $user2WarlightID=>null));
	if ($warlightGameID === null) {
		db()->rollbackTransaction();
		return false;
	}
	
	$ladderNameHtml = htmlentities($ladderName);
	$userName1Html = htmlentities($userName1);
	$userName2Html = htmlentities($userName2);
	$htmlName = "<a href=\"http://WarLight.net/MultiPlayer?GameID=$warlightGameID\">$ladderNameHtml game #$gameID</a>: " .
			"<a href=\"player.php?ladder=$ladderID&player=$userID1\">$userName1Html</a> vs " .
			"<a href=\"player.php?ladder=$ladderID&player=$userID2\">$userName2Html</a>";
	
	db()->stdSet("ladderGames", array("gameID"=>$gameID), array("warlightGameID"=>$warlightGameID, "name"=>$name, "htmlName"=>$htmlName));
	db()->commitTransaction();
	
	return true;
}

function createGames($ladderID)
{
	$time = time();
	$ladderIDSql = db()->addSlashes($ladderID);
	
	/*
	 * Compute the average game length.
	 */
	$averageGameLengthRecord = db()->query("SELECT AVG(endTime - startTime) AS averageGameLength FROM ladderGames WHERE ladderID = '$ladderIDSql' AND status = 'FINISHED'")->fetchArray();
	if ($averageGameLengthRecord["averageGameLength"] === null) {
		/*
		 * This is only possible if no games have been completed at this ladder yet, which means the ladder has only just begun.
		 * In that case, we may as well accept any possible game, because all scores are equal anyway.
		 */
		$averageGameLength = 1;
	} else {
		$averageGameLength = $averageGameLengthRecord["averageGameLength"];
	}
	
	/*
	 * Initialize the player database.
	 */
	$players = db()->stdMap("ladderPlayers", array("ladderID"=>$ladderID, "joinStatus"=>"JOINED", "active"=>1), "userID", array("simultaneousGames", "joinTime", "mu", "sigma"));
	
	/*
	 * Add the number of active games.
	 * Remove players that are at their game limit.
	 */
	$activeGames = db()->query("
		SELECT userID, COUNT(gameID) AS games
		FROM (
			SELECT * from ladderGames WHERE ladderGames.status = 'RUNNING'
		) AS ladderGames
		INNER JOIN gamePlayers USING (gameID)
		RIGHT JOIN ladderPlayers USING (userID)
		WHERE ladderPlayers.ladderID = '$ladderIDSql'
		GROUP BY userID
	")->fetchMap("userID", "games");
	foreach ($activeGames as $userID=>$games) {
		if (!isset($players[$userID])) {
			continue;
		}
		if ($players[$userID]["simultaneousGames"] == $games) {
			unset($players[$userID]);
		} else {
			$players[$userID]["activeGames"] = $games;
		}
	}
	
	/*
	 * For each remaining player, compute the relative missed game seconds.
	 *
	 * For each game that you want to play but are not currently playing, you get 1/simultaneousGames relative missed game seconds
	 * for each second between now and the last time you played that many games (or joinTime if you never played that many games).
	 *
	 * Query, for each finished game, the number of games you were playing when that game ended, the finishing game included; this
	 * is the subquery. Then keep the most recent finished game for each user and game count.
	 */
	$gameCounts = db()->query("
		SELECT
		userID,
		MAX(activeGamesHistory.timestamp) AS timestamp,
		gameCount
		
		FROM (
			SELECT player1.userID AS userID,
			game1.endTime AS timestamp,
			COUNT(game2.gameID) AS gameCount
			
			FROM gamePlayers AS player1
			INNER JOIN ladderGames AS game1 ON game1.gameID = player1.gameID
			INNER JOIN gamePlayers AS player2 ON player2.userID = player1.userID
			INNER JOIN ladderGames AS game2 ON game2.gameID = player2.gameID
			
			WHERE game1.ladderID = '$ladderIDSql'
			AND game2.ladderID = '$ladderIDSql'
			AND game1.status = 'FINISHED'
			AND game2.startTime <= game1.endTime
			AND (game2.status = 'RUNNING' OR game2.endTime >= game1.endTime)
			
			GROUP BY player1.userID, game1.gameID
		) AS activeGamesHistory
		
		GROUP BY userID, gameCount
		
		ORDER BY userID ASC, gameCount DESC
	")->fetchList();
	$playerGameCounts = array();
	foreach ($gameCounts as $gameCount) {
		if (!isset($playerGameCounts[$gameCount["userID"]])) {
			$playerGameCounts[$gameCount["userID"]] = array();
		}
		$playerGameCounts[$gameCount["userID"]][$gameCount["gameCount"]] = $gameCount["timestamp"];
	}
	foreach ($players as $userID=>$player) {
		if (!isset($playerGameCounts[$userID])) {
			$playerGameCounts[$userID] = array();
		}
		if (!isset($playerGameCounts[$userID][$player["simultaneousGames"]])) {
			$timestamp = $player["joinTime"];
		} else {
			$timestamp = $playerGameCounts[$userID][$player["simultaneousGames"]];
		}
		$players[$userID]["relativeSingleMissedGameSeconds"] = array();
		for ($i = $player["simultaneousGames"]; $i >= $player["activeGames"]; $i--) {
			if (isset($playerGameCounts[$userID][$i]) && $playerGameCounts[$userID][$i] > $timestamp) {
				$timestamp = $playerGameCounts[$userID][$i];
			}
			$players[$userID]["relativeSingleMissedGameSeconds"][$i] = ($time - $timestamp) / ((float)$player["simultaneousGames"]);
		}
		for ($i = $player["activeGames"] - 1; $i >= 0; $i--) {
			$players[$userID]["relativeSingleMissedGameSeconds"][$i] = 0.0;
		}
		$players[$userID]["relativeMissedGameSeconds"] = array_sum($players[$userID]["relativeSingleMissedGameSeconds"]);
	}
	
	/*
	 * Compute the match quality between all pairs of players.
	 */
	$qualityMatrix = tsMatchMatrix(db()->stdList("ladderPlayers", array("ladderID"=>$ladderID, "joinStatus"=>"JOINED", "active"=>1), array("userID", "mu", "sigma")));
	foreach ($qualityMatrix as $userID => $qualities) {
		if (!isset($players[$userID])) {
			continue;
		}
		$players[$userID]["poolSize"] = array_sum($qualities);
	}
	
	/*
	 * Compute, between each pair of players, the number of games played by player 1 since their last game together.
	 */
	$interveningGamesMatrix = array();
	/*
	 * As a default case, two players who have never played a game together have an infinite number of games played since that point,
	 * denoted as true.
	 */
	foreach ($players as $userID1 => $player) {
		$interveningGamesMatrix[$userID1] = array();
		foreach ($players as $userID2 => $player) {
			if ($userID1 == $userID2) {
				continue;
			}
			$interveningGamesMatrix[$userID1][$userID2] = true;
		}
	}
	/*
	 * Collect all pairs of players that have played and finished a game together, and set their games-since to zero.
	 * The next query will catch all cases where they have played a nonzero number of games since that point.
	 */
	$playedPlayerPairs = db()->query("
		SELECT DISTINCT
		gamePlayer1.userID AS userID1,
		gamePlayer2.userID AS userID2
		
		FROM ladderGames
		INNER JOIN gamePlayers AS gamePlayer1 USING (gameID)
		INNER JOIN gamePlayers AS gamePlayer2 USING (gameID)
		
		WHERE ladderGames.ladderID = '$ladderIDSql'
		AND gamePlayer1.userID <> gamePlayer2.userID
		AND ladderGames.status = 'FINISHED'
	")->fetchList();
	foreach ($playedPlayerPairs as $pair) {
		if (isset($players[$pair["userID1"]]) &&
		    isset($players[$pair["userID2"]]))
		{
			$interveningGamesMatrix[$pair["userID1"]][$pair["userID2"]] = 0;
		}
	}
	/*
	 * Two players can have played a game together and have played other games since then.
	 */
	$interveningGames = db()->query("
		SELECT
		lastGames.userID1 AS userID1,
		lastGames.userID2 AS userID2,
		COUNT(ladderGames.gameID) AS gamesPlayedSince
		
		FROM (
			SELECT
			gamePlayer1.userID AS userID1,
			gamePlayer2.userID AS userID2,
			MAX(IFNULL(ladderGames.endTime, $time)) AS lastGameTime
			
			FROM ladderGames
			INNER JOIN gamePlayers AS gamePlayer1 USING (gameID)
			INNER JOIN gamePlayers AS gamePlayer2 USING (gameID)
			
			WHERE ladderGames.ladderID = '$ladderIDSql'
			AND gamePlayer1.userID <> gamePlayer2.userID
			
			GROUP BY userID1, userID2
		) AS lastGames
		LEFT JOIN gamePlayers ON lastGames.userID1 = gamePlayers.userID
		LEFT JOIN ladderGames USING (gameID)
		
		WHERE ladderGames.startTime >= lastGames.lastGameTime
		
		GROUP BY userID1, userID2
	")->fetchList();
	foreach ($interveningGames as $games) {
		if (isset($players[$games["userID1"]]) &&
		    isset($players[$games["userID2"]]))
		{
			$interveningGamesMatrix[$games["userID1"]][$games["userID2"]] = $games["gamesPlayedSince"];
		}
	}
	/*
	 * Finally, if two players are currently in a game together, their intervening games count is minus infinity, denoted as false.
	 */
	$playingPlayerPairs = db()->query("
		SELECT DISTINCT
		gamePlayer1.userID AS userID1,
		gamePlayer2.userID AS userID2
		
		FROM ladderGames
		INNER JOIN gamePlayers AS gamePlayer1 USING (gameID)
		INNER JOIN gamePlayers AS gamePlayer2 USING (gameID)
		
		WHERE ladderGames.ladderID = '$ladderIDSql'
		AND gamePlayer1.userID <> gamePlayer2.userID
		AND ladderGames.status = 'RUNNING'
	")->fetchList();
	foreach ($playingPlayerPairs as $pair) {
		if (isset($players[$pair["userID1"]]) &&
		    isset($players[$pair["userID2"]]))
		{
			$interveningGamesMatrix[$pair["userID1"]][$pair["userID2"]] = false;
		}
	}
	
	/*
	 * This function computes how much player 1 would like to play with player 2.
	 * If this returns >0, then he does want to play, otherwise he does not.
	 */
	$preference = function($userID1, $userID2) use (&$players, &$qualityMatrix, &$interveningGamesMatrix, $averageGameLength) {
		$interveningGames = $interveningGamesMatrix[$userID1][$userID2];
		$poolSizeSqrt = sqrt($players[$userID1]["poolSize"]);
		$quality = $qualityMatrix[$userID1][$userID2];
		
		if ($interveningGames === false) {
			return false;
		}
		
		// The memory score is a score multiplier that drops if the players have recently played a game together.
		if ($interveningGames === true) {
			$memoryScore = 1.0;
		} else if ($poolSizeSqrt < 0.001) {
			$memoryScore = 1.0;
		} else if ($interveningGames > $poolSizeSqrt) {
			$memoryScore = 1.0;
		} else {
			$memoryScore = ((float)$interveningGames) / $poolSizeSqrt;
		}
		
		$relativeMissedGames = $players[$userID1]["relativeMissedGameSeconds"] / $averageGameLength;
		
		return ($quality * $memoryScore) - (ALWAYS_ACCEPTABLE_MATCH_QUALITY - (ACCEPTABLE_MATCH_QUALITY_SLACK_PER_MISSING_GAME * $relativeMissedGames));
	};
	
	/*
	 * Sort the players by descending relative missed game seconds.
	 */
	uasort($players, function($a, $b) {
		return $b["relativeMissedGameSeconds"] - $a["relativeMissedGameSeconds"];
	});
	
	/*
	 * The players are now sorted by urgency of getting a new game.
	 * As long as this queue is not empty, try to create a new game for the first player in the queue.
	 */
	while (count($players) > 0) {
		reset($players);
		list($userID, $player) = each($players);
		
		/*
		 * Find all players willing to start a match with $player.
		 */
		$candidates = array();
		foreach ($players as $userID2 => $player2) {
			if ($userID == $userID2) {
				continue;
			}
			
			$userPreference1 = $preference($userID, $userID2);
			if ($userPreference1 === false || $userPreference1 < 0) {
				continue;
			}
			
			$userPreference2 = $preference($userID2, $userID);
			if ($userPreference2 === false || $userPreference2 < 0) {
				continue;
			}
			
			$candidates[$userID2] = $userPreference1 + $userPreference2;
		}
		
		/*
		 * If there are no candidates, $player cannot start a game now. Remove him from the list.
		 */
		if (count($candidates) == 0) {
			unset($players[$userID]);
			continue;
		}
		
		/*
		 * Create a game with the best candidate.
		 */
		arsort($candidates);
		reset($candidates);
		list($userID2, $score) = each($candidates);
		
		
		
		/*
		 * Create a game.
		 */
		$gamePlayers = array($userID, $userID2);
		shuffle($gamePlayers);
		$success = createGame($ladderID, $gamePlayers[0], $gamePlayers[1]);
		if (!$success) {
			// Blacklist this combination.
			$interveningGamesMatrix[$userID][$userID2] = false;
			$interveningGamesMatrix[$userID2][$userID] = false;
			continue;
		}
		
		
		
		/*
		 * Update the player preference data.
		 */
		$interveningGamesMatrix[$userID][$userID2] = false;
		$interveningGamesMatrix[$userID2][$userID] = false;
		
		$players[$userID]["relativeSingleMissedGameSeconds"][$players[$userID]["activeGames"]] = 0;
		$players[$userID]["relativeMissedGameSeconds"] = array_sum($players[$userID]["relativeSingleMissedGameSeconds"]);
		$players[$userID2]["relativeSingleMissedGameSeconds"][$players[$userID2]["activeGames"]] = 0;
		$players[$userID2]["relativeMissedGameSeconds"] = array_sum($players[$userID2]["relativeSingleMissedGameSeconds"]);
		
		$players[$userID]["activeGames"]++;
		if ($players[$userID]["activeGames"] >= $players[$userID]["simultaneousGames"]) {
			unset($players[$userID]);
		}
		$players[$userID2]["activeGames"]++;
		if ($players[$userID2]["activeGames"] >= $players[$userID2]["simultaneousGames"]) {
			unset($players[$userID2]);
		}
		
		uasort($players, function($a, $b) {
			return $b["relativeMissedGameSeconds"] - $a["relativeMissedGameSeconds"];
		});
	}
}

db()->startTransaction();
db()->stdLock("ladderPlayers", null);
db()->stdLock("ladderGames", null);

$ladders = db()->stdList("ladders", array("active"=>1), "ladderID");
foreach($ladders as $ladderID) {
	finishGames($ladderID);
	createGames($ladderID);
}

db()->commitTransaction();
