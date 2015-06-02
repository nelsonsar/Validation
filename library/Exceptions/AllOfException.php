<?php

/*
 * This file is part of Respect\Validation.
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Respect\Validation\Exceptions;

use IteratorAggregate;
use RecursiveIteratorIterator;
use Respect\Validation\RecursiveResultIterator;
use Respect\Validation\Result;
use SplObjectStorage;

class AllOfException extends ValidationException implements IteratorAggregate
{
    private $children;

    /**
     * {@inheritDoc}
     */
    public function getTemplates()
    {
        return [
            self::MODE_AFFIRMATIVE => [
                self::MESSAGE_STANDARD => 'All rules must pass for {{placeholder}}',
            ],
            self::MODE_NEGATIVE => [
                self::MESSAGE_STANDARD => 'All rules must not pass for {{placeholder}}',
            ],
        ];
    }

    private function buildChildren(Result $result)
    {
        $children = new SplObjectStorage();

        $resultIterator = new RecursiveResultIterator($result);
        $iteratorIterator = new RecursiveIteratorIterator($resultIterator, RecursiveIteratorIterator::SELF_FIRST);

        $factory = $result->getFactory();

        $lastLevel = 0;
        $lastLevelOriginal = 0;
        $knownLevels = [];
        foreach ($iteratorIterator as $childResult) {
            if ($childResult->isValid()) {
                continue;
            }

            if ($childResult->hasChildren()
                && count($childResult->getChildren()) < 2) {
                continue;
            }

            $currentLevel = $lastLevel;
            $currentLevelOriginal = $iteratorIterator->getDepth() + 1;

            if (isset($knownLevels[$currentLevelOriginal])) {
                $currentLevel = $knownLevels[$currentLevelOriginal];
            } elseif ($currentLevelOriginal > $lastLevelOriginal) {
                $currentLevel++;
            }

            if (!isset($knownLevels[$currentLevelOriginal])) {
                $knownLevels[$currentLevelOriginal] = $currentLevel;
            }

            $lastLevel = $currentLevel;
            $lastLevelOriginal = $currentLevelOriginal;

            $children->attach(
                $factory->createException($childResult),
                [
                    'level' => $currentLevel,
                    'level_original' => $currentLevelOriginal,
                    'previous_level' => $lastLevel,
                    'previous_level_original' => $lastLevelOriginal,
                ]
            );
        }

        return $children;
    }

    public function getIterator()
    {
        if (!$this->children instanceof SplObjectStorage) {
            $this->children = $this->buildChildren($this->getResult());
        }

        return $this->children;
    }

    public function getFullMessage()
    {
        $iterator = $this->getIterator();
        $message = '- '.$this->getMessage().PHP_EOL;
        foreach ($iterator as $exception) {
            $level = $iterator[$exception]['level'];
            $prefix = str_repeat(' ', $level * 2);
            $message .= sprintf('%s- %s', $prefix, $exception->getMessage()).PHP_EOL;
        }

        return $message;
    }
}
