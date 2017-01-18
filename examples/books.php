<?php

require dirname(__DIR__) . '/src/Collection.php';

use alejoluc\Collection\Collection;

class Author {
    public $name;
    public $nationality;

    public function __construct($name, $nationality) {
        $this->name         = $name;
        $this->nationality  = $nationality;
    }
}

class Book {
    public $title;

    /** @var  Author */
    public $author;

    /** @var  DateTime */
    public $pubDate;

    public $price;
    public $genres;

    public function __construct($title, $author, $pubDate, $price, $genres = []) {
        $this->title    = $title;
        $this->author   = $author;
        $this->pubDate  = $pubDate;
        $this->price    = $price;
        $this->genres   = $genres;
    }
}

$HunterThompson = new Author('Hunter S. Thompson',                  'American');
$MarkTwain      = new Author('Mark Twain',                          'American');
$Tolkien        = new Author('J. R. R. Tolkien',                    'English');

$library = new Collection([
    new Book('The Hobbit', $Tolkien, new DateTime('1937-09-21'), 4.99, ['fiction','fantasy']),
    new Book('The Fellowship of the Ring (LOTRO #1)', $Tolkien, new DateTime('1954-07-29'), 14.50, ['fiction','fantasy']),
    new Book('The Two Towers (LOTRO #2)', $Tolkien, new DateTime('1954-11-11'), 14.50, ['fiction','fantasy']),
    new Book('The Return of the King (LOTRO #3)', $Tolkien, new DateTime('1955-10-20'), 14.50, ['fiction','fantasy']),

    new Book('Fear and Loathing in Las Vegas', $HunterThompson, new DateTime('1972-07'), 10, ['gonzo']),
    new Book('Hell\'s Angels', $HunterThompson, new DateTime('1967-01-01'), 8.99, ['gonzo']),

    new Book('The Adventures of Tom Sawyer', $MarkTwain, new DateTime('1876-01'), 3.50, ['fiction'])
]);

/* Get all the Tolkien books */
$tolkienBooks = $library->where('author', $Tolkien);

/* What if we just want the price of those? */
$tolkienBooksPrices = $tolkienBooks->pluckColumn('price');

/* Now we want the prices of Thompson's book, but lets do it with the fluent interface */
$thompsonBooksPrices = $library->where('author', $HunterThompson)->pluckColumn('price');

/* Get all the books published since the 1st of January of 1950 */
$booksAfter1950 = $library->whereGreaterOrEqual('pubDate', new DateTime('1950-01-01'));

/* Now I want to get all the books of the Lord of the Rings series - strictly those three. I could query for Tolkien,
   but he also has The Hobbit linked to him! We were sloppy in design and didn't include series in our Book class.
   Luckily, we notice that the books in the series carry the book order preceded by the numeral symbol (#) so let's
   use that (do NOT do this, by the way; it's awful) */
$booksInLOTRO = $tolkienBooks->whereContains('title', '#');

/* You pass along a bookstore and feel for some fiction, but you only carry 5 bucks with you. What to do? */
$cheapFiction = $library->whereContains('genres', 'fiction')->whereLessOrEqual('price', 5);

/* Uh-oh. We want all of Tolkien's books again, but we somehow forgot where we put Tolkien's Author instance.
   No biggie, we can use a custom filter */
$tolkienBooks_Again = $library->filter(function($book){
    return $book->author->name === 'J. R. R. Tolkien';
});

/* In fact that may come in handy - why don't we create a custom filter so we can later use it by passing the name
   of the authors? (we must add a key param though, although we won't use it in this case - it's just the key
   each item has in the Collection we are filtering) */
$authorNameFilter = function($book, $key, $authorName) {
    return $book->author->name === $authorName;
};

/* And we use it like this. */
$tolkienBooks_withCustomFilter = $library->filter($authorNameFilter, 'J. R. R. Tolkien');

/* This way, we can also look for Twain's books without his object, but also, without having to write the callback
   again */
$twainBooks = $library->filter($authorNameFilter, 'Mark Twain');