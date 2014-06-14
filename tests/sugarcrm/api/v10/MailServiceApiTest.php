<?php
/*
 * By installing or using this file, you are confirming on behalf of the entity
 * subscribed to the SugarCRM Inc. product ("Company") that Company is bound by
 * the SugarCRM Inc. Master Subscription Agreement ("MSA"), which is viewable at:
 * http://www.sugarcrm.com/master-subscription-agreement
 *
 * If Company is not bound by the MSA, then by installing or using this file
 * you are agreeing unconditionally that Company will be bound by the MSA and
 * certifying that you have authority to bind Company accordingly.
 *
 * Copyright  2004-2013 SugarCRM Inc.  All rights reserved.
 */

require_once("sugarcrm/api/v10/MailServiceApi.php");
require_once("sugarcrm/services/TrackingService/MailTrackingService.php");

/**
 * @group api
 */
class MailServiceApiTest extends SugarTestRestApi
{

    private $valid_customer = 'sugartraining1';
    private $invalid_customer = 'xyz';

    public function setUp()
    {
        self::$json_encoding = 'auto';
        SugarClassLoader::clearMockInstances();
    }

    public function tearDown()
    {
    }

    /**
     * @covers MailServerviceApi::getSendParameters
     * @covers MailServerviceApi::queueMail
     */
    public function testRestApi_email_Send_Success()
    {
        $this->mockAuthentication(true);
        $extraHeaders = $this->getCustomerHeader(create_guid());
        $restInput = $this->getDefaultRestInputData();

        $mockJobQueue = self::getMock('JobQueue', array('writeQueue'));
        $mockJobQueue->expects($this->once())
            ->method('writeQueue')
            ->will($this->returnValue(true));

        SugarClassLoader::addMockInstance('JobQueue', $mockJobQueue);

        $result = $this->restapi_post('/rest/v10/email/send', $restInput, $extraHeaders);

        $this->assertEquals('200', $this->getStatusCode(), "Request Failed");
    }

    /**
     * @covers MailServerviceApi::getSendParameters
     * @covers SugarAuthenticationManager::authenticate
     */
    public function testRestApi_email_Send_NotAuthorized()
    {
        $this->mockAuthentication(false);
        $extraHeaders = $this->getCustomerHeader(create_guid());
        $restInput = $this->getDefaultRestInputData();

        $mockJobQueue = self::getMock('JobQueue', array('writeQueue'));
        $mockJobQueue->expects($this->never())
            ->method('writeQueue');

        SugarClassLoader::addMockInstance('JobQueue', $mockJobQueue);

        $result = $this->restapi_post('/rest/v10/email/send', $restInput, $extraHeaders);

        $this->assertEquals('403', $this->getStatusCode(), "Expected Request o Fail");
    }

    /**
     * @covers MailServerviceApi::getSendParameters
     * @covers MailServerviceApi::queueMail
     */
    public function testRestApi_email_Send_MissingRequiredParameter()
    {
        $this->mockAuthentication(true);
        $extraHeaders = $this->getCustomerHeader(create_guid());
        $restInput = $this->getDefaultRestInputData();
        unset($restInput['recipients']);

        $result = $this->restapi_post('/rest/v10/email/send', $restInput, $extraHeaders);
        $this->assertEquals('422', $this->getStatusCode(), "Expected Missing Required Parameter");
    }

    /**
     * @covers MailServiceSendParameters::toArray
     * @covers MailServiceSendParameters::fromArray
     */
    public function MailServiceSendParameters_toArray_fromArray_()
    {
        $inputArray = $this->getDefaultRestInputData();

        $sendParameters = MailServiceSendParameters::fromArray($inputArray);
        $toArray = $sendParameters->toArray();

        $this->assertArraysEqual($inputArray, $toArray, "fromArray/toArray are not compatible");
    }

    /**
     * @covers MailServerviceApi::getSendParameters
     */
    public function testRestInputValidation_SetCustomerId_CustomerIdPropagatesAsExpected()
    {
        $customer_id = create_guid();
        $restInput = $this->getDefaultRestInputData();
        $serviceInput = $this->getDefaultServiceInputData();

        $api = new TestMailServiceApi();

        /* Set Customer Id on the SugarApi Parent Class
           as occurs for each Rest Service invoked */
        $api->customer_id = $customer_id;

        /* The Customer ID should be present in the Service Input
           Data passed to the Configured Mail Service */
        $serviceInput['customer_id'] = $customer_id;
        $serviceInput['headers']['X-CUSTOMER-ID'] = $customer_id;

        $sendParams = $api->getSendParameters($restInput);
        $actual = $sendParams->toArray();

        $this->assertArraysEqual($serviceInput, $actual, "Customer ID is only change expected");
    }

