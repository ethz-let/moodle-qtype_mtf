<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * qtype_mtf grading class for scoringmethod subpoints with deduction
 *
 * @package     qtype_mtf
 * @author      Philipp E. Imhof (philipp.imhof@ksso.ch)
 * @copyright   2023 ETHZ {@link http://ethz.ch/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/mtf/grading/qtype_mtf_grading_subpoints.class.php');

/**
 * Provides grading functionality for scoring method subpoints with deduction
 *
 * @package     qtype_mtf
 * @copyright   2016 ETHZ {@link http://ethz.ch/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_mtf_grading_subpointdeduction extends qtype_mtf_grading_subpoints {

    /** @var string TYPE */
    const TYPE = 'subpointdeduction';

    /**
     * Returns the question's grade.
     * @param object $question
     * @param array $answers
     * @return int
     */
    public function grade_question($question, $answers) {
        $totalrows = count($question->rows);
        $correctrows = 0;
        $emptyrows = 0;
        foreach ($question->order as $key => $rowid) {
            $row = $question->rows[$rowid];
            if (!$question->is_answered($answers, $key)) {
                ++$emptyrows;
                continue;
            }
            $grade = $this->grade_row($question, $key, $row, $answers);
            if ($grade > 0) {
                ++$correctrows;
            }
        }
        $wrongrows = $totalrows - $correctrows - $emptyrows;
        // For each correct response, the student gets subpoints.
        // That is: max. points divided by number of options times number of correct options.
        // If a deduction is set (and allowed), the corresponding proportion will be subtracted for
        // each wrong anser.
        $grade = 1.0 * ($correctrows - $wrongrows * $question->deduction) / $totalrows;
        $grade = max(0, $grade);

        return $grade;
    }
}
