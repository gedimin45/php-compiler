<?php

namespace PHPCompiler\Cgen;

final class VariableDefinition
{
    private string $type;
    private $value;
    private string $name;

    public function __construct(string $type, string $name, $value)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value()
    {
        return $this->value;
    }

    public function name(): string
    {
        return $this->name;
    }
}
