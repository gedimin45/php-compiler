<?php

namespace PHPCompiler\Cgen;

final class FunctionCall
{
    private string $name;
    private array $args;

    public function __construct(string $name, array $args)
    {
        $this->name = $name;
        $this->args = $args;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function args(): array
    {
        return $this->args;
    }
}
