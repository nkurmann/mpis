<?php
/**
 * Template Name: AMBIMA JSON OUTPUT
 * No Sidebar, No Loop (for use as full screen Gallery with WP Supersized)
 *
 * Description: AMBIMA JSON OUTPUT
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 *
 */
?>

<?php




// Start the Loop.
// $args = array('post_type' => 'person');
// $loop = new WP_Query($args);
// print_r(json_encode($loop));
$temp = [];
switch ($_GET['ptype']) {
	case "person" :
		$loop = query_posts("post_type=" . $_GET['ptype'] . "&posts_per_page=20");
		// print_r($loop);
		//print_r(json_encode($loop));

		while (have_posts()) : the_post();
			//echo get_field_value(get_the_ID(),"person","basic-information");
			$postcustomTemp = get_post_custom();
			$postcustomTemp["title"] = get_the_title();

			if (has_post_thumbnail($post -> ID)) :
				$img = wp_get_attachment_image_src(get_post_thumbnail_id($post -> ID), 'thumbnail');
				$postcustomTemp["thumb"] = $img[0];
				$postcustomTemp["thumb-width"] = $img[1];
				$postcustomTemp["thumb-height"] = $img[2];
			endif;

			//user's displays
			// $userDis = usersDisplays($post -> ID);
			// $postcustomTemp["displays"] = $userDis;

			// schedule
			$schedule = outlook_reader($postcustomTemp['wpcf-person-e-mail'][0], date('Y-m-d\T08:00:00'), date('Y-m-d\T23:00:00'));
			//print_r($schedule);
			if (isset($schedule)) {
				$postcustomTemp["schedule"] = $schedule;

				$freebusyStatus = "Free";
				foreach ($schedule as $sched) {
					$dNow = current_time('mysql');
					$dStart = date($sched["startTime"]);
					$dEnd = date($sched["endTime"]);
					// echo $dNow . "<br>" . $dStart . "<br>" . $dEnd;

					if (($dNow > $dStart) && ($dNow < $dEnd)) {
						$freebusyStatus = $sched["busytype"];
						$currentSubject = $sched["Subject"];
						$currentLocation = $sched["Location"];
						$postcustomTemp["currentStart"] = substr($dStart, -8);
						$postcustomTemp["currentEnd"] = substr($dEnd, -8);
					}
					//echo $sched["busytype"];

				}
				$postcustomTemp["currentStatus"] = $freebusyStatus;
				if (isset($currentSubject)) {
					$postcustomTemp["currentSubject"] = $currentSubject;

				}
				if (isset($currentLocation)) {
					$postcustomTemp["currentLocation"] = $currentLocation;
				}

			} else {
				$postcustomTemp["currentStatus"] = "Free";
			}// end schedule

			//echo date('Y-m-d\Th:i:s');
			//echo $postcustomTemp -> wpcf-group-mail;
			//echo ($postcustomTemp["wpcf-person-external-member"][0]);
			$ext_mem = $postcustomTemp["wpcf-person-external-member"][0];
			$group_mail = $postcustomTemp["wpcf-group-mail"][0];
			if (($ext_mem != 1)) {
				$temp[] = $postcustomTemp;
			}
		endwhile;

		break;
	// end person type

	//tour
	case "tour" :		
		//Get Google_maps Collections'E 108.1 TV'
		$google_mapsID = get_page_by_title($_GET['tourName'], OBJECT, "google_maps") -> ID;

		//gather data for this shortcode
		$post = get_post($google_mapsID);
		$all_meta = get_post_custom($google_mapsID);
		//print_r ($all_meta);
		$visual_info = maybe_unserialize($all_meta['gmb_width_height'][0]);
		$lat_lng = maybe_unserialize($all_meta['gmb_lat_lng'][0]);

		//Put markers into an array for JS usage
		$map_marker_array = array();
		$markers_repeatable = maybe_unserialize($all_meta['gmb_markers_group'][0]);
		//print_r ($markers_repeatable);
		foreach ($markers_repeatable as $marker) {
			array_push($map_marker_array, $marker);
		}
		$temp = $map_marker_array;
		// // echo 	get_the_title( $google_mapsID );
		// $meta = get_post_meta($google_mapsID);
		// get_post_meta($google_mapsID, "marker", $single = false);
		// //print_r( $meta);

		break;
	case "user"	:
		//Get user by name
			
		$userID = get_page_by_title($_GET['username'], OBJECT, "person") -> ID;		



			//echo get_field_value(get_the_ID(),"person","basic-information");
			
			$postcustomTemp["id"] = $userID;
			$postcustomTemp["title"] = get_the_title( $userID );
			if (has_post_thumbnail($userID)) :
				$img = wp_get_attachment_image_src(get_post_thumbnail_id($userID), 'map-thumb2');
				$postcustomTemp["thumb"] = $img[0];
				$postcustomTemp["thumb-width"] = $img[1];
				$postcustomTemp["thumb-height"] = $img[2];
			endif;
			$temp = $postcustomTemp;
			
		break;


	default :

}

echo(json_encode($temp));
?>

