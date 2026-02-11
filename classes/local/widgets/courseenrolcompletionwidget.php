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
 * Table class that contains the list of course enrolments and completion reports.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class that contains the list of course enrolments and completions.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseenrolcompletionwidget extends widgets_info {

    /**
     * @var object $records
     */
    private $records;

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $context
     */
    public $context = "course";

    /**
     * Course context.
     *
     * @var \context
     */
    public $coursecontext;

    /**
     * Selected course.
     *
     * @var int
     */
    public $selectedcourse;

    /**
     * Call to.
     *
     * @var string
     */
    protected $callto;

    /**
     * Implemented the constructor.
     * @param int $courseid
     * @param string $filter
     * @param array $sqlparams
     * @param int $selectedcourse
     * @param string $callto
     */
    public function __construct($courseid, $filter = '', $sqlparams = [], $selectedcourse = 0, $callto = '') {
        parent::__construct();
        $this->filter = $filter;
        $this->course = get_course($courseid);
        $this->coursecontext = \context_course::instance($courseid);
        $this->selectedcourse = $selectedcourse;
        $this->callto = $callto;
        $this->param_sql($sqlparams);
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return 'doughnut';
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
        $key = "c_" . $this->course->id;

        if ($this->callto) {
            $key .= "_". $this->callto;
        }

        $key .= "_courseenrolcompletion_" . $this->filter;

        return $key;
    }

    /**
     * Set the report data.
     * @param array $sqlparams
     * @return void
     */
    private function param_sql($sqlparams = []) {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $completionmonthsql = '';
            $enrolmentsmonthsql = '';
            $completionfiltersql = '';
            $enrolmentsmonthfiltersql = '';
            $studentsql = '';
            $studentparams = [];
            $durationparams = [];
            if (!empty($this->selectedcourse)) {
                $courseid = $this->selectedcourse;
                $enrolmentsmonthsql = 'AND ue.timestart BETWEEN :timestart AND :timeend';
                $completionmonthsql = 'AND cp.timecompleted BETWEEN :starttime AND :endtime';
                $contextid = \context_course::instance($courseid)->id;
            } else {
                $courseid = $this->course->id;
                $contextid = $this->coursecontext->id;
                $duration = report_helper::get_duration_info($this->filter);
                if (!empty($duration)) {
                    $durationparams = [
                        'timestart' => $duration['timestart'],
                        'timeend' => $duration['timeend'],
                        'starttime' => $duration['timestart'],
                        'endtime' => $duration['timeend'],
                    ];
                    $enrolmentsmonthfiltersql = 'AND ue.timecreated BETWEEN :timestart AND :timeend';
                    $completionfiltersql = 'AND cp.timecompleted BETWEEN :starttime AND :endtime';
                }
            }
            $studentroles = \report_lmsace_reports\widgets::get_student_roles();
            if ($studentroles) {
                list($studentsql, $studentparams) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);
            }

            $sql = "SELECT c.id, count(ue.id) AS enrolments,
            (SELECT count(cp.id)
            FROM {user_enrolments} eue
            JOIN {enrol} ee ON ee.id = eue.enrolid AND ee.courseid = :course
            JOIN {role_assignments} era ON era.userid = eue.userid AND era.contextid = :coursecontext1
            JOIN {course_completions} cp ON cp.course = ee.courseid AND cp.userid = eue.userid
            WHERE eue.status = 0 AND eue.timestart < :now1 AND (eue.timeend = 0 OR eue.timeend > :now2)
            AND cp.timecompleted IS NOT NULL $completionmonthsql $completionfiltersql
            GROUP BY cp.course
            ) AS completions
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
            JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.contextid = :coursecontext2
            JOIN {user} u ON u.id = ue.userid AND u.deleted != 1
            JOIN {course} c ON c.id = e.courseid
            WHERE ue.status = 0 AND ue.timestart < :now3 AND (ue.timeend = 0 OR ue.timeend > :now4) AND ra.roleid $studentsql
            $enrolmentsmonthsql $enrolmentsmonthfiltersql
            GROUP BY c.id";

            $params = [
                'courseid' => $courseid,
                'course' => $courseid,
                'coursecontext1' => $contextid,
                'coursecontext2' => $contextid,
                'now1' => time(),
                'now2' => time(),
                'now3' => time(),
                'now4' => time(),
            ];
            if (!empty($sqlparams)) {
                $durationparams = $sqlparams;
            }
            $params = array_merge($params, $durationparams, $studentparams);
            $records = $DB->get_record_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
        $this->reportdata = report_helper::chart_values($this->records);
        $this->reportdata['courseid'] = $this->course->id;
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }

    /**
     * Get the report records.
     */
    public function get_report() {
        return $this->records;
    }
}
