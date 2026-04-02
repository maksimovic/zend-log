<?php

use PHPUnit\Framework\TestCase;

class ZendLogTest extends TestCase
{
    private Zend_Log $log;
    private Zend_Log_Writer_Mock $mock;

    protected function setUp(): void
    {
        $this->mock = new Zend_Log_Writer_Mock();
        $this->log = new Zend_Log($this->mock);
    }

    public function testConstructorWithoutWriter(): void
    {
        $log = new Zend_Log();
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testLogWithValidPriority(): void
    {
        $this->log->log('test message', Zend_Log::INFO);
        $this->assertCount(1, $this->mock->events);
        $this->assertSame('test message', $this->mock->events[0]['message']);
        $this->assertSame(Zend_Log::INFO, $this->mock->events[0]['priority']);
    }

    public function testLogThrowsOnBadPriority(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        $this->log->log('message', 999);
    }

    public function testLogThrowsWithNoWriters(): void
    {
        $log = new Zend_Log();
        $this->expectException(Zend_Log_Exception::class);
        $log->log('message', Zend_Log::INFO);
    }

    public function testMagicMethodsForPriorities(): void
    {
        $this->log->emerg('emergency');
        $this->log->alert('alert');
        $this->log->crit('critical');
        $this->log->err('error');
        $this->log->warn('warning');
        $this->log->notice('notice');
        $this->log->info('info');
        $this->log->debug('debug');

        $this->assertCount(8, $this->mock->events);
        $this->assertSame(Zend_Log::EMERG, $this->mock->events[0]['priority']);
        $this->assertSame(Zend_Log::DEBUG, $this->mock->events[7]['priority']);
    }

    public function testMagicMethodThrowsOnBadPriority(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        $this->log->nonexistentpriority('message');
    }

    public function testMagicMethodThrowsWithNoMessage(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        $this->log->info();
    }

    public function testAddWriter(): void
    {
        $mock2 = new Zend_Log_Writer_Mock();
        $this->log->addWriter($mock2);
        $this->log->info('test');
        $this->assertCount(1, $this->mock->events);
        $this->assertCount(1, $mock2->events);
    }

    public function testAddWriterThrowsOnInvalid(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        $this->log->addWriter(new stdClass());
    }

    public function testAddPriority(): void
    {
        $this->log->addPriority('CUSTOM', 8);
        $this->log->log('custom message', 8);
        $this->assertSame('CUSTOM', $this->mock->events[0]['priorityName']);
    }

    public function testAddPriorityThrowsOnDuplicate(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        $this->log->addPriority('INFO', 6);
    }

    public function testLogWithExtrasArray(): void
    {
        $this->log->log('msg', Zend_Log::INFO, ['foo' => 'bar']);
        $this->assertSame('bar', $this->mock->events[0]['foo']);
    }

    public function testLogWithExtrasScalar(): void
    {
        $this->log->log('msg', Zend_Log::INFO, 'extra info');
        $this->assertSame('extra info', $this->mock->events[0]['info']);
    }

    public function testSetEventItem(): void
    {
        $this->log->setEventItem('requestId', 'abc123');
        $this->log->info('test');
        $this->assertSame('abc123', $this->mock->events[0]['requestId']);
    }

    public function testSetTimestampFormat(): void
    {
        $this->log->setTimestampFormat('Y-m-d');
        $this->assertSame('Y-m-d', $this->log->getTimestampFormat());
        $this->log->info('test');
        $this->assertSame(date('Y-m-d'), $this->mock->events[0]['timestamp']);
    }

    public function testDefaultTimestampFormat(): void
    {
        $this->assertSame('c', $this->log->getTimestampFormat());
    }

    public function testPriorityConstants(): void
    {
        $this->assertSame(0, Zend_Log::EMERG);
        $this->assertSame(1, Zend_Log::ALERT);
        $this->assertSame(2, Zend_Log::CRIT);
        $this->assertSame(3, Zend_Log::ERR);
        $this->assertSame(4, Zend_Log::WARN);
        $this->assertSame(5, Zend_Log::NOTICE);
        $this->assertSame(6, Zend_Log::INFO);
        $this->assertSame(7, Zend_Log::DEBUG);
    }

    public function testDestructor(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = new Zend_Log($mock);
        unset($log);
        $this->assertTrue($mock->shutdown);
    }
}
