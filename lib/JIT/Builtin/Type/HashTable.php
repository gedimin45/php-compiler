<?php

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
                , $this->context->getTypeFromString('__value__')

            );
            $this->context->structFieldMap['__htbucket__'] = [
                'hash' => 0
                , 'key' => 1
                , 'value' => 2

            ];

    $fntype___cfcd208495d565ef66e7dff9f98764da = $this->context->context->functionType(
                $this->context->getTypeFromString('__htbucket__*'),
                false ,
                $this->context->getTypeFromString('__hashtable__*')
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

    private function implementSearch()
    {
        $builder = $this->context->builder;

        $hashtableSearchFn = $this->context->lookupFunction('__hashtable__search');

        $mainBlock = $hashtableSearchFn->appendBasicBlock('main');
        $builder->positionAtEnd($mainBlock);

        $hashTable = $hashtableSearchFn->getParam(0);
        $key = $hashtableSearchFn->getParam(1);

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

        $type = $this->context->getTypeFromString('__htbucket__[20]');
        $elementsPointer = $builder->alloca($type);
        // todo store into $elements
        $indexPointer = $builder->alloca($sizeTType);
        $builder->store($sizeTType->constInt(0, false), $indexPointer);
        $currentElementPointer = $builder->alloca($this->context->getTypeFromString('__htbucket__'));
        $elementToReturnPointer = $builder->alloca(
            $this->context->getTypeFromString('__htbucket__*')
        );
        $builder->branch($loopBlock);

        $builder->positionAtEnd($loopBlock);
        $index = $builder->load($indexPointer);
        $elementPointer = $builder->inBoundsGep($elementsPointer, $sizeTType->constInt(0, false), $index);
        $element = $builder->load($elementPointer);
        $builder->store($element, $currentElementPointer);
        $comparisonResult = $builder->iCmp(\PHPLLVM\Builder::INT_EQ, $elementPointer, $elementPointer->typeOf()->constNull());
        $builder->branchIf($comparisonResult, $returnNullBlock, $iterateBlock);

        $builder->positionAtEnd($iterateBlock);
//        $currentElement = $builder->load($currentElementPointer);
//        $builder->gep($currentElementPointer, $this->context->getTypeFromString('size_t')->constInt(1, false));
        $elementKey = $builder->load($builder->structGep($currentElementPointer, 1));
        $comparisonResult = $builder->iCmp(\PHPLLVM\Builder::INT_EQ, $elementKey, $key);
        $builder->branchIf($comparisonResult, $returnElementBlock, $nextCellBlock);

        $builder->positionAtEnd($returnElementBlock);
        $builder->store($currentElementPointer, $elementToReturnPointer);
        $builder->branch($returnBlock);

        $builder->positionAtEnd($returnNullBlock);
        $builder->store($this->context->getTypeFromString('__htbucket__*')->constNull(), $elementToReturnPointer);
        $builder->branch($returnBlock);

        $builder->positionAtEnd($returnBlock);
        $elementToReturn = $builder->load($elementToReturnPointer);
        $builder->returnValue($elementToReturn);

        $builder->positionAtEnd($nextCellBlock);
        $index = $builder->load($indexPointer);
        $builder->store(
            $builder->add($index, $sizeTType->constInt(1, false)),
            $indexPointer,
        );
        $builder->branch($iterateBlock);

        $builder->clearInsertionPosition();
    }

    public function initialize(): void {
    }

    public function init(
        PHPLLVM\Value $dest,
        PHPLLVM\Value $value,
        PHPLLVM\Value $length
    ): void {
    }
}
