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
 * Table class that contains the list of course high score reports.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Class that contains the list of sitvisits.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursehighscorewidget extends widgets_info {

    /**
     * @var string $context
     */
    public $context = "course";

    /**
     * @var object $records
     */
    private $records;

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
        $this->prepare_chardata();
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
        return "c_" . $this->course->id . "_coursehighscore";
    }

    /**
     * Get course highest score.
     */
    public function get_course_highest_score() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $sql = "SELECT  DISTINCT(ue.userid) AS id, u.username AS username, g.finalgrade, g.itemid
                FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                    JOIN {role_assignments} ra ON ra.userid = ue.userid
                    JOIN {user} u ON u.id = ue.userid  AND u.deleted != 1
                    JOIN {course} c ON c.id = e.courseid
                    LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                    LEFT JOIN {grade_grades} g ON g.itemid = gi.id  AND g.userid = u.id
                    WHERE ra.contextid = :coursecontext AND ra.roleid $this->studentsql
                    ORDER BY g.finalgrade DESC Limit 20";
            $params = [
                'courseid' => $this->course->id,
                'coursecontext' => $this->coursecontext->id,
            ];
            $params += $this->studentparams;
            $records = $DB->get_records_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
    }

    /**
     * Set the report data.
     * @return void
     */
    public function prepare_chardata() {
        $label = [];
        $scoredata = [];
        $this->get_course_highest_score();
        if (!empty($this->records)) {
            foreach ($this->records as $record) {
                $label[] = $record->username;
                $scoredata[] = !empty($record->finalgrade) ? round($record->finalgrade) : 0;
            }
        }
        $this->reportdata['label'] = $label;
        $this->reportdata['score'] = $scoredata;
        $this->reportdata['strscore'] = get_string('strscore', 'report_lmsace_reports');
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }

}
