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
 * Teacher activity completion widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Teacher activity completion line chart widget.
 */
class teacheractivitycompletionwidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "teacher";

    /** @var string Duration filter. */
    public $filter;

    /** @var int Teacher user ID. */
    protected $teacherid;

    /**
     * Constructor.
     * @param int $teacherid
     * @param string $filter
     */
    public function __construct($teacherid, $filter = '') {
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->filter = $filter ?: 'year';
        $this->prepare_chartdata();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return 'line';
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return true;
    }

    /**
     * Get cache key.
     * @return string
     */
    public function get_cache_key() {
        return "t_" . $this->teacherid . "_activitycompletion_" . $this->filter;
    }

    /**
     * Prepare chart data.
     */
    private function prepare_chartdata() {
        global $DB;
        if (!$this->cache->get($this->get_cache_key())) {
            $teachercourses = report_helper::get_teacher_courses($this->teacherid);
            $courseids = array_column((array) $teachercourses, 'courseid');

            $labels = [];
            $values = [];

            // Build 12-month time slots.
            for ($i = 11; $i >= 0; $i--) {
                $time = strtotime("-{$i} months");
                $key = date('Y-m', $time);
                $labels[] = date('M Y', $time);
                $values[$key] = 0;
            }

            if (!empty($courseids)) {
                list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                $timestart = strtotime('-12 months');

                // Portable approach: fetch records and group by month in PHP.
                $sql = "SELECT cmc.id, cmc.timemodified
                    FROM {course_modules_completion} cmc
                    JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                    WHERE cm.course $coursesql
                        AND cmc.completionstate = 1
                        AND cmc.timemodified > :timestart";
                $params = array_merge($courseparams, ['timestart' => $timestart]);
                $records = $DB->get_records_sql($sql, $params);
                foreach ($records as $record) {
                    $key = date('Y-m', $record->timemodified);
                    if (isset($values[$key])) {
                        $values[$key]++;
                    }
                }
            }

            $data = [
                'label' => $labels,
                'value' => array_values($values),
                'teacherid' => $this->teacherid,
            ];
            $this->cache->set($this->get_cache_key(), $data);
        }
        $this->reportdata = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get data.
     * @return array
     */
    public function get_data() {
        return $this->reportdata;
    }
}
