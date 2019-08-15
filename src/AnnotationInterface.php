<?php

declare(strict_types=1);

namespace Dgame\Annotation;

/**
 * Interface AnnotationInterface
 */
interface AnnotationInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    public function acceptValue(string $name, $value): bool;
}
