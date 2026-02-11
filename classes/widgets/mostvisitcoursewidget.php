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
 * Table class that contains the list of most visits courses.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;



/**
 * Class Most visit course.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mostvisitcoursewidget extends widgets_info {

    /**
     * @var object $records
     */
    public $filter;

    /**
     * @var string $filter
     */
    public $context = "user";

    /**
     * Course context.
     *
     * @var array
     */
    public $records;

    /**
     * Implemented the constructor.
     * @param int $userid
     * @param string $filter
     */
    public function __construct($userid, $filter = '') {
        global $DB;
        parent::__construct();
        $this->filter = $filter;
        $this->user = $DB->get_record('user', ['id' => $userid]);
        $this->param_sql();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return "line";
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return true;
    }

    /**
     * Get the cache key.
     * @return string
     */
    public function get_cache_key() {
        return  "u_" . $this->user->id . "_mostvisitcourse_" . $this->filter;
    }

    /**
     * Set the report data.
     * @return void
     */
    private function param_sql() {
        global $DB;
        $params = ['userid' => $this->user->id];
        $durationparams = report_helper::get_duration_info($this->filter);
        $durationsql = '';

        if (!empty($durationparams)) {
            $durationsql .= " AND ls.timecreated BETWEEN :timestart AND :timeend";
            $params = array_merge($params, $durationparams);
        }

        if ($this->cache == null) {
            $this->load_cache();
        }

        if (!$this->cache->get($this->get_cache_key())) {
            $sql = "SELECT ls.courseid, count(ls.id) AS visits
            FROM {logstore_standard_log} ls
            JOIN {course} c ON c.id = ls.courseid
            WHERE ls.action = 'viewed' AND ls.target = 'course' AND ls.courseid > 1
            AND ls.userid = :userid $durationsql GROUP BY ls.courseid
            ORDER BY visits DESC";

            $records = $DB->get_records_sql($sql, $params, 0, 10);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());

    }

    /**
     * Get the report data.
     */
    private function get_report_data() {
        global $PAGE;

        $label = [];
        $value = [];
        if (!empty($this->records)) {
            foreach ($this->records as $record) {
                $course = get_course($record->courseid);
                $courselist = new \core_course_list_element($course);
                $value[] = $record->visits;
                $label[] = $courselist->get_formatted_shortname();
            }
        }
        $this->reportdata['label'] = $label;
        $this->reportdata['value'] = $value;
        $this->reportdata['visits'] = get_string('visitstitle', 'report_lmsace_reports');
        $this->reportdata['userid'] = $this->user->id;

    }

    /**
     * Get chart data.
     */
    public function get_data() {
        $this->get_report_data();
        return $this->reportdata;
    }

}
