<?php
/**
 * Template Name: Wall JSON OUTPUT
 * No Sidebar, No Loop (for use as full screen Gallery with WP Supersized)
 *
 * Description: Wall JSON OUTPUT
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 *
 */
?>

<?php

if ($_GET['ptype'] == "twitterOverall" || $_GET['ptype'] == "twitterFriends") {
	require_once ('TwitterAPIExchange.php');
	require_once ('TwitterOauth.php');

	// Target Twitter account
	$twitterAccount = "@ambientimagery";

	$tempTweets = [];

	$maxValue = 50;
	$overallView = [];
	// Creating the structure
	$url = "https://api.twitter.com/1.1/friends/ids.json";
	$requestMethod = "GET";
	$getfield = "?cursor=-1&screen_name=$twitterAccount&count=500";
	$twitter = new TwitterAPIExchange($settings);
	$string = json_decode($twitter -> setGetfield($getfield) -> buildOauth($url, $requestMethod) -> performRequest(), $assoc = TRUE);
	foreach ($string["ids"] as $key => $items) {
		$tempData[$items]["recentTweet"] = "Wed Jul 21 12:00:00 +0000 2000";
		$tempData[$items]["recentTimeDif"] = 24;
	}

	// Number of friends
	$tempSize = count($string["ids"]);

	//Getting the recent tweets on the timeline
	$url = "https://api.twitter.com/1.1/statuses/home_timeline.json";
	$requestMethod = "GET";
	$getfield = "?count=500";
	$twitter = new TwitterAPIExchange($settings);
	$string2 = json_decode($twitter -> setGetfield($getfield) -> buildOauth($url, $requestMethod) -> performRequest(), $assoc = TRUE);

	foreach ($string2 as $key => $items) {

		// Difference between the current time and the tweet time
		$tempDif = (strtotime(date('D M j G:i:s O Y')) - strtotime($items["created_at"])) / ((60 * (5))); 
		/*
		 $dif = (24 - $tempDif);
		 if ($dif > 24) {
		 $dif = 24;
		 }
		 */
		if ($tempDif > 24) {
			$tempDif = 24;
		}

		if (isset($tempData[$items["user"]["id"]]["recentTweet"])) {
			// Updating the recent Tweet time
			$tempRecentTweetDif = (strtotime($tempData[$items["user"]["id"]]["recentTweet"]) - strtotime($items["created_at"])) / ((60 * (5)));
			if ($tempRecentTweetDif < 0) {
				$tempData[$items["user"]["id"]]["recentTweet"] = $items["created_at"];
				// Storing the difference between the current time and the most recent tweet time

				$tempData[$items["user"]["id"]]["recentTimeDif"] = $tempDif;
			}

		} else {
			$tempData[$items["user"]["id"]]["recentTweet"] = $items["created_at"];
			$tempData[$items["user"]["id"]]["recentTimeDif"] = $tempDif;

		}
		
		// Overall view data
		if ($tempDif < 0.5) {
			$overallView[$items["user"]["id"]][19] = 1;
		} elseif ($tempDif < 1) {
			$overallView[$items["user"]["id"]][18] = 1;
		} elseif ($tempDif < 1.5) {
			$overallView[$items["user"]["id"]][17] = 1;
		} elseif ($tempDif < 2) {
			$overallView[$items["user"]["id"]][16] = 1;
		} elseif ($tempDif < 2.5) {
			$overallView[$items["user"]["id"]][15] = 1;
		} elseif ($tempDif < 3) {
			$overallView[$items["user"]["id"]][14] = 1;
		} elseif ($tempDif < 3.5) {
			$overallView[$items["user"]["id"]][13] = 1;
		} elseif ($tempDif < 4) {
			$overallView[$items["user"]["id"]][12] = 1;
		} elseif ($tempDif < 4.5) {
			$overallView[$items["user"]["id"]][11] = 1;
		} elseif ($tempDif < 5) {
			$overallView[$items["user"]["id"]][10] = 1;
		} elseif ($tempDif < 5.5) {
			$overallView[$items["user"]["id"]][9] = 1;
		} elseif ($tempDif < 6) {
			$overallView[$items["user"]["id"]][8] = 1;
		} elseif ($tempDif < 6.5) {
			$overallView[$items["user"]["id"]][7] = 1;
		} elseif ($tempDif < 7) {
			$overallView[$items["user"]["id"]][6] = 1;
		} elseif ($tempDif < 7.5) {
			$overallView[$items["user"]["id"]][5] = 1;
		} elseif ($tempDif < 8) {
			$overallView[$items["user"]["id"]][4] = 1;
		} elseif ($tempDif < 8.5) {
			$overallView[$items["user"]["id"]][3] = 1;
		} elseif ($tempDif < 9) {
			$overallView[$items["user"]["id"]][2] = 1;
		} elseif ($tempDif < 9.5) {
			$overallView[$items["user"]["id"]][1] = 1;
		} elseif ($tempDif < 10) {
			$overallView[$items["user"]["id"]][0] = 1;
		}		


	}

	$usersUpdates = [];
	//Users data reformatting
	foreach ($tempData as $key => $items) {
		array_push($usersUpdates, $items["recentTimeDif"]);

	}

}
//Normalizer
function normalizer($a, $min, $max, $new_min, $new_max) {
	foreach ($a as $i => $v) {
		$a[$i] = ((($new_max - $new_min) * ($v - $min)) / ($max - $min)) + $new_min;
	}
	// var_dump($a);
	return ($a);
}

