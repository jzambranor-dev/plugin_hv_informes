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
 * Get Reports widgets.
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports;

use report_lmsace_reports\report_helper;

/**
 * Define widgets.
 */
class widgets {

    /**
     * Get the report all widgets.
     */
    public static function get_widgets() {
        return report_helper::load_widgets();
    }

    /**
     * Get the students in the course.
     * @param int $courseid
     * @param array $durationparams
     */
    public static function get_enroluser_incourse($courseid, $durationparams) {
        global $DB;

        $durationsql = '';
        $coursecontext = \context_course::instance($courseid);
        $studentroles = self::get_student_roles();
        if ($studentroles) {
            list($studentsql, $studentparams) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);
        }

        $params = [
            'courseid' => $courseid,
            'contextid' => $coursecontext->id,
        ];
        $params += $studentparams;
        if (!empty($durationparams)) {
            $params = array_merge($params, $durationparams);
            $durationsql .= "AND ue.timecreated BETWEEN :timestart AND :timeend";
        }

        $sql = "SELECT c.id, COUNT(ue.id) AS enrolments
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            JOIN {role_assignments} ra ON ra.userid = ue.userid
            WHERE ra.contextid = :contextid $durationsql AND ra.roleid $studentsql
            AND e.courseid = :courseid GROUP BY c.id";
        $records = $DB->get_record_sql($sql, $params);
        return !empty($records) ? $records->enrolments : 0;
    }

    /**
     * Get the activities state.
     * @param int $courseid
     * @param array $durationparams
     * @return array records
     */
    public static function get_course_activity_completion($courseid, $durationparams = []) {
        global $DB;
        $durationsql = '';
        $coursecontext = \context_course::instance($courseid);
        $student = $DB->get_record('role', ['shortname' => 'student']);
        if (!$student) {
            return [];
        }
        $params = [
            'courseid' => $courseid,
            'contextid' => $coursecontext->id,
            'studentid' => $student->id,
        ];
        if (!empty($durationparams)) {
            $params = array_merge($params, $durationparams);
            $durationsql .= "AND cmp.timemodified BETWEEN :timestart AND :timeend ";
        }
        $sql = "SELECT cm.id, cm.instance AS instanceid, m.name AS modulename, count(cmp.id) AS completions
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
            JOIN {role_assignments} ra ON ra.userid = ue.userid
            JOIN {user} u ON u.id = ue.userid  AND u.deleted != 1
            JOIN {course} c ON c.id = e.courseid
            JOIN {course_modules} cm ON cm.course = c.id
            LEFT JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {course_modules_completion} cmp ON cmp.coursemoduleid = cm.id AND cmp.userid = u.id
                                                AND cmp.completionstate = 1
            $durationsql
            WHERE ra.contextid = :contextid AND ra.roleid = :studentid
            GROUP BY cm.id, cm.instance, m.name";
        $records = $DB->get_records_sql($sql, $params);
        return $records;
    }

    /**
     * Get course course completion users.
     *
     * @param int $courseid
     * @param array $durationparams
     * @param bool $count
     */
    public static function get_course_completion_users($courseid, $durationparams = [], $count = false) {
        global $DB;
        $completiondurationsql = '';
        if (!empty($durationparams)) {
            $completiondurationsql = 'AND timecompleted BETWEEN :timestart AND :timeend';
        }

        $sql = "SELECT DISTINCT(cp.userid)
        FROM {course} c
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        JOIN {course_completions} cp ON cp.course = e.courseid AND cp.userid = ue.userid
        JOIN {user} u ON u.id = ue.userid  AND u.deleted != 1
        WHERE c.id = :courseid AND ue.status = 0
        AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2) AND cp.timecompleted IS NOT NULL
        $completiondurationsql";

        $params = [
            'courseid' => $courseid,
            'now1' => time(),
            'now2' => time(),
        ];
        $params = array_merge($params, $durationparams);
        $records = $DB->get_records_sql($sql, $params);
        if ($count) {
            return count($records);
        }

        return $records;
    }

    /**
     * Get count registered users for timebased
     * @param array $durationparams
     * @return int users.
     */
    public static function get_timebased_siteusers($durationparams = []) {
        global $DB;
        $durationsql = '';
        if (!empty($durationparams)) {
            $durationsql = 'AND timecreated BETWEEN :timestart AND :timeend';
        }
        $sql = "SELECT count(id) FROM {user} WHERE id > 2 $durationsql";
        $users = $DB->count_records_sql($sql, $durationparams);
        return $users;
    }

    /**
     * Get count the site user visits
     * @param array $durationparams
     * @return int visits
     *
     */
    public static function get_timebased_visits($durationparams = []) {
        global $DB;
        $durationsql = '';
        if (!empty($durationparams)) {
            $durationsql = "AND timecreated BETWEEN :timestart AND :timeend";
        }
        $sql = "SELECT count(id) FROM {logstore_standard_log}
        WHERE action = 'loggedin' AND target = 'user' AND userid > 2  $durationsql";
        $visits = $DB->count_records_sql($sql, $durationparams);
        return $visits;
    }

    /**
     * Get the students roles.
     * @return array
     */
    public static function get_student_roles() {
        $studentroles = get_archetype_roles('student');
        return array_keys($studentroles);
    }

    /**
     * Get course status (i.e complete, inprogress)
     * @param int $courseid
     * @param bool $enrolusers
     * @param string $filter
     * @return array list of course status.
     */
    public static function get_course_progress_status($courseid, $enrolusers = false, $filter = '') {
        global $DB;

        $filterduration = '';

        $duration = report_helper::get_duration_info($filter, 'all');
        $durationparams = [];
        if (!empty($duration)) {
            $timestart = strtotime("-1 $filter");
            $filterduration = 'AND ue.timecreated > :timestart';
            $durationparams = ['timestart' => $duration['timestart']];
        }
        $studentroles = self::get_student_roles();
        $rolesql = '';
        $roleparams = [];
        if (!empty($studentroles)) {
            list($rolesql, $roleparams) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);
        }
        $context = \context_course::instance($courseid);
        $sql = "SELECT DISTINCT(ue.userid) AS id, cc.timecompleted
        FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
            JOIN {role_assignments} ra ON ra.userid = ue.userid
            JOIN {user} u ON u.id = ue.userid  AND u.deleted != 1
            JOIN {course} c ON c.id = e.courseid
            LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
            WHERE ra.contextid = :coursecontext AND ue.status = 0 AND ue.timestart < :now1 AND
            (ue.timeend = 0 OR timeend > :now2) AND ra.roleid $rolesql $filterduration";

        $params = [
            'courseid' => $courseid,
            'coursecontext' => $context->id,
            'now1' => time(),
            'now2' => time(),
        ];
        $params = array_merge($params, $durationparams, $roleparams);
        $records = $DB->get_records_sql($sql, $params);
        $data = self::course_status_details($records, $enrolusers, $context, $courseid);

        $duration = report_helper::get_duration_info($filter, 'all');
        $params = [];
        $params['course'] = $courseid;

        $durationsql = '';
        if ($duration) {
            $durationsql = ' AND timecompleted > :timestart';
            $params['timestart'] = $timestart;
        }
        $completionsql = "SELECT count(*) FROM {course_completions} WHERE course = :course
            AND timecompleted IS NOT NULL $durationsql";
        if (is_array($data)) {
            $data['completed'] = $DB->count_records_sql($completionsql, $params);
        }
        return $data;
    }

    /**
     * Get details course status
     * @param array $records
     * @param bool $enrolusers
     * @param object $context
     * @param object $courseid
     * @return array.
     */
    public static function course_status_details($records, $enrolusers, $context, $courseid) {

        $enrolments = 0;
        $incompleted = 0;
        $notstarted = 0;
        if (!empty($records)) {
            $enrolments = count($records);
            foreach ($records as $record) {
                if (!$record->timecompleted) {
                    if (self::check_visit_course($record->id, $context->id)) {
                        $incompleted++;
                    } else {
                        $notstarted++;
                    }
                }
            }
        }

        if ($enrolusers) {
            return $enrolments;
        }
        $data  = [
            'notstarted' => $notstarted,
            'incompleted' => $incompleted,
            'enrolments' => $enrolments,
        ];
        return $data;
    }

    /**
     * Check user visit course or not
     * @param int $userid
     * @param int $contextid
     * @return bool visiable status
     */
    public static function check_visit_course($userid, $contextid) {
        global $DB;

        $params = [
            'action' => 'viewed',
            'target' => 'course',
            'contextid' => $contextid,
            'userid' => $userid,
        ];

        $visitlog = $DB->get_records('logstore_standard_log', $params);
        if (!empty($visitlog)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the enrolment and completion details.
     * @param int $timestart
     * @param int $timeend
     * @param string $filter
     * @return array $data
     */
    public static function enrollment_compltion_info_bymonth($timestart, $timeend, $filter) {
        $params = [
            'timestart' => $timestart,
            'timeend' => $timeend,
            'starttime' => $timestart,
            'endtime' => $timeend,
        ];
        $courses = report_helper::get_course(0, true);
        $label = [];
        $enrolment = [];
        $completion = [];
        foreach ($courses as $id => $course) {
            $enrolcompletion = new \report_lmsace_reports\local\widgets\courseenrolcompletionwidget($course->id, '',
                $params, 0, "infoby_month_" . $filter);
            $list = $enrolcompletion->get_report();
            $label[] = $course->fullname;
            $enrolment[] = !empty($list->enrolments) ? $list->enrolments : 0;
            $completion[] = !empty($list->completions) ? $list->completions : 0;
        }
        $data['label'] = $label;
        $data['enrolment'] = $enrolment;
        $data['completion'] = $completion;
        return $data;
    }
}
