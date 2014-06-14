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

class TrackingEvent
{
    const TRACKING_EVENT_SEND   = 'send';
    const TRACKING_EVENT_OPEN   = 'open';
    const TRACKING_EVENT_CLICK  = 'click';
    const TRACKING_EVENT_HARD_BOUNCE  = 'hard_bounce';
    const TRACKING_EVENT_SOFT_BOUNCE  = 'soft_bounce';
    const TRACKING_EVENT_REJECT  = 'reject';
    const TRACKING_EVENT_SPAM_COMPLAINT  = 'spam_complaint';
    const TRACKING_EVENT_UNSUBSCRIBE  = 'unsubscribe';

    public $id;
    public $customer_id;
    public $communication_id;
    public $datetime;
    public $event_type;
    public $event_id;
    public $ip;
    public $email;
    public $url;
    public $user_agent;
    public $tags;
    public $description;
    public $location;

    public static function fromArray(array $params)
    {
        $trackingEvent = new TrackingEvent();

        $trackingEvent->id = empty($params['id']) ? '' : $params['id'];
        $trackingEvent->customer_id = empty($params['customer_id']) ? '' : $params['customer_id'];
        $trackingEvent->communication_id = empty($params['communication_id']) ? '' : $params['communication_id'];
        $trackingEvent->datetime = empty($params['datetime']) ? '' : $params['datetime'];
        $trackingEvent->event_type = empty($params['event_type']) ? '' : $params['event_type'];
        $trackingEvent->event_id = empty($params['event_id']) ? '' : $params['event_id'];
        $trackingEvent->ip = empty($params['ip']) ? '' : $params['ip'];
        $trackingEvent->email = empty($params['email']) ? '' : $params['email'];
        $trackingEvent->url = empty($params['url']) ? '' : $params['url'];
        $trackingEvent->user_agent = empty($params['user_agent']) ? '' : $params['user_agent'];
        $trackingEvent->tags = empty($params['tags']) ? '' : $params['tags'];
        $trackingEvent->description = empty($params['description']) ? '' : $params['description'];
        $trackingEvent->location = empty($params['location']) ? '' : $params['location'];

        return $trackingEvent;
    }

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'communication_id' => $this->communication_id,
            'datetime' => $this->datetime,
            'event_type' => $this->event_type,
            'event_id' => $this->event_id,
            'ip' => $this->ip,
            'email' => $this->email,
            'url' => $this->url,
            'user_agent' => $this->user_agent,
            'tags' => $this->tags,
            'description' => $this->description,
            'location' => $this->location
        );
    }
}
