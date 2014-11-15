<?php

function pipeExec($command, $stdin)
{
	$fdSpec = array(
		0 => array("pipe", "r"),
		1 => array("pipe", "w"),
		2 => array("pipe", "w")
	);
	
	$dir = getcwd();
	chdir(dirname(__FILE__));
	$process = proc_open($command, $fdSpec, $pipes);
	chdir($dir);
	if ($process === false) {
		return null;
	}
	
	while (strlen($stdin) > 0) {
		$size = fwrite($pipes[0], $stdin);
		if ($size === false) {
			return null;
		}
		$stdin = substr($stdin, $size);
	}
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	proc_close($process);
	
	return $stdout;
}

function tsDefaultScore()
{
	return array("mu"=>1000.0, "sigma"=>(1000.0/3.0), "rating"=>0.0);
}

function tsMatchMatrix($players)
{
	$playerList = array();
	foreach ($players as $player) {
		$playerList[] = array("id"=>(int)$player["userID"], "mu"=>(float)$player["mu"], "sigma"=>(float)$player["sigma"]);
	}
	$input = array("players"=>$playerList);
	$jsonInput = json_encode($input);
	
	$jsonOutput = pipeExec("python trueskill/matchmatrix.py", $jsonInput);
	$output = json_decode($jsonOutput);
	
	$matrix = array();
	foreach ($players as $userID => $player) {
		$matrix[$userID] = array();
	}
	foreach ($output as $match) {
		$matrix[$match->id1][$match->id2] = $match->quality;
		$matrix[$match->id2][$match->id1] = $match->quality;
	}
	return $matrix;
}

function tsProcessMatchResults($players, $results)
{
	$playerList = array();
	foreach ($players as $player) {
		$playerList[] = array("id"=>(int)$player["userID"], "mu"=>(float)$player["mu"], "sigma"=>(float)$player["sigma"]);
	}
	$matchList = array();
	foreach ($results as $result) {
		$match = array();
		$match["teams"] = array();
		$match["rankings"] = $result["rankings"];
		foreach ($result["teams"] as $team) {
			$newTeam = array();
			foreach($team as $userID) {
				$newTeam[] = (int)$userID;
			}
			$match["teams"][] = $newTeam;
		}
		$matchList[] = $match;
	}
	$input = array("players"=>$playerList, "matches"=>$matchList);
	$jsonInput = json_encode($input);
	
	$jsonOutput = pipeExec("python trueskill/processresults.py", $jsonInput);
	$output = json_decode($jsonOutput);
	
	$newScores = array();
	$rank = 1;
	foreach($output as $score) {
		$newScores[$score->id] = array();
		$newScores[$score->id]["mu"] = $score->mu;
		$newScores[$score->id]["sigma"] = $score->sigma;
		$newScores[$score->id]["rating"] = $score->rating;
		$newScores[$score->id]["rank"] = $rank++;
	}
	return $newScores;
}

