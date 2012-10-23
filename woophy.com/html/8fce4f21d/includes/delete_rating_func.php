<?php

function deleteRating($pid,$uid,$rate){
	$pid= (int)$pid;
	$uid= (int)$uid;
	$rate = (int)$rate;
	if($rate>0){
		DB::query("DELETE FROM rating WHERE photo_id=$pid AND user_id=$uid;");
				
		$result = DB::query("SELECT camera,user_id,average_rate,rate1,rate2,rate3,rate4,rate5 FROM photos WHERE photo_id=$pid;");
		if($result && DB::numRows($result)==1){
			$row = DB::fetchAssoc($result);
			$rate1 = $row["rate1"];
			$rate2 = $row["rate2"];
			$rate3 = $row["rate3"];
			$rate4 = $row["rate4"];
			$rate5 = $row["rate5"];
			$uid = $row["user_id"];				
			$num_voters = $rate1+$rate2+$rate3+$rate4+$rate5;
			$num_voters = max(0, $num_voters - 1);
			$nr = "rate".$rate;
			$$nr= $$nr-1;
			if($num_voters>0){
				$average_rate = ($rate1*1+$rate2*2+$rate3*3+$rate4*4+$rate5*5) / $num_voters;

				$min_voters = 1.9;
				$mid_rate = 3;//(1+2+3+4+5)/5;
				
				$weighted_rate = ($num_voters / ($num_voters+$min_voters)) * $average_rate + ($min_voters / ($num_voters+$min_voters)) * $mid_rate;
				
				if($weighted_rate > 4.25) $camera = 3;
				elseif($weighted_rate > 4) $camera = 2;
				elseif($weighted_rate > 3.75) $camera = 1;
				else $camera = 0;

			}else{
				$weighted_rate = 'NULL';
				$camera = 0;
			}
			
			DB::query("UPDATE photos SET rate$rate = GREATEST(rate$rate,1)-1, average_rate = $weighted_rate, camera = $camera WHERE photo_id=$pid;");
			

			if($num_voters > MIN_NUM_VOTERS){
				if($row['camera'] != $camera){
					$camera_total = 0;
					//recalculate camera:
					$result = DB::query('SELECT camera, COUNT(*) AS num FROM photos WHERE user_id = '.$uid.' GROUP BY camera');//slow query!!
					//TODO: is php approach faster then mysql: ORDER BY num DESC LIMIT 1???
					while($row = DB::fetchAssoc($result)){
						if($row['num']>MIN_NUM_PHOTOS_AWARD){
							if($camera_total<$row['camera'])$camera_total = $row['camera'];
						}
					}
					DB::query('UPDATE users SET camera = '.$camera_total .' WHERE user_id = '.$uid);
				}
			}
		}
	}
}