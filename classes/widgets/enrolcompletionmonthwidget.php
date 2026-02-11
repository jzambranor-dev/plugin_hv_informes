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
 * Table class that contains the list of enrollments and completion by month.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Class enrollments and completion by month.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolcompletionmonthwidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * Implemented the constructor.
     * @param string $filter
     */
    public function __construct($filter = '') {
        $this->filter = $filter;
        $this->timestart = strtotime(date("y-m-01"));
        $this->timeend = time();
        $this->get_report_data();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return "pie";
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return true;
    }

    /**
     * Prepare the report data.
     */
    private function get_report_data() {
        if ($this->filter) {
            $this->timestart = strtotime($this->filter);
            $this->timeend = strtotime($this->filter. "+1months");
        }
        $this->reportdata = \report_lmsace_reports\widgets::enrollment_compltion_info_bymonth($this->timestart,
            $this->timeend, $this->filter);
        $this->reportdata['strenrolment'] = get_string('enrolments', 'report_lmsace_reports');
        $this->reportdata['strcompletion'] = get_string('completions', 'report_lmsace_reports');
        $this->reportdata['title'] = get_string('enrolmentcompletion_month', 'report_lmsace_reports');
    }

    /**
     * Get chart data.
     */
    public function get_data() {
        return $this->reportdata;
    }

    /**
     * Get the report table.
     */
    public function get_report_table() {
    }
}
