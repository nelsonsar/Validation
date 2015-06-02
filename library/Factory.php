<?php

/*
 * This file is part of Respect\Validation.
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Respect\Validation;

use RecursiveIteratorIterator;
use ReflectionClass;
use Respect\Validation\Exceptions\ComponentException;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Rules\RuleInterface;

class Factory
{
    /**
     * @var array
     */
    protected $namespaces = array('Respect\\Validation');

    /**
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * @param string $namespace
     */
    public function appendNamespace($namespace)
    {
        array_push($this->namespaces, $namespace);
    }

    /**
     * @param string $namespace
     */
    public function prependNamespace($namespace)
    {
        array_unshift($this->namespaces, $namespace);
    }

    /**
     * @param string $rule
     * @param array  $settings
     *
     * @return RuleInterface
     */
    public function createRule($rule, array $settings = array())
    {
        if ($rule instanceof RuleInterface) {
            return $rule;
        }

        foreach ($this->getNamespaces() as $namespace) {
            $className = $namespace.'\\Rules\\'.ucfirst($rule);
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (!$reflection->isSubclassOf('Respect\\Validation\\Rules\\RuleInterface')) {
                throw new ComponentException(sprintf('"%s" is not a valid respect rule', $className));
            }

            return $reflection->newInstanceArgs($settings);
        }

        throw new ComponentException(sprintf('"%s" is not a valid rule name', $rule));
    }

    /**
     * @param Result $result
     *
     * @return ValidationException
     */
    public function createException(Result $result)
    {
        $ruleName = get_class($result->getRule());
        $ruleShortName = substr(strrchr($ruleName, '\\'), 1);
        foreach ($this->getNamespaces() as $namespace) {
            $className = $namespace.'\\Exceptions\\'.$ruleShortName.'Exception';
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (!$reflection->isSubclassOf('Respect\\Validation\\Exceptions\\ValidationException')) {
                throw new ComponentException(sprintf('"%s" is not a validation exception', $className));
            }

            return $reflection->newInstance($result);
        }

        throw new ValidationException($result);
    }

    /**
     * @param Result $result
     *
     * @return ValidationException
     */
    public function createFilteredException(Result $result)
    {
        $resultIterator = new RecursiveResultIterator($result);
        $iteratorIterator = new RecursiveIteratorIterator($resultIterator);
        foreach ($iteratorIterator as $childResult) {
            $result = $childResult;
            break;
        }

        return $this->createException($result);
    }

    /**
     * @return Result
     */
    public function createResult(RuleInterface $rule, array $properties)
    {
        return new Result($rule, $properties, $this);
    }
}
