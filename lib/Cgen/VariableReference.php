<?php

namespace PHPCompiler\Cgen;

final class VariableReference
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }
}
