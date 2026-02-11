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
 * Table class that contains the list of top course completion .
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class top course completion.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topcoursecompletionwidget extends widgets_info {

    /**
     * @var array $records
     */
    private $records;

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
     * @param int $userid
     */
    public function __construct($filter = '', $userid = 0) {
        parent::__construct();
        if (!$filter) {
            $filter = 'all';
        }
        $this->filter = $filter;
        $this->param_sql();
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
     * Get the cache key.
     * @return string
     */
    public function get_cache_key() {
        return "topcoursecompletion_" . $this->filter;
    }

    /**
     * Load the data.
     */
    private function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $durationparams = report_helper::get_duration_info($this->filter);
            $durationsql = '';
            $params = [];
            if (!empty($durationparams)) {
                $durationsql .= " WHERE cc.timecompleted BETWEEN :timestart AND :timeend";
                $params = array_merge($params, $durationparams);
            }
            $sql = "SELECT c.id, c.fullname, c.shortname, COUNT(cc.id) as completions
                    FROM {course} c
                    JOIN {enrol} e ON e.courseid = c.id
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id
                    JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
                    $durationsql GROUP BY c.id, c.fullname, c.shortname
                    ORDER BY completions DESC Limit 10";
            $records = $DB->get_records_sql($sql, $params);
            $data = [];
            foreach ($records as $record) {
                $course = get_course($record->id);
                $courselist = new \core_course_list_element($course);
                $record->fullname = $courselist->get_formatted_name();
                $data[] = $record;
            }
            $this->cache->set($this->get_cache_key(), $data);
        }
        $this->reportdata = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        global $OUTPUT, $PAGE;

        return $this->records = $OUTPUT->render_from_template('report_lmsace_reports/topcompletion_info',
            ((!empty($this->reportdata)) ? array_values($this->reportdata) : ['noresult' => true]) );
    }
}
