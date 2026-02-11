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
 * Table class that contains the list of course visits.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use context_course;

/**
 * Class course visits.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursevisitswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var array $chartdata
     */
    private $chartdata = [];

    /**
     * @var string $context
     */
    public $context = "course";

    /**
     * Course context.
     *
     * @var array
     */
    public $records;

    /**
     * Implemented the constructor.
     * @param int $courseid
     * @param string $filter
     */
    public function __construct($courseid, $filter = '') {
        parent::__construct();
        if (!$filter) {
            $filter = 'year';
        }
        $this->course = get_course($courseid);
        $this->filter = $filter;
        $this->param_sql();
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
     * @return string
     */
    public function is_chart() {
        return true;
    }

    /**
     * Get the cache key.
     * @return string
     */
    public function get_cache_key() {
        return "c_" . $this->course->id . "_coursevisits_" . $this->filter;
    }

    /**
     * Set the report data.
     * @return void
     */
    private function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $timestart = strtotime("-1". $this->filter);
            $timeend = time();
            list($studentsql, $studentparams) = $this->get_students_sql();
            $usersql = '';
            if ($studentsql) {
                $usersql = "ls.userid $studentsql AND";
            }
            $sql = "SELECT FLOOR(ls.timecreated / 86400) AS userdate, COUNT(DISTINCT userid) as coursevisits
                    FROM {logstore_standard_log} ls
                    WHERE ls.courseid = :courseid AND ls.action = 'viewed' AND ls.target = 'course'
                    AND $usersql ls.timecreated BETWEEN :timestart AND :timeend
                    GROUP BY FLOOR(ls.timecreated / 86400)";
            $params = [
                'courseid' => $this->course->id,
                'timestart' => $timestart,
                'timeend' => $timeend,
            ];
            $params += $studentparams;
            $records = $DB->get_records_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());

    }

    /**
     * Get students users.
     * @return void
     */
    public function get_students_sql() {
        global $DB;
        if ($this->context == 'course' && !empty($this->course)) {
            $coursecontext = context_course::instance($this->course->id);
            $students = get_enrolled_users($coursecontext, 'report/lmsace_reports:definestudents');
            if ($students) {
                return $DB->get_in_or_equal(array_keys($students), SQL_PARAMS_NAMED, 'user');
            }
        }
        return ['', []];
    }

    /**
     * Get chart data.
     */
    public function get_data() {
        return $this->chartdata;
    }

    /**
     * Prepare chartdata.
     */
    public function prepare_chartdata() {
        $labels = [];
        $values = [];
        $labelcount = $this->get_label_count();
        for ($i = 0; $i < $labelcount; $i++) {
            $time = time() - $i * 24 * 60 * 60;
            $values[floor($time / (24 * 60 * 60))] = 0;
            $labels[] = date("d M y", $time);
        }
        if (!empty($this->records)) {
            foreach (array_keys($values) as $key) {
                if (!isset($this->records[$key])) {
                    continue;
                }
                $values[$key] = $this->records[$key]->coursevisits;
            }
        }

        // Labels.
        $labels = array_reverse($labels);
        $values = array_reverse($values);

        // Current is filtered by year then grouped by month.
        if ($this->filter == 'year') {
            $groupedbymonth = [];
            $combined = array_combine(array_values($labels), array_values($values));
            foreach ($combined as $date => $value) {
                $datetime = explode(' ', $date);
                // Remove the day from the label date.
                array_shift($datetime);
                // Create month with year of the data.
                $month = trim(implode(' ', $datetime));
                // Increase the visits by month.
                if (array_key_exists($month, $groupedbymonth)) {
                    $groupedbymonth[$month] += $value;
                } else {
                    $groupedbymonth[$month] = $value;
                }
            }
            // Use the grouped month data as data for year report.
            $values = array_values($groupedbymonth);
            // Use the grouped months.
            $labels = array_keys($groupedbymonth);
        }

        $this->chartdata['label'] = $labels;
        $this->chartdata['value'] = $values;
    }

}
