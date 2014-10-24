<?php

/*
class ListQuery
{
	public function __construct($table, $where, $get, $sort = null)
	{
		$this->table = $table;
		$this->where = $where;
		$this->get = $get;
		$this->sort = $sort;
	}
	
	public function run($number = 0, $skip = 0)
	{
		$sql = "";
		$sql .= db()->buildSelect($this->get);
		$sql .= db()->buildFrom($this->table);
		$sql .= db()->buildWhere($this->where);
		if ($this->sort === null) {
		} else if (is_array($this->sort)) {
			$sql .= db()->buildSort($this->sort);
		} else {
			$sql .= $this->sort;
		}
		$sql .= db()->buildLimit($number, $skip);
		
		$qresult = $this->query($sql);
		$result = $qresult->fetchList((is_array($this->get) || $this->get == "*") ? null : $this->get);
		$qresult->free();
		return $result;
	}
}
*/

function renderLongtable($title, $class, $header, $query, $render, $url, $pageSize, $page, $from, $count)
{
	$titleHtml = htmlentities($title);
	
	if ($page !== null) {
		$from = ($page - 1) * $pageSize;
		$count = $pageSize;
	}
	
	$limitQuery = "$query LIMIT $from, $count";
	$items = db()->query($limitQuery)->fetchList();
	
	$output = "";
	$output .= "<div class=\"panel panel-default $class\">\n";
	$output .= "<div class=\"panel-heading\">$titleHtml</div>\n";
	$output .= "<table class=\"table\">\n";
	$output .= "<thead><tr>";
	foreach($header as $head) {
		$output .= "<th>$head</th>";
	}
	$output .= "</tr></thead>\n";
	$output .= "<tbody>\n";
	foreach($items as $item) {
		$output .= $render($item) . "\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	if ($page === null) {
		$urlHtml = htmlentities($url);
		$output .= "<div class=\"show-all\"><a href=\"$urlHtml\" class=\"btn btn-default\">Show All</a></div>\n";
	} else {
		$count = db()->query($query)->numRows();
		$pages = (int)(($count + $pageSize - 1) / $pageSize);
		$page = (int)($from / $pageSize) + 1;
		
		if ($pages > 1) {
			if (strpos($url, "?")) {
				$amp = "&";
			} else {
				$amp = "?";
			}
			
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
		}
	}
	$output .= "</div>\n";
	return $output;
}

function renderRanking($ladderID, $title, $highlightPlayerID, $from, $count, $page = null)
{
	$ladderIDSql = db()->addSlashes($ladderID);
	$query = "SELECT rating, rank, userID, name FROM ladderPlayers INNER JOIN users USING(userID) WHERE ladderPlayers.ladderID = '$ladderIDSql' ORDER BY rank ASC, userID ASC";
	
	$render = function($player) use($ladderID, $highlightPlayerID) {
		$rankHtml = htmlentities($player["rank"]);
		$ratingHtml = htmlentities($player["rating"]);
		$nameHtml = htmlentities($player["name"]);
		$userIDHtml = htmlentities($player["userID"]);
		$class = "";
		if ($highlightPlayerID !== null && $player["userID"] == $highlightPlayerID) {
			$class = " class=\"highlight\"";
		}
		return "<tr$class><td>$rankHtml</td><td><a href=\"player.php?ladder=$ladderID&player=$userIDHtml\">$nameHtml</a></td><td>$ratingHtml</td></tr>\n";
	};
	
	return renderLongtable($title, "ranking", array("Rank", "Name", "Rating"), $query, $render, "ranking.php?ladder=$ladderID", 50, $page, $from, $count);
}
