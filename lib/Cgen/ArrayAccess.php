<?php

namespace PHPCompiler\Cgen;

final class ArrayAccess
{
    private string $variableName;
    private int $index;

    public function __construct(string $variableName, int $index)
    {
        $this->variableName = $variableName;
        $this->index = $index;
    }

    public function variableName(): string
    {
        return $this->variableName;
    }

    public function index(): int
    {
        return $this->index;
    }
}
