<?php

namespace alejoluc\Collection;

use \ArrayAccess;
use \JsonSerializable;

class Collection implements ArrayAccess, JsonSerializable {

    /** @var array */
    private $items = [];

    public function __construct($origin = null)
    {
        if (is_array($origin) || $origin instanceof ArrayAccess) {
            $this->items = $origin;
        }
    }

    /**
     * Access a member of one of the items of the collection, differentiating between arrays and objects
     * @param array|object $item
     * @param string $keyName
     * @param mixed [$default]
     * @return mixed
     */
    private function access($item, $keyName, $default = null) {
        if (is_array($item)) {
            return $item[$keyName];
        } elseif (is_object($item)) {
            return $item->{$keyName};
        }
        return $default;
    }

    /**
     * Returns the array of elements of the Collection
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Adds an item at the end of the collection
     * @param mixed $value
     * @param null|string $key
     * @return $this
     */
    public function add($value, $key = null) {
        if ($key !== null) {
            $this->items[$key] = $value;
        } else {
            $this->items[] = $value;
        }
        return $this;
    }

    /**
     * Applias a callback to all items in the collection and returns a new collection with the results, keeping
     * the key => value relationships.
     * @param callable $callback The function to be applied to each value. The first argument will be the value,
     * and the second, mostly optional, argument is the key. <em>function($value, $key)</em>
     * @return Collection
     */
    public function map(callable $callback) {
        $newArray = [];
        foreach ($this->items as $k => $v) {
            $newArray[$k] = $callback($v, $k);
        }
        return new static($newArray);
    }

    /**
     * Performs a call to a callback for each element of the Collection, stopping if the callback returns false
     * at any time
     * @param callable $callback <em>function($value, $key)</em>
     * @return $this
     */
    public function each(callable $callback) {
        foreach ($this->items as $key => $value) {
            $res = $callback($value, $key);
            if ($res === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Returns a new Collection that contains all the elements of the Original collection upon which the call of a
     * callback returned *true*.
     * The new Collection keeps the key => value relationship by default
     * @param callable $callback <em>function($value, $key)</em>
     * @param array [$args] Extra arguments to pass to the function
     * @return static The new collection
     */
    public function filter(callable $callback,...$args) {
        $filtered = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key, ...$args) === true) {
                $filtered[$key] = $value;
            }
        }

        return new static($filtered);
    }

    /**
     * Applies a callback to all the elements of the collection, carrying the result from one call to the next
     * and returning the last result
     * @param callable $callback
     * @param mixed $startValue
     * @return mixed
     */
    public function reduce(callable $callback, $startValue) {
        $res = $startValue;
        foreach ($this->items as $key => $value) {
            $res = $callback($res, $value, $key);
        }
        return $res;
    }

    /**
     * Sums the values of a collection. If a key is passed, it will sum the values of the items of the collection
     * by that key's name
     * @param string|null [$key]
     * @return int|float
     */
    public function sum($key = null) {
        if ($key === null) {
            return array_sum($this->items);
        } else {
            return $this->reduce(function($tot, $item) use ($key){
                return $tot + $this->access($item, $key);
            }, 0);
        }
    }

    public function avg($key = null) {
        return $this->sum($key) / $this->count();
    }

    public function count() {
        return count($this->items);
    }

    /**
     * Compares a column of all the elements of the collection against a $matchValue and returns a new Collection with the
     * elements that match
     * @param string $key
     * @param mixed $matchValue
     * @param bool $strictComparison
     * @return Collection
     */
    public function whereEquals($key, $matchValue, $strictComparison = true) {
        return $this->where($key, $matchValue, '=', $strictComparison);
    }

    public function whereLess($key, $limitValue) {
        return $this->where($key, $limitValue, '<');
    }

    public function whereLessOrEqual($key, $limitValue) {
        return $this->where($key, $limitValue, '<=');
    }

    public function whereGreater($key, $limitValue) {
        return $this->where($key, $limitValue, '>');
    }

    public function whereGreaterOrEqual($key, $limitValue) {
        return $this->where($key, $limitValue, '>=');
    }

    public function whereContains($key, $value, $strictComparison = true) {
        return $this->where($key, $value, 'contains', $strictComparison);
    }

