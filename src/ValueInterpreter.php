<?php

declare(strict_types=1);

namespace Dgame\Annotation;

/**
 * Class ValueInterpreter
 * @package Dgame\Annotation
 */
final class ValueInterpreter
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * DefaultValue constructor.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;

        $value = json_decode($this->value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->value = $value;
        }
    }

    /**
     * @param string $value
     *
     * @return ValueInterpreter
     */
    public static function for(string $value): self
    {
        return new self($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
