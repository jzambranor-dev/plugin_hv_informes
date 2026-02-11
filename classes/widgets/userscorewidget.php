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
 * Table class that contains the list of user scores.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;



/**
 * Class user My Scores.
 */
class userscorewidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var array $records
     */
    private $records;

    /**
     * @var string $context
     */
    public $context = "user";

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
        $studentroles = \report_lmsace_reports\widgets::get_student_roles();
        $this->studentsql = '';
        $this->studentparams = [];
        if ($studentroles) {
            list($this->studentsql, $this->studentparams) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);
        }
        $this->param_sql();
    }

    /**
     * Get chart type.
     */
    public function get_charttype() {
        return "bar";
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
        return "u_" . $this->user->id . "_userscore_" . $this->filter;
    }

    /**
     * Load the data.
     */
    public function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $sql = "SELECT  DISTINCT(c.id), g.finalgrade AS score
            FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                JOIN {context} cn ON cn.instanceid = e.courseid AND cn.contextlevel = :contextlevel
                JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.contextid = cn.id
                JOIN {user} u ON u.id = ue.userid  AND u.deleted != 1
                LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id  AND g.userid = u.id
                WHERE   ra.roleid $this->studentsql  AND ue.userid = :userid
                ORDER BY g.finalgrade  DESC LIMIT 20";
            $student = $DB->get_record('role', ['shortname' => 'student']);
            $params = [
                'userid' => $this->user->id,
                'contextlevel' => CONTEXT_COURSE,
            ];
            $params += $this->studentparams;
            $records = $DB->get_records_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get report data.
     * @return array Get enrol users count each month
     */
    private function get_report_data() {
        $label = [];
        $value = [];
        if (!empty($this->records)) {
            foreach ($this->records as $record) {
                $course = report_helper::get_course_info($record->id);
                $label[] = $course->get_formatted_shortname();
                $value[] = !empty($record->score) ? round($record->score) : 0;
            }
        }

        $this->reportdata['label'] = $label;
        $this->reportdata['value'] = $value;
        $this->reportdata['strscore'] = get_string('strscore', 'report_lmsace_reports');
    }

    /**
     * Get chart data.
     * @return string
     */
    public function get_data() {
        $this->get_report_data();
        return $this->reportdata;
    }

}
