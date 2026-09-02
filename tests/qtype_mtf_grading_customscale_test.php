<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * PHPUnit tests for customscale grading.
 *
 * @package     qtype_mtf
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_mtf;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/mtf/question.php');
require_once($CFG->dirroot . '/question/type/mtf/grading/qtype_mtf_grading_customscale.class.php');

/**
 * Tests for customscale grading method.
 *
 * @group qtype_mtf
 */
final class qtype_mtf_grading_customscale_test extends \advanced_testcase {

    /**
     * Build a 4-row MTF question for grading tests.
     *
     * @return \qtype_mtf_question
     */
    protected function make_customscale_question(): \qtype_mtf_question {

        \question_bank::load_question_definition_classes('mtf');

        $question = new \qtype_mtf_question();

        \test_question_maker::initialise_a_question($question);

        $question->name = 'Customscale test';
        $question->scoringmethod = 'customscale';

        $question->numberofrows = 4;
        $question->numberofcolumns = 2;

        $question->order = [1, 2, 3, 4];

        $question->rows = [
            1 => (object) [
                'number' => 1,
            ],
            2 => (object) [
                'number' => 2,
            ],
            3 => (object) [
                'number' => 3,
            ],
            4 => (object) [
                'number' => 4,
            ],
        ];

        $question->columns = [
            1 => (object) [
                'number' => 1,
            ],
            2 => (object) [
                'number' => 2,
            ],
        ];

        $question->weights = [
            1 => [
                1 => (object) ['weight' => 1.0],
                2 => (object) ['weight' => 0.0],
            ],
            2 => [
                1 => (object) ['weight' => 1.0],
                2 => (object) ['weight' => 0.0],
            ],
            3 => [
                1 => (object) ['weight' => 1.0],
                2 => (object) ['weight' => 0.0],
            ],
            4 => [
                1 => (object) ['weight' => 1.0],
                2 => (object) ['weight' => 0.0],
            ],
        ];

        return $question;
    }

    /**
     * @covers ::grade_question
     */
    public function test_customscale_zero_correct(): void {

        $grading = new \qtype_mtf_grading_customscale();

        $grade = $grading->grade_question(
            $this->make_customscale_question(),
            [
                'option0' => 2,
                'option1' => 2,
                'option2' => 2,
                'option3' => 2,
            ]
        );

        $this->assertEqualsWithDelta(
            0.0,
            $grade,
            0.00001
        );
    }

    /**
     * @covers ::grade_question
     */
    public function test_customscale_one_correct(): void {

        $grading = new \qtype_mtf_grading_customscale();

        $grade = $grading->grade_question(
            $this->make_customscale_question(),
            [
                'option0' => 1,
                'option1' => 2,
                'option2' => 2,
                'option3' => 2,
            ]
        );

        $this->assertEqualsWithDelta(
            0.10,
            $grade,
            0.00001
        );
    }

    /**
     * @covers ::grade_question
     */
    public function test_customscale_two_correct(): void {

        $grading = new \qtype_mtf_grading_customscale();

        $grade = $grading->grade_question(
            $this->make_customscale_question(),
            [
                'option0' => 1,
                'option1' => 1,
                'option2' => 2,
                'option3' => 2,
            ]
        );

        $this->assertEqualsWithDelta(
            0.25,
            $grade,
            0.00001
        );
    }

    /**
     * @covers ::grade_question
     */
    public function test_customscale_three_correct(): void {

        $grading = new \qtype_mtf_grading_customscale();

        $grade = $grading->grade_question(
            $this->make_customscale_question(),
            [
                'option0' => 1,
                'option1' => 1,
                'option2' => 1,
                'option3' => 2,
            ]
        );

        $this->assertEqualsWithDelta(
            0.50,
            $grade,
            0.00001
        );
    }

    /**
     * @covers ::grade_question
     */
    public function test_customscale_four_correct(): void {

        $grading = new \qtype_mtf_grading_customscale();

        $grade = $grading->grade_question(
            $this->make_customscale_question(),
            [
                'option0' => 1,
                'option1' => 1,
                'option2' => 1,
                'option3' => 1,
            ]
        );

        $this->assertEqualsWithDelta(
            1.0,
            $grade,
            0.00001
        );
    }

    /**
     * Ensure fallback behaviour works for non-4-row questions.
     *
     * @covers ::grade_question
     */
    public function test_customscale_fallback(): void {

        $question = $this->make_customscale_question();

        $question->numberofrows = 5;

        $question->rows[5] = (object) [
            'number' => 5,
        ];

        $question->weights[5] = [
            1 => (object) ['weight' => 1.0],
            2 => (object) ['weight' => 0.0],
        ];

        $question->order = [1, 2, 3, 4, 5];

        $grading = new \qtype_mtf_grading_customscale();

        $grade = $grading->grade_question(
            $question,
            [
                'option0' => 1,
                'option1' => 1,
                'option2' => 1,
                'option3' => 1,
                'option4' => 2,
            ]
        );

        $this->assertEqualsWithDelta(
            0.8,
            $grade,
            0.00001
        );
    }
}
