<?php

function rawPage($pageHtml, $activeItem, $title = null)
{
	$menu = menu($activeItem);
	
	$titleHtml = "";
	if ($title !== null) {
		$titleHtml = htmlentities($title) . " - ";
	}
	
	$header = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<title>{$titleHtml}Warladder.net</title>
	<link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="assets/css/warladder.css">
	<script src="assets/js/jquery-2.1.1.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/js/chat.js"></script>
</head>
<body>
	$menu
	
HTML;
	
	$footer = <<<HTML
</body>
</html>

HTML;
	
	echo $header . $pageHtml . $footer;
}

function page($pageHtml, $activeItem, $title = null, $subtitle = null, $headerMessage = null, $pageTitle = null)
{
	if ($pageTitle === null) {
		$pageTitle = $title;
	}
	
	$emailHeader = "";
	if (isLoggedIn()) {
		$emailData = db()->stdGet("users", array("userID"=>currentUserID()), array("email", "emailConfirmation"));
		if ($emailData["email"] !== null && $emailData["emailConfirmation"] !== null) {
			$emailHeader = "<div class=\"top-notification alert alert-warning\"><p>We will not send you notification emails until you confirm your email address. Please confirm your address, or <a href=\"mysettings.php?resendemailnotification=1\">resend the confirmation email</a>.</p></div>\n";
		}
	}
	
	$jumbotron = "";
	if ($title !== null) {
		$jumbotron .= "<div class=\"jumbotron\">\n";
		$jumbotron .= "<div class=\"container\">\n";
		
		$jumbotron .= "<h1>";
		$jumbotron .= $title;
		if ($subtitle !== null) {
			$jumbotron .= "<br><small>$subtitle</small>";
		}
		$jumbotron .= "</h1>\n";
		
		if ($headerMessage !== null) {
			$jumbotron .= "$headerMessage\n";
		}
		$jumbotron .= "</div>\n";
		$jumbotron .= "</div>\n";
	}
	
	return rawpage($emailHeader . $jumbotron . '<div class="container">' . $pageHtml . '</div>', $activeItem, $pageTitle);
}

function menu($activeItem)
{
	$menuLeft = array(
		array("name"=>"home", "label"=>"Home", "url"=>"index.php"),
		array("name"=>"ladders", "label"=>"Ladders", "url"=>"ladders.php"),
		array("name"=>"about", "label"=>"About", "url"=>"about.php"),
	);
	if(isLoggedIn()) {
		$menuRight = array(
			array("name"=>"myladders", "label"=>"My Ladders", "url"=>"myladders.php"),
			array("name"=>"mygames", "label"=>"My Games", "url"=>"games.php?player=" . currentUserID()),
			array("name"=>"mysettings", "label"=>"My Settings", "url"=>"mysettings.php"),
			array("name"=>"logout", "label"=>"Logout", "url"=>"logout.php"),
		);
	} else {
		$menuRight = array(
			array("name"=>"login", "label"=>"Login", "url"=>$GLOBALS["config"]["loginUrl"]),
		);
	}
	return renderMenu($menuLeft, $menuRight, $activeItem);
}

function renderMenu($menuLeft, $menuRight, $activeItem)
{
	$menuLeftHtml = renderMenuItems($menuLeft, $activeItem);
	$menuRightHtml = renderMenuItems($menuRight, $activeItem);
	
	$menu = <<<HTML
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
	<div class="container-fluid">
		<div class="navbar-header">
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#header-navbar-collapse-1">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="index.php">Warladder.net</a>
		</div>
	
		<div class="collapse navbar-collapse" id="header-navbar-collapse-1">
			<ul class="nav navbar-nav">
				{$menuLeftHtml}
			</ul>
			<ul class="nav navbar-nav navbar-right">
				{$menuRightHtml}
			</ul>
		</div>
	</div>
</nav>

HTML;
	return $menu;
}

function renderMenuItems($items, $activeItem)
{
	$html = "";
	foreach($items as $item) {
		$html .= "<li" . ($item["name"] == $activeItem ? " class=\"active\"" : "") . "><a href=\"{$item["url"]}\">{$item["label"]}</a></li>";
	}
	return $html;
}

function formError($error)
{
	return "<div class=\"alert alert-warning\">$error</div>\n";
}


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

function renderEditTable($emptyMessage, $class, $header, $query, $render, $formAction, $addRow)
{
	$items = db()->query($query)->fetchList();
	$itemCount = count($items);
	
	$output = "";
	if ($formAction !== null) {
		$output .= "<form action=\"$formAction\" method=\"post\">\n";
	}
	$output .= "<div class=\"panel panel-default $class\">\n";
	$output .= "<table class=\"table table-condensed\">\n";
	$output .= "<thead><tr>";
	$first = true;
	foreach($header as $head) {
		$output .= "<th>$head";
		if($first) {
			if ($formAction !== null) {
				$output .= "<input type=\"submit\" style=\"visibility: hidden; height: 0px; width: 0px\" />";
			}
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
	if ($addRow !== null) {
		$output .= "$addRow\n";
	}
	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$output .= "</div>\n";
	if ($formAction !== null) {
		$output .= "</form>\n";
	}
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

