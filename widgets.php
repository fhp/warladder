<?php

function renderLongtable($title, $emptyMessage, $class, $header, $query, $render, $url, $pageSize, $page, $from, $count)
{
	if ($title !== null) {
		$titleHtml = htmlentities($title);
	}
	
	if ($page !== null) {
		$from = ($page - 1) * $pageSize;
		$count = $pageSize;
	}
	
	$limitQuery = "$query LIMIT $from, $count";
	$items = db()->query($limitQuery)->fetchList();
	
	$itemCount = db()->query($query)->numRows();
	
	$output = "";
	$output .= "<div class=\"panel panel-default $class\">\n";
	if ($title !== null) {
		$output .= "<div class=\"panel-heading\"><h3>$titleHtml</h3></div>\n";
	}
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
				$amp = "&amp;";
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

function renderEditTable($title, $emptyMessage, $class, $header, $query, $render, $formAction, $addRow)
{
	$titleHtml = htmlentities($title);
	
	$items = db()->query($query)->fetchList();
	$itemCount = count($items);
	
	$output = "";
	$output .= "<form action=\"$formAction\" method=\"post\">\n";
	$output .= "<div class=\"panel panel-default $class\">\n";
//	$output .= "<div class=\"panel-heading\">$title</div>\n";
	$output .= "<table class=\"table table-condensed\">\n";
	$output .= "<thead><tr>";
	$first = true;
	foreach($header as $head) {
		$output .= "<th>$head";
		if($first) {
			$output .= "<input type=\"submit\" style=\"visibility: hidden; height: 0px; width: 0px\" />";
			$first = false;
		}
		$output .= "</th>";
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
	$output .= "$addRow\n";
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	$output .= "</form>\n";
	return $output;
}

function renderButton($target, $text, $fields)
{
	$output = "";
	$output .= "<form action=\"$target\" method=\"post\">\n";
	foreach($fields as $name=>$value) {
		$nameHtml = htmlentities($name);
		$valueHtml = htmlentities($value);
		$output .= "<input type=\"hidden\" name=\"$nameHtml\" value=\"$valueHtml\" />\n";
	}
	//$output .= "<button type=\"submit\" class=\"btn btn-link\">$text</button>\n";
	$output .= "<input type=\"submit\" class=\"btn btn-default\" value=\"$text\" />\n";
	$output .= "</form>\n";
	return $output;
}

function renderRanking($ladderID, $title, $emptyMessage, $highlightUserID, $from, $count, $page = null)
{
	$ladderIDSql = db()->addSlashes($ladderID);
	$query = "SELECT rating, rank, userID, name "
		. "FROM ladderPlayers INNER JOIN users USING(userID) "
		. "WHERE ladderPlayers.ladderID = '$ladderIDSql' "
		. "AND ladderPlayers.joinStatus = 'JOINED' "
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
		return "<tr$class><td>$rankHtml</td><td><a href=\"player.php?ladder=$ladderID&amp;player=$userIDHtml\">$nameHtml</a></td><td>$ratingHtml</td></tr>\n";
	};
	
	$urlBase = "ranking.php?ladder=$ladderID";
	if ($highlightUserID !== null) {
		$urlBase .= "&player=$highlightUserID";
	}
	
	return renderLongtable($title, $emptyMessage, "ranking", array("Rank", "Name", "Rating"), $query, $render, $urlBase, 50, $page, $from, $count);
}

function renderOpenLadders($title, $from, $count, $page = null)
{
	$query = "SELECT ladderID, name, summary, COUNT(userID) AS players "
		. "FROM ladders "
		. "INNER JOIN ladderPlayers USING (ladderID) "
		. "WHERE visibility = 'PUBLIC' AND ladders.active = '1' "
		. "AND joinStatus = 'JOINED' AND ladderPlayers.active = '1' "
		. "GROUP BY ladderID, name, summary "
		. "ORDER BY ladderID DESC ";
	
	$render = function($ladder) {
		$nameHtml = htmlentities($ladder["name"]);
		$summaryHtml = htmlentities($ladder["summary"]);
		return "<tr><td><a href=\"ladder.php?ladder={$ladder["ladderID"]}\">$nameHtml</a></td><td>$summaryHtml</td><td><a href=\"ranking.php?ladder={$ladder["ladderID"]}\">{$ladder["players"]}</a></td></tr>\n";
	};
	
	return renderLongtable($title, "There are currently no open ladders available.", "ladders open-ladders", array("Name", "Description", "Players"), $query, $render, "ladders.php", 50, $page, $from, $count);
}

function renderMyLadders($userID, $title, $from, $count, $page = null)
{
	$userIDSql = db()->addSlashes($userID);
	$query = "SELECT ladderID, name, summary, rank "
		. "FROM ladders INNER JOIN ladderPlayers USING(ladderID) "
		. "WHERE ladderPlayers.userID = '$userIDSql' AND ladderPlayers.active = 1 AND ladders.active = 1 "
		. "ORDER BY ladderID DESC ";
	
	$render = function($ladder) use($userID) {
		$nameHtml = htmlentities($ladder["name"]);
		$summaryHtml = htmlentities($ladder["summary"]);
		$rankingUrlHtml = htmlentities("ranking.php?ladder={$ladder["ladderID"]}&player=$userID");
		return "<tr><td><a href=\"ladder.php?ladder={$ladder["ladderID"]}\">$nameHtml</a></td><td>$summaryHtml</td><td><a href=\"$rankingUrlHtml\">{$ladder["rank"]}</a></td></tr>\n";
	};
	
	return renderLongtable($title, "You are not currently playing on any ladders.", "ladders my-ladders", array("Name", "Description", "Rank"), $query, $render, "myladders.php", 50, $page, $from, $count);
}

function renderAcceptList($ladderID, $title, $from, $count, $page = null)
{
	$ladderIDSql = db()->addSlashes($ladderID);
	$query = "SELECT userID, name "
		. "FROM ladderPlayers LEFT JOIN users USING (userID) "
		. "WHERE ladderID = '$ladderIDSql' AND joinStatus = 'SIGNEDUP' "
		. "ORDER BY joinTime DESC ";
	
	$render = function($user) use($ladderID) {
		$nameHtml = htmlentities($user["name"]);
		$acceptUrlHtml = htmlentities("acceptplayer.php?ladder={$ladderID}&player={$user["userID"]}&accept=1");
		$rejectUrlHtml = htmlentities("acceptplayer.php?ladder={$ladderID}&player={$user["userID"]}&accept=0");
		return "<tr><td><a href=\"player.php?ladder={$ladderID}&amp;player={$user["userID"]}\">$nameHtml</a></td><td><a href=\"$acceptUrlHtml\">Accept player</a></td><td><a href=\"$rejectUrlHtml\">Reject player</a></td></tr>\n";
	};
	
	return renderLongtable($title, "There are no players waiting to join this ladder.", "acceptlist", array("Name", "Accept", "Reject"), $query, $render, "acceptlist.php?ladder=$ladderID", 50, $page, $from, $count);
}

function renderLadderTemplates($ladderID, $formAction, $nameField, $templateIDField, $action)
{
	$ladderIDSql = db()->addSlashes($ladderID);
	$query = "SELECT templateID, warlightTemplateID, name FROM ladderTemplates WHERE ladderID='$ladderIDSql' ORDER BY name ASC";
	
	$render = function($template) use($ladderID) {
		$nameHtml = htmlentities($template["name"]);
		$deleteHtml = "<button type=\"submit\" name=\"remove-template\" value=\"{$template["templateID"]}\" class=\"btn btn-default form-control\">Remove Template</button>";
		return "<tr><td>$nameHtml</td><td><a href=\"http://warlight.net/MultiPlayer?TemplateID={$template["warlightTemplateID"]}\">{$template["warlightTemplateID"]}</a></td><td>$deleteHtml</td></tr>\n";
	};
	
	return renderEditTable("Templates", "No templates are configured for this ladder yet.", "laddertemplatelist", array("Name", "ID", ""), $query, $render, $formAction,
		"<tr><td><input type=\"text\" name=\"$nameField\" class=\"stretch\" /></td><td><input type=\"text\" name=\"$templateIDField\" class=\"stretch\" /></td><td><input type=\"hidden\" name=\"action\" value=\"$action\" /><input type=\"submit\" value=\"Add Template\" class=\"btn btn-default form-control\" /></td></tr>");
}

function renderLadderMods($ladderID, $formAction, $userIDField, $action)
{
	$ladderIDSql = db()->addSlashes($ladderID);
	$query = "SELECT userID, name FROM ladderAdmins INNER JOIN users USING (userID) WHERE ladderID='$ladderIDSql' ORDER BY name ASC";
	
	$render = function($admin) use($ladderID) {
		$nameHtml = htmlentities($admin["name"]);
		$deleteHtml = "<button type=\"submit\" name=\"remove-admin\" value=\"{$admin["userID"]}\" class=\"btn btn-default form-control\">Remove Moderator</button>";
		return "<tr><td><a href=\"player.php?ladder={$ladderID}&amp;player={$admin["userID"]}\">$nameHtml</a></td><td>$deleteHtml</td></tr>\n";
	};
	
	$users = db()->query("SELECT userID, name FROM ladderPlayers INNER JOIN users USING (userID) WHERE ladderID='$ladderIDSql' AND ladderPlayers.joinStatus = 'JOINED' ORDER BY name ASC")->fetchList();
	$options = "";
	foreach($users as $user) {
		$nameHtml = htmlentities($user["name"]);
		$options .= "<option value=\"{$user["userID"]}\">$nameHtml</option>\n";
	}
	
	return renderEditTable("Ladder moderators", "This ladder doesn't have any moderators.", "laddermodlist", array("Name", ""), $query, $render, $formAction,
		"<tr><td><select name=\"$userIDField\" class=\"form-control stretch\">$options</select></td><td><input type=\"hidden\" name=\"action\" value=\"$action\" /><input type=\"submit\" value=\"Add Moderator\" class=\"btn btn-default form-control\" /></td></tr>");
}

function renderGames($userID, $ladderID, $title, $emptyMessage, $from, $count, $page = null)
{
	$header = array();
	$header[] = "Name";
	
	$userIDSql = db()->addSlashes($userID);
	$ladderIDSql = db()->addSlashes($ladderID);
	$select = "
		SELECT ladderGames.gameID,
			ladderGames.warlightGameID,
			ladderGames.name AS gameName,
			ladderGames.htmlName as htmlName,
			ladderGames.status,
			ladderGames.winningUserID,
			ladders.ladderID as ladderID,
			ladders.name AS ladderName,
			winningUser.name AS winningUserName
		";
	$fromSql = "
		FROM ladderGames
			INNER JOIN ladders USING(ladderID)
			LEFT JOIN users AS winningUser ON winningUser.userID = ladderGames.winningUserID
		";
	$where = "
		WHERE status = 'FINISHED'
		";
	if ($ladderID !== null) {
		$where .= "AND ladderGames.ladderID = '$ladderIDSql' ";
	} else {
		$header[] = "Ladder";
	}
	
	if ($userID !== null) {
		$select .= "
			, opponent.userID AS opponentUserID
			, opponent.name AS opponentName
			";
		$fromSql .= "
			INNER JOIN gamePlayers AS playerYou USING(gameID)
			INNER JOIN gamePlayers AS playerOpponent USING(gameID)
			INNER JOIN users AS opponent ON playerOpponent.userID = opponent.userID
			";
		$where .= "
			AND playerYou.userID = '$userIDSql'
			AND opponent.userID <> '$userIDSql'
			";
		$header[] = "Opponent";
	}
	$query = $select . $fromSql . $where . " ORDER BY endTime DESC";
	
	$header[] = "Winner";
	
	$render = function($game) use($ladderID, $userID) {
		$gameNameHtml = htmlentities($game["gameName"]);
		$ladderNameHtml = htmlentities($game["ladderName"]);
		
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
		
		$output = "<tr class=\"$class\">";
		$output .= "<td>{$game["htmlName"]}</td>";
		if ($ladderID === null) {
			$output .= "<td><a href=\"ladder.php?ladder={$game["ladderID"]}\">$ladderNameHtml</a></td>";
		}
		if ($userID !== null) {
			$opponentNameHtml = htmlentities($game["opponentName"]);
			$output .= "<td><a href=\"player.php?ladder={$game["ladderID"]}&amp;player={$game["opponentUserID"]}\">$opponentNameHtml</a></td>";
		}
		if ($game["winningUserName"] === null) {
			$output .= "<td>-</td>";
		} else {
			$winnerNameHtml = htmlentities($game["winningUserName"]);
			$output .= "<td><a href=\"player.php?ladder={$game["ladderID"]}&amp;player={$game["winningUserID"]}\">$winnerNameHtml</a></td>";
		}
		$output .= "</tr>\n";
		return $output;
	};
	
	return renderLongtable($title, $emptyMessage, "games my-games", $header, $query, $render, "games.php?" . ($userID !== null ? "player=$userID" : "") . ($ladderID !== null ? ($userID !== null ? "&" : "") . "ladder=$ladderID" : ""), 50, $page, $from, $count);
}