    /**
     * @covers MailServerviceApi::getSendParameters
     * @dataProvider dataProviderForRestInputValidation
     */
    public function testRestInputValidation($restChanges, $serviceChanges, $exceptionExpected = false)
    {
        $customer_id = create_guid();
        $restInput = $this->getDefaultRestInputData($restChanges);
        $serviceInput = $this->getDefaultServiceInputData($serviceChanges);
        $serviceInput['customer_id'] = $customer_id;
        $serviceInput['headers']['X-CUSTOMER-ID'] = $customer_id;

        $api = new TestMailServiceApi();
        $api->customer_id = $customer_id;

        $exceptionDetected = false;
        $exception = null;
        $sendParams = array();
        try {
            $sendParams = $api->getSendParameters($restInput);
        } catch (SugarApiException $e) {
            $exceptionDetected = true;
            $exception = $e;
        }

        if ($exceptionExpected && !$exceptionDetected) {
            $this->fail("Exception Expected - No Exception Thrown");
        }
        if (!$exceptionExpected && $exceptionDetected) {
            $this->fail("No Exception Expected - Exception Thrown: {$exception->getMessage()}");
        }

        if ($exceptionExpected) {
            $this->assertEquals($exceptionExpected, $exceptionDetected);
        } else {
            $actual = $sendParams->toArray();
            foreach ($serviceChanges as $change) {
                foreach ($change as $field => $value) {
                    if ($value == '[DUMP]') {
                        var_dump($actual[$field]);
                    }
                }
            }
            $this->assertArraysEqual($serviceInput, $actual, "RestInputValidation Failure");
        }
    }

    public function dataProviderForRestInputValidation()
    {
        return array(
            // Dataset #0
            array(
                //--- REST Input Changes ---
                array(
                    array('from' => array('name' => 'Johnny Appleseed', 'email' => 'johnny@appleseed.com'))
                ),
                //--- Resulting MailService Input Changes ---
                array(
                    array('from_name' => 'Johnny Appleseed'),
                    array('from_email' => 'johnny@appleseed.com'),

                ),
                //--- Audit Exception Expected ---
                false,
            ),
            // Dataset #1
            array(
                array(
                    array('recipients' => '[UNSET]')
                ),
                array(),
                //--- Audit Exception Expected - recipients required ---
                true,
            ),
            // Dataset #2
            array(
                array(
                    array('communication_id' => '[UNSET]')
                ),
                array(),
                true,
            ),
            // Dataset #3
            array(
                array(
                    array('from' => '[UNSET]')
                ),
                array(),
                true,
            ),
            // Dataset #4
            array(
                array(
                    array('communication_id' => '12345')
                ),
                array(
                    array('communication_id' => '12345'),
                    array('headers' => array('X-COMMUNICATION-ID' => '12345', 'reply-to' => 'noreply@starkist.com')),
                ),
                false,
            ),
            // Dataset #5
            array(
                array(
                    array('merge_field_delimiters' => '[UNSET]')
                ),
                array(
                    array(
                        'merge_field_delimiters' => array(
                            "begin" => "*|",
                            "end" => "|*",
                        )
                    )
                ),
                false,
            ),
            // Dataset #6
            array(
                array(
                    array(
                        'merge_field_delimiters' => array(
                            "begin" => "{*",
                            "end" => "*}",
                        )
                    )
                ),
                array(
                    array(
                        'merge_field_delimiters' => array(
                            "begin" => "{*",
                            "end" => "*}",
                        )
                    )
                ),
                false,
            ),
            // Dataset #7
            array(
                array(
                    array('global_merge_data' => '[UNSET]')
                ),
                array(
                    array('global_merge_data' => array())
                ),
                false,
            ),
            // Dataset #8
            array(
                array(
                    array('reply_to' => '[UNSET]')
                ),
                array(
                    array(
                        'headers' => array(
                            'X-COMMUNICATION-ID' => '1234567890',
                            'reply-to' => 'noreply@redherring.net'
                        )
                    ),
                ),
                false,
            ),
            // Dataset #9
            array(
                array(
                    array(
                        'reply_to' => array(
                            'email' => 'noreply@yahoo.com'
                        ),
                    ),
                ),
                array(
                    array('headers' => array('X-COMMUNICATION-ID' => '1234567890', 'reply-to' => 'noreply@yahoo.com')),
                ),
                false,
            ),
            // Dataset #10
            array(
                array(
                    array(
                        'from' => array(
                            'name' => 'Jack Sprat',
                            'email' => 'noreply@google.com'
                        ),
                    ),
                ),
                array(
                    array('from_name' => 'Jack Sprat'),
                    array('from_email' => 'noreply@google.com'),
                    array(
                        'headers' => array(
                            'X-COMMUNICATION-ID' => '1234567890',
                            'reply-to' => 'noreply@starkist.com'
                        )
                    ),
                ),
                false,
            ),
            // Dataset #11
            array(
                array(
                    array('subject' => array('Hello' => 'World')),
                ),
                array(),
                true,
            ),
            // Dataset #12
            array(
                array(
                    array('subject' => 25),
                ),
                array(),
                true,
            ),
            // Dataset #13
            array(
                array(
                    array('subject' => true),
                ),
                array(),
                true,
            ),
            // Dataset #14
            array(
                array(
                    array('subject' => 'Hello World. This is the Subject'),
                ),
                array(
                    array('subject' => 'Hello World. This is the Subject'),
                ),
                false,
            ),
            // Dataset #15
            array(
                array(
                    array('text_body' => 'Hello World. This is Text'),
                ),
                array(
                    array('text_body' => 'Hello World. This is Text'),
                ),
                false,
            ),
            // Dataset #16
            array(
                array(
                    array('html_body' => '<b>Hello World. <i>This is HTML</i></b>'),
                ),
                array(
                    array('html_body' => '<b>Hello World. <i>This is HTML</i></b>'),
                ),
                false,
            ),
            // Dataset #17
            array(
                array(
                    array('recipient_merge_vars' => '[UNSET]'),
                ),
                array(
                    array('recipient_merge_vars' => array()),
                ),
                false,
            ),
            // Dataset #18
            array(
                array(
                    array('recipient_merge_vars' => 'Not An Array'),
                ),
                array(
                    array('recipient_merge_vars' => array()),
                ),
                true,
            ),
        );
    }


