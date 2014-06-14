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

require_once("sugarcrm/services/TrackingService/MandrillMailTrackingService.php");

/**
 *
 */
class MandrillMailTrackingServiceTest extends Sugar_PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    /**
     * @covers MandrillMailTrackingService::process_event
     */
    public function testMandrillEvent_send_EventIgnored()
    {
        /*-- the Mandrill 'Send' Event should be ignored and not recorded --*/
        $mockTrackingService = self::getMock('TestMandrillMailTrackingService', array('signalEvent'));
        $mockTrackingService->expects($this->never())
            ->method('signalEvent');

        $sendEvent = array(
            'event' => 'send',
            'ts' => 1388722851,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/534.57.7 (KHTML, like Gecko)',
            'user_agent_parsed' => array(),
            'ip' => '65.190.133.223'
        );

        $mockTrackingService->process_event($sendEvent);
    }

    /**
     * @covers MandrillMailTrackingService::signalEvent
     * @dataProvider dataProviderForTrackingEventFormulation
     */
    public function testMandrillTrackingService_AddTrackingEvent(
        array $baseMandrillEvent,
        array $mandrillEventOverrides = array(),
        TrackingEvent $expectedTrackingEvent,
        array $trackingEventOverrides = array()
    ) {

        $mandrillEvent = array_replace_recursive($baseMandrillEvent, $mandrillEventOverrides);

        foreach ($trackingEventOverrides as $property => $value) {
            $expectedTrackingEvent->$property = $value;
        }

        $mockMandrillTrackingService = self::getMock('TestMandrillMailTrackingService', array('addTrackingEvent'));
        $mockMandrillTrackingService->expects($this->once())
            ->method('addTrackingEvent');
            // ->with($expectedTrackingEvent);

        $actualTrackingEvent = new TrackingEvent();
        $mockMandrillTrackingService->test_SignalEvent($mandrillEvent, $actualTrackingEvent);

        $expected = $expectedTrackingEvent->toArray();
        $actual = $actualTrackingEvent->toArray();

        $this->assertArraysEqual($expected, $actual, "Unexpected Tracking Event Created From Mandrill Event");
    }


    public function dataProviderForTrackingEventFormulation()
    {
        $ts = time();
        return array(
            /*------ Open Event -------*/
            array(
                $this->getMandrillOpenEvent(),
                array(),
                $this->getTrackingOpenEvent(),
                array()
            ),
            array(
                $this->getMandrillOpenEvent(),
                array('ts' => $ts),
                $this->getTrackingOpenEvent(),
                array('datetime' => gmdate("Y-m-d H:i:s", $ts))
            ),
            array(
                $this->getMandrillOpenEvent(),
                array('msg' => array('metadata' => array('customer' => 'customer-12345'))),
                $this->getTrackingOpenEvent(),
                array('customer_id' => 'customer-12345')
            ),
            array(
                $this->getMandrillOpenEvent(),
                array('msg' => array('metadata' => array('communication' => '1234567890'))),
                $this->getTrackingOpenEvent(),
                array('communication_id' => '1234567890')
            ),
            array(
                $this->getMandrillOpenEvent(),
                array('msg' => array('tags' => array('campaigns', 'transactional'))),
                $this->getTrackingOpenEvent(),
                array('tags' => 'campaigns,transactional')
            ),
            array(
                $this->getMandrillOpenEvent(),
                array('_id' => '9876543210'),
                $this->getTrackingOpenEvent(),
                array('event_id' => '9876543210')
            ),

            /*------ Click Event -------*/
            array(
                $this->getMandrillClickEvent(),
                array(),
                $this->getTrackingClickEvent(),
                array()
            ),
            array(
                $this->getMandrillClickEvent(),
                array('ts' => $ts),
                $this->getTrackingClickEvent(),
                array('datetime' => gmdate("Y-m-d H:i:s", $ts))
            ),
            array(
                $this->getMandrillClickEvent(),
                array('url' => 'http://yahoo.com/register'),
                $this->getTrackingClickEvent(),
                array('url' => 'http://yahoo.com/register'),
            ),

                    /*------ Other Miscellaneous Event Types -------*/
            array(
                $this->getMandrillClickEvent(),
                array('event' => 'spam'),
                $this->getTrackingClickEvent(),
                array('event_type' =>  TrackingEvent::TRACKING_EVENT_SPAM_COMPLAINT)
            ),
            array(
                $this->getMandrillClickEvent(),
                array('event' => 'unsub'),
                $this->getTrackingClickEvent(),
                array('event_type' =>  TrackingEvent:: TRACKING_EVENT_UNSUBSCRIBE)
            ),
        );
    }


    /**
     * Private Helper Function
     * @return array Mandrill Open Event
     */
    private function getMandrillOpenEvent()
    {
        $openEvent = array(
            'event' => 'open',
            'ts' => 1388722851,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/534.57.7 (KHTML, like Gecko)',
            'user_agent_parsed' => array(),
            'ip' => '65.190.133.223',
            'location' => array(
                'country_short' => 'US',
                'country' => 'United States',
                'region' => 'North Carolina',
                'city' => 'Raleigh',
                'latitude' => 35.7720985413,
                'longitude' => -78.6386108398,
                'postal_code' => '27601',
                'timezone' => '-04:00',
            ),
            '_id' => '769023fbf0374a6eaa499952856b58b8',
            'msg' => array(
                'ts' => 1388722764,
                '_id' => '769023fbf0374a6eaa499952856b58b8',
                'state' => 'sent',
                'subject' => 'This email was sent by Mandrill',
                'email' => 'twolf@sugarcrm.com',
                'tags' => array(),
                'opens' => array(),
                'clicks' => array(),
                'smtp_events' => array(),
                'resends' => array(),
                '_version' => 'ROv7s1N6RAqZ9zqSvA1BSA',
                'sender' => 'noreply@redherring.net',
                'template' => null,
            ),
        );

        return $openEvent;
    }

    /**
     * Private Helper Function
     * @return array Mandrill Click Event
     */
    private function getMandrillClickEvent()
    {
        $clickEvent = array(
            'event' => 'click',
            'url' => 'http://www.mycompany.com/register',
            'ts' => 1388722831,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:26.0) Gecko/20100101 Firefox/26.0',
            'user_agent_parsed' => array(),
            'ip' => '65.190.133.223',
            'location' => array(
                'country_short' => 'US',
                'country' => 'United States',
                'region' => 'North Carolina',
                'city' => 'Raleigh',
                'latitude' => 35.7720985413,
                'longitude' => -78.6386108398,
                'postal_code' => '27601',
                'timezone' => '-04:00',
            ),
            '_id' => '769023fbf0374a6eaa499952856b58b8',
            'msg' => array(
                'ts' => 1388722764,
                '_id' => '769023fbf0374a6eaa499952856b58b8',
                'state' => 'sent',
                'subject' => 'This email was sent by Mandrill',
                'email' => 'twolf@sugarcrm.com',
                'tags' => array(),
                'opens' => array(),
                'clicks' => array(),
                'smtp_events' => array(),
                'resends' => array(),
                '_version' => 'eqCoMJ-hlA7ZeU53ICqwQw',
                'sender' => 'noreply@redherring.net',
                'template' => null,
            ),
        );
        return $clickEvent;
    }

    /**
     * Private Helper Function
     * @return TrackingEvent  Open Tracking Event
     */
    private function getTrackingOpenEvent()
    {
        return TrackingEvent::fromArray(
             array(
                'id' => NULL,
                'customer_id' => NULL,
                'communication_id' => NULL,
                'datetime' => '2014-01-03 04:20:51',
                'event_type' => 'open',
                'event_id' => '769023fbf0374a6eaa499952856b58b8',
                'ip' => '65.190.133.223',
                'email' => 'twolf@sugarcrm.com',
                'url' => '',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/534.57.7 (KHTML, like Gecko)',
                'tags' => '',
                'description' => '',
                'location' => '%7B%22country_short%22%3A%22US%22%2C%22country%22%3A%22United+States%22%2C%22region%22%3A%22North+Carolina%22%2C%22city%22%3A%22Raleigh%22%2C%22latitude%22%3A35.7720985413%2C%22longitude%22%3A-78.6386108398%2C%22postal_code%22%3A%2227601%22%2C%22timezone%22%3A%22-04%3A00%22%7D',
            )
        );
    }

    /**
     * Private Helper Function
     * @return TrackingEvent  Click Tracking Event
     */
    private function getTrackingClickEvent()
    {
        return TrackingEvent::fromArray(
            array (
                'id' => NULL,
                'customer_id' => '',
                'communication_id' => '',
                'datetime' => '2014-01-03 04:20:31',
                'event_type' => 'click',
                'event_id' => '769023fbf0374a6eaa499952856b58b8',
                'ip' => '65.190.133.223',
                'email' => 'twolf@sugarcrm.com',
                'url' => 'http://www.mycompany.com/register',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:26.0) Gecko/20100101 Firefox/26.0',
                'tags' => '',
                'description' => '',
                'location' => '%7B%22country_short%22%3A%22US%22%2C%22country%22%3A%22United+States%22%2C%22region%22%3A%22North+Carolina%22%2C%22city%22%3A%22Raleigh%22%2C%22latitude%22%3A35.7720985413%2C%22longitude%22%3A-78.6386108398%2C%22postal_code%22%3A%2227601%22%2C%22timezone%22%3A%22-04%3A00%22%7D',
            )
        );
    }
}


class TestMandrillMailTrackingService extends MandrillMailTrackingService
{
    protected function send_notice($toName, $toEmail, $fromName, $fromEmail, $subject, $message) {}
    public function process_event(array $event) {}
    public function setServiceAccountInfo($api_user, $api_pass) {}

    public function test_SignalEvent(array $event, TrackingEvent &$trackingEvent=null)
    {
        parent::signalEvent($event, $trackingEvent);
    }

}
