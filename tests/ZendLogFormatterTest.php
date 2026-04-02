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

    public function testXmlFormatterCustomRoot(): void
    {
        $formatter = new Zend_Log_Formatter_Xml('customRoot');
        $output = $formatter->format(['message' => 'test']);
        $this->assertStringContainsString('<customRoot>', $output);
    }

    public function testXmlFormatterWithElementMap(): void
    {
        $formatter = new Zend_Log_Formatter_Xml([
            'rootElement' => 'entry',
            'elementMap' => ['msg' => 'message', 'lvl' => 'priority'],
        ]);
        $output = $formatter->format([
            'message' => 'mapped',
            'priority' => 6,
        ]);
        $this->assertStringContainsString('<msg>mapped</msg>', $output);
        $this->assertStringContainsString('<lvl>6</lvl>', $output);
    }

    public function testXmlFormatterEncoding(): void
    {
        $formatter = new Zend_Log_Formatter_Xml();
        $this->assertSame('UTF-8', $formatter->getEncoding());
        $formatter->setEncoding('ISO-8859-1');
        $this->assertSame('ISO-8859-1', $formatter->getEncoding());
    }

    public function testXmlFormatterFactory(): void
    {
        $formatter = Zend_Log_Formatter_Xml::factory([]);
        $this->assertInstanceOf(Zend_Log_Formatter_Xml::class, $formatter);
    }

    public function testSimpleFormatterFactory(): void
    {
        $formatter = Zend_Log_Formatter_Simple::factory(['format' => '%message%']);
        $output = $formatter->format(['message' => 'hello']);
        $this->assertSame('hello', $output);
    }

    public function testSimpleFormatterFactoryNull(): void
    {
        $formatter = Zend_Log_Formatter_Simple::factory(null);
        $this->assertInstanceOf(Zend_Log_Formatter_Simple::class, $formatter);
    }

    public function testXmlFormatterEscapesHtml(): void
    {
        $formatter = new Zend_Log_Formatter_Xml();
        $output = $formatter->format([
            'message' => '<script>alert("xss")</script>',
        ]);
        $this->assertStringNotContainsString('<script>', $output);
    }
}
