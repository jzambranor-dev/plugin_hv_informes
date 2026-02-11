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
 * Table class that contains the list of course active and inactive users.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Class active and inactive users.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseactiveinactiveuserswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $context
     */
    public $context = "course";

    /**
     * @var object $cache
     */
    public $cache;

    /**
     * @var object $course
     */
    public $course;

    /**
     * Course context.
     *
     * @var \context
     */
    public $coursecontext;

    /**
     * Implemented the constructor.
     * @param int $courseid
     */
    public function __construct($courseid) {
        global $DB;
        parent::__construct();
        $this->course = get_course($courseid);
        $this->coursecontext = \context_course::instance($this->course->id);
        $studentroles = \report_lmsace_reports\widgets::get_student_roles();
        $this->studentsql = '';
        $this->studentparams = [];
        if ($studentroles) {
            list($this->studentsql, $this->studentparams) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);
        }
        $this->prepare_chartdata();
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
     * @param string $type
     * @return string
     */
    public function get_cache_key($type) {
        return "c_" . $this->course->id . "_courseactivieinactive_" . $type . "_users";
    }

    /**
     * Get enroll users in course
     * @return array Get enrol users count each month
     */
    private function get_enrolled_users_incourse_year() {
        global $DB;
        $timestart = strtotime('-1year');
        $timeend = time();
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key('enrolled'))) {
            $sql = "SELECT ue.id, ue.timecreated
                        FROM  {user_enrolments} ue
                        JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                        JOIN {role_assignments} ra ON ra.userid = ue.userid
                        JOIN {user} u ON u.id = ue.userid
                        JOIN {course} c ON c.id = e.courseid
                        WHERE ra.contextid = :contextid  AND u.deleted != 1
                        AND u.suspended != 1 AND ue.timecreated BETWEEN :timestart AND :timeend AND ra.roleid $this->studentsql
                        ";
            $params = [
                'courseid' => $this->course->id,
                'contextid' => $this->coursecontext->id,
                'timestart' => $timestart,
                'timeend' => $timeend,
                'starttime' => $timestart,
                'endtime' => $timeend,
            ];
            $params = array_merge($params, $this->studentparams);
            $records = $DB->get_records_sql($sql, $params);
            $list = [];
            foreach ($records as $id => $record) {
                $month = date('M', $record->timecreated);
                if (array_key_exists($month, $list)) {
                    $list[$month] += 1;
                } else {
                    $list[$month] = 1;
                }
            }

            $this->cache->set($this->get_cache_key('enrolled'), $list);
        }
        return $this->cache->get($this->get_cache_key('enrolled'));
    }

    /**
     * Get active users in course
     * @return array Get users count each month
     */
    private function get_active_users_incourse_year() {
        global $DB;
        $data = [];
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key('active'))) {
            $timestart = strtotime('last year');
            $timeend = time();
            $sql = "SELECT ls.id, ls.userid, ls.timecreated
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                    JOIN {role_assignments} ra ON ra.userid = ue.userid
                    JOIN {user} u ON u.id = ue.userid  AND u.deleted != 1
                    JOIN {course} c ON c.id = e.courseid
                    JOIN {logstore_standard_log} ls ON ls.userid = u.id  AND ls.courseid = c.id AND ls.contextid = ra.contextid
                    AND ls.timecreated BETWEEN :starttime AND :endtime
                    WHERE ls.userid = ra.userid AND ls.action ='viewed' AND ls.target = 'course' AND ra.contextid = :contextid
                    AND ra.roleid $this->studentsql AND ue.timecreated BETWEEN :timestart AND :timeend
                    GROUP BY ls.id, ls.userid, ls.timecreated";
            $params = [
                'courseid' => $this->course->id,
                'contextid' => $this->coursecontext->id,
                'timestart' => $timestart,
                'timeend' => $timeend,
                'starttime' => $timestart,
                'endtime' => $timeend,
            ];
            $params = array_merge($params, $this->studentparams);
            $records = $DB->get_records_sql($sql, $params);
            $list = [];
            foreach ($records as $id => $record) {
                $month = date('M', $record->timecreated);
                if (array_key_exists($month, $list)) {
                    array_push($list[$month], $record->userid);
                } else {
                    $list[$month] = [$record->userid];
                }
            }

            array_walk($list, function(&$a) {
                $unique = array_unique(array_values($a));
                $a = count($unique);
            });

            $this->cache->set($this->get_cache_key('active'), $list);
        }
        return $this->cache->get($this->get_cache_key('active'));
    }

    /**
     * Set the report data.
     *
     * @return void
     */
    public function prepare_chartdata() {
        $activeusersvalue = [];
        $inactiveusersvalue = [];
        $labels = [];
        // Get active users.
        $enrolusersrecords = $this->get_enrolled_users_incourse_year();
        $activeusersrecords = $this->get_active_users_incourse_year();
        for ($i = 0; $i < 12; $i++) {
            $time = strtotime('this month') - $i * 30 * 24 * 60 * 60;
            $activeusersvalue[date('M', $time)] = 0;
            $inactiveusersvalue[date('M', $time)] = 0;
            $labels[] = date("M y", $time);
        }

        $prevenrols = 0;
        foreach (array_reverse(array_keys($activeusersvalue)) as $key) {

            $enrolusers = 0;
            if (isset($enrolusersrecords[$key])) {
                $enrolusers = $enrolusersrecords[$key];
            }
            $activeusers = isset($activeusersrecords[$key]) ? $activeusersrecords[$key] : 0;
            $activeusersvalue[$key] = $activeusers;
            $prevenrols = $prevenrols + $enrolusers;
            $inactivevalue = $prevenrols - $activeusers;
            $inactiveusersvalue[$key] = ($inactivevalue);
        }

        $this->reportdata['label'] = array_reverse($labels);
        $this->reportdata['activeusers_data'] = array_values(array_reverse($activeusersvalue));
        $this->reportdata['inactiveusers_data'] = array_values(array_reverse($inactiveusersvalue));
        $this->reportdata['activeusers'] = get_string('activeusers', 'report_lmsace_reports');
        $this->reportdata['inactiveusers'] = get_string('inactiveusers', 'report_lmsace_reports');
        $this->reportdata['title'] = get_string('active_inactiveuser_title', 'report_lmsace_reports');

    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }
}
