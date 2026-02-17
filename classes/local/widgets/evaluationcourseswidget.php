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
 * Evaluation courses table widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Evaluation courses table widget rendering a dynamic table of teacher courses.
 */
class evaluationcourseswidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "evaluation";

    /** @var int Teacher user ID. */
    protected $teacherid;

    /**
     * Constructor.
     * @param int $teacherid
     */
    public function __construct($teacherid) {
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->get_report_data();
    }

    /**
     * Get chart type.
     * @return null
     */
    public function get_charttype() {
        return null;
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return false;
    }

    /**
     * Prepare report data.
     */
    private function get_report_data() {
        global $CFG;

        require_once($CFG->dirroot . '/report/lmsace_reports/classes/table/evaluationcourses_table.php');

        $table = new \report_lmsace_reports\table\evaluationcourses_table(
            'evaluation-courses-table', $this->teacherid
        );

        ob_start();
        $table->out(20, true);
        $this->reportdata = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Get data.
     * @return string
     */
    public function get_data() {
        return $this->reportdata;
    }
}
