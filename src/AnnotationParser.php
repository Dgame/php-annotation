<?php

declare(strict_types=1);

namespace Dgame\Annotation;

/**
 * Class AnnotationParser
 * @package Dgame\Annotation
 */
final class AnnotationParser
{
    private const ANNOTATION_PATTERN = '/@(?<name>\w+)(?:\s+(?<value>.+)|\((?<properties>.+?)\))?/S';
    private const PROPERTY_PATTERN   = '/(?<name>[\w-]+)(?:\s*=\s*(?<value>.+))?/S';

    /**
     * @var array<string, array<string|int, mixed>>
     */
    private $annotations = [];

    /**
     * @param string $comment
     */
    public function parse(string $comment): void
    {
        $offset = 0;
        while (preg_match(self::ANNOTATION_PATTERN, $comment, $matches, 0, $offset) === 1) {
            $offset += strlen($matches[0]);
            $name   = $matches['name'];
            if (array_key_exists('properties', $matches)) {
                $this->parseProperties($matches['properties'], $name);
            } else {
                $this->setAnnotation($name, $matches);
            }
        }
    }

    /**
     * @param string $name
     * @param array $matches
     */
    private function setAnnotation(string $name, array $matches): void
    {
        $value = self::getValue($matches, 'value');
        if (array_key_exists($name, $this->annotations)) {
            $this->appendAnnotation($name, $value);
        } else {
            $this->annotations[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    private function appendAnnotation(string $name, $value): void
    {
        $annotation = $this->getAnnotation($name);
        if (!is_array($annotation)) {
            $this->annotations[$name] = [$annotation];
        }

        $this->annotations[$name][] = $value;
    }

    /**
     * @param string $properties
     * @param string $name
     */
    private function parseProperties(string $properties, string $name): void
    {
        foreach (explode(',', $properties) as $property) {
            if (preg_match(self::PROPERTY_PATTERN, trim($property), $matches) !== 0) {
                $propertyName = trim($matches['name']);

                $this->annotations[$name][$propertyName] = self::getValue($matches, 'value');
            }
        }
    }

    /**
     * @param array  $values
     * @param string $key
     *
     * @return mixed
     */
    private static function getValue(array $values, string $key)
    {
        return array_key_exists($key, $values) ? ValueInterpreter::for(trim($values[$key]))->getValue() : true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAnnotation(string $name): bool
    {
        return array_key_exists($name, $this->annotations);
    }

    /**
     * @param string $name
     *
     * @return array|mixed
     */
    public function getAnnotation(string $name)
    {
        return $this->annotations[$name] ?? null;
    }

    /**
     * @param AnnotationInterface $annotationObject
     *
     * @return bool
     */
    public function emplaceAnnotationIn(AnnotationInterface $annotationObject): bool
    {
        $name = $annotationObject->getName();
        if (!$this->hasAnnotation($name)) {
            return false;
        }

        $setter = new AnnotationPropertySetter($annotationObject);
        $setter->emplaceAnnotation($this->getAnnotation($name));

        return true;
    }
}
