<?php

require_once("common.php");

requireLogin();

return page(renderMyLadders(currentUserID(), "Your ladders", null, null, pageNumber()), "myladders");
