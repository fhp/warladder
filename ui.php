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
	<title>{$titleHtml}Warladder</title>
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

function page($pageHtml, $activeItem, $title = null, $subtitle = null, $headerMessage = null, $titleLink = null)
{
	$jumbotron = "";
	if ($title !== null) {
		$jumbotron .= "<div class=\"jumbotron\">\n";
		$jumbotron .= "<div class=\"container\">\n";
		
		$jumbotron .= "<h1>";
		if ($titleLink !== null) {
			$titleLinkHtml = htmlentities($titleLink);
			$jumbotron .= "<a href=\"$titleLinkHtml\">";
		}
		$jumbotron .= $title;
		if ($titleLink !== null) {
			$jumbotron .= "</a>";
		}
		
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
	
	return rawpage($jumbotron . '<div class="container">' . $pageHtml . '</div>', $activeItem, $title);
}

function menu($activeItem)
{
	$menuLeft = array(
		array("name"=>"home", "label"=>"Home", "url"=>"index.php"),
		array("name"=>"ladders", "label"=>"Ladders", "url"=>"ladders.php"),
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
			<a class="navbar-brand" href="index.php">Warladder</a>
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
	return "<div class=\"alert alert-warning\" role=\"alert\">$error</div>\n";
}
