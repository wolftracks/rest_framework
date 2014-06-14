<?php

define('STATUS_CODE_SUCCESS', 200);

$GLOBALS['config'] = array(
	'apiUrl'   => 'http://localhost:8888/Mango/toffee/ent/sugarcrm/rest/v10',
	'username' => 'admin',
	'password' => 'asdf',
	'clientid' => 'sugar',
	'oauth_token' => '',
	'oauth_token_refresh' => '',
);


function login() {
	global $GLOBALS;	
 
    $args = array(
        'grant_type' => 'password',
        'username' =>  $GLOBALS['config']['username'],
        'password' =>  $GLOBALS['config']['password'],
        'client_id' => $GLOBALS['config']['clientid'],
        'client_secret' => '',
    );
    
    // Prevent an infinite loop, put a fake authtoken in here.
   $GLOBALS['config']['oauth_token'] = 'LOGGING_IN';

   $result = callResource('/oauth2/token',"POST",$args);
   if ($result['code'] == 200 && !empty($result['data']) && !empty($result['data']['access_token'])) {
		 $GLOBALS['config']['oauth_token'] = $result['data']['access_token'];
		 $GLOBALS['config']['oauth_token_refresh'] = $result['data']['refresh_token'];
		 return(true);	// Success	
	}
	
	$GLOBALS['config']['oauth_token'] = 'AUTHORIZATION_FAILED';

printf("-------------- LOGIN FAILED ---------------\n");
print_r($args);
print_r($result);

    return false;
}


function callResource($uri, $method, $data = null)
{
	global $GLOBALS;
	if (empty($GLOBALS['config']['oauth_token'])) {
		$res=login();
		if (!$res) {
			exit;
		}
	}
	
	$url	= $GLOBALS['config']['apiUrl'] . $uri;
	$handle = curl_init();
	
	printf("\n\nURL: %s\n",$url);

    $headers = array(); 
 	$headers[] = 'Accept: application/json';
	// $headers[] = 'Content-Type: application/json';
	
	// $headers[] = 'Accept: text/xml';
	// $headers[] = 'Content-Type: text/xml';     
	
	if (!empty($GLOBALS['config']['oauth_token']) && substr($uri,0,13) != "/oauth2/token") {
	    $headers = array("oauth_token: " . $GLOBALS['config']['oauth_token']);
	}
	
	curl_setopt($handle, CURLOPT_URL, $url);
	if (count($headers) > 0) {
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
		// print_r($headers);
	}
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);


/*----------
    //printf("<PRE>\n");
    printf("(%s)  URL: %s\n",$method,$url);
	if (is_array($headers)) {
		printf("(Request Headers)\n");
    	print_r($headers);
	}
    if ($method=="POST" || $method=="PUT") {
		printf("($method Data)\n");
		print_r($data);
	}
	//printf("</PRE>\n");
    //die;
-----------*/

    curl_setopt($handle, CURLOPT_HEADER, true);
	switch ($method) {
		case 'POST':
			curl_setopt($handle, CURLOPT_POST, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
		break;
		case 'PUT':
			curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
			// $file_handle = fopen($data, 'r');
			// curl_setopt($handle, CURLOPT_INFILE, $file_handle);
		break;
		case 'DELETE':
			curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
			if (!empty($data)) {
			    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
			}
		break;
	}

	$result = curl_exec($handle);
	print_r($result); 
	
 	
	list($response_headers, $response) = explode("\r\n\r\n", $result, 2);  


/* ------------------------
 	print_r($result);
 	curl_close($handle);
  	exit;
-------------------------- */

	$raw = $response;
	$response = json_decode($response, true);
	$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	curl_close($handle);

	return array(
		'code' => $code,
		'data' => $response,
		'raw'  => $raw,
		'response_headers' => $response_headers
	);
}

function dump($array)
{
	echo "<pre>" . print_r($array, true) . "</pre>";
}

?>
