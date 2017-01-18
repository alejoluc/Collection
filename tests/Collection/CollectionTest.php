<?php

use \alejoluc\Collection\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase {

    /** @var  Collection */
    private $colIntegers;

    /** @var  Collection */
    private $colCharacters;

    public function setUp() {
        $this->colIntegers   = new Collection([1,2,3,4,5,6,7,8,9]);
        $this->colCharacters = new Collection(['a','b','c','d','e','f']);
    }

    public function testSatisfiesArrayAccessContract() {
        $col = $this->colIntegers;
        unset($col[1]);
        $this->assertEquals(1, $col[0]);
        $this->assertEquals(9, $col[8]);
    }

    public function testAllReturnsAnArray()
    {
        $c = new Collection();
        $this->assertTrue(is_array($c->all()));
    }
    
    public function testCollectionIsEmptyUponCreation()
    {
        $c = new Collection();
        $this->assertEquals($c->all(), []);
    }

    public function testAddNoKey() {
        $col = $this->colIntegers;
        $col->add(10);
        $col->add(11);
        $expect = [1,2,3,4,5,6,7,8,9,10,11];
        $this->assertEquals(11, $col->count());
        $this->assertEquals($expect, $col->all());
    }

    public function testAddWithKey() {
        $col = new Collection([
            'Negro'  => 'Black',
            'Blanco' => 'White'
        ]);
        $col->add('Yellow', 'Amarillo');
        $this->assertEquals(3, $col->count());
        $this->assertEquals('Yellow', $col->get('Amarillo'));
        
    }

    public function testMapReturnsNewCollection() {
        $c1 = $this->colIntegers;
        $c2 = $this->colIntegers->map(function($v){
            return $v;
        });
        $this->assertEquals($c1, $c2);
        $this->assertNotSame($c1, $c2);
    }

    public function testMapNumericValues()
    {
        $c  = $this->colIntegers;
        $expectedArr = [2,3,4,5,6,7,8,9,10];
        $f = function($item, $key) {
            return $item + 1;
        };
        $this->assertEquals($expectedArr, $c->map($f)->all());
    }

    public function testEachStopsWhenFalseIsReturned() {
        $c = $this->colIntegers;

        $total   = count($c->all());
        $counter = 0;

        $c->each(function($v, $k) use (&$counter){
            if ($v === 5) { return false; }
            $counter++;
        });

        $this->assertEquals(4, $counter);
    }

    public function testFilterReturnsNewCollection() {
        $c1 = $this->colIntegers;
        $c2 = $c1->filter(function(){
            return true;
        });
        $this->assertEquals($c1, $c2);
        $this->assertNotSame($c2, $c1);
    }

    public function testFilter() {
        $col = $this->colIntegers;
        $expected = [2,4,6,8];
        $keepEvenNumbers = function($value) {
            return $value % 2 === 0;
        };
        $even = $col->filter($keepEvenNumbers);
        $this->assertEquals($expected, array_values($even->all()));
    }

    public function testFilterWorksWithKeys() {
        $col = $this->colIntegers;
        $expected = [1,3,5,7,9];
        $keepIfKeyEven = function($value, $key) {
            return $key % 2 === 0;
        };
        $even = $col->filter($keepIfKeyEven);
        $this->assertEquals($expected, array_values($even->all()));
    }

    public function testFilterCustomParameters() {
        $col = $this->coffeeArrayCollection();
        $filterPrice = function($item, $key, $priceMin, $priceMax) {
            return $item['cost'] >= $priceMin && $item['cost'] <= $priceMax;
        };
        $res = $col->filter($filterPrice, 5, 7.50);
        $this->assertEquals(2, $res->count());
        $this->assertEquals($col->get(1), $res->get(1));
        $this->assertEquals($col->get(2), $res->get(2));
    }

    public function testGetReturnsNullIfNoElement() {
        $col = $this->colIntegers;
        $res = $col->get(10);
        $this->assertNull($res);
    }

    public function testGetReturnsCorrectElement() {
        $col = $this->colIntegers;
        $res = $col->get(1);
        $this->assertEquals(2, $res);
    }

    public function testRemoveModifiesTheElementsArray() {
        $a1 = $this->colIntegers->all();
        $a2 = $this->colIntegers->remove(1)->all();
        $this->assertNotEquals($a1, $a2);
    }

    public function testReduce() {
        $col = $this->colIntegers;
        $sumFunction = function($tot, $x){ return $tot + $x; };

        // Sum all elements
        $sumAll = $col->reduce($sumFunction, 0);
        $this->assertEquals(45, $sumAll);

        // Sum only even elements
        $iseven = function($x){ return $x % 2 === 0; };
        $sumEven = $col->filter($iseven)->reduce($sumFunction, 0);
        $this->assertEquals(20, $sumEven);

        // Sum only elements with even keys
        $isevenkey = function($v, $k) { return $k % 2 === 0; };
        $sumEvenKeys = $col->filter($isevenkey)->reduce($sumFunction, 0);
        $this->assertEquals(25, $sumEvenKeys);
    }

    public function testReduceString() {
        $col = $this->colCharacters;
        $string = $col->reduce(function($buffer, $x){
            return $buffer . $x;
        }, 'Result: ');
        $expect = 'Result: abcdef';
        $this->assertEquals($expect, $string);
    }

    public function testPluckColumnArray() {
        $col = $this->coffeeArrayCollection();
        $names = $col->pluckColumn('name');
        $expect = ['Black', 'Decaf', 'Cappuccino', 'Submarine'];
        $this->assertEquals($expect, $names->all());
    }

    public function testPluckColumnObject() {
        $col = $this->coffeeObjectCollection();
        $names = $col->pluckColumn('name');
        $expect = ['Black', 'Decaf', 'Cappuccino', 'Submarine'];
        $this->assertEquals($expect, $names->all());
    }

    public function testSumNumericValues() {
        $col = $this->colIntegers;
        $sum = $col->sum();
        $this->assertEquals(45, $sum);
    }

    public function testSumArrayValues() {
        $col = $this->coffeeArrayCollection();
        $sum = $col->sum('cost');
        $this->assertEquals(26.99, $sum);
    }

    public function testSumObjectValues() {
        $col = $this->coffeeObjectCollection();
        $sum = $col->sum('cost');
        $this->assertEquals(26.99, $sum);
    }

    public function testAvgNumericValues() {
        $col = $this->colIntegers;
        $avg = $col->avg();
        $this->assertEquals(5, $avg);
    }

    public function testAvgArrayValues() {
        $col = $this->coffeeArrayCollection();
        $avg = $col->avg('cost');
        $this->assertEquals(6.7475, $avg);
    }

    public function testAvgObjectValues() {
        $col = $this->coffeeObjectCollection();
        $avg = $col->avg('cost');
        $this->assertEquals(6.7475, $avg);
    }

    public function testKeyByArray() {
        $col = $this->coffeeArrayCollection();
        $colKeyed = $col->keyBy('name');

        $expect_keys = ['Black', 'Decaf', 'Cappuccino', 'Submarine'];
        $expect_values = array_values($col->all());
        $result_keys   = array_keys($colKeyed->all());
        $result_values = array_values($colKeyed->all());

        $this->assertEquals($expect_keys, $result_keys);
        $this->assertEquals($expect_values, $result_values);
    }

    public function testKeyByObject() {
        $col = $this->coffeeObjectCollection();
        $colKeyed = $col->keyBy('name');

        $expect_keys = ['Black', 'Decaf', 'Cappuccino', 'Submarine'];
        $expect_values = array_values($col->all());
        $result_keys   = array_keys($colKeyed->all());
        $result_values = array_values($colKeyed->all());

        $this->assertEquals($expect_keys, $result_keys);
        $this->assertEquals($expect_values, $result_values);
    }
    
    public function testWhereDefaultsToEquality() {
        $col = $this->coffeeArrayCollection();
        // Get all coffees with no extra ingredients
        $res = $col->where('ingredients', []);
        $this->assertEquals('Black', $res->get(0)['name']);
        $this->assertEquals('Decaf', $res->get(1)['name']);
    }

    public function testWhereEqualsArray(){
        $col = $this->coffeeArrayCollection();
        // Get all coffees with no extra ingredients
        $res = $col->whereEquals('ingredients', []);
        $this->assertEquals('Black', $res->get(0)['name']);
        $this->assertEquals('Decaf', $res->get(1)['name']);
    }

    public function testWhereEqualsObject(){
        $col = $this->coffeeObjectCollection();
        // Get all coffees with no extra ingredients
        $res = $col->whereEquals('ingredients', []);
        $this->assertEquals(2, $res->count());
        $this->assertEquals('Black', $res->get(0)->name);
        $this->assertEquals('Decaf', $res->get(1)->name);
    }

    public function testWhereLessArray() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereLess('cost', 7.51);
        $this->assertEquals(3, $result->count());
    }

    public function testWhereLessOrEqualArray() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereLessOrEqual('cost', 7.50);
        $this->assertEquals(3, $result->count());
    }

    public function testWhereGreaterArray() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereGreater('cost', 5);
        $this->assertEquals(2, $result->count());
    }

    public function testWhereGreaterOrEqualArray() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereGreaterOrEqual('cost', 5);
        $this->assertEquals(3, $result->count());
    }

    public function testWhereContainsArrayString() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereContains('name', 'e');
        $this->assertEquals(2, $result->count());
        $this->assertEquals($col->get(1), $result->get(1));
        $this->assertEquals($col->get(3), $result->get(3));
    }

    public function testWhereContainsArrayArray() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereContains('ingredients', 'Milk');
        $this->assertEquals(2, $result->count());
        $this->assertEquals($col->get(2), $result->get(2));
        $this->assertEquals($col->get(3), $result->get(3));
    }

    public function testWhereContainsReturnsEmptyCollectionIfFieldNotArrayOrString() {
        $col = $this->coffeeArrayCollection();
        $result = $col->whereContains('cost', 5);
        $this->assertEquals(0, $result->count());
    }
    
    public function testWhereNoStrictEquality() {
        $col = $this->coffeeArrayCollection();
        $results = $col->whereEquals('cost', '5', false);
        $this->assertEquals(1, $results->count());
    }

    public function testGroupByArray() {
        $col = $this->peopleArrayCollection();
        $byAge = $col->groupBy('Age');

        $this->assertEquals(2, $byAge->count());
        $this->assertEquals($col->get(0), $byAge->get(21)->get(0));
        $this->assertEquals($col->get(2), $byAge->get(21)->get(1));

        $this->assertEquals($col->get(2), $byAge->get(21)->get(1));
    }

    public function testGroupByObject() {
        $col = $this->peopleObjectCollection();

        $byAge = $col->groupBy('Age');
        $this->assertEquals(2, $byAge->count());
        $this->assertEquals($col->get(0), $byAge->get(21)->get(0));
        $this->assertEquals($col->get(2), $byAge->get(21)->get(1));

        $this->assertEquals($col->get(1), $byAge->get(19)->get(0));
    }

    public function testGroupByKeepingKeys() {
        $col = $this->peopleArrayCollection();
        $byAge = $col->groupBy('Age', true);

        $this->assertEquals(2, $byAge->count());
        $this->assertEquals($col->get(0), $byAge->get(21)->get(0));
        $this->assertEquals($col->get(2), $byAge->get(21)->get(2));

        $this->assertEquals($col->get(1), $byAge->get(19)->get(1));
    }

    public function testGroupByCallbackArrayNumeric() {
        $col = $this->peopleArrayCollection();
        $olderThan20 = function($item) {
            if ($item['Age'] >= 20) { return '>=20'; } else { return '<20'; }
        };
        $grouped = $col->groupByCallback($olderThan20);
        $this->assertEquals('John', $grouped['>=20'][0]['Name']);
        $this->assertEquals('July', $grouped['>=20'][1]['Name']);
        $this->assertEquals('Nathan', $grouped['<20'][0]['Name']);
    }

    public function testGroupByCallbackArrayString() {
        $col = $this->coffeeObjectCollection();
        $containsLetterB = function($item){ return (int)(strpos(strtolower($item->name), 'b') !== false); };
        $grouped = $col->groupByCallback($containsLetterB);
        
        $this->assertEquals('Black', $grouped[1][0]->name);
        $this->assertEquals('Submarine', $grouped[1][1]->name);

        $this->assertEquals('Decaf', $grouped[0][0]->name);
        $this->assertEquals('Cappuccino', $grouped[0][1]->name);
    }

    public function testChunkCorrectSize() {
        $col = $this->peopleArrayCollection();
        $chunks = $col->chunk(2);
        $this->assertEquals(2, $chunks->count());
        $this->assertEquals(2, $chunks->get(0)->count());
        $this->assertEquals(1, $chunks->get(1)->count());

        $col = $this->coffeeObjectCollection();
        $chunks = $col->chunk(2);
        $this->assertEquals(2, $chunks->count());
        $this->assertEquals(2, $chunks->get(0)->count());
        $this->assertEquals(2, $chunks->get(1)->count());
    }

    public function testSortNumericValues() {
        $col = new Collection([6,4,5,7,9,6,3,2,5]);
        $expected = [2,3,4,5,5,6,7,9];
        $sorted = $col->sort();
        $this->assertEquals($col->count(), $sorted->count());
        $this->assertEquals($expected, array_values($expected));
    }

    public function testSortByKey() {
        $col = $this->coffeeArrayCollection();
        $expected = ['Black', 'Cappuccino', 'Decaf', 'Submarine'];

        $sorted = $col->sortBy('name');
        $values = array_values($sorted->pluckColumn('name')->all());

        $this->assertEquals($col->count(), $sorted->count());
        $this->assertEquals($expected, $values);
    }

    public function testReverse() {
        $col = $this->colIntegers;
        $expected = [9,8,7,6,5,4,3,2,1];
        $reversed = $col->reverse();
        $this->assertEquals($expected, $reversed->all());
    }

    public function testToJsonNumeric() {
        $col = $this->colIntegers;
        $values = array_values($col->all());
        $expected = json_encode($values);

        $this->assertEquals($expected, json_encode($col));
    }

    public function testToJsonArray() {
        $col = $this->coffeeArrayCollection();
        $values = array_values($col->all());
        $expected = json_encode($values);

        $this->assertEquals($expected, json_encode($col));
    }

    public function testToJsonObject() {
        $col = $this->coffeeObjectCollection();
        $values = array_values($col->all());
        $expected = json_encode($values);

        $this->assertEquals($expected, json_encode($col));
    }


    /**
     * @return Collection
     */
    private function coffeeArrayCollection()
    {
        return new Collection([
            [
                'name'          => 'Black',
                'ingredients'   => [],
                'cost'          => 4.50
            ],
            [
                'name'          => 'Decaf',
                'ingredients'   => [],
                'cost'          => 5,
            ],
            [
                'name'          => 'Cappuccino',
                'ingredients'   => ['Milk', 'Chocolate'],
                'cost'          => 7.50
            ],
            [
                'name'          => 'Submarine',
                'ingredients'   => ['Milk', 'Chocolate Bar'],
                'cost'          => 9.99
            ]
        ]);
    }

    /**
     * @return Collection
     */
    private function coffeeObjectCollection() {
        $arr = $this->coffeeArrayCollection();
        $objCollection = $arr->map(function($item){
            return (object)$item;
        });
        return $objCollection;
    }

    /**
     * @return Collection
     */
    private function peopleArrayCollection() {
        $col = new Collection([
            [
                'Name'  => 'John',
                'Age'   =>  21,
                'sex'   => 'M'
            ],
            [
                'Name'  => 'Nathan',
                'Age'   => 19,
                'sex'   => 'M'
            ],
            [
                'Name'  => 'July',
                'Age'   => 21,
                'sex'   => 'F'
            ]
        ]);
        return $col;
    }

    private function peopleObjectCollection() {
        $arr = $this->peopleArrayCollection();
        $objCollection = $arr->map(function($item){
            return (object)$item;
        });
        return $objCollection;
    }


    
}