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

            public function acceptValue(string $name, $value): bool
            {
                return true;
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

            public function acceptValue(string $name, $value): bool
            {
                return true;
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

            public function acceptValue(string $name, $value): bool
            {
                return true;
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

            public function acceptValue(string $name, $value): bool
            {
                return true;
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

            public function acceptValue(string $name, $value): bool
            {
                return true;
            }
        };

        $comment = '@multiple foo = Test, bar=42';
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertFalse($parser->hasAnnotation($single->getName()));
        $this->assertNull($parser->getAnnotation($single->getName()));

        $this->assertNull($single->value);
        $result = $parser->emplaceAnnotationIn($single);
        $this->assertFalse($result);
        $this->assertNull($single->value);
    }

    /**
     * @param string $comment
     * @param mixed  $annotationValue
     *
     * @param string $key
     *
     * @param        $propertyValue
     *
     * @dataProvider getSnakeCamelCaseComments
     */
    public function testEmplaceSnakeToCamel(string $comment, $annotationValue, string $key, $propertyValue): void
    {
        $case = new class() implements AnnotationInterface {
            public $aCamelCaseValue;

            public function getName(): string
            {
                return 'case';
            }

            public function acceptValue(string $name, $value): bool
            {
                return true;
            }
        };

        $parser = new AnnotationParser();
        $parser->parse($comment);

        $this->assertTrue($parser->hasAnnotation($case->getName()));
        $this->assertEquals([$key => $annotationValue], $parser->getAnnotation($case->getName()));
        $this->assertNull($case->aCamelCaseValue);
        $result = $parser->emplaceAnnotationIn($case);
        $this->assertNotNull($result);
        $this->assertEquals($propertyValue, $case->aCamelCaseValue);
    }

    public function getSnakeCamelCaseComments(): array
    {
        return [
            ['@case(a_camel_case_value = 42)', 42, 'a_camel_case_value', 42],
            ['@case(aCamelCaseValue = Test)', 'Test', 'aCamelCaseValue', 'Test'],
            ['@case(ACamelCaseValue = Test)', 'Test', 'ACamelCaseValue', 'Test'],
            ['@case(a-camel-case-value = 3.14)', 3.14, 'a-camel-case-value', 3.14],
            ['@case(A-Camel-Case-Value = 3.14)', 3.14, 'A-Camel-Case-Value', 3.14],
            ['@case(b)', true, 'b', null], // 'cause 'b' does not exist
            ['@case(a Camel Case Value = false)', true, 'a', null], // 'cause only 'a' is parsed but 'a' does not exist
            ['@case(a-Camel-Case-Value = 42)', 42, 'a-Camel-Case-Value', null], // 'cause it's an invalid case
            ['@case(a_Camel_Case_Value = Foo)', 'Foo', 'a_Camel_Case_Value', null], // 'cause it's an invalid case
        ];
    }

    public function testMultipleAnnotations(): void
    {
        $comment = implode(PHP_EOL, [
            '@alias a',
            '@alias b',
            '@alias c',
            '@alias d',
        ]);

        $parser = new AnnotationParser();
        $parser->parse($comment);

        $this->assertEquals(['a', 'b', 'c', 'd'], $parser->getAnnotation('alias'));

        $alias             = new class() implements AnnotationInterface {
            public $values = [];

            public function getName(): string
            {
                return 'alias';
            }

            public function acceptValue(string $name, $value): bool
            {
                return true;
            }
        };

        $parser->emplaceAnnotationIn($alias);
        $this->assertEquals(['a', 'b', 'c', 'd'], $alias->values);

        $comment = implode(PHP_EOL, [
            '@alias(test = a)',
            '@alias(test = b)',
            '@alias(a = Foo, b = false)',
            '@alias(my = c)',
            '@alias(d)',
        ]);

        $parser = new AnnotationParser();
        $parser->parse($comment);

        $this->assertEquals(['test' => 'b', 'my' => 'c', 'a' => 'Foo', 'b' => false, 'd' => true], $parser->getAnnotation('alias'));
    }

    public function testWithAndWithoutProperties(): void
    {
        $comment = "@rename(deserialize = Foo)\n@rename Bar";
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $rename = new class() implements AnnotationInterface {
            public $value;
            public $deserialize;

            public function getName(): string
            {
                return 'rename';
            }

            public function acceptValue(string $name, $value): bool
            {
                return true;
            }
        };

        $parser->emplaceAnnotationIn($rename);

        $this->assertEquals('Bar', $rename->value);
        $this->assertEquals('Foo', $rename->deserialize);
    }

    public function testWithoutAndWithProperties(): void
    {
        $comment = "@rename Bar\n@rename(serialize = Foo)";
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $rename = new class() implements AnnotationInterface {
            public $value;
            public $serialize;

            public function getName(): string
            {
                return 'rename';
            }

            public function acceptValue(string $name, $value): bool
            {
                return true;
            }
        };

        $parser->emplaceAnnotationIn($rename);

        $this->assertEquals('Bar', $rename->value);
        $this->assertEquals('Foo', $rename->serialize);
    }

    public function testAcceptValue(): void
    {
        $comment = "@rename\n@rename(serialize)";
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $rename = new class() implements AnnotationInterface {
            public $value;
            public $serialize;

            public function getName(): string
            {
                return 'rename';
            }

            public function acceptValue(string $name, $value): bool
            {
                return is_string($value) && trim($value) !== '';
            }
        };

        $parser->emplaceAnnotationIn($rename);

        $this->assertNull($rename->value);
        $this->assertNull($rename->serialize);
    }

    public function testAcceptSpecificValue(): void
    {
        $comment = "@rename\n@rename(serialize)";
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $rename = new class() implements AnnotationInterface {
            public $value;
            public $serialize;

            public function getName(): string
            {
                return 'rename';
            }

            public function acceptValue(string $name, $value): bool
            {
                if ($name !== 'serialize') {
                    return true;
                }

                return is_string($value) && trim($value) !== '';
            }
        };

        $parser->emplaceAnnotationIn($rename);

        $this->assertTrue($rename->value);
        $this->assertNull($rename->serialize);
    }

    public function testDocComment(): void
    {
        $comment = "/**\n* @var int\n*/";
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertEquals('int', $parser->getAnnotation('var'));
    }

    public function testPropertyDocComment(): void
    {
        $comment = "/**\n* @property int \$id\n*/";
        $parser  = new AnnotationParser();
        $parser->parse($comment);

        $this->assertEquals('int $id', $parser->getAnnotation('property'));
    }
}
