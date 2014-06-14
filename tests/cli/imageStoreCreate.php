<?php
include_once('../../lib/utils/utils.php');
include_once('cloud_api.php');

$GLOBALS['config']['api_user'] = 'sugartraining1';
 
// $GLOBALS['config']['apiUrl'] = 'http://campaigns.sugarcrmlabs.com/cloud/rest/v10';
$GLOBALS['config']['apiUrl'] = 'http://localhost:8888/cloud/rest/v10';
              
echo "\n\n--------------- CREATE --------------------\n";  
 
$filePath = '/Users/twolfe/superman.jpg';
$fh=fopen($filePath, "r");
$contents = fread($fh, filesize($filePath));
fclose($fh);
   
$resourceName = create_guid() . ".jpg";
$contentType = 'image/jpeg';

$data = array(
	'resource_name' => $resourceName, 
	'mime_type'     => $contentType,
	'contents'     	=> base64_encode($contents)
);

$method = 'POST';
$uri = '/imagestore'; 

$result = callResource($uri, $method, $data);

echo "\n----------------------------\n";
echo indent(json_encode($result['data']));
print_r($result);
echo "\n";
         
if ($result['code'] == '200') {
	echo "\n----------------------------\n";
	print_r($result['data']);
	echo "\n";
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

