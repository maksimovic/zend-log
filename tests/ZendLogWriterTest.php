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
}
