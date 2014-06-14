<?php
include_once('../../lib/utils/utils.php');
include_once('cloud_api.php');

$GLOBALS['config']['api_user'] = 'sugartraining1';

if ($argc >= 2 && $argv[1] == 'local') {
    $GLOBALS['config']['apiUrl'] = 'http://localhost:8888/cloud/rest/v10';
} else {
    $GLOBALS['config']['apiUrl'] = 'http://campaigns.sugarcrmlabs.com/cloud/rest/v10';
}

echo "\n\n--------------- CAPTURE --------------------\n";

$contents = '<html>
<body bgcolor="#FFFFFF">
	<br />
	<p style="text-align: center;">
		Once Upon a 
	    <span style="font-family: georgia,palatino; font-size: 36pt; font-weight:bold">Time</span> 
		in a 
	    <span style="font-size: 18pt; font-weight:bold">galaxy</span>
	    far, far, away
	</p> 
</body>
</html>'; 
 
// echo $contents;
// exit;

$data = array(
    // 'url' => 'http://my.yahoo.com',

    'html_body' => base64_encode($contents),

    // 'image_type' => 'png',
    'image_width' => 200,
    'image_height' => 150,
    //  'image_width' => 160,
    //  'image_height' => 120,
);

$method = 'POST';
$uri = '/imagecapture';

$result = callResource($uri, $method, $data);

if ($result['code'] == '200' && !empty($result['data']['success'])) {
    $b64_contents = $result['data']['contents'];
    unset($result['data']['contents']);

    print_r($result['data']);

    $contents = base64_decode($b64_contents);
    $filePath = '/Users/twolfe/www/TEST/images/' . $result['data']['fileName'];
    $url = "http://localhost:8888/TEST/images/" .  $result['data']['fileName'];

    file_put_contents($filePath, $contents);

    echo "\n\n";
    echo "FILE:  " . $filePath . "\n";
    echo "URL:   " . $url. "\n\n";
} else {
    print_r($result);
}

exit;
 

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
    $indentStr = '    ';
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

function startsWith($source, $aString)
  {
    $src = strtolower($source);
    $str = strtolower($aString);
    $lensrc=strlen($src);
    $lenstr=strlen($str);
    if ($lenstr > $lensrc)
      return(FALSE);
    if ($str == $src)
      return(TRUE);
    if (substr($src,0,$lenstr) == $str)
      return(TRUE);
    return(FALSE);
  }

