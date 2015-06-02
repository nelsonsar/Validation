<?php

/*
 * This file is part of Respect\Validation.
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Respect\Validation;

use Countable;
use RecursiveIterator;

class RecursiveResultIterator implements RecursiveIterator, Countable
{
    protected $results = array();
    protected $index = 0;

    public function __construct(Result $result)
    {
        $this->results = $result->getChildren();
    }

    public function count()
    {
        return count($this->results);
    }

    public function hasChildren()
    {
        if (!$this->valid()) {
            return false;
        }

        return $this->current()->hasChildren();
    }

    public function getChildren()
    {
        return new static($this->current());
    }

    public function current()
    {
        return $this->results[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return isset($this->results[$this->index]);
    }
}