    /**
     * @covers MailServerviceApi::getTrackingDetail
     */
    public function testRestApi_email_tracking_Success()
    {
        $this->mockAuthentication(true);
        $extraHeaders = $this->getCustomerHeader($this->valid_customer);

        $options = array(
            'last_id' => 0,
            'max_num' => 3
        );
        $row = array();
        $rows = array();
        $rows[] = $row;
        $rows[] = $row;
        $rows[] = $row;

        $trackingServiceClassName = $this->getTrackingServiceClassName();
        $mockTrackingService = self::getMock(
            $trackingServiceClassName,
            array('getTrackingRecords', 'send_notice', 'setServiceAccountInfo', 'process_event')
        );
        $mockTrackingService->expects($this->once())
            ->method('getTrackingRecords')
            ->will($this->returnValue($rows));

        SugarClassLoader::addMockInstance($trackingServiceClassName, $mockTrackingService);

        $queryString = http_build_query($options);
        $result = $this->restapi_get('/rest/v10/email/tracking?' . $queryString, $extraHeaders);

        $this->assertEquals('200', $this->getStatusCode(), "Request Failed");
    }

    //----------------------------------------------------------
    //   Private Helper Functions
    //----------------------------------------------------------
    private function getDefaultRestInputData($changes = array())
    {
        $communication_id = '1234567890';

        $subject = 'Attention *|first_name|*';
        $text_body = 'Hello  *|first_name|* *|last_name|*';
        $html_body = 'Goodbye  *|first_name|* *|last_name|*';
        $merge_field_delimiters = array(
            'begin' => '*|',
            'end' => '|*',
        );

        $global_merge_data = array(
            array(
                'name' => 'company_name',
                'content' => 'Smith Realty'
            )
        );
        $recipient_merge_vars = array(
            'first_name',
            'last_name'
        );
        $recipients = array(
            array(
                'email' => 'woody@yahoo.com',
                'name' => 'Woody Woodpecker',
                'merge-data' => array(
                    'Woody',
                    'Woodpecker'
                )
            ),
        );

        $from = array(
            "name" => 'Red Herring',
            "email" => 'noreply@redherring.net'
        );
        $reply_to = array(
            "name" => 'Charlie Tuna',
            "email" => 'noreply@starkist.com'
        );

        $headers = array();
        $images = array(
            array(
                'name' => 'image-101',
                'type' => 'image/jpeg', // image/png  image/jpeg  image/gif
                'content' => 'XXXX', // base64_encoded
            ),
        );
        $attachments = array(
            array(
                'name' => 'MyDocument.pdf', // FileName
                'type' => 'text/plain', //
                'content' => 'AAAA', // base64_encoded
            ),
        );

        $post_data = array(
            'communication_id' => $communication_id,
            'merge_field_delimiters' => $merge_field_delimiters,
            'global_merge_data' => $global_merge_data,
            'recipient_merge_vars' => $recipient_merge_vars,
            'recipients' => $recipients,
            'headers' => $headers,
            'from' => $from,
            'reply_to' => $reply_to,
            'subject' => $subject,
            'html_body' => $html_body,
            'text_body' => $text_body,
            'inline_images' => $images,
            'attachments' => $attachments,
        );

        foreach ($changes as $change) {
            foreach ($change as $field => $value) {
                if ($value == '[UNSET]') {
                    unset($post_data[$field]);
                } elseif ($value == '[DUMP]') {
                    var_dump($post_data[$field]);
                } else {
                    $post_data[$field] = $value;
                }
            }
        }

        return $post_data;
    }

