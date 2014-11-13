<?php

function rawPage($pageHtml, $activeItem)
{
	$menu = menu($activeItem);
	
	$header = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Warladder</title>
	<link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="assets/css/warladder.css">
	<script src="assets/js/jquery-2.1.1.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>
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

function page($pageHtml, $activeItem)
{
	return rawpage('<div class="container">' . $pageHtml . '</div>', $activeItem);
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
