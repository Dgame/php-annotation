<?php

declare(strict_types=1);

namespace Dgame\Annotation;

use ReflectionObject;
use ReflectionProperty;

/**
 * Class AnnotationPropertySetter
 * @package Dgame\Annotation
 */
final class AnnotationPropertySetter
{
    private const DEFAULT_PROPERTY = 'value';

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
            $this->setAnnotationProperties([self::DEFAULT_PROPERTY => $annotation]);
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
        foreach ($this->properties as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $annotation)) {
                $this->setAnnotationProperty($property, $annotation[$name]);
            }
        }
    }
}
