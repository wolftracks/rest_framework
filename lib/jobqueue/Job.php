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

/**
 * This is a Container Class for Jobs returned by JobQueue read functions
 */
class Job
{
    public static $job_fields = array(
        "job_id"     => 'string',
        "job_type"   => 'string',
        "customer_id"  => 'string',
        "payload" =>  ''
    );

    public $job_id;      // Unique Database-assigned ID For this Job
    public $job_type;    // Job Type
    public $customer_id; // Customer ID
    public $payload;     // payload


    /**
     * Return Job Info as Array
     * @return array job
     */
    public static function fromArray($array)
    {
        $job = new Job;
        foreach($array as $key => $value) {
            if (isset(self::$job_fields[$key])) {
                $job->$key = $value;
            }
        }
        return $job;
    }

    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;
    }

    public function getCustomerId()
    {
        return $this->customer_id;
    }

    protected function setJobId($job_id)
    {
        $this->job_id = $job_id;
    }

    public function getJobId()
    {
        return $this->job_id;
    }

    public function setJobType($job_type)
    {
        $this->job_type = $job_type;
    }

    public function getJobType()
    {
        return $this->job_type;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Return Job Info as Array
     * @return array job
     */
    public function toArray()
    {
        return array(
            "job_id" => $this->job_id,
            "job_type" => $this->job_type,
            "customer_id" => $this->customer_id,
            "payload" => $this->payload,
        );
    }
}
