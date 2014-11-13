<?php

function renderLongtable($title, $emptyMessage, $class, $header, $query, $render, $url, $pageSize, $page, $from, $count)
{
	$titleHtml = htmlentities($title);
	
	if ($page !== null) {
		$from = ($page - 1) * $pageSize;
		$count = $pageSize;
	}
	
	$limitQuery = "$query LIMIT $from, $count";
	$items = db()->query($limitQuery)->fetchList();
	
	$itemCount = db()->query($query)->numRows();
	
	$output = "";
	$output .= "<div class=\"panel panel-default $class\">\n";
	$output .= "<div class=\"panel-heading\">$titleHtml</div>\n";
	$output .= "<table class=\"table table-condensed\">\n";
	$output .= "<thead><tr>";
	foreach($header as $head) {
		$output .= "<th>$head</th>";
	}
	$output .= "</tr></thead>\n";
	$output .= "<tbody>\n";
	if ($itemCount == 0) {
		$colspan = count($header);
		$output .= "<tr><td class=\"empty-message\" colspan=\"$colspan\">$emptyMessage</td></tr>\n";
	} else {
		foreach($items as $item) {
			$output .= $render($item) . "\n";
		}
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	if ($page === null) {
		$urlHtml = htmlentities($url);
		$output .= "<div class=\"panel-footer\">\n";
		$output .= "<div class=\"show-all\"><a href=\"$urlHtml\" class=\"btn btn-default\">Show All</a></div>\n";
		$output .= "</div>\n";
	} else {
		$pages = (int)(($itemCount + $pageSize - 1) / $pageSize);
		$page = (int)($from / $pageSize) + 1;
		
		if ($pages > 1) {
			if (strpos($url, "?")) {
				$amp = "&";
			} else {
				$amp = "?";
			}
			$output .= "<div class=\"text-center\">\n";
			$output .= "<ul class=\"pagination\">\n";
			if ($page == 1) {
				$output .= "<li class=\"disabled\"><a href=\"$url{$amp}page=1\">&laquo;</a></li>\n";
			} else {
				$back = $page - 1;
				$output .= "<li><a href=\"$url{$amp}page=$back\">&laquo;</a></li>\n";
			}
			
			if ($page >= 5) {
				$output .= "<li><a href=\"$url{$amp}page=1\">1</a></li>\n";
				$output .= "<li class=\"disabled\"><span>...</span></li>\n";
			}
			
			for ($i = max($page - 2, 1); $i <= min($page + 2, $pages); $i++) {
				if ($i == $page) {
					$output .= "<li class=\"active\"><a href=\"$url{$amp}page=$i\">$i</a></li>\n";
				} else {
					$output .= "<li><a href=\"$url{$amp}page=$i\">$i</a></li>\n";
				}
			}
			
			if ($page <= $pages - 4) {
				$output .= "<li class=\"disabled\"><span>...</span></li>\n";
				$output .= "<li><a href=\"$url{$amp}page=$pages\">$pages</a></li>\n";
			}
			
			if ($page == $pages) {
				$output .= "<li class=\"disabled\"><a href=\"$url{$amp}page=$pages\">&raquo;</a></li>\n";
			} else {
				$next = $page + 1;
				$output .= "<li><a href=\"$url{$amp}page=$next\">&raquo;</a></li>\n";
			}
			
			$output .= "</ul>\n";
			$output .= "</div>\n";
		}
	}
	$output .= "</div>\n";
	return $output;
}

function renderRanking($ladderID, $title, $emptyMessage, $highlightUserID, $from, $count, $page = null)
{
	$ladderIDSql = db()->addSlashes($ladderID);
	$query = "SELECT rating, rank, userID, name "
		. "FROM ladderPlayers INNER JOIN users USING(userID) "
		. "WHERE ladderPlayers.ladderID = '$ladderIDSql' "
		. "ORDER BY rank ASC, userID ASC ";
	
	$render = function($player) use($ladderID, $highlightUserID) {
		$rankHtml = htmlentities($player["rank"]);
		$ratingHtml = htmlentities($player["rating"]);
		$nameHtml = htmlentities($player["name"]);
		$userIDHtml = htmlentities($player["userID"]);
		$class = "";
		if ($highlightUserID !== null && $player["userID"] == $highlightUserID) {
			$class = " class=\"active\"";
		}
		return "<tr$class><td>$rankHtml</td><td><a href=\"player.php?ladder=$ladderID&player=$userIDHtml\">$nameHtml</a></td><td>$ratingHtml</td></tr>\n";
	};
	
	$urlBase = "ranking.php?ladder=$ladderID";
	if ($highlightUserID !== null) {
		$urlBase .= "&player=$highlightUserID";
	}
	
	return renderLongtable($title, $emptyMessage, "ranking", array("Rank", "Name", "Rating"), $query, $render, $urlBase, 50, $page, $from, $count);
}

function renderOpenLadders($title, $from, $count, $page = null)
{
	$query = "SELECT ladderID, name, description "
		. "FROM ladders "
		. "WHERE accessibility = 'PUBLIC' AND (visibility = 'PUBLIC' OR visibility = 'MODERATED') AND active = '1' "
		. "ORDER BY ladderID DESC ";
	
	$render = function($ladder) {
		$nameHtml = htmlentities($ladder["name"]);
		$descriptionHtml = htmlentities($ladder["description"]);
		return "<tr><td><a href=\"ladder.php?ladder={$ladder["ladderID"]}\">$nameHtml</a></td><td>$descriptionHtml</td></tr>\n";
	};
	
	return renderLongtable($title, "There are currently no open ladders available.", "ladders open-ladders", array("Name", "Description"), $query, $render, "ladders.php", 50, $page, $from, $count);
}

function renderMyLadders($userID, $title, $from, $count, $page = null)
{
	$userIDSql = db()->addSlashes($userID);
	$query = "SELECT ladderID, name, description, rank "
		. "FROM ladders INNER JOIN ladderPlayers USING(ladderID) "
		. "WHERE ladderPlayers.userID = '$userIDSql' AND ladderPlayers.active = 1 AND ladders.active = 1 "
		. "ORDER BY ladderID DESC ";
	
	$render = function($ladder) use($userID) {
		$nameHtml = htmlentities($ladder["name"]);
		$descriptionHtml = htmlentities($ladder["description"]);
		$rankingUrlHtml = htmlentities("ranking.php?ladder={$ladder["ladderID"]}&player=$userID");
		return "<tr><td><a href=\"ladder.php?ladder={$ladder["ladderID"]}\">$nameHtml</a></td><td>$descriptionHtml</td><td><a href=\"$rankingUrlHtml\">{$ladder["rank"]}</a></td></tr>\n";
	};
	
	return renderLongtable($title, "You are not currently playing on any ladders.", "ladders my-ladders", array("Name", "Description", "Rank"), $query, $render, "myladders.php", 50, $page, $from, $count);
}

function renderGames($userID, $ladderID, $title, $from, $count, $page = null)
{
	$userIDSql = db()->addSlashes($userID);
	$ladderIDSql = db()->addSlashes($ladderID);
	$select = "
		SELECT ladderGames.gameID,
			ladderGames.warlightGameID,
			ladderGames.name AS gameName,
			ladderGames.status,
			ladderGames.winningUserID,
			ladders.ladderID as ladderID,
			ladders.name AS ladderName,
			winningUser.name AS winningUserName
		FROM ladderGames
			INNER JOIN ladders USING(ladderID)
			LEFT JOIN users AS winningUser ON winningUser.userID = ladderGames.winningUserID
		";
	$where = "WHERE status = 'FINISHED' ";
	if ($userID !== null) {
		$select .= "INNER JOIN gamePlayers USING(gameID) ";
		$where .= "AND gamePlayers.userID = '$userIDSql' ";
	}
	if ($ladderID !== null) {
		$where .= "AND ladderGames.ladderID = '$ladderIDSql' ";
	}
	$query = $select . $where . " ORDER BY endTime DESC";
	
	$render = function($game) use($userID) {
		$gameNameHtml = htmlentities($game["gameName"]);
		$ladderNameHtml = htmlentities($game["ladderName"]);
		if(isset($game["winningUserName"])) {
			$winningUserName = htmlentities($game["winningUserName"]);
		} else {
			$winningUserName = "-";
		}
		if ($userID === null) {
			$class = "game-neutral";
		} else if ($game["winningUserID"] === null) {
			$class = "game-draw";
		} else if($game["winningUserID"] == $userID) {
			$class = "game-won";
		} else {
			$class = "game-lost";
		}
		$class .= " game-finished";
		// TODO: Reconstruct game name, add player links
		return "<tr class=\"$class\">"
			. "<td><a href=\"http://WarLight.net/MultiPlayer?GameID={$game["warlightGameID"]}\">$gameNameHtml</a></td>"
			. "<td><a href=\"ladder.php?ladder={$game["ladderID"]}\">$ladderNameHtml</a></td>"
			. "<td>" . (isset($game["winningUserName"]) ? "<a href=\"player.php?ladder={$game["ladderID"]}&player={$game["winningUserID"]}\">" : "") . $winningUserName . (isset($game["winningUserName"]) ? "</a>" : "") . "</td>"
			. "</tr>\n";
	};
	
	return renderLongtable($title, "", "games my-games", array("Name", "Ladder", "Winner"), $query, $render, "games.php?" . ($userID !== null ? "player=$userID" : "") . ($ladderID !== null ? ($userID !== null ? "&" : "") . "ladder=$ladderID" : ""), 50, $page, $from, $count);
}
