<?php
/**
 * Provides grading functionality for custom scale scoring method.
 *
 * 0/4 = 0%
 * 1/4 = 10%
 * 2/4 = 25%
 * 3/4 = 50%
 * 4/4 = 100%
 *
 * @author      Nguyen Duy Hung (ktol888hnd@gmail.com)
 * @package     qtype_mtf
 * @copyright   2026 NDHv {@link nguyenduyhung.io.vn/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(
    $CFG->dirroot .
    '/question/type/mtf/grading/qtype_mtf_grading.class.php'
);

class qtype_mtf_grading_customscale extends qtype_mtf_grading {

    /** @var string TYPE */
    const TYPE = 'customscale';

    public function get_name() {
        return self::TYPE;
    }

    public function get_title() {
        return get_string(
            'scoringcustomscale',
            'qtype_mtf'
        );
    }

    public function grade_question($question, $answers) {

        $correctrows = 0;

        foreach ($question->order as $key => $rowid) {

            $row = $question->rows[$rowid];

            $grade = $this->grade_row(
                $question,
                $key,
                $row,
                $answers
            );

            if ($grade > 0) {
                ++$correctrows;
            }
        }

        $totalrows = count($question->rows);

        if ($totalrows === 4) {

            $scale = [
                0 => 0.00,
                1 => 0.10,
                2 => 0.25,
                3 => 0.50,
                4 => 1.00,
            ];

            return $scale[$correctrows];
        }
        // Custom scale is defined only for 4-row questions.
        // Other question sizes fall back to proportional grading.
        return 1.0 * $correctrows / $totalrows;
    }
}
