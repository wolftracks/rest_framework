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
require_once("sugarcrm/services/TrackingService/MailTrackingService.php");

class MandrillMailTrackingService extends MailTrackingService
{
    const MANDRILL_UNSUBSCRIBE_CLICK_URL = 'http://mandrillapp.com/track/unsub.php';

    static $event_map = array(
        // 'send'  => TrackingEvent::TRACKING_EVENT_SEND,
        'open' => TrackingEvent::TRACKING_EVENT_OPEN,
        'click' => TrackingEvent::TRACKING_EVENT_CLICK,
        'hard_bounce' => TrackingEvent::TRACKING_EVENT_HARD_BOUNCE,
        'soft_bounce' => TrackingEvent::TRACKING_EVENT_SOFT_BOUNCE,
        'reject' => TrackingEvent::TRACKING_EVENT_REJECT,
        'spam' => TrackingEvent::TRACKING_EVENT_SPAM_COMPLAINT,
        'unsub' => TrackingEvent::TRACKING_EVENT_UNSUBSCRIBE,
    );

    protected $service_account_user;
    protected $service_account_pass;

    protected $unsubscribe_click_url;
    protected $unsubscribe_click_url_length;


    /**
     * Call MailTrackingService constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->unsubscribe_click_url = self::MANDRILL_UNSUBSCRIBE_CLICK_URL;
        $this->unsubscribe_click_url_length = strlen($this->unsubscribe_click_url);
    }

    /**
     * @param string $service_account_user
     * @param string $service_account_pass
     */
    public function setServiceAccountInfo($service_account_user, $service_account_pass)
    {
        $this->service_account_user = $service_account_user;
        $this->service_account_pass = $service_account_pass;
    }

    /**
     * @param array $event  -  Mandrill Event
     */
    public function process_event(array $event)
    {
        if ($this->send_event_notice || $event['event'] == 'send') {
            $this->send_notice(
                'Tim Wolf',
                'twolf@sugarcrm.com',
                'Mandrill Service',
                'sugar@wolftracks.org',
                'Mandrill Email Stats',
                print_r($event, true)
            );
        }

        $event_type = empty($event['event']) ? '' : $event['event'];
        if (empty(self::$event_map[$event_type])) {
            // This Event is Not Being Tracked
            return false;
        }

        if (self::$event_map[$event['event']] === TrackingEvent::TRACKING_EVENT_CLICK) {
            if ($this->filterClickEvent($event)) {
                // This Event will Not Being Tracked
                return false;
            }
        }

        return $this->signalEvent($event);
    }

    /**
     * @param array $event  -  Mandrill Event
     * @param TrackingEvent  $trackingEvent  -  Tracking Event
     * @return bool - true = Event successfully added
     */
    protected function signalEvent(array $event, TrackingEvent &$trackingEvent = null)
    {
        if ($trackingEvent == null) {
            $trackingEvent = new TrackingEvent();
        }

        $msg = empty($event['msg']) ? array() : $event['msg'];
        $trackingEvent->customer_id = empty($msg['metadata']['customer']) ? '' : $msg['metadata']['customer'];
        $trackingEvent->communication_id = empty($msg['metadata']['communication']) ? '' : $msg['metadata']['communication'];
        $trackingEvent->datetime = empty($event['ts']) ? '' : gmdate("Y-m-d H:i:s", $event['ts']);
        $trackingEvent->event_type = self::$event_map[$event['event']];
        $trackingEvent->event_id = empty($event['_id']) ? '' : $event['_id'];
        $trackingEvent->ip = empty($event['ip']) ? '' : $event['ip'];
        $trackingEvent->email = empty($msg['email']) ? '' : $msg['email'];
        $trackingEvent->url = empty($event['url']) ? '' : $event['url'];
        $trackingEvent->user_agent = empty($event['user_agent']) ? '' : $event['user_agent'];
        $trackingEvent->tags = empty($msg['tags']) ? '' : implode(',', $msg['tags']);
        $trackingEvent->description = empty($msg['description']) ? '' : $msg['description'];
        $trackingEvent->location = empty($event['location']) ? '' : urlencode(json_encode($event['location']));

        return array($this->addTrackingEvent($trackingEvent), $trackingEvent);
    }

    /**
     * @param array $event  -  Mandrill Event
     * @return bool - true = Filter this Event
     */
    protected function filterClickEvent(array $event, TrackingEvent &$trackingEvent = null)
    {
        $url = empty($event['url']) ? '' : $event['url'];
        if (strlen($url) > $this->unsubscribe_click_url_length &&
            substr($url, 0, $this->unsubscribe_click_url_length) === $this->unsubscribe_click_url
        ) {
            return true; // This is a Mandrill Unsubscribe Clieck Ebent - Don't Track These
        }
        return false;
    }
}
