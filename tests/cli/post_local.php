<?php
//error_reporting(E_ALL);
//error_reporting(E_STRICT);

include_once('../../lib/utils/utils.php');
include_once('cloud_api.php');

$GLOBALS['config']['api_user'] = 'sugartraining1';
 
// $GLOBALS['config']['apiUrl'] = 'http://campaigns.sugarcrmlabs.com/cloud/rest/v10';
$GLOBALS['config']['apiUrl'] = 'http://localhost:8888/cloud/rest/v10';

$method = 'POST';
$index = 1;
$when='';
$uri = '/email/send';
$arg_email_address = 'twolf@sugarcrm.com';
if ($argc > $index) {
    if ($argv[$index] == 'now') {
        $when="NOW";
        $uri = '/email/sendimmediate';
        $index++;
    }
}

if ($argc > $index) {
    if (strpos($argv[$index], "@") !== false) {
        $arg_email_address = $argv[$index];
    } else {
        $arg_email_address = "twolf@sugarcrm.com";
    }
}

//printf("Sending email to: %s %s\n\n",$arg_email_address, $when);
//exit;

$text = <<<TEXTMESSAGE
Hello  *|first_name|* *|last_name|*,

Thank you for your interest in our products. We have set up an appointment
to call you  on *|appointment_date|* at *|appointment_time|* to discuss your needs in more detail. Your 
representative is  *|representative_name|*.

Regards,
Fred
TEXTMESSAGE;

$html = <<<HTMLMESSAGE
<html>
  <head></head>
  <body style="font-family:Arial; font-size:13; font-weight:normal; color:#333333; line-height:30px;">
    <p style="color:#990000">Hello *|first_name|* *|last_name|*,</p>
	<p style="color:#000099">
       Thank you for your interest in our products. We have set up an appointment for you with *|representative_name|* to meet with you right there
       in  *|city|*, *|state|*  on *|appointment_date|* at *|appointment_time|*. 
       <br /> 
       *|representative_first_name|* will be calling you shortly to confirm the location and time with you.
	   <br />      
	   <br /> 
       Regards,
	   Fred
	   <br />
    </p>
  </body>
</html>
HTMLMESSAGE;

$company_name = 'BakersField Electronics, Inc.';
$service_provider = 'Mandrill';

$merge_field_delimiters = array(
    'begin' => '*|',
    'end' => '|*',
);

$global_merge_data = array(
    array(
        'name' => 'company_name',
        'content' => $company_name
    ),
    array(
        'name' => 'service_provider',
        'content' => $service_provider,
    ),
);

$recipient_merge_vars = array(
    'first_name',
    'last_name',
    'city',
    'state',
    'appointment_date',
    'appointment_time',
    'representative_name',
    'representative_first_name',
);

$recipients = array(
    array(
        'email' => "$arg_email_address",
        'name' => 'Captain Kangaroo',
        'merge-data' => array(
            'Captain',
            'Kangaroo',
            'Chicago',
            'Illinois',
            '10/24/2014',
            '9:15 AM',
            'Robert Blake',
            'Robert'
        )
    ),

    /*
    array(
        'email' => 'sugar@ebmt.net',
        'name' => 'Curly Howard',
        'merge-data' => array(
            'Curly',
            'Howard',
            'Minneapolis',
            'Minnesota',
            '9/3/2014',
            '10:25 AM',
            'Clark Kent',
            'Clark'
        ),
    ),
  */

);

$from_name = 'Red Herring';
$from_email = 'noreply@redherring.net';
$communication_id = create_guid();
$unsubscribe_url = 'http://google.com';
$headers = array();

$cid = create_guid();
$images = array(
    array(
        'name' => $cid, // CID
        'type' => 'image/jpeg', // image/png  image/jpeg  image/gif
        'content' => base64_encode_file('files/superman.jpg'), // base64_encoded
    ),
);

$attachments = array(
    array(
        'name' => 'spiderman.jpg', // FileName
        'type' => 'image/jpeg', //
        'content' => base64_encode_file('files/spiderman.jpg'), // base64_encoded
    ),
);


$subject = 'This email was sent by *|service_provider|*';
$text_body = 'This is a Text Message';

//$text_body  = $text;


$html_body = '<p>Example HTML content</p><br /><br />Sponsored By *|company_name|*<br /><br />Hi *|first_name|* *|last_name|*,<br />';
$html_body .= '<img src="cid:' . $cid . '" /><br />';
$html_body .= 'Your representative, *|representative_name|* will be reaching out to you in the next few days.<br /><br />';
$html_body .= '*|representative_first_name|* is hoping that you can be available for the trade show preview we will be hosting on *|appointment_date|* at *|appointment_time|* <br /><br />';
$html_body .= 'Regards,<br />';
$html_body .= 'Tony<br />';

//$html_body = $html;


//  $images=array();
//  $attachments=array();


$post_data = array(
    'communication_id' => $communication_id,
    'merge_field_delimiters' => $merge_field_delimiters,
    'global_merge_data' => $global_merge_data,
    'recipient_merge_vars' => $recipient_merge_vars,
    'recipients' => $recipients,
    'headers' => $headers,
    'from' => array(
        "name" => $from_name,
        "email" => $from_email
    ),
    'reply_to' => array(
        "name" => $from_name,
        "email" => $from_email
    ),
    'subject' => $subject,
    'html_body' => $html_body,
    'text_body' => $text_body,
    'inline_images' => $images,
    'attachments' => $attachments,
);

$result = callResource($uri, $method, $post_data);

echo "\n----------------------------\n";
print_r($result);
echo "\n";
         
if ($result['code'] == '200') {
	echo "\n----------------------------\n";
	print_r($result['data']);
	echo "\n";
}
exit;