//XY($row, $size) //filling the xs, ys, axisx and axisy with raw data according to the number of dots in the wall and also number of the rows
function XY($row, $size) {

	if ($size >= $row) {
		$col = ceil($size / $row);
	} else {
		$col = 1;
		$row = $size;
	}
	$counter = 0;
	$xs = [];
	$ys = [];

	// Mapping the dots
	for ($i = 0; $i < $row; $i++) {
		for ($j = 0; $j < $col; $j++) {
			$counter = $counter + 1;
			array_push($xs, $i);
			array_push($ys, $j);

		}
	}

	// Labeling the axis
	for ($i = 0; $i < $col; $i++) {
		$axisx[$i] = "";
	}

	for ($i = 0; $i < $row; $i++) {
		$axisy[$i] = "";
	}

	$tempXY["xs"] = $xs;
	$tempXY["ys"] = $ys;
	$tempXY["axisx"] = $axisx;
	$tempXY["axisy"] = $axisy;

	return ($tempXY);

}

$temp = [];
switch ($_GET['ptype']) {

	case "sample" :
		//Initialize wall size
		$temp["width"] = 300;
		$temp["height"] = 250;
		$temp["leftgutter"] = 0;
		$temp["bottomgutter"] = 100;

		//Initialize wall axis
		$temp["axisx"] = array("", "", "", "", "");
		$temp["axisy"] = array("", "");

		// Circle positions, sizes (0 to 1), colors (-1 to 1) and labels
		$temp["xs"] = array(0, 0, 0, 0, 0, 1, 1, 1, 1, 1);
		$temp["ys"] = array(0, 1, 2, 3, 4, 0, 1, 2, 3, 4);
		$temp["size"] = array(1, 1, 0.5, 1, 0.5, 1, 0.5, 1, 0.5, 1);
		$temp["color"] = array(-1, -0.7, -0.5, -0.3, -0.1, 0, 0.2, 0.5, 0.7, 1);
		$temp["label"] = array("0,0", "0,1", "0,2", "0,3", "0,4", "1,0", "1,1", "1,2", "1,3", "1,4");

		break;

	case "static" :
		// Configuring the wall
		$temp["width"] = 700;
		$temp["height"] = 700;
		$temp["leftgutter"] = 0;
		$temp["bottomgutter"] = 30;

		// number of the dots in the wall
		$tempSize = 56;
		$tempRow = 7;
		// auto filling out of the xs, ys, axisx and axisy according to the number of dots in the wall and also size of the row	using XY($row, $size)
		// e.g. XY(7, 70);
		$tempWallFormat = XY($tempRow, $tempSize);
		$temp["xs"] = $tempWallFormat["xs"];
		$temp["ys"] = $tempWallFormat["ys"];
		$temp["axisx"] = $tempWallFormat["axisx"];
		$temp["axisy"] = $tempWallFormat["axisy"];

		//filling the wall with default value
		for ($z = 0; $z < ceil($tempSize / $tempRow) * $tempRow; $z++) {
			if ((!isset($usersUpdates[$z])) || ($temp["size"][$z] < 0.1)) {
				$temp["size"][$z] = 1;
				$temp["color"][$z] = -2;
				$temp["label"][$z] = 1;
			}
		}

		break;
	case "random" :
		// Configuring the wall
		$temp["width"] = 1000;
		$temp["height"] = 1000;
		$temp["leftgutter"] = 0;
		$temp["bottomgutter"] = 100;

		// number of the dots in the wall
		$tempSize = 70;
		$tempRow = 7;
		// auto filling out of the xs, ys, axisx and axisy according to the number of dots in the wall and also size of the row	using XY($row, $size)
		// e.g. XY(7, 70);
		$tempWallFormat = XY($tempRow, $tempSize);
		$temp["xs"] = $tempWallFormat["xs"];
		$temp["ys"] = $tempWallFormat["ys"];
		$temp["axisx"] = $tempWallFormat["axisx"];
		$temp["axisy"] = $tempWallFormat["axisy"];

		//filling the wall with default value
		for ($z = 0; $z < ceil($tempSize / $tempRow) * $tempRow; $z++) {
			if ((!isset($usersUpdates[$z])) || ($temp["size"][$z] < 0.1)) {
				$temp["size"][$z] = mt_rand(0 * 10, 1 * 10) / 10;
				// mt_rand ($min*10, $max*10) / 10
				$temp["color"][$z] = mt_rand(-0.1 * 10, 1 * 10) / 10;
				;
				$temp["label"][$z] = 1;
			}
		}

		break;
	case "twitterFriends" :
		$tempWallFormat = XY(4, $tempSize);
		$temp["width"] = 500;
		$temp["height"] = 300;
		$temp["leftgutter"] = 30;
		$temp["bottomgutter"] = 20;

		$temp["xs"] = $tempWallFormat["xs"];
		$temp["ys"] = $tempWallFormat["ys"];
		$temp["axisx"] = $tempWallFormat["axisx"];
		$temp["axisy"] = $tempWallFormat["axisy"];

		//Normalizing the data
		$temp["label"] = normalizer($usersUpdates, 0, 24, 0, 1);
		$temp["color"] = normalizer($usersUpdates, 0, 24, 0, 1);
		$temp["size"] = normalizer($usersUpdates, 0, 24, 0, 1);

		//Reverse the values to make the most recent tweets bigger
		foreach ($usersUpdates as $key => $items) {
			$temp["size"][$key] = 1 - $temp["size"][$key];
			$temp["color"][$key] = 1 - $temp["color"][$key];
			$temp["label"][$key] = 1 - $temp["label"][$key];

		}

		//filling the wall with default value
		for ($z = 0; $z < ceil($tempSize / 4) * 4; $z++) {
			if ((!isset($usersUpdates[$z])) || ($temp["size"][$z] < 0.1)) {
				$temp["size"][$z] = 0.1;
				$temp["color"][$z] = 0.1;
				$temp["label"][$z] = 0.1;
			}
		}
		break;
	case "twitterOverall" :
		$tempWallFormat = XY($tempSize, 20 * $tempSize);
		$temp["width"] = 1000;
		$temp["height"] = 1400;
		$temp["leftgutter"] = 30;
		$temp["bottomgutter"] = 20;

		$temp["xs"] = $tempWallFormat["xs"];
		$temp["ys"] = $tempWallFormat["ys"];
		$temp["axisx"] = $tempWallFormat["axisx"];
		$temp["axisy"] = $tempWallFormat["axisy"];

		//filling the wall with default value
		for ($z = 0; $z < 20 * $tempSize; $z++) {

			$temp["size"][$z] = 0.7;
			$temp["color"][$z] = 0.1;
			$temp["label"][$z] = 0;

		}
		
		// Updating the wall
	foreach ($string["ids"] as $key => $items) {

		for ($z = 0; $z < 20; $z++) {

			if (isset($overallView[$items][$z])){
			$whichTemp = ($key * 20) + $z;					

				 $temp["color"][$whichTemp] = 1;
				 $temp["label"][$whichTemp] = $items;				
			}
			
		}		
	
	}
}

if ($temp) {
	// Print => array 2 json
	print_r(json_encode($temp));
}
?>
