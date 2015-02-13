<?php

require_once("common.php");

$html = <<<HTML
<p>Warladder.net is a custom <a href="http://warlight.net">WarLight</a> ladder organization service. Warladder.net is not affiliated with WarLight.</p>

<p>If you have any questions or remarks regarding warladder.net, please email us at <a href="mailto:warladder@warladder.net">warladder@warladder.net</a>.</p>

HTML;

page($html, "about", "About Warladder.net", null, null, "About");
