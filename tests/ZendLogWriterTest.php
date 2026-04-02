<?php

use PHPUnit\Framework\TestCase;

class ZendLogWriterTest extends TestCase
{
    public function testMockWriter(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $log = new Zend_Log($mock);
        $log->info('test');
        $this->assertCount(1, $mock->events);
        $this->assertFalse($mock->shutdown);
    }

    public function testMockWriterShutdown(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $mock->shutdown();
        $this->assertTrue($mock->shutdown);
    }

    public function testStreamWriterToFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zend_log_test_');
        try {
            $writer = new Zend_Log_Writer_Stream($file);
            $log = new Zend_Log($writer);
            $log->info('test message');
            unset($log);

            $content = file_get_contents($file);
            $this->assertStringContainsString('test message', $content);
            $this->assertStringContainsString('INFO', $content);
        } finally {
            @unlink($file);
        }
    }

    public function testStreamWriterToPhpOutput(): void
    {
        $writer = new Zend_Log_Writer_Stream('php://memory');
        $log = new Zend_Log($writer);
        $log->info('memory test');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testStreamWriterThrowsOnInvalidStream(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        new Zend_Log_Writer_Stream('/nonexistent/path/to/file.log');
    }

    public function testStreamWriterThrowsOnNonStreamResource(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        new Zend_Log_Writer_Stream(xml_parser_create());
    }

    public function testNullWriter(): void
    {
        $writer = new Zend_Log_Writer_Null();
        $log = new Zend_Log($writer);
        $log->info('goes nowhere');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testWriterWithFilter(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $mock->addFilter(new Zend_Log_Filter_Priority(Zend_Log::ERR));
        $log = new Zend_Log($mock);
        $log->info('filtered out');
        $log->err('passes through');
        $this->assertCount(1, $mock->events);
        $this->assertSame('passes through', $mock->events[0]['message']);
    }

    public function testWriterWithFormatter(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zend_log_fmt_');
        try {
            $formatter = new Zend_Log_Formatter_Simple('%message% [%priorityName%]' . PHP_EOL);
            $writer = new Zend_Log_Writer_Stream($file);
            $writer->setFormatter($formatter);
            $log = new Zend_Log($writer);
            $log->info('formatted');
            unset($log);

            $content = file_get_contents($file);
            $this->assertSame('formatted [INFO]' . PHP_EOL, $content);
        } finally {
            @unlink($file);
        }
    }

    public function testSyslogWriter(): void
    {
        $writer = new Zend_Log_Writer_Syslog(['application' => 'zend-log-test']);
        $log = new Zend_Log($writer);
        $log->info('syslog test');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testWriterAddFilterByInt(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $mock->addFilter(Zend_Log::ERR);
        $log = new Zend_Log($mock);
        $log->info('filtered');
        $log->err('not filtered');
        $this->assertCount(1, $mock->events);
    }

    public function testWriterAddFilterThrowsOnInvalid(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $this->expectException(Zend_Log_Exception::class);
        $mock->addFilter('invalid');
    }

    public function testStreamWriterFactory(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zend_log_factory_');
        try {
            $writer = Zend_Log_Writer_Stream::factory(['stream' => $file]);
            $this->assertInstanceOf(Zend_Log_Writer_Stream::class, $writer);
        } finally {
            @unlink($file);
        }
    }

    public function testMockWriterFactory(): void
    {
        $writer = Zend_Log_Writer_Mock::factory([]);
        $this->assertInstanceOf(Zend_Log_Writer_Mock::class, $writer);
    }

    public function testNullWriterFactory(): void
    {
        $writer = Zend_Log_Writer_Null::factory([]);
        $this->assertInstanceOf(Zend_Log_Writer_Null::class, $writer);
    }

    public function testMultipleFiltersOnWriter(): void
    {
        $mock = new Zend_Log_Writer_Mock();
        $mock->addFilter(new Zend_Log_Filter_Priority(Zend_Log::WARN));
        $mock->addFilter(new Zend_Log_Filter_Message('/critical/'));
        $log = new Zend_Log($mock);
        $log->warn('just a warning');
        $log->warn('critical warning');
        $log->info('critical info');
        // only "critical warning" passes both filters
        $this->assertCount(1, $mock->events);
        $this->assertSame('critical warning', $mock->events[0]['message']);
    }

    public function testStreamWriterWithResource(): void
    {
        $stream = fopen('php://memory', 'a');
        $writer = new Zend_Log_Writer_Stream($stream);
        $log = new Zend_Log($writer);
        $log->info('resource test');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testStreamWriterShutdownClosesStream(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zend_log_shut_');
        try {
            $writer = new Zend_Log_Writer_Stream($file);
            $writer->shutdown();
            // after shutdown, writing should fail
            $this->expectException(Zend_Log_Exception::class);
            $writer->write([
                'timestamp' => date('c'),
                'message' => 'after shutdown',
                'priority' => 6,
                'priorityName' => 'INFO',
            ]);
        } finally {
            @unlink($file);
        }
    }

    public function testSyslogWriterFactory(): void
    {
        $writer = Zend_Log_Writer_Syslog::factory(['application' => 'test']);
        $this->assertInstanceOf(Zend_Log_Writer_Syslog::class, $writer);
    }

    public function testSyslogWriterWithFacility(): void
    {
        $writer = new Zend_Log_Writer_Syslog([
            'application' => 'test',
            'facility' => LOG_LOCAL0,
        ]);
        $log = new Zend_Log($writer);
        $log->info('facility test');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testSyslogWriterSetApplicationName(): void
    {
        $writer = new Zend_Log_Writer_Syslog();
        $result = $writer->setApplicationName('myapp');
        $this->assertSame($writer, $result);
    }

    public function testSyslogWriterSetFacilityThrowsOnInvalid(): void
    {
        $writer = new Zend_Log_Writer_Syslog();
        $this->expectException(Zend_Log_Exception::class);
        $writer->setFacility(999999);
    }

    public function testSyslogWriterAllPriorities(): void
    {
        $writer = new Zend_Log_Writer_Syslog(['application' => 'test']);
        $log = new Zend_Log($writer);
        $log->emerg('emerg');
        $log->alert('alert');
        $log->crit('crit');
        $log->err('err');
        $log->warn('warn');
        $log->notice('notice');
        $log->info('info');
        $log->debug('debug');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testSyslogWriterWithCustomPriority(): void
    {
        $writer = new Zend_Log_Writer_Syslog(['application' => 'test']);
        $log = new Zend_Log($writer);
        $log->addPriority('CUSTOM', 8);
        $log->log('custom priority goes to default syslog priority', 8);
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testSyslogWriterWithFormatter(): void
    {
        $writer = new Zend_Log_Writer_Syslog(['application' => 'test']);
        $writer->setFormatter(new Zend_Log_Formatter_Simple('%message%'));
        $log = new Zend_Log($writer);
        $log->info('formatted syslog');
        $this->assertInstanceOf(Zend_Log::class, $log);
    }

    public function testSyslogWriterShutdown(): void
    {
        $writer = new Zend_Log_Writer_Syslog(['application' => 'test']);
        $writer->shutdown();
        $this->assertInstanceOf(Zend_Log_Writer_Syslog::class, $writer);
    }
}
