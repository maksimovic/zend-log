<?php

use PHPUnit\Framework\TestCase;

class ZendLogFormatterTest extends TestCase
{
    public function testSimpleFormatterDefault(): void
    {
        $formatter = new Zend_Log_Formatter_Simple();
        $output = $formatter->format([
            'timestamp' => '2026-01-01',
            'message' => 'hello',
            'priority' => 6,
            'priorityName' => 'INFO',
        ]);
        $this->assertStringContainsString('hello', $output);
        $this->assertStringContainsString('INFO', $output);
        $this->assertStringContainsString('2026-01-01', $output);
    }

    public function testSimpleFormatterCustomFormat(): void
    {
        $formatter = new Zend_Log_Formatter_Simple('%message% - %priorityName%');
        $output = $formatter->format([
            'timestamp' => '2026-01-01',
            'message' => 'test',
            'priority' => 6,
            'priorityName' => 'INFO',
        ]);
        $this->assertSame('test - INFO', $output);
    }

    public function testSimpleFormatterHandlesArrayValues(): void
    {
        $formatter = new Zend_Log_Formatter_Simple('%message% %extra%');
        $output = $formatter->format([
            'message' => 'test',
            'extra' => ['nested' => 'data'],
        ]);
        $this->assertStringContainsString('array', $output);
    }

    public function testSimpleFormatterHandlesObjectWithoutToString(): void
    {
        $formatter = new Zend_Log_Formatter_Simple('%message% %extra%');
        $output = $formatter->format([
            'message' => 'test',
            'extra' => new stdClass(),
        ]);
        $this->assertStringContainsString('object', $output);
    }

    public function testSimpleFormatterThrowsOnNonStringFormat(): void
    {
        $this->expectException(Zend_Log_Exception::class);
        new Zend_Log_Formatter_Simple(123);
    }

    public function testXmlFormatter(): void
    {
        $formatter = new Zend_Log_Formatter_Xml();
        $output = $formatter->format([
            'timestamp' => '2026-01-01',
            'message' => 'xml test',
            'priority' => 6,
            'priorityName' => 'INFO',
        ]);
        $this->assertStringContainsString('<logEntry>', $output);
        $this->assertStringContainsString('xml test', $output);
    }
}
