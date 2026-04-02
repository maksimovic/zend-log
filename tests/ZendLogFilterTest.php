<?php

use PHPUnit\Framework\TestCase;

class ZendLogFilterTest extends TestCase
{
    public function testPriorityFilterAccepts(): void
    {
        $filter = new Zend_Log_Filter_Priority(Zend_Log::WARN);
        $this->assertTrue($filter->accept(['priority' => Zend_Log::ERR]));
        $this->assertTrue($filter->accept(['priority' => Zend_Log::WARN]));
    }

    public function testPriorityFilterRejects(): void
    {
        $filter = new Zend_Log_Filter_Priority(Zend_Log::WARN);
        $this->assertFalse($filter->accept(['priority' => Zend_Log::NOTICE]));
        $this->assertFalse($filter->accept(['priority' => Zend_Log::DEBUG]));
    }

    public function testPriorityFilterCustomOperator(): void
    {
        $filter = new Zend_Log_Filter_Priority(Zend_Log::WARN, '==');
        $this->assertTrue($filter->accept(['priority' => Zend_Log::WARN]));
        $this->assertFalse($filter->accept(['priority' => Zend_Log::ERR]));
    }

    public function testPriorityFilterThrowsOnNonInt(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        new Zend_Log_Filter_Priority('not an int');
    }

    public function testMessageFilterAccepts(): void
    {
        $filter = new Zend_Log_Filter_Message('/error/i');
        $this->assertTrue($filter->accept(['message' => 'An Error occurred']));
    }

    public function testMessageFilterRejects(): void
    {
        $filter = new Zend_Log_Filter_Message('/error/i');
        $this->assertFalse($filter->accept(['message' => 'All good']));
    }

    public function testMessageFilterThrowsOnInvalidRegex(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        new Zend_Log_Filter_Message('/invalid[');
    }

    public function testSuppressFilter(): void
    {
        $filter = new Zend_Log_Filter_Suppress();
        $this->assertTrue($filter->accept(['message' => 'test']));
        $filter->suppress(true);
        $this->assertFalse($filter->accept(['message' => 'test']));
        $filter->suppress(false);
        $this->assertTrue($filter->accept(['message' => 'test']));
    }

    public function testAddFilterToLog(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = new Zend_Log($mock);
        $log->addFilter(Zend_Log::WARN);
        $log->info('should be filtered');
        $log->warn('should pass');
        $this->assertCount(1, $mock->events);
        $this->assertSame('should pass', $mock->events[0]['message']);
    }

    public function testAddFilterObjectToLog(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = new Zend_Log($mock);
        $log->addFilter(new Zend_Log_Filter_Message('/important/'));
        $log->info('not relevant');
        $log->info('important stuff');
        $this->assertCount(1, $mock->events);
        $this->assertSame('important stuff', $mock->events[0]['message']);
    }

    public function testAddFilterThrowsOnInvalid(): void
    {
        $log = new Zend_Log(new Zend_Log_Writer_Mock());
        $this->expectException(Zend_Log_Exception::class);
        $log->addFilter(new stdClass());
    }
}
