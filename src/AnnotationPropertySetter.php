<?php

declare(strict_types=1);

namespace Dgame\Annotation;

use Jawira\CaseConverter\CaseConverterException;
use Jawira\CaseConverter\Convert;
use Jawira\CaseConverter\Glue\DashGluer;
use Jawira\CaseConverter\Glue\UnderscoreGluer;
use ReflectionObject;
use ReflectionProperty;

/**
 * Class AnnotationPropertySetter
 * @package Dgame\Annotation
 */
final class AnnotationPropertySetter
{
    private const DEFAULT_SINGLE_PROPERTY   = 'value';
    private const DEFAULT_MULTIPLE_PROPERTY = 'values';

    private const CONVERT_METHODS = [
        'toLower',
        'toSnake',
        'toKebab',
        'toAda',
        'toTrain',
        'toCamel',
        'toPascal'
    ];

    /**
     * @var AnnotationInterface
     */
    private $annotationObject;
    /**
     * @var ReflectionProperty[]
     */
    private $properties;

    public function __construct(AnnotationInterface $annotationObject)
    {
        $refl = new ReflectionObject($annotationObject);

        $this->annotationObject = $annotationObject;
        $this->properties       = $refl->getProperties();
    }

    /**
     * @param mixed $annotation
     */
    public function emplaceAnnotation($annotation): void
    {
        if (is_array($annotation)) {
            $this->setAnnotationProperties($annotation);
        } else {
            $this->setSingleProperty($annotation);
        }
    }

    /**
     * @param mixed $annotation
     */
    private function setSingleProperty($annotation): void
    {
        if (count($this->properties) === 1) {
            $this->setAnnotationProperty($this->properties[0], $annotation);
        } else {
            $this->setAnnotationProperties([self::DEFAULT_SINGLE_PROPERTY => $annotation]);
        }
    }

    /**
     * @param ReflectionProperty $property
     * @param mixed              $value
     */
    private function setAnnotationProperty(ReflectionProperty $property, $value): void
    {
        $property->setAccessible(true);
        $property->setValue($this->annotationObject, $value);
    }

    /**
     * @param array $annotation
     */
    private function setAnnotationProperties(array $annotation): void
    {
        if (self::isNumericIndexed($annotation)) {
            $this->setAnnotationProperties([self::DEFAULT_MULTIPLE_PROPERTY => $annotation]);
        } else {
            $this->assignProperties($annotation);
        }
    }

    /**
     * @param array $annotation
     */
    private function assignProperties(array $annotation): void
    {
        foreach ($this->properties as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $annotation)) {
                $this->setAnnotationProperty($property, $annotation[$name]);
            } else {
                $this->setByCaseConversion($annotation, $property);
            }
        }
    }

    /**
     * @param array              $annotation
     * @param ReflectionProperty $property
     */
    private function setByCaseConversion(array $annotation, ReflectionProperty $property): void
    {
        $name = self::findName($annotation, $property);
        if ($name !== null && array_key_exists($name, $annotation)) {
            $this->setAnnotationProperty($property, $annotation[$name]);
        }
    }

    /**
     * @param array              $annotation
     * @param ReflectionProperty $property
     *
     * @return string|null
     */
    private static function findName(array $annotation, ReflectionProperty $property): ?string
    {
        foreach (self::CONVERT_METHODS as $method) {
            $name = self::convert($property->getName(), $method);
            if (array_key_exists($name, $annotation)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $method
     *
     * @return string
     */
    private static function convert(string $name, string $method): string
    {
        try {
            /** @var callable $closure */
            $closure = [new Convert($name), $method];

            return str_replace(' ', self::getSplitter($name), $closure());
        } catch (CaseConverterException $_) {
            return $name;
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private static function getSplitter(string $name): string
    {
        if (mb_strpos($name, UnderscoreGluer::DELIMITER) !== false) {
            return UnderscoreGluer::DELIMITER;
        }

        if (mb_strpos($name, DashGluer::DELIMITER) !== false) {
            return DashGluer::DELIMITER;
        }

        return '';
    }

    /**
     * @param array $annotation
     *
     * @return bool
     */
    private static function isNumericIndexed(array $annotation): bool
    {
        return !empty(array_filter(array_keys($annotation), 'is_int'));
    }
}