    /**
     * Compares a column of all the elements of the collection against a $compareValue and returns a new Collection with the
     * elements that satisfy the $compare condition. This is the filtering method that the filtering helpers call.
     * @param string $key
     * @param mixed $compareValue
     * @param string [$compare] The constraint to use. Possible values are: =, <, >, <=, >=, contains (for arrays
     * and strings). Defaults to <em>=</em>
     * @param bool [$strictComparison] If false, equality will use == and array searches will be non-strict
     * @return Collection
     */
    public function where($key, $compareValue, $compare = '=', $strictComparison = true) {
        return $this->filter(function($item) use ($key, $compareValue, $compare, $strictComparison){
            $value = $this->access($item, $key);
            switch ($compare) {
                case '=':
                    return $strictComparison ? $value === $compareValue : $value == $compareValue;
                    break;

                case '<':
                    return $value < $compareValue;
                    break;

                case '>':
                    return $value > $compareValue;
                    break;

                case '<=':
                    return $value <= $compareValue;
                    break;

                case '>=':
                    return $value >= $compareValue;
                    break;

                case 'contains':
                    if (is_string($value)) {
                            return strpos($value, $compareValue) !== false;
                    }

                    if (is_array($value)) {
                        return in_array($compareValue, $value, $strictComparison) !== false;
                    }
                    break;
            }
        });
    }

    /**
     * Creates a new Collection with the items keyed and grouped by the values of a field in this Collection,
     * preserving the items' keys by default.
     * <strong>Currently it only works for fields whose values are either strings or numeric</strong>
     * @param string $groupKey
     * @param bool [$keepKeys]
     * @return static
     */
    public function groupBy($groupKey, $keepKeys = false) {
        $res = [];
        foreach ($this->items as $key => $item) {
            $keyValue = (string)$this->access($item, $groupKey);
            if (!array_key_exists($keyValue, $res)) {
                $res[$keyValue] = new static;
            }
            if ($keepKeys) {
                $res[$keyValue][$key] = $item;
            } else {
                $res[$keyValue][] = $item;
            }
        }
        return new static($res);
    }

    public function groupByCallback(callable $callback) {
        $res = [];
        foreach ($this->items as $key => $item) {
            $groupKey = $callback($item);
            if (!isset($res[$groupKey])) { $res[$groupKey] = []; }
            $res[$groupKey][] = $item;
        }
        return new static($res);
    }

    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        return $default;
    }

    public function remove($key) {
        unset($this->items[$key]);
        return $this;
    }

    /**
     * Gets a member of each item of the Collection and returns a new Collection with the result
     * @param $columnName
     * @return static The new collection
     */
    public function pluckColumn($columnName) {
        $res = [];

        foreach ($this->items as $key => $item) {
            $value = $this->access($item, $columnName);
            $res[$key] = $value;
        }

        return new static($res);
    }

    /**
     * Returns a new collection with the same values, but keyed by the values of the specified key $keyName
     * @param string $keyName
     * @return static
     */
    public function keyBy($keyName) {
        $res = [];
        foreach ($this->items as $item) {
            $value = $this->access($item, $keyName);
            $res[$value] = $item;
        }
        return new static($res);
    }

    public function chunk($chunkSize) {
        $res = [];
        $chunks = array_chunk($this->items, $chunkSize, true);
        foreach ($chunks as $chunk) {
            $res[] = new static($chunk);
        }
        return new static($res);
    }

    /**
     * Returns a new Collection with the same items and keys but in reversed order
     * @return static
     */
    public function reverse() {
        return new static(array_reverse($this->items));
    }

    /**
     * @param null|callable $callback
     * @return static
     */
    public function sort($callback = null) {
        $itemsCopy = $this->items;
        if ($callback) {
            uasort($itemsCopy, $callback);
        } else {
            uasort($itemsCopy, function($a, $b){
                if ($a === $b) { return 0; }
                if ($a < $b) {
                    return -1;
                } else {
                    return 1;
                }
            });
        }
        return new static($itemsCopy);
    }

    /**
     * @param string $key
     * @return static
     */
    public function sortBy($key) {
        return $this->sort(function($a, $b) use ($key){
            $value1 = $this->access($a, $key);
            $value2 = $this->access($b, $key);

            if ($value1 === $value2) { return 0; }
            if ($value1 < $value2) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    /*
     * =================
     * == ArrayAccess ==
     * =================
     */

    public function offsetExists($key) { return array_key_exists($key, $this->items); }
    public function offsetGet($key) {    return $this->items[$key]; }
    public function offsetUnset($key) { unset($this->items[$key]); }

    public function offsetSet($key, $value) {
        if ($key === null) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }


    /*
     * ========================
     * == Object-like access ==
     * ========================
    */

    public function __isset($key) {         return array_key_exists($key, $this->items); }
    public function __get($key){            return $this->items[$key]; }
    public function __set($key, $value){    $this->items[$key] = $value; }
    public function __unset($key){          unset($this->items[$key]); }


    /*
     * ======================
     * == JsonSerializable ==
     * ======================
     */
    public function jsonSerialize()
    {
        return array_map(function($value){
            if ($value instanceof JsonSerializable) {  // If it's an object, it may want to decide what to output
                return $value->jsonSerialize();
            } else {
                return $value;
            }
        }, $this->items);
    }
}