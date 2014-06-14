<?php
//error_reporting(E_ALL);
//error_reporting(E_STRICT);

include_once('../../lib/utils/utils.php');

// $GLOBALS['config']['apiUrl'] = 'http://campaigns.sugarcrmlabs.com/cloud/sugarcrm/webhooks/mandrill/email_status.php';
$GLOBALS['config']['apiUrl'] = 'http://localhost:8888/cloud/sugarcrm/webhooks/mandrill/email_status.php';

$files = array(
    /* */
    'all-2014-01-02.log',
    'all-2014-01-03.log',
    'all-2014-01-04.log',
    'all-2014-01-06.log',
    'all-2014-01-07.log',
    'all-2014-01-08.log',
    'all-2014-01-15.log',

    'all-2014-01-16.log',
);

$raw_events = array();
$events = array();
$tevents = array();

foreach ($files AS $filename) {
    process_file($filename);
    printf("TOTAL %s \n\n", count($raw_events));
}

/*
foreach ($events AS $id => $events) {
    printf("\n-- [%s] --------------------\n", $id);
    print_r($events);
}

foreach ($tevents AS $id => $events) {
    printf("\n-- [%s] --------------------\n", $id);
    ksort($events);
    print_r($events);
}
*/

$count = 0;
foreach ($raw_events AS $event) {
    $post_args = array();

    $post_args['mandrill_events'] = urlencode($event);

    $result = postFormData('', 'POST', $post_args);

    echo "\n----------------------------\n";
    print_r($result);
    echo "\n";

    list($response_headers, $response) = explode("\r\n\r\n", $result, 2);

    echo "\n----------------------------\n";
    printf("\n%s\n", indent($response));
    echo "\n";

    $count++;
    if ($count == 3) {
        // break;
    }
}

exit;


function process_file($filename)
{

    printf("\n-------- FILE: %s ------------------------------------------\n", $filename);

    global $raw_events;
    global $events;
    global $tevents;
    $fh = @fopen($filename, "r");
    if ($fh) {
        while (($buffer = fgets($fh, 32765)) !== false) {
            // $raw_events[] = $buffer;
            $event = json_decode($buffer, true);

            $event_type = $event['event'];
            $event_id = $event['_id'];
            $event_ts = $event['ts'];
            $msg = $event['msg'];
            $dtm = getDateTimeFromTimestamp($event_ts, true);

            //if ($event_type == 'send') {
            //    continue;
            //}

            $raw_events[] = json_encode(array($event));

            //print_r($event);

            $event_string = sprintf("%-20s %-8s %s", $dtm, $event_type, $event_id);

            if (!key_exists($event_id, $events)) {
                $events[$event_id] = array();
            }
            $events[$event_id][] = $event_string;

            if (!key_exists($event_id, $tevents)) {
                $tevents[$event_id] = array();
            }
            $tevents[$event_id][$dtm] = $event_string;

            echo  $event_string . "\n";
        }
        if (!feof($fh)) {
            echo "Error: unexpected fgets() fail\n";
        }
        fclose($fh);
    }

}


function getDateTimeFromTimestamp($tm = 0, $incsec = false)
{
    // $tm = time();
    $mm = strftime("%m", $tm);
    $dd = strftime("%d", $tm);
    $yy = strftime("%Y", $tm);
    $hr = strftime("%H", $tm);
    $mn = strftime("%M", $tm);
    if ($incsec) {
        $sc = strftime("%S", $tm);
        $dateTime = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $yy, $mm, $dd, $hr, $mn, $sc);
    } else {
        $dateTime = sprintf("%04d-%02d-%02d %02d:%02d", $yy, $mm, $dd, $hr, $mn);
    }

    return ($dateTime);
}

function getDateToday()
{
    $tm = time();
    $mm = strftime("%m", $tm);
    $dd = strftime("%d", $tm);
    $yy = strftime("%Y", $tm);
    $date = sprintf("%04d-%02d-%02d", $yy, $mm, $dd);
    return ($date);
}

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
function indent($json)
{

    $result = '';
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = '  ';
    $newLine = "\n";
    $prevChar = '';
    $outOfQuotes = true;

    for ($i = 0; $i <= $strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
        } else {
            if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}


function postFormData($uri, $method, array $data = null)
{
    $url = $GLOBALS['config']['apiUrl'] . $uri;
    $handle = curl_init();

    printf("\n\nMETHOD: %s  URL: %s\n", $method, $url);

    $headers = array();
    $headers[] = 'Expect:';
    $headers[] = 'Accept: text/html';
    $headers[] = 'Content-type: application/x-www-form-urlencoded';

    if (!empty($data) && is_array($data)) {
        $post_data = http_build_query($data);
    } else {
        $post_data = '';
    }

    curl_setopt($handle, CURLOPT_URL, $url);
    if (count($headers) > 0) {
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        // print_r($headers);
    }
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($handle, CURLOPT_HEADER, true);
    switch ($method) {
        case 'POST':
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
            break;
        case 'PUT':
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
            break;
        case 'DELETE':
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($data)) {
                curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
            }
            break;
    }

    $result = curl_exec($handle);
    //print_r($result);

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

    return $result;

    return $response;

    return array(
        'code' => $code,
        'data' => $response,
        // 'raw' => $raw,
        'response_headers' => $response_headers
    );
}

