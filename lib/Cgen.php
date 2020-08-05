<?php

/*
 * This file is part of PHP-Compiler, a PHP CFG Compiler for PHP code
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCompiler;

use PHPCfg\Operand;
use PHPCompiler\Cgen\ArrayAccess;
use PHPCompiler\Cgen\FunctionCall;
use PHPCompiler\Cgen\VariableDefinition;
use PHPCompiler\Cgen\VariableReference;

class Cgen
{
    private array $queue = [];

    /** @var VariableDefinition[] */
    private array $variableDefs = [];
    private array $functionCalls = [];

    public function __construct()
    {
    }

    public function compile(Block $block)
    {
        $this->compileBlock($block);
        $this->runQueue();

        return array_merge($this->variableDefs, $this->functionCalls);
    }


    private function runQueue(): void
    {
        while (!empty($this->queue)) {
            $run = array_shift($this->queue);
            $this->compileBlockInternal($run[0]);
        }
    }

    private function compileBlock(Block $block, ?string $funcName = null)
    {
        $this->queue[] = [$block];
    }

    private function compileBlockInternal(
        Block $block
    ) {
//        if ($this->context->scope->blockStorage->contains($block)) {
//            return $this->context->scope->blockStorage[$block];
//        }
//        $origBasicBlock = $basicBlock = $func->appendBasicBlock('block_' . self::$blockNumber);
//        $this->context->scope->blockStorage[$block] = $basicBlock;
//        $builder = $this->context->builder;
//        $builder->positionAtEnd($basicBlock);
        // Handle hoisted variables
        foreach ($block->orig->hoistedOperands as $operand) {
//            $this->context->makeVariableFromOp($func, $basicBlock, $block, $operand);
        }

        for ($i = 0, $length = count($block->opCodes); $i < $length; $i++) {
            $op = $block->opCodes[$i];
            switch ($op->type) {
                case OpCode::TYPE_ASSIGN:
                    // array variables are defined in TYPE_INIT_ARRAY
                    // TYPE_ASSIGN($10, $11, $7)
                    if (array_key_exists($op->arg3, $this->variableDefs)) {
                        // TODO eliminate temporary variables

                        $targetVariable = $this->variableDefs[$op->arg3];

                        if ($targetVariable->type() === 'string_array') {
                            $this->variableDefs[$op->arg2] = new \PHPCompiler\Cgen\VariableDefinition(
                                $targetVariable->type(),
                                'var_' . $op->arg2,
                                $targetVariable->value(),
                            );
                        } else {
                            $this->variableDefs[$op->arg2] = new \PHPCompiler\Cgen\VariableDefinition(
                                $targetVariable->type() . '_ref',
                                'var_' . $op->arg2,
                                'var_' . $op->arg3,
                            );
                        }

                        break;
                    }

                    // TYPE_ASSIGN($3, $4, LITERAL('abc'))
                    // TYPE_ECHO($4, null, null)

                    $value = $this->unrollTemporary($block->getOperand($op->arg3))->value;

                    $this->variableDefs[$op->arg2] = new \PHPCompiler\Cgen\VariableDefinition(
                        gettype($value),
                        'var_' . $op->arg2,
                        $value
                    );
                    break;

                case OpCode::TYPE_ECHO:
                case OpCode::TYPE_PRINT:
//                    TYPE_ECHO($12, null, null)

                    $argOffset = $op->type === OpCode::TYPE_ECHO ? $op->arg1 : $op->arg2;
                    $arg = $block->getOperand($argOffset);

                    if ($arg instanceof Operand\Literal) {
                        if (is_string($arg->value)) {
                            $format = '%s';
                            $element = str_replace("\n", '\n', $arg->value);
                        } else {
                            $format = '%d';
                            $element = $arg->value;
                        }
                        $this->functionCalls[] = new FunctionCall('printf', [$format, $element]);
                        break;
                    }

                    $var = $this->variableDefs[$argOffset];
                    switch ($var->type()) {
                        case 'string':
                            $format = "%s";
                            break;

                        case 'integer':
                            $format = "%d";
                            break;
                    }

                    $this->functionCalls[] = new FunctionCall(
                        'printf',
                        [$format, new VariableReference('var_' . $argOffset)]
                    );

                    break;

                case OpCode::TYPE_INIT_ARRAY:
//                TYPE_INIT_ARRAY($7, LITERAL(6), null)

                    $element = $this->unrollTemporary($block->getOperand($op->arg2))->value;

                    $this->variableDefs[$op->arg1] = new \PHPCompiler\Cgen\VariableDefinition(
                        gettype($element) . '_array',
                        'var_' . $op->arg1,
                        [$element],
                    );

                    break;

                case OpCode::TYPE_ADD_ARRAY_ELEMENT:
//                TYPE_ADD_ARRAY_ELEMENT($7, LITERAL(8), null)

                    $element = $this->unrollTemporary($block->getOperand($op->arg2))->value;

                    $array = $this->variableDefs[$op->arg1]->value();
                    $array[] = $element;
                    $this->variableDefs[$op->arg1] = new \PHPCompiler\Cgen\VariableDefinition(
                        gettype($element) . '_array',
                        'var_' . $op->arg1,
                        $array,
                    );

                    break;

                case OpCode::TYPE_ARRAY_DIM_FETCH:
//                    TYPE_ADD_ARRAY_ELEMENT($7, LITERAL(8), null)
//                    TYPE_ASSIGN($10, $11, $7)
//                    TYPE_ARRAY_DIM_FETCH($12, $11, LITERAL(0))
//                    TYPE_ECHO($12, null, null)

                    $index = $block->getOperand($op->arg3)->value;

                    $type = $this->variableDefs[$op->arg2]->type() === 'integer_array_ref' ? 'integer' : 'string';

                    $this->variableDefs[$op->arg1] = new \PHPCompiler\Cgen\VariableDefinition(
                        $type,
                        'var_' . $op->arg1,
                        new ArrayAccess('var_'.$op->arg2, $index),
                    );

                    break;
            }
        }
    }

    private function unrollTemporary(Operand $original)
    {
        $depth = 0;
        while ($original instanceof Operand\Temporary && $depth < 10) {
            $original = $original->original;
            $depth++;
        }
        return $original;
    }
}
