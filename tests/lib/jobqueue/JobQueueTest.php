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

class JobQueueTest extends Sugar_PHPUnit_Framework_TestCase
{
    protected static $dbManagerClass;
    protected static $dbMethods = array(
        'connect',
        'closeStatement',
        'close',
        'query',
        'fetchAssoc',
        'fetchArray',
        'lockTables',
        'unlockTables',
        'prepare',
        'getStatement',
        'execute',
        'getSelectSet',
        'bindParameters',
        'fetchResult',
        'freeQueryResult',
        'affectedRows',
        'getLastError',
        'getErrno',
        'getAutoIncrement',
    );

    public function setUp()
    {
        self::$dbManagerClass = SugarConfig::get('database.dbManagerClassName');
        if (self::$dbManagerClass !== 'MysqliDatabaseManager') { // 'MysqliManager')
            $this->markTestSkipped('MysqliDatabaseManager is not Active - Skip these Tests');
        }
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     */
    public function testSetFinalJobStatus_ValidFinalStatus_OK()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('prepare');
        $mockDBM->expects($this->never())->method('query');

        $mockJobQueue = $this->getMock('TestJobQueue', array('setJobStatus'));
        $mockJobQueue->expects($this->once())->method('setJobStatus')
            ->will($this->returnValue(true));

        $mockJobQueue->setDBM($mockDBM);

        $result = $mockJobQueue->setFinalJobStatus('12345', JobQueue::STATUS_COMPLETE);
        $this->assertTrue($result, "Should be able to Set Final Job Status");
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     */
    public function testSetFinalJobStatus_NonFinalStatusFailure_OK()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('prepare');
        $mockDBM->expects($this->never())->method('query');

        $mockJobQueue = $this->getMock('TestJobQueue', array('setJobStatus'));
        $mockJobQueue->expects($this->never())->method('setJobStatus');

        $mockJobQueue->setDBM($mockDBM);

        $result = $mockJobQueue->setFinalJobStatus('12345', JobQueue::STATUS_PENDING);
        $this->assertFalse($result, "Should Not be able to Set a Non Final Job Status");
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     */
    public function testSetFinalJobStatus_ValidFinalStatus_setJobStatusFailed()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('prepare');
        $mockDBM->expects($this->never())->method('query');

        $mockJobQueue = $this->getMock('TestJobQueue', array('setJobStatus'));
        $mockJobQueue->expects($this->once())->method('setJobStatus')
            ->will($this->returnValue(false));

        $mockJobQueue->setDBM($mockDBM);

        $result = $mockJobQueue->setFinalJobStatus('12345', JobQueue::STATUS_COMPLETE);
        $this->assertFalse($result, "Failure Expected because setJobStatus Failed");
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     * @covers JobQueue::setJobStatus
     */
    public function testSetFinalJobStatus_ValidFinalStatus_DBPrepareFailure()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');
        $mockDBM->expects($this->once())->method('prepare')
            ->will($this->throwException(new DatabaseException));

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->setFinalJobStatus('12345', JobQueue::STATUS_COMPLETE);
        $this->assertFalse($result, "Expected Final Status Update to Fail due to Database prepare exception");
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     * @covers JobQueue::setJobStatus
     */
    public function testSetFinalJobStatus_ValidFinalStatus_DBBindFailure()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');
        $mockDBM->expects($this->once())->method('prepare')
            ->will($this->returnValue(1));
        $mockDBM->expects($this->once())->method('bindParameters')
            ->will($this->throwException(new DatabaseException));

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->setFinalJobStatus('12345', JobQueue::STATUS_COMPLETE);
        $this->assertFalse($result, "Expected Final Status Update to Fail due to Database bindParameters exception");
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     * @covers JobQueue::setJobStatus
     */
    public function testSetFinalJobStatus_ValidFinalStatus_DBExecuteFailure()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');
        $mockDBM->expects($this->once())->method('prepare')
            ->will($this->returnValue(1));
        $mockDBM->expects($this->once())->method('bindParameters')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('execute')
            ->will($this->throwException(new DatabaseException));

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->setFinalJobStatus('12345', JobQueue::STATUS_COMPLETE);
        $this->assertFalse($result, "Expected Final Status Update to Fail due to Database execute exception");
    }

