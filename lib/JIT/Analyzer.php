<?php

declare(strict_types=1);

/**
 * This file is part of PHP-Compiler, a PHP CFG Compiler for PHP code
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCompiler\JIT;

use PHPCfg\Op;
use PHPCfg\Operand;
use PHPTypes\Type;
use SplObjectStorage;

class Analyzer
{
    public function needsBoundsCheck(Variable $var, Operand $dimOp): bool
    {
        if ($dimOp instanceof Operand\Literal) {
            return false;
        }
        if (count($dimOp->ops) !== 1) {
            return true;
        }
        if ($dimOp->ops[0] instanceof Op\Expr\BinaryOp\Mod) {
            // validate that the right side is <= var->nextFreeElement
            if ($dimOp->ops[0]->right instanceof Operand\Literal && $dimOp->ops[0]->right->type->type === Type::TYPE_LONG) {
                return $dimOp->ops[0]->right->value > $var->nextFreeElement;
            }
        }

        return true;
    }

    public function canEscape(Operand $operand, ?SplObjectStorage $seen = null): bool
    {
        if (null === $seen) {
            $seen = new SplObjectStorage();
        } elseif ($seen->contains($operand)) {
            return false;
        }
        $seen->attach($operand);
        foreach ($operand->usages as $usage) {
            if ($usage instanceof Op\Expr\Assign) {
                if ($this->canEscape($usage->var, $seen) || $this->canEscape($usage->result, $seen)) {
                    return true;
                }
            } elseif ($usage instanceof Op\Expr\ArrayDimFetch || $usage instanceof Op\Phi) {
                continue;
            } else {
                throw new \LogicException('Not implemented escape operand '.get_class($usage));
            }
        }

        return false;
    }

    public function hasDynamicArrayAppend(Operand $operand, int $size, ?SplObjectStorage $seen = null): bool
    {
        if (null === $seen) {
            $seen = new SplObjectStorage();
        } elseif ($seen->contains($operand)) {
            return false;
        }
        $seen->attach($operand);
        foreach ($operand->usages as $usage) {
            if ($usage instanceof Op\Expr\Assign) {
                if ($this->hasDynamicArrayAppend($usage->var, $size, $seen) || $this->hasDynamicArrayAppend($usage->result, $size, $seen)) {
                    return true;
                }
            } elseif ($usage instanceof Op\Expr\ArrayDimFetch) {
                if (null !== $usage->dim) {
                    if (! $usage->dim instanceof Operand\Literal) {
                        if (count($usage->result->ops) > 1) {
                            // this means that it's a write, disallow it
                            return true;
                        }
//                    } elseif ($usage->dim->type->type !== Type::TYPE_LONG) {
//                        return true;
                    } elseif ($usage->dim->value >= $size) {
                        return true;
                    }
                } else {
                    return true;
                }
            } elseif ($usage instanceof Op\Phi) {
                // unsure what to do here skip for now
            } else {
                throw new \LogicException('Not implemented dynamic append operand '.get_class($usage));
            }
        }

        return false;
    }

    public function computeStaticArraySize(Operand $operand, ?SplObjectStorage $seen = null): ?int
    {
        if (null === $seen) {
            $seen = new SplObjectStorage();
        } elseif ($seen->contains($operand)) {
            return null;
        }
        $seen->attach($operand);
        $size = 0;
        foreach ($operand->ops as $op) {
            if ($op instanceof Op\Expr\Array_) {
                $newSize = 0;
                foreach ($op->keys as $key) {
                    if ($key instanceof Operand\NullOperand || $key->type->type === Type::TYPE_STRING) {
                        ++$newSize;
                    } elseif (! $key instanceof Operand\Literal || $key->type->type !== Type::TYPE_LONG) {
                        return null;
                    } elseif ($key->value >= $newSize) {
                        $newSize = $key->value + 1;
                    }
                }
                $size = max($size, $newSize);
            } elseif ($op instanceof Op\Expr\Assign) {
                $newSize = $this->computeStaticArraySize($op->expr, $seen);
                if (null === $newSize) {
                    return null;
                }
                $size = max($size, $newSize);
            } else {
                throw new \LogicException('Unknown array write op: '.get_class($op));
            }
        }

        return $size;
    }

    public function isList(Operand $operand): bool
    {
        foreach ($operand->ops as $op) {
            if ($op instanceof Op\Expr\Array_) {
                foreach ($op->keys as $key) {
                    if (!($key instanceof Operand\NullOperand) && $key->type->type !== Type::TYPE_LONG) {
                        return false;
                    }

                    return true;
                }
            } elseif ($op instanceof Op\Expr\Assign) {
                return $this->isList($op->expr);
            } else {
                throw new \LogicException('Unknown array write op: '.get_class($op));
            }
        }

        return false;
    }
}
