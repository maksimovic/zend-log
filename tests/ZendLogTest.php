<?php

use PHPUnit\Framework\TestCase;

class Zend_Log_TestHelper extends Zend_Log
{
    public function initErrorHandlerMap(): void
    {
        $this->_errorHandlerMap = [
            E_NOTICE            => Zend_Log::NOTICE,
            E_USER_NOTICE       => Zend_Log::NOTICE,
            E_WARNING           => Zend_Log::WARN,
            E_CORE_WARNING      => Zend_Log::WARN,
            E_USER_WARNING      => Zend_Log::WARN,
            E_ERROR             => Zend_Log::ERR,
            E_USER_ERROR        => Zend_Log::ERR,
            E_CORE_ERROR        => Zend_Log::ERR,
            E_RECOVERABLE_ERROR => Zend_Log::ERR,
            E_DEPRECATED        => Zend_Log::DEBUG,
            E_USER_DEPRECATED   => Zend_Log::DEBUG,
        ];
        $this->_origErrorHandler = null;
    }
}

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

    private function makeLogWithErrorHandler(Zend_Log_Writer_Mock $mock): Zend_Log_TestHelper
    {
        $log = new Zend_Log_TestHelper($mock);
        $log->initErrorHandlerMap();
        return $log;
    }

    public function testErrorHandlerWarning(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = $this->makeLogWithErrorHandler($mock);

        $oldLevel = error_reporting(E_ALL);
        $log->errorHandler(E_USER_WARNING, 'test warning', __FILE__, __LINE__);
        error_reporting($oldLevel);

        $this->assertCount(1, $mock->events);
        $this->assertSame('test warning', $mock->events[0]['message']);
        $this->assertSame(Zend_Log::WARN, $mock->events[0]['priority']);
    }

    public function testErrorHandlerMapsAllPriorities(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = $this->makeLogWithErrorHandler($mock);

        $oldLevel = error_reporting(E_ALL);
        $log->errorHandler(E_USER_WARNING, 'warning', __FILE__, __LINE__);
        $log->errorHandler(E_USER_NOTICE, 'notice', __FILE__, __LINE__);
        $log->errorHandler(E_USER_ERROR, 'error', __FILE__, __LINE__);
        $log->errorHandler(E_DEPRECATED, 'deprecated', __FILE__, __LINE__);
        $log->errorHandler(E_USER_DEPRECATED, 'user_deprecated', __FILE__, __LINE__);
        error_reporting($oldLevel);

        $this->assertSame(Zend_Log::WARN, $mock->events[0]['priority']);
        $this->assertSame(Zend_Log::NOTICE, $mock->events[1]['priority']);
        $this->assertSame(Zend_Log::ERR, $mock->events[2]['priority']);
        $this->assertSame(Zend_Log::DEBUG, $mock->events[3]['priority']);
        $this->assertSame(Zend_Log::DEBUG, $mock->events[4]['priority']);
    }

    public function testErrorHandlerUnmappedPriorityDefaultsToInfo(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = $this->makeLogWithErrorHandler($mock);

        $oldLevel = error_reporting(E_ALL);
        // use an errno value that exists in error_reporting but not in the handler map
        $log->errorHandler(E_COMPILE_WARNING, 'unknown', __FILE__, __LINE__);
        error_reporting($oldLevel);

        $this->assertSame(Zend_Log::INFO, $mock->events[0]['priority']);
    }

    public function testRegisterErrorHandlerCalledTwiceIsIdempotent(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = new Zend_Log($mock);
        $result1 = $log->registerErrorHandler();
        $result2 = $log->registerErrorHandler();
        restore_error_handler();
        $this->assertSame($result1, $result2);
    }

    public function testErrorHandlerRespectsErrorReporting(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = $this->makeLogWithErrorHandler($mock);

        $oldLevel = error_reporting(0);
        $log->errorHandler(E_USER_WARNING, 'suppressed', __FILE__, __LINE__);
        error_reporting($oldLevel);

        $this->assertCount(0, $mock->events);
    }

    public function testErrorHandlerIncludesFileAndLine(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = $this->makeLogWithErrorHandler($mock);

        $oldLevel = error_reporting(E_ALL);
        $log->errorHandler(E_USER_WARNING, 'ctx test', '/some/file.php', 42);
        error_reporting($oldLevel);

        $this->assertSame('/some/file.php', $mock->events[0]['file']);
        $this->assertSame(42, $mock->events[0]['line']);
    }

    public function testLogWithMagicMethodAndExtras(): void
    {
        $this->log->warn('warning msg', ['extra_key' => 'extra_val']);
        $this->assertSame('extra_val', $this->mock->events[0]['extra_key']);
    }

    public function testFactoryWithWriterConfig(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zend_log_factory_');
        try {
            $log = Zend_Log::factory([
                ['writerName' => 'Stream', 'writerParams' => ['stream' => $file]],
            ]);
            $log->info('factory test');
            $this->assertInstanceOf(Zend_Log::class, $log);
            unset($log);
            $content = file_get_contents($file);
            $this->assertStringContainsString('factory test', $content);
        } finally {
            @unlink($file);
        }
    }

    public function testFactoryWithMultipleWriters(): void
    {
        $file1 = tempnam(sys_get_temp_dir(), 'zend_log_f1_');
        $file2 = tempnam(sys_get_temp_dir(), 'zend_log_f2_');
        try {
            $log = Zend_Log::factory([
                ['writerName' => 'Stream', 'writerParams' => ['stream' => $file1]],
                ['writerName' => 'Stream', 'writerParams' => ['stream' => $file2]],
            ]);
            $log->info('multi');
            unset($log);
            $this->assertStringContainsString('multi', file_get_contents($file1));
            $this->assertStringContainsString('multi', file_get_contents($file2));
        } finally {
            @unlink($file1);
            @unlink($file2);
        }
    }

    public function testFactoryThrowsOnEmptyConfig(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        Zend_Log::factory([]);
    }

    public function testFactoryThrowsOnNonArrayConfig(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        Zend_Log::factory('invalid');
    }

    public function testFactoryWithTimestampFormat(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zend_log_ts_');
        try {
            $log = Zend_Log::factory([
                'timestampFormat' => 'Y-m-d',
                ['writerName' => 'Stream', 'writerParams' => ['stream' => $file]],
            ]);
            $log->info('ts test');
            $this->assertSame('Y-m-d', $log->getTimestampFormat());
        } finally {
            @unlink($file);
        }
    }
}
