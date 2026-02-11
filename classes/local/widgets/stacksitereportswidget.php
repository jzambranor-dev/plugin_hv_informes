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
 * Table class that contains the list of stack site reports.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class stack site report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stacksitereportswidget extends widgets_info {

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * @var string $filter
     */
    public $filter = '';

    /**
     * Implemented the constructor.
     * @param string $filter
     */
    public function __construct($filter = '') {
        parent::__construct();
        $this->filter = $filter;
        $this->get_report_block();
    }

    /**
     * Get chart type.
     * @return string
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
     * Prepare report data
     */
    private function get_report_block() {
        global $DB;
        $this->reportdata['users'] = count(get_users_listing());
        $this->reportdata['courses'] = count(report_helper::get_course());
        $this->reportdata['enrolments'] = $DB->count_records('user_enrolments');
        $completionsql = 'SELECT count(DISTINCT(cp.id))
            FROM {course_completions} cp
            JOIN {enrol} e ON e.courseid = cp.course
            JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = cp.userid
            WHERE ue.status = 0 AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
            AND cp.timecompleted IS NOT NULL';
        $this->reportdata['completions'] = $DB->count_records_sql($completionsql, ['now1' => time(), 'now2' => time()]);
        $this->reportdata['modules'] = $DB->count_records('course_modules');
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }
}
