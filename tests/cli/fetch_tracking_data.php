<?php
include_once('../../lib/utils/utils.php');
include_once('cloud_api.php');

$GLOBALS['config']['api_user'] = 'sugartraining1';
//$GLOBALS['config']['apiUrl'] = 'http://campaigns.sugarcrmlabs.com/cloud/rest/v10';
$GLOBALS['config']['apiUrl'] = 'http://localhost:8888/cloud/rest/v10';
 
$total=0;
$max_num=25;
$last_id='';
do {
     $uri = "/email/tracking?max_num={$max_num}&last_id={$last_id}";
     $result = callResource($uri, 'GET');
     // print_r($result);
     $code = $result['code'];
	 $count=0;
     if ($code=='200') {
 	 	$rows = $result['data'];  
	 	$count=count($rows);
		$total += $count;
	 	for ($i=0; $i<$count; $i++) {
		 	$row = $rows[$i];
		 	$last_id = $row['id'];
		 	printf("    .... ID: %s\n",$last_id);
	 	}
	 } 
	 printf("CODE={$code}  COUNT={$count}  MAX_NUM={$max_num}\n");
   }
while ($code=='200' && $count == $max_num);
printf("\nTOTAL={$total}\n\n"); 
exit;
