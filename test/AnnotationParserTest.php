<?php

namespace Dgame\Test\Annotation;

use Dgame\Annotation\AnnotationInterface;
use Dgame\Annotation\AnnotationParser;
use PHPUnit\Framework\TestCase;

class AnnotationParserTest extends TestCase
{
    /**
     * @param string $comment
     * @param string $name
     * @param mixed  $value
     *
     * @dataProvider getComments
     */
    public function testParse(string $comment, string $name, $value): void
    {
        $parser = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($name));
        $this->assertEquals($value, $parser->getAnnotation($name));
    }

    public function getComments(): array
    {
        return [
            ['@default 42', 'default', 42],
            ['@ignore', 'ignore', true],
            ['@rename(serialize = abc)', 'rename', ['serialize' => 'abc']],
            ['@rename(serialize = abc, deserialize=bar)', 'rename', ['serialize' => 'abc', 'deserialize' => 'bar']],
            ['@rename xyz', 'rename', 'xyz'],
            ['@deny(unknown_properties)', 'deny', ['unknown_properties' => true]],
        ];
    }

    public function testEmplaceSingleWithOneValueProperty(): void
    {
        $single = new class() implements AnnotationInterface {
            public $value;

            public function getName(): string
            {
                return 'single';
            }
        };

        $comment = '@single 3.14';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($single->getName()));
        $this->assertEquals(3.14, $parser->getAnnotation($single->getName()));

        $this->assertNull($single->value);
        $result = $parser->emplaceAnnotationIn($single);
        $this->assertNotNull($result);
        $this->assertEquals(3.14, $single->value);
    }

    public function testEmplaceSingleWithOneProperty(): void
    {
        $single = new class() implements AnnotationInterface {
            public $name;

            public function getName(): string
            {
                return 'single';
            }
        };

        $comment = '@single FooBar';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($single->getName()));
        $this->assertEquals('FooBar', $parser->getAnnotation($single->getName()));

        $this->assertNull($single->name);
        $result = $parser->emplaceAnnotationIn($single);
        $this->assertNotNull($result);
        $this->assertEquals('FooBar', $single->name);
    }

    public function testEmplaceProperties(): void
    {
        $single = new class() implements AnnotationInterface {
            public $foo;
            public $bar;

            public function getName(): string
            {
                return 'multiple';
            }
        };

        $comment = '@multiple(foo = Test, bar=42)';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($single->getName()));
        $this->assertEquals(['foo' => 'Test', 'bar' => 42], $parser->getAnnotation($single->getName()));

        $this->assertNull($single->foo);
        $this->assertNull($single->bar);

        $result = $parser->emplaceAnnotationIn($single);
        $this->assertNotNull($result);

        $this->assertEquals('Test', $single->foo);
        $this->assertEquals(42, $single->bar);
    }

    public function testEmplaceSingleOrProperties(): void
    {
        $single = new class() implements AnnotationInterface {
            public $value;
            public $foo;
            public $bar;

            public function getName(): string
            {
                return 'mixed';
            }
        };

        $comment = '@mixed(foo = Test, bar=4.2)';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($single->getName()));
        $this->assertEquals(['foo' => 'Test', 'bar' => 4.2], $parser->getAnnotation($single->getName()));

        $this->assertNull($single->value);
        $this->assertNull($single->foo);
        $this->assertNull($single->bar);

        $result = $parser->emplaceAnnotationIn($single);
        $this->assertNotNull($result);

        $this->assertEquals('Test', $single->foo);
        $this->assertEquals(4.2, $single->bar);
        $this->assertNull($single->value);

        $comment = '@mixed';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($single->getName()));
        $this->assertEquals(true, $parser->getAnnotation($single->getName()));

        $single->value = null;
        $single->foo   = null;
        $single->bar   = null;

        $result = $parser->emplaceAnnotationIn($single);
        $this->assertNotNull($result);

        $this->assertNull($single->foo);
        $this->assertNull($single->bar);
        $this->assertEquals(true, $single->value);
    }

    public function testEmplaceNoValue(): void
    {
        $single = new class() implements AnnotationInterface {
            public $value;

            public function getName(): string
            {
                return 'single';
            }
        };

        $comment = '@multiple foo = Test, bar=42';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertFalse($parser->hasAnnotation($single->getName()));
        $this->assertNull($parser->getAnnotation($single->getName()));

        $this->assertNull($single->value);
        $result = $parser->emplaceAnnotationIn($single);
        $this->assertNull($result);
        $this->assertNull($single->value);
    }
}
