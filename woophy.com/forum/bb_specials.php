<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/


$clForums=array(10,16);
$clForumsUsers[10]=array();
$clForumsUsers[16]=array(19397,27648,134,2229,2647,5739,6416,7618,7866,7917,8750,9423,14190,17547,21029,24795,25200,26938,27811,27851,30254,30383,30413,30520,31275,31334,28840,9243);
$roForums=array(8,11,14);
$poForums=array(16);
$regUsrForums=array();

//ANTISPAM: set all forums to readonly if user has no photo uploads
@session_start();
if(isset($_SESSION['userid'])){
	$result = DB::query('SELECT last_upload_date FROM users WHERE user_id='.(int)$_SESSION['userid']);
	$ro = false;
	if($result){
		if(is_null(DB::result($result, 0)))$ro = true;
	}
	if($ro) $roForums = range(1, 50);//TRICKY: increase range if more than 50 forums
}

/*
Abílio Silveira, 27648
zerega, 134
Martin de Rijk, 2229
Nonkel Duvel, 2647
Aline, 5739
Bernhard, 6416
www.erwinG-photography.com, 7618
Oscar_, 7866
Dieuwertje, 7917
Paulo Calafate, 8750
joopvandijk, 9423
pansa, 14190
Jan Hemels, 17547
A.Miguel Oliveira, 21029
trudy tuinstra, 24795
Thomas Brandenburg, 25200
Kambrosis, 26938
paolo la farina, 27811
Coffeejunkie, 27851
lupisjim, 30254
la rafale, 30383
Lali, 30413
Zeeg, 30520
Monicats.Oz, 31275
Kolibri, 31334
Hector.O, 28840
Laura C. 9243
WoophySF(JP) 43166
*/

$staff = 'Staff';
$moderator = 'Moderator';
$userRanks=array(
	4=>$moderator,
	21=>$moderator,
	912=>$moderator,
	1097=>$moderator,
	3576=>$moderator,
	12379=>$moderator,
	7866=>$moderator,
	21409=>$moderator,
	7414=>$moderator,
	28661=>$moderator,
	6416=>$moderator,
	17547=>$moderator,
	30413=>$moderator,
	19397=>$staff,
	43166=>$staff
	);
//21 ConradM
//3576 old uid of Marcos
//12379 Marcos
//19397 joris
//3802 old uid of bunny
//21409 New Unofficial.Contest
//7866 Oscar_
//9970 Ruud~
//7414 Leon
//27851 Coffeejunkie
//28661 zoidberg
//6416 Bernhard
//17547 Jan Hemels
//30413 SleepingLali

$mods=array(2=>array(19397,7414,7866,28661,6416,17547,30413),
			3=>array(19397,7414,7866,28661,6416,17547,30413),
			4=>array(19397,1097,12379,7414,7866,28661,6416,17547,30413),
			5=>array(19397,12379,7414,7866,28661,6416,17547,30413),
			6=>array(19397,7414,7866,28661,6416,17547,30413),
			7=>array(19397,7414,7866,28661,6416,17547,30413),
			8=>array(19397,43166),
			9=>array(19397,7866,21409,7414,28661,6416,17547,30413),
			10=>array(19397,7414,6416,30413),
			11=>array(19397,21,7414,6416,30413),
			12=>array(19397,12379,7414,7866,28661,6416,17547,30413),
			14=>array(19397,21,7414,6416,30413),
			15=>array(19397,7414,7866,21409,27851,28661,6416,17547,30413),
			17=>array(19397,21,7414,6416,30413)
)
?>