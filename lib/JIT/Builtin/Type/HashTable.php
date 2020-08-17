<?php

// This file is generated and changes you make will be lost.
// Change /Users/ged15/Projects/php-compiler/lib/JIT/Builtin/Type/HashTable.pre instead.

// This file is generated and changes you make will be lost.
// Change /compiler/lib/JIT/Builtin/Type/HashTable.pre instead.

/*
 * This file is part of PHP-Compiler, a PHP CFG Compiler for PHP code
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCompiler\JIT\Builtin\Type;

use PHPCompiler\JIT\Builtin\Type;
use PHPCompiler\JIT\Builtin\Refcount;
use PHPCompiler\JIT\Variable;
use function \PHPCompiler\debug;
use PHPLLVM;

class HashTable extends Type {
    public PHPLLVM\Type $pointer;

    public function register(): void {




        $struct___cfcd208495d565ef66e7dff9f98764da = $this->context->context->namedStructType('__hashtable__');
            // declare first so recursive structs are possible :)
            $this->context->registerType('__hashtable__', $struct___cfcd208495d565ef66e7dff9f98764da);
            $this->context->registerType('__hashtable__' . '*', $struct___cfcd208495d565ef66e7dff9f98764da->pointerType(0));
            $this->context->registerType('__hashtable__' . '**', $struct___cfcd208495d565ef66e7dff9f98764da->pointerType(0)->pointerType(0));
            $struct___cfcd208495d565ef66e7dff9f98764da->setBody(
                false ,  // packed
                $this->context->getTypeFromString('__ref__')
                , $this->context->getTypeFromString('size_t')

            );
            $this->context->structFieldMap['__hashtable__'] = [
                'ref' => 0
                , 'size' => 1

            ];





        $struct___cfcd208495d565ef66e7dff9f98764da = $this->context->context->namedStructType('__htbucket__');
            // declare first so recursive structs are possible :)
            $this->context->registerType('__htbucket__', $struct___cfcd208495d565ef66e7dff9f98764da);
            $this->context->registerType('__htbucket__' . '*', $struct___cfcd208495d565ef66e7dff9f98764da->pointerType(0));
            $this->context->registerType('__htbucket__' . '**', $struct___cfcd208495d565ef66e7dff9f98764da->pointerType(0)->pointerType(0));
            $struct___cfcd208495d565ef66e7dff9f98764da->setBody(
                false ,  // packed
                $this->context->getTypeFromString('size_t')
                , $this->context->getTypeFromString('size_t')
                , $this->context->getTypeFromString('size_t')

            );
            $this->context->structFieldMap['__htbucket__'] = [
                'hash' => 0
                , 'key' => 1
                , 'value' => 2

            ];

    $fntype___cfcd208495d565ef66e7dff9f98764da = $this->context->context->functionType(
                $this->context->getTypeFromString('__htbucket__*'),
                false
//                , $this->context->getTypeFromString('__hashtable__*')
                , $this->context->getTypeFromString('size_t')

            );
            $fn___cfcd208495d565ef66e7dff9f98764da = $this->context->module->addFunction('__hashtable__search', $fntype___cfcd208495d565ef66e7dff9f98764da);




            $this->context->registerFunction('__hashtable__search', $fn___cfcd208495d565ef66e7dff9f98764da);






        $this->pointer = $this->context->getTypeFromString('__hashtable__*');
    }

    public function implement(): void
    {
        $this->implementSearch();
    }

    private function implementAlloc(): void {
        $fn___cfcd208495d565ef66e7dff9f98764da = $this->context->lookupFunction('__hashtable__alloc');
    $block___cfcd208495d565ef66e7dff9f98764da = $fn___cfcd208495d565ef66e7dff9f98764da->appendBasicBlock('main');
    $this->context->builder->positionAtEnd($block___cfcd208495d565ef66e7dff9f98764da);
    $size = $fn___cfcd208495d565ef66e7dff9f98764da->getParam(0);

    $__right = $size->typeOf()->constInt(1, false);










                            $allocSize = $this->context->builder->addNoSignedWrap($size, $__right);
    $type = $this->context->getTypeFromString('__string__');
                    $struct = $this->context->memory->mallocWithExtra($type, $size);
    $offset = $this->context->structFieldMap[$struct->typeOf()->getElementType()->getName()]['length'];
                $this->context->builder->store(
                    $size,
                    $this->context->builder->structGep($struct, $offset)
                );
    $offset = $this->context->structFieldMap[$struct->typeOf()->getElementType()->getName()]['value'];
                    $char = $this->context->builder->structGep($struct, $offset);
    $this->context->intrinsic->memset(
                    $char,
                    $this->context->context->int8Type()->constInt(0, false),
                    $allocSize,
                    false
                );
    $__type = $this->context->getTypeFromString('__ref__virtual*');


                    $__kind = $__type->getKind();
                    $__value = $struct;
                    switch ($__kind) {
                        case \PHPLLVM\Type::KIND_INTEGER:
                            if (!is_object($__value)) {
                                $ref = $__type->constInt($__value, false);
                                break;
                            }
                            $__other_type = $__value->typeOf();
                            switch ($__other_type->getKind()) {
                                case \PHPLLVM\Type::KIND_INTEGER:
                                    if ($__other_type->getWidth() >= $__type->getWidth()) {
                                        $ref = $this->context->builder->truncOrBitCast($__value, $__type);
                                    } else {
                                        $ref = $this->context->builder->zExtOrBitCast($__value, $__type);
                                    }
                                    break;
                                case \PHPLLVM\Type::KIND_DOUBLE:

                                    $ref = $this->context->builder->fpToSi($__value, $__type);

                                    break;
                                case \PHPLLVM\Type::KIND_ARRAY:
                                case \PHPLLVM\Type::KIND_POINTER:
                                    $ref = $this->context->builder->ptrToInt($__value, $__type);
                                    break;
                                default:
                                    throw new \LogicException("Unknown how to handle type pair (int, " . $__other_type->toString() . ")");
                            }
                            break;
                        case \PHPLLVM\Type::KIND_DOUBLE:
                            if (!is_object($__value)) {
                                $ref = $__type->constReal($struct);
                                break;
                            }
                            $__other_type = $__value->typeOf();
                            switch ($__other_type->getKind()) {
                                case \PHPLLVM\Type::KIND_INTEGER:

                                    $ref = $this->context->builder->siToFp($__value, $__type);

                                    break;
                                case \PHPLLVM\Type::KIND_DOUBLE:
                                    $ref = $this->context->builder->fpCast($__value, $__type);
                                    break;
                                default:
                                    throw new \LogicException("Unknown how to handle type pair (double, " . $__other_type->toString() . ")");
                            }
                            break;
                        case \PHPLLVM\Type::KIND_ARRAY:
                        case \PHPLLVM\Type::KIND_POINTER:
                            if (!is_object($__value)) {
                                // this is very likely very wrong...
                                $ref = $__type->constInt($__value, false);
                                break;
                            }
                            $__other_type = $__value->typeOf();
                            switch ($__other_type->getKind()) {
                                case \PHPLLVM\Type::KIND_INTEGER:
                                    $ref = $this->context->builder->intToPtr($__value, $__type);
                                    break;
                                case \PHPLLVM\Type::KIND_ARRAY:
                                    // $__tmp = $this->context->builder->($__value, $this->context->context->int64Type());
                                    // $(result) = $this->context->builder->intToPtr($__tmp, $__type);
                                    // break;
                                case \PHPLLVM\Type::KIND_POINTER:
                                    $ref = $this->context->builder->pointerCast($__value, $__type);
                                    break;
                                default:
                                    throw new \LogicException("Unknown how to handle type pair (double, " . $__other_type->toString() . ")");
                            }
                            break;
                        default:
                            throw new \LogicException("Unsupported type cast: " . $__type->toString());
                    }
    $__type = $this->context->getTypeFromString('int32');


                    $__kind = $__type->getKind();
                    $__value = Refcount::TYPE_INFO_TYPE_STRING|Refcount::TYPE_INFO_REFCOUNTED;
                    switch ($__kind) {
                        case \PHPLLVM\Type::KIND_INTEGER:
                            if (!is_object($__value)) {
                                $typeinfo = $__type->constInt($__value, false);
                                break;
                            }
                            $__other_type = $__value->typeOf();
                            switch ($__other_type->getKind()) {
                                case \PHPLLVM\Type::KIND_INTEGER:
                                    if ($__other_type->getWidth() >= $__type->getWidth()) {
                                        $typeinfo = $this->context->builder->truncOrBitCast($__value, $__type);
                                    } else {
                                        $typeinfo = $this->context->builder->zExtOrBitCast($__value, $__type);
                                    }
                                    break;
                                case \PHPLLVM\Type::KIND_DOUBLE:

                                    $typeinfo = $this->context->builder->fpToSi($__value, $__type);

                                    break;
                                case \PHPLLVM\Type::KIND_ARRAY:
                                case \PHPLLVM\Type::KIND_POINTER:
                                    $typeinfo = $this->context->builder->ptrToInt($__value, $__type);
                                    break;
                                default:
                                    throw new \LogicException("Unknown how to handle type pair (int, " . $__other_type->toString() . ")");
                            }
                            break;
                        case \PHPLLVM\Type::KIND_DOUBLE:
                            if (!is_object($__value)) {
                                $typeinfo = $__type->constReal(Refcount::TYPE_INFO_TYPE_STRING|Refcount::TYPE_INFO_REFCOUNTED);
                                break;
                            }
                            $__other_type = $__value->typeOf();
                            switch ($__other_type->getKind()) {
                                case \PHPLLVM\Type::KIND_INTEGER:

                                    $typeinfo = $this->context->builder->siToFp($__value, $__type);

                                    break;
                                case \PHPLLVM\Type::KIND_DOUBLE:
                                    $typeinfo = $this->context->builder->fpCast($__value, $__type);
                                    break;
                                default:
                                    throw new \LogicException("Unknown how to handle type pair (double, " . $__other_type->toString() . ")");
                            }
                            break;
                        case \PHPLLVM\Type::KIND_ARRAY:
                        case \PHPLLVM\Type::KIND_POINTER:
                            if (!is_object($__value)) {
                                // this is very likely very wrong...
                                $typeinfo = $__type->constInt($__value, false);
                                break;
                            }
                            $__other_type = $__value->typeOf();
                            switch ($__other_type->getKind()) {
                                case \PHPLLVM\Type::KIND_INTEGER:
                                    $typeinfo = $this->context->builder->intToPtr($__value, $__type);
                                    break;
                                case \PHPLLVM\Type::KIND_ARRAY:
                                    // $__tmp = $this->context->builder->($__value, $this->context->context->int64Type());
                                    // $(result) = $this->context->builder->intToPtr($__tmp, $__type);
                                    // break;
                                case \PHPLLVM\Type::KIND_POINTER:
                                    $typeinfo = $this->context->builder->pointerCast($__value, $__type);
                                    break;
                                default:
                                    throw new \LogicException("Unknown how to handle type pair (double, " . $__other_type->toString() . ")");
                            }
                            break;
                        default:
                            throw new \LogicException("Unsupported type cast: " . $__type->toString());
                    }
    $this->context->builder->call(
                    $this->context->lookupFunction('__ref__init') ,
                    $typeinfo
                    , $ref

                );
    $this->context->builder->returnValue($struct);

    $this->context->builder->clearInsertionPosition();
    }

    private function implementInit()
    {
        $fn___c81e728d9d4c2f636f067f89cc14862c = $this->context->lookupFunction('__hashtable__init');
    $block___c81e728d9d4c2f636f067f89cc14862c = $fn___c81e728d9d4c2f636f067f89cc14862c->appendBasicBlock('main');
    $this->context->builder->positionAtEnd($block___c81e728d9d4c2f636f067f89cc14862c);
    $size = $fn___c81e728d9d4c2f636f067f89cc14862c->getParam(0);
    $value = $fn___c81e728d9d4c2f636f067f89cc14862c->getParam(1);

    $result = $this->context->builder->call(
                        $this->context->lookupFunction('__hashtable__alloc') ,
                        $size

                    );
    $offset = $this->context->structFieldMap[$result->typeOf()->getElementType()->getName()]['value'];
                    $char = $this->context->builder->structGep($result, $offset);
    $this->context->intrinsic->memcpy($char, $value, $size, false);
    $this->context->builder->returnValue($result);

    $this->context->builder->clearInsertionPosition();
    }

    private function implementSearch()
    {
        $builder = $this->context->builder;

        $hashtableSearchFn = $this->context->lookupFunction('__hashtable__search');

        $mainBlock = $hashtableSearchFn->appendBasicBlock('main');
        $builder->positionAtEnd($mainBlock);

//        $hashTable = $hashtableSearchFn->getParam(0);
        $key = $hashtableSearchFn->getParam(0);
        // todo calculate key

        $prev = $this->context->builder->getInsertBlock();

        $loopBlock = $prev->insertBasicBlock('loop');
        $prev->moveBefore($loopBlock);

        $returnNullBlock = $prev->insertBasicBlock('returnNull');
        $prev->moveBefore($returnNullBlock);

        $iterateBlock = $prev->insertBasicBlock('iterate');
        $prev->moveBefore($iterateBlock);

        $returnElementBlock = $prev->insertBasicBlock('returnElement');
        $prev->moveBefore($returnElementBlock);

        $nextCellBlock = $prev->insertBasicBlock('nextCell');
        $prev->moveBefore($nextCellBlock);

        $returnBlock = $prev->insertBasicBlock('return');
        $prev->moveBefore($returnBlock);

        $sizeTType = $this->context->getTypeFromString('size_t');

        $type = $this->context->getTypeFromString('__htbucket__*[3]');
        $elementsPointer = $builder->alloca($type);
        // todo store into $elements
        $indexPointer = $builder->alloca($sizeTType);
        $builder->store($sizeTType->constInt(0, false), $indexPointer);
//        $currentElementPointer = $builder->alloca($this->context->getTypeFromString('__htbucket__'));
        $elementToReturnPointer = $builder->alloca(
            $this->context->getTypeFromString('__htbucket__*')
        );
        $builder->branch($loopBlock);

        $builder->positionAtEnd($loopBlock);
        $index = $builder->load($indexPointer);
//        debug($this->context, "loop block", $index);
        $elementPointer = $builder->inBoundsGep($elementsPointer, $sizeTType->constInt(0, false), $index);
        $element = $builder->load($elementPointer);
//        $builder->store($element, $currentElementPointer);
        $comparisonResult = $builder->iCmp(\PHPLLVM\Builder::INT_EQ, $element, $element->typeOf()->constNull());
        $builder->branchIf($comparisonResult, $returnNullBlock, $iterateBlock);

        $builder->positionAtEnd($iterateBlock);
//        debug($this->context, "iterate block",);
        $index = $builder->load($indexPointer);
        $currentElementPointer = $builder->inBoundsGep($elementsPointer, $sizeTType->constInt(0, false), $index);
        $currentElement = $builder->load($currentElementPointer);
        $elementKey = $builder->load($builder->structGep($currentElement, 1));
        $comparisonResult = $builder->iCmp(\PHPLLVM\Builder::INT_EQ, $elementKey, $key);
        $builder->branchIf($comparisonResult, $returnElementBlock, $nextCellBlock);

        $builder->positionAtEnd($returnElementBlock);
        $index = $builder->load($indexPointer);
        $currentElementPointer = $builder->inBoundsGep($elementsPointer, $sizeTType->constInt(0, false), $index);
        $currentElement = $builder->load($currentElementPointer);
        $builder->store($currentElement, $elementToReturnPointer);
        $builder->branch($returnBlock);

        $builder->positionAtEnd($returnNullBlock);
        $builder->store($this->context->getTypeFromString('__htbucket__*')->constNull(), $elementToReturnPointer);
        $builder->branch($returnBlock);

        $builder->positionAtEnd($returnBlock);
        $elementToReturn = $builder->load($elementToReturnPointer);
        $builder->returnValue($elementToReturn);

        $builder->positionAtEnd($nextCellBlock);
        $index = $builder->load($indexPointer);
        $incrementedIndex = $builder->add($index, $sizeTType->constInt(1, false));
        $wrappedIndex = $builder->unsigendRem($incrementedIndex, $sizeTType->constInt(3, false));
        $builder->store($wrappedIndex, $indexPointer);
        // todo wrap around the table
        $builder->branch($loopBlock);

        $builder->clearInsertionPosition();
    }

    public function initialize(): void {
    }

    public function init(
        PHPLLVM\Value $dest,
        array $values
    ): void {
//        compile {
//            function __hashmap_init($size) {
//                $elemOne = malloc __htbucket__ $size;
//                $elemOne->key = 1;
//                $elemOne->data = 20;
//
//                $elemTwo = malloc __htbucket__ $size;
//                $elemTwo->key = 69;
//                $elemTwo->data = 55;
//
//                return $array;
//            }
//        }
    }
}
