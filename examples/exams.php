<?php

require dirname(__DIR__) . '/src/Collection.php';

use alejoluc\Collection\Collection;

class Exam {
    public $class;
    public $status;
    public $month;
    public function __construct($class, $status, $month){
        $this->class = $class;
        $this->status = $status;
        $this->month = $month;
    }
}

$col = new Collection([
    new Exam('Math', 'ok', 4),
    new Exam('Statistics', 'ok', 4),
    new Exam('Programming', 'ok', 4),
    new Exam('Accounting', 'failed', 4),

    new Exam('Math', 'ok', 5),
    new Exam('Statistics', 'failed', 5),
    new Exam('Programming', 'ok', 5),
    new Exam('Accounting', 'failed', 5),

    new Exam('Math', 'failed', 6),
    new Exam('Statistics', 'failed', 6),
    new Exam('Programming', 'ok', 6),
    new Exam('Accounting', 'ok', 6)
]);

/* Get all passed exams */
$passedExams = $col->whereEquals('status', 'ok');

/* Get all the exams in one month */
$mayExams = $col->whereEquals('month', 5);

/* Get all the exams taken in May that you passed */
$passedExamsInMay = $col->whereEquals('month', 5)
                        ->whereEquals('status', 'ok');

/* Get all the exams taken after April that you failed */
$failedExamsAfterApril = $col->whereGreater('month', 4)
                              ->where('status', 'failed');

/* This produces the same result as above, order of criteria is not important when they are in the same level in data */
$failedExamsAfterApril = $col->where('status', 'failed')
                             ->whereGreater('month', 4);

/* How did you do in Math, overall? */
$mathResults = $col->where('class', 'Math');

/* And how many Statistics exams did you pass?  */
$statisticsOkCount = $col->where('class', 'Statistics')
                         ->where('status', 'ok')
                         ->count();