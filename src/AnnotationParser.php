<?php

declare(strict_types=1);

namespace Dgame\Annotation;

/**
 * Class AnnotationParser
 * @package Dgame\Annotation
 */
final class AnnotationParser
{
    private const PROPERTY_PATTERN   = '/(?<name>\w+)(?:\s*=\s*(?<value>.+?)(?:\s*,\s*|\z))?/S';
    private const ANNOTATION_PATTERN = '/@(?<name>\w+)(?:\s+(?<value>.+)|\((?<properties>.+?)\))?/S';

    /**
     * @var array<string, mixed>
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
                $this->annotations[$name] = self::getValue($matches, 'value');
            }
        }
    }

    /**
     * @param string $content
     * @param string $name
     */
    private function parseProperties(string $content, string $name): void
    {
        if (preg_match_all(self::PROPERTY_PATTERN, $content, $properties, PREG_SET_ORDER) === 0) {
            return;
        }

        foreach ($properties ?? [] as $property) {
            $this->annotations[$name][$property['name']] = self::getValue($property, 'value');
        }
    }

    /**
     * @param array  $values
     * @param string $key
     *
     * @return bool|mixed
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
     * @return AnnotationInterface|null
     */
    public function emplaceAnnotationIn(AnnotationInterface $annotationObject): ?AnnotationInterface
    {
        $name = $annotationObject->getName();
        if (!$this->hasAnnotation($name)) {
            return null;
        }

        $setter = new AnnotationPropertySetter($annotationObject);
        $setter->emplaceAnnotation($this->getAnnotation($name));

        return $annotationObject;
    }
}
