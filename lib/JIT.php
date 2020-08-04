<?php

# This file is generated, changes you make will be lost.
# Make your changes in /compiler/lib/JIT.pre instead.

/*
 * This file is part of PHP-Compiler, a PHP CFG Compiler for PHP code
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCompiler;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCompiler\Cgen\ArrayAccess;
use PHPCompiler\Cgen\FunctionCall;
use PHPCompiler\Cgen\VariableDefinition;
use PHPCompiler\Cgen\VariableReference;
use PHPCompiler\Func as CoreFunc;
use PHPCompiler\JIT\Context;
use PHPCompiler\JIT\Variable;
use PHPLLVM;
use PHPTypes\Type;

class JIT
{

    private static int $functionNumber = 0;
    private static int $blockNumber = 0;

    public int $optimizationLevel = 3;


    private array $stringConstant = [];
    private array $intConstant = [];
    private array $builtIns = [];

    private array $queue = [];

    public Context $context;

    /** @var VariableDefinition[] */
    private array $variableDefs = [];
    private array $functionCalls = [];

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function compile(Block $block)
    {
        $return = $this->compileBlock($block);
        $this->runQueue();

        return array_merge($this->variableDefs, $this->functionCalls);
    }

    public function compileFunc(CoreFunc $func): void
    {
        if ($func instanceof CoreFunc\PHP) {
            $this->compileBlock($func->block, $func->getName());
            $this->runQueue();
            return;
        } elseif ($func instanceof CoreFunc\JIT) {
            // No need to do anything, already compiled
            return;
        } elseif ($func instanceof CoreFunc\Internal) {
            $this->context->functionProxies[strtolower($func->getName())] = $func;
            return;
        }
        throw new \LogicException("Unknown func type encountered: " . get_class($func));
    }

    private function runQueue(): void
    {
        while (!empty($this->queue)) {
            $run = array_shift($this->queue);
            $this->compileBlockInternal($run[0], $run[1], ...$run[2]);
        }
    }

    private function compileBlock(Block $block, ?string $funcName = null): PHPLLVM\Value
    {
        if (!is_null($funcName)) {
            $internalName = $funcName;
        } else {
            $internalName = "internal_" . (++self::$functionNumber);
        }
        $args = [];
        $rawTypes = [];
        $argVars = [];
        if (!is_null($block->func)) {
            $callbackType = '';
            if ($block->func->returnType instanceof Op\Type\Literal) {
                switch ($block->func->returnType->name) {
                    case 'void':
                        $callbackType = 'void';
                        break;
                    case 'int':
                        $callbackType = 'long long';
                        break;
                    case 'string':
                        $callbackType = '__string__*';
                        break;
                    default:
                        throw new \LogicException("Non-void return types not supported yet");
                }
            } else {
                $callbackType = '__value__';
            }
            $returnType = $this->context->getTypeFromString($callbackType);
            $this->context->functionReturnType[strtolower($internalName)] = $callbackType;

            $callbackType .= '(*)(';
            $callbackSep = '';
            foreach ($block->func->params as $idx => $param) {
                if (empty($param->result->usages)) {
                    // only compile for param
                    assert($param->declaredType instanceof Op\Type\Literal);
                    $rawType = Type::fromDecl($param->declaredType->name);
                } else {
                    $rawType = $param->result->type;
                }
                $type = $this->context->getTypeFromType($rawType);
                $callbackType .= $callbackSep . $this->context->getStringFromType($type);
                $callbackSep = ', ';
                $rawTypes[] = $rawType;
                $args[] = $type;
            }
            $callbackType .= ')';
        } else {
            $callbackType = 'void(*)()';
            $returnType = $this->context->getTypeFromString('void');
        }

        $isVarArgs = false;

        $func = $this->context->module->addFunction(
            $internalName,
            $this->context->context->functionType(
                $returnType,
                $isVarArgs,
                ...$args
            )
        );

        foreach ($args as $idx => $arg) {
            $argVars[] = new Variable($this->context, Variable::getTypeFromType($rawTypes[$idx]), Variable::KIND_VALUE, $func->getParam($idx));
        }

        if (!is_null($funcName)) {
            $lcname = strtolower($funcName);
            $this->context->functions[$lcname] = $func;
            if ($isVarArgs) {
                $this->context->functionProxies[$lcname] = new JIT\Call\Vararg($func, $funcName, count($args));
            } else {
                $this->context->functionProxies[$lcname] = new JIT\Call\Native($func, $funcName, $args);
            }
        }

        $this->queue[] = [$func, $block, $argVars];
        if ($callbackType === 'void(*)()') {
            $this->context->addExport($internalName, $callbackType, $block);
        }
        return $func;
    }

    private function compileBlockInternal(
        PHPLLVM\Value $func,
        Block $block,
        Variable ...$args
    ) {
//        if ($this->context->scope->blockStorage->contains($block)) {
//            return $this->context->scope->blockStorage[$block];
//        }
        self::$blockNumber++;
        $origBasicBlock = $basicBlock = $func->appendBasicBlock('block_' . self::$blockNumber);
        $this->context->scope->blockStorage[$block] = $basicBlock;
        $builder = $this->context->builder;
        $builder->positionAtEnd($basicBlock);
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
                        $targetVariable = $this->variableDefs[$op->arg3];
                        $this->variableDefs[$op->arg2] = new \PHPCompiler\Cgen\VariableDefinition(
                            'intref',
                            'var_' . $op->arg2,
                            'var_' . $op->arg3,
                        );
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
                        'array',
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
                        'array',
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

                    $this->variableDefs[$op->arg1] = new \PHPCompiler\Cgen\VariableDefinition(
                        'integer', // todo this should be determined based on array contents
                        'var_' . $op->arg1,
                        new ArrayAccess('var_'.$op->arg2, $index),
                    );

                    break;
            }
        }
    }

    private function compileClass(?Block $block, int $classId)
    {
        if ($block === null) {
            return;
        }
        foreach ($block->opCodes as $op) {
            switch ($op->type) {
                case OpCode::TYPE_DECLARE_PROPERTY:
                    $name = $block->getOperand($op->arg1);
                    assert($name instanceof Operand\Literal);
                    assert(is_null($op->arg2)); // no defaults for now
                    $type = Variable::getTypeFromType($block->getOperand($op->arg3)->type);
                    $this->context->type->object->defineProperty($classId, $name->value, $type);
                    break;
                default:
                    var_dump($op);
                    throw new \LogicException('Other class body types are not jittable for now');
            }

        }
    }

    private function assignOperand(Operand $result, Variable $value): void
    {
        if (empty($result->usages) && !$this->context->scope->variables->contains($result)) {
            return;
        }
        if (!$this->context->hasVariableOp($result)) {
            // it's a kind!
            $this->context->makeVariableFromValueOp($this->context->helper->loadValue($value), $result);
            return;
        }
        $result = $this->context->getVariableFromOp($result);
        if ($result->kind !== Variable::KIND_VARIABLE) {
            throw new \LogicException("Cannot assign to a value");
        }
        if ($value->type === $result->type) {
            $result->free();
            if ($value->type & Variable::IS_NATIVE_ARRAY) {
                // copy over the nextfreelement
                //$result->nextFreeElement = $value->nextFreeElement;
            }
            $this->context->builder->store(
                $this->context->helper->loadValue($value),
                $result->value
            );
            $result->addref();
            return;
        } elseif ($result->type === Variable::TYPE_VALUE) {
            // wrap
            $valueRef = $result->value;
            $valueFrom = $value->value;
            switch ($value->type) {
                case Variable::TYPE_NULL:
                    $this->context->builder->call(
                        $this->context->lookupFunction('__value__writeNull'),
                        $valueRef

                    );

                    return;
                case Variable::TYPE_NATIVE_LONG:
                    $this->context->builder->call(
                        $this->context->lookupFunction('__value__writeLong'),
                        $valueRef
                        , $valueFrom

                    );

                    return;
                case Variable::TYPE_NATIVE_DOUBLE:
                    $this->context->builder->call(
                        $this->context->lookupFunction('__value__writeDouble'),
                        $valueRef
                        , $valueFrom

                    );

                    return;
                default:
                    throw new \LogicException("Source type: {$value->type}");
            }
        }
        throw new \LogicException("Cannot assign operands of different types (yet): {$value->type}, {$result->type}");
    }

    private function assignOperandValue(Operand $result, PHPLLVM\Value $value): void
    {
        if (empty($result->usages) && !$this->context->scope->variables->contains($result)) {
            return;
        }
        if (!$this->context->hasVariableOp($result)) {
            // it's a kind!
            $this->context->makeVariableFromValueOp($value, $result);
            return;
        }
        $result = $this->context->getVariableFromOp($result);
        if ($result->kind !== Variable::KIND_VARIABLE) {
            throw new \LogicException("Cannot assign to a value");
        }
        $result->free();

        $this->context->builder->store(
            $value,
            $result->value
        );
        $result->addref();
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