    /**
     * @covers JobQueue::setFinalJobStatus
     * @covers JobQueue::setJobStatus
     */
    public function testSetFinalJobStatus_ValidFinalStatus_DBUpdate_NoRowsAffected()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');
        $mockDBM->expects($this->once())->method('prepare')
            ->will($this->returnValue(1));
        $mockDBM->expects($this->once())->method('bindParameters')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('execute')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('affectedRows')
            ->will($this->returnValue(0));

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->setFinalJobStatus('12345', JobQueue::STATUS_COMPLETE);
        $this->assertFalse($result, "Expected Final Status Update to Fail because No Rows were Affected by DB Update");
    }

    /**
     * @covers JobQueue::writeQueue
     * @covers JobQueue::createJob
     * @covers JobQueue::argRequired
     * @dataProvider dataProviderForWriteQueue_ArgumentValidation
     */
    public function testWriteQueue_ArgumentValidation(Job $job, $exceptionExpected = false) {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');

        if ($exceptionExpected) {
            $mockDBM->expects($this->never())->method('prepare');
            self::setExpectedException("JobQueueException");
        } else {
            $mockDBM->expects($this->once())->method('prepare')
                ->will($this->returnValue(1));
            $mockDBM->expects($this->once())->method('bindParameters')
                ->will($this->returnValue(true));
            $mockDBM->expects($this->once())->method('execute')
                ->will($this->returnValue(true));
            $mockDBM->expects($this->once())->method('affectedRows')
                ->will($this->returnValue(1));
        }

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->writeQueue($job);
        if (!$exceptionExpected) {
            $this->assertTrue($result, "Expected Success on writeQueue");
        }
    }

    public function dataProviderForWriteQueue_ArgumentValidation()
    {
        return array(
            array( Job::fromArray(array(
                    'job_type' => JobQueue::JOBTYPE_SENDMAIL,
                    'customer_id' => '111',
                    'payload' => '222')),
                false), // Success
            array( Job::fromArray(array(
                    'job_type' => JobQueue::JOBTYPE_SENDMAIL,
                    'customer_id' => array('abc'),
                    'payload' => '222')),
                true),  // Exception: Invalid Type: customer_id
            array( Job::fromArray(array(
                    'job_type' => JobQueue::JOBTYPE_SENDMAIL,
                    'customer_id' => null,
                    'payload' => '222')),
                true), // Exception: Argument Missing: customer_id
             array( Job::fromArray(array(
                    'job_type' => JobQueue::JOBTYPE_SENDMAIL,
                    'customer_id' => '111',
                    'payload' => null)),
                true), // Exception: Argument Missing: payload
            array( Job::fromArray(array(
                    'job_type' => null,
                    'customer_id' => '111',
                    'payload' => '222')),
                    true), // Exception: Argument Missing: job_type
            array( Job::fromArray(array(
                    'job_type' => 123.45,
                    'customer_id' => '111',
                    'payload' => '222')),
                    true), // Exception: Invalid Type: job_type
            array( Job::fromArray(array(
                    'job_type' => 'This is not a Valid Job Type',
                    'customer_id' => '111',
                    'payload' => '222')),
                true), // Exception: Invalid job_type
        );
    }

    /**
     * @covers JobQueue::writeQueue
     * @covers JobQueue::createJob
     * @covers JobQueue::argRequired
     */
    public function testWriteQueue_InsertFailure()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');
        $mockDBM->expects($this->once())->method('prepare')
            ->will($this->returnValue(1));
        $mockDBM->expects($this->once())->method('bindParameters')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('execute')
            ->will($this->throwException(new DatabaseException));

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $job = Job::fromArray(array(
                'job_type' => JobQueue::JOBTYPE_SENDMAIL,
                'customer_id' => '111',
                'communication_id' => '222',
                'payload' => '333'));

        $result = $jobQueue->writeQueue($job);
        $this->assertFalse($result, "Expected Failure on writeQueue - Insert Failed");
    }

    /**
     * @covers JobQueue::writeQueue
     * @covers JobQueue::createJob
     * @covers JobQueue::argRequired
     */
    public function testWriteQueue_NoRowsInserted()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->never())->method('query');
        $mockDBM->expects($this->once())->method('prepare')
            ->will($this->returnValue(1));
        $mockDBM->expects($this->once())->method('bindParameters')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('execute')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('affectedRows')
            ->will($this->returnValue(0));

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $job = Job::fromArray(array(
                'job_type' => JobQueue::JOBTYPE_SENDMAIL,
                'customer_id' => '111',
                'communication_id' => '222',
                'payload' => '333'));

        $result = $jobQueue->writeQueue($job);
        $this->assertFalse($result, "Expected Failure on writeQueue - No Rows Affected On Insert");
    }

    /**
     * @covers JobQueue::readQueue
     * @covers JobQueue::dequeueNextJob
     * @covers JobQueue::setJobStatus
     */
    public function testReadQueue_QueryFailedWithException_LocksRestored()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->once())->method('query')
            ->will($this->throwException(new DatabaseException));
        $mockDBM->expects($this->once())->method('lockTables');
        $mockDBM->expects($this->once())->method('unlockTables');

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->readQueue();
        $this->assertFalse($result, "Expected Failure on readQueue - Query Failed");
    }

    /**
     * @covers JobQueue::readQueue
     * @covers JobQueue::dequeueNextJob
     * @covers JobQueue::setJobStatus
     */
    public function testReadQueue_NoJobsReturnedOnQuery_LocksRestored()
    {
        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->once())->method('query')
            ->will($this->returnValue(false));
        $mockDBM->expects($this->once())->method('lockTables');
        $mockDBM->expects($this->once())->method('unlockTables');

        $jobQueue = new TestJobQueue();
        $jobQueue->setDBM($mockDBM);

        $result = $jobQueue->readQueue();
        $this->assertFalse($result, "Expected Failure on readQueue - Query Failed");
    }

    /**
     * @covers JobQueue::readQueue
     * @covers JobQueue::dequeueNextJob
     * @covers JobQueue::setJobStatus
     */
    public function testReadQueue_JobRetrieved_FailedToUpdateJobStatus()
    {
        $dbRow = $this->getAvailableJob();

        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->once())->method('query')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('lockTables');
        $mockDBM->expects($this->once())->method('unlockTables');
        $mockDBM->expects($this->once())->method('fetchAssoc')
            ->will($this->returnValue($dbRow));
        $mockDBM->expects($this->once())->method('freeQueryResult')
            ->will($this->returnValue(true));

        $mockJobQueue = $this->getMock('TestJobQueue', array('setJobStatus'));
        $mockJobQueue->expects($this->once())
            ->method('setJobStatus')
            ->will($this->returnValue(false));

        $mockJobQueue->setDBM($mockDBM);

        $job = $mockJobQueue->readQueue();
        $this->assertFalse($job, "Expected readQueue To Fail - Unable to Update Status of Fetched Job");
    }

    /**
     * @covers JobQueue::readQueue
     * @covers JobQueue::dequeueNextJob
     * @covers JobQueue::setJobStatus
     */
    public function testReadQueue_Success()
    {
        $dbRow = $this->getAvailableJob();

        $mockDBM = $this->getMock(self::$dbManagerClass, self::$dbMethods);
        $mockDBM->expects($this->once())->method('query')
            ->will($this->returnValue(true));
        $mockDBM->expects($this->once())->method('lockTables');
        $mockDBM->expects($this->once())->method('unlockTables');
        $mockDBM->expects($this->once())->method('fetchAssoc')
            ->will($this->returnValue($dbRow));
        $mockDBM->expects($this->once())->method('freeQueryResult')
            ->will($this->returnValue(true));

        $mockJobQueue = $this->getMock('TestJobQueue', array('setJobStatus'));
        $mockJobQueue->expects($this->once())
            ->method('setJobStatus')
            ->will($this->returnValue(true));

        $mockJobQueue->setDBM($mockDBM);
        $expectedResult = $mockJobQueue->dbRowToJobArray($dbRow);

        $job = $mockJobQueue->readQueue();
        $this->assertArraysEqual($expectedResult, $job->toArray(), "Expected Job Not Returned");
    }

    private function getAvailableJob($job_id = '20')
    {
        $customer_id = '1001';
        $communication_id = '2002';
        $payload = 'This is the Payload';
        $data = base64_encode(serialize($payload));

        $row = array(
            'id' => $job_id,
            'customer_id' => $customer_id,
            'communication_id' => $communication_id,
            'status' => 'pending',
            'deleted' => 0,
            'not_before' => 0,
            'retries' => 0,
            'data' => $data,
        );
        return $row;
    }
}

class TestJobQueue extends JobQueue
{
    public function setDBM($dbm)
    {
        $this->dbm = $dbm;
    }

    public function dbRowToJobArray($dbRow)
    {
        $job = $this->toJob($dbRow);
        return $job->toArray();
    }
}