    private function getDefaultServiceInputData($changes = array())
    {
        $mailServiceInput = array(
            'customer_id' => null,
            'communication_id' => '1234567890',
            'html_body' => 'Goodbye  *|first_name|* *|last_name|*',
            'text_body' => 'Hello  *|first_name|* *|last_name|*',
            'subject' => 'Attention *|first_name|*',
            'from_email' => 'noreply@redherring.net',
            'from_name' => 'Red Herring',
            'headers' => array(
                'reply-to' => 'noreply@starkist.com',
                'X-COMMUNICATION-ID' => '1234567890',
            ),
            'merge_field_delimiters' => array(
                'begin' => '*|',
                'end' => '|*',
            ),
            'global_merge_data' => array(
                array(
                    'name' => 'company_name',
                    'content' => 'Smith Realty',
                ),
            ),
            'recipient_merge_vars' => array(
                'first_name',
                'last_name',
            ),
            'recipients' => array(
                array(
                    'email' => 'woody@yahoo.com',
                    'name' => 'Woody Woodpecker',
                    'merge-data' => array(
                        'Woody',
                        'Woodpecker',
                    ),
                ),
            ),
            'images' => array(
                array(
                    'name' => 'image-101',
                    'type' => 'image/jpeg',
                    'content' => 'XXXX',
                ),
            ),
            'attachments' => array(
                array(
                    'name' => 'MyDocument.pdf',
                    'type' => 'text/plain',
                    'content' => 'AAAA',
                ),
            ),
            'tags' => array(
                'campaign'
            ),
            'metadata' => array(),
        );

        foreach ($changes as $change) {
            foreach ($change as $field => $value) {
                if ($value == '[UNSET]') {
                    unset($mailServiceInput[$field]);
                } elseif ($value == '[DUMP]') {
                    var_dump($mailServiceInput[$field]);
                } else {
                    $mailServiceInput[$field] = $value;
                }
            }
        }

        return $mailServiceInput;
    }

    private function getCustomerHeader($customer_id)
    {
        return array(
            'HTTP_API_USER' => $customer_id,
            'HTTP_API_INSTANCE' => md5("PHPUNIT Tests"),
            'HTTP_API_SITE_URL' => 'http://localhost:8888/phpunit/tests'
        );
    }

    private function mockAuthentication($isValid)
    {
        $mockAuth = self::getMock('SugarAuthentication', array('authenticate'));
        $mockAuth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($isValid));
        SugarClassLoader::addMockInstance('SugarAuthenticationManager', $mockAuth);
    }

    private function getTrackingServiceClassName()
    {
        $mailProvider = SugarConfig::getEmailServiceProvider();
        $trackingServiceClass = $mailProvider['provider_name'] . 'MailTrackingService';
        return $trackingServiceClass;
    }

}

class TestMailServiceApi extends MailServiceApi
{
    public function getSendParameters(array $params)
    {
        return parent::getSendParameters($params);
    }
}

class TestMailTrackingService extends MailTrackingService
{
    protected function send_notice($toName, $toEmail, $fromName, $fromEmail, $subject, $message)
    {
    }

    public function process_event(array $event)
    {
    }

    public function setServiceAccountInfo($api_user, $api_pass)
    {
    }
}
