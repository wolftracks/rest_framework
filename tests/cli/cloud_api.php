<?php

$GLOBALS['config'] = array(
    'apiUrl' => '',
);


function callResource($uri, $method, $data = null)
{
    $url = $GLOBALS['config']['apiUrl'] . $uri;
    $handle = curl_init();

    printf("\n\nMETHOD: %s  URL: %s\n", $method, $url);

    $headers = array();
    $headers[] = 'Expect:';
    $headers[] = 'Accept: application/json';

    if (isset($GLOBALS['config']['api_user'])) {
        $headers[] = 'API-USER: ' . $GLOBALS['config']['api_user'];
    }

    //   if (isset($GLOBALS['config']['api_pass'])) {
    //       $headers[] = 'API-PASS: ' . $GLOBALS['config']['api_pass'];
    //   }

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
    // print_r($result);


    list($response_headers, $response) = explode("\r\n\r\n", $result, 2);
    $raw = $response;
    $response = json_decode($response, true);
    $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    return array(
        'code' => $code,
        'data' => $response,
        // 'raw' => $raw,
        'response_headers' => $response_headers
    );
}

function dump($array)
{
    echo "<pre>" . print_r($array, true) . "</pre>";
}

?>
