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
 * Table class that contains the consolidated evaluation data for all teachers.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\table;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/tablelib.php');
require_once($CFG->dirroot. '/report/lmsace_reports/lib.php');

/**
 * Consolidated evaluation table showing all teachers and their courses.
 */
class evaluationconsolidated_table extends \table_sql {

    /** @var int Month filter timestamp (first day of month). */
    protected $evalmonth;

    /**
     * Constructor.
     * @param string $uniqueid
     * @param int $evalmonth Timestamp of first day of month to filter, 0 for all.
     */
    public function __construct($uniqueid, $evalmonth = 0) {
        parent::__construct($uniqueid);
        $this->evalmonth = $evalmonth;
    }

    /**
     * Define table field definitions and filter data.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $columns = ['teachername', 'coursename', 'totalstudents', 'completed', 'notevaluated',
            'avggrade', 'completionrate'];
        $headers = [
            get_string('teacher', 'report_lmsace_reports'),
            get_string('coursename', 'report_lmsace_reports'),
            get_string('totalstudents', 'report_lmsace_reports'),
            get_string('coursecompletions', 'report_lmsace_reports'),
            get_string('notevaluated', 'report_lmsace_reports'),
            get_string('averagegrade', 'report_lmsace_reports'),
            get_string('completionrate', 'report_lmsace_reports'),
        ];
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->no_sorting('totalstudents');
        $this->no_sorting('completed');
        $this->no_sorting('notevaluated');
        $this->no_sorting('avggrade');
        $this->no_sorting('completionrate');
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);
        $this->guess_base_url();
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Set the context of the current block.
     *
     * @return \context
     */
    public function get_context(): \context {
        return \context_system::instance();
    }

    /**
     * Check if the current user has the capability to view this table.
     *
     * @return bool
     */
    public function has_capability(): bool {
        return has_capability('report/lmsace_reports:viewevaluationreports', $this->get_context());
    }

    /**
     * Set the base url of the table, used in the ajax data update.
     *
     * @return void
     */
    public function guess_base_url(): void {
        global $PAGE;
        $this->baseurl = $PAGE->url;
    }

    /**
     * Set the sql query to fetch consolidated evaluation data.
     *
     * @param int $pagesize
     * @param boolean $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $roles = get_roles_with_capability("report/lmsace_reports:viewcoursereports");
        $roleids = array_keys($roles);
        if (empty($roleids)) {
            $this->rawdata = [];
            return;
        }

        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        $params = [
            'contextlevel' => CONTEXT_COURSE,
        ];
        $params = array_merge($params, $roleparams);

        $uniqueid = $DB->sql_concat('ra.userid', "'-'", 'c.id');
        $select = "$uniqueid AS id,
            ra.userid AS teacherid,
            u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
            u.middlename, u.alternatename,
            c.id AS courseid, c.fullname AS coursename";
        $from = "{role_assignments} ra
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel
            JOIN {course} c ON c.id = ctx.instanceid
            JOIN {user} u ON u.id = ra.userid AND u.deleted = 0";
        $where = "ra.roleid $rolesql";

        $this->set_sql($select, $from, $where, $params);
        parent::query_db($pagesize, false);
    }

    /**
     * Generate the teachername column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_teachername($row) {
        global $PAGE;
        $fullname = fullname($row);
        if ($this->is_downloading()) {
            return $fullname;
        }
        $url = new \moodle_url($PAGE->url, [
            'report' => 'evaluationreport',
            'evalteacher' => $row->teacherid,
        ]);
        return \html_writer::link($url, $fullname);
    }

    /**
     * Generate the coursename column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursename($row) {
        global $PAGE;
        if ($this->is_downloading()) {
            return format_string($row->coursename);
        }
        $url = new \moodle_url($PAGE->url, [
            'report' => 'evaluationreport',
            'evalteacher' => $row->teacherid,
            'evalcourse' => $row->courseid,
        ]);
        return \html_writer::link($url, format_string($row->coursename));
    }

    /**
     * Generate the totalstudents column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_totalstudents($row) {
        return \report_lmsace_reports\widgets::get_course_progress_status($row->courseid, true);
    }

    /**
     * Generate the completed column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_completed($row) {
        if ($this->evalmonth) {
            return $this->get_completions_in_month($row->courseid);
        }
        return \report_lmsace_reports\widgets::get_course_completion_users($row->courseid, [], true);
    }

    /**
     * Generate the notevaluated column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_notevaluated($row) {
        global $DB;
        $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($row->courseid, true);

        $sql = "SELECT COUNT(DISTINCT cmc.userid)
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                WHERE cm.course = :courseid AND cm.deletioninprogress = 0";
        $params = ['courseid' => $row->courseid];

        if ($this->evalmonth) {
            $monthend = strtotime('+1 month', $this->evalmonth);
            $sql .= " AND cmc.timemodified >= :timefrom AND cmc.timemodified < :timeto";
            $params['timefrom'] = $this->evalmonth;
            $params['timeto'] = $monthend;
        }

        $witheval = $DB->count_records_sql($sql, $params);
        $without = $enrolled - $witheval;
        return $without > 0 ? $without : 0;
    }

    /**
     * Generate the avggrade column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_avggrade($row) {
        global $DB;
        $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 10) as avggrade
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gi.id = gg.itemid
            WHERE gi.itemtype = 'course' AND gi.courseid = :courseid
            AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
        $params = ['courseid' => $row->courseid];

        if ($this->evalmonth) {
            $monthend = strtotime('+1 month', $this->evalmonth);
            $sql .= " AND gg.timemodified >= :timefrom AND gg.timemodified < :timeto";
            $params['timefrom'] = $this->evalmonth;
            $params['timeto'] = $monthend;
        }

        $result = $DB->get_record_sql($sql, $params);
        $avg = ($result && $result->avggrade !== null) ? round($result->avggrade, 1) : 0;
        return $avg . '/10';
    }

    /**
     * Generate the completionrate column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_completionrate($row) {
        $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($row->courseid, true);
        if ($enrolled == 0) {
            return '0%';
        }
        if ($this->evalmonth) {
            $completed = $this->get_completions_in_month($row->courseid);
        } else {
            $completed = \report_lmsace_reports\widgets::get_course_completion_users($row->courseid, [], true);
        }
        $rate = round(($completed / $enrolled) * 100, 1);
        return $rate . '%';
    }

    /**
     * Get course completions within the filtered month.
     *
     * @param int $courseid
     * @return int
     */
    protected function get_completions_in_month($courseid) {
        global $DB;
        $monthend = strtotime('+1 month', $this->evalmonth);
        $sql = "SELECT COUNT(cc.id)
                FROM {course_completions} cc
                WHERE cc.course = :courseid
                AND cc.timecompleted IS NOT NULL
                AND cc.timecompleted >= :timefrom
                AND cc.timecompleted < :timeto";
        return $DB->count_records_sql($sql, [
            'courseid' => $courseid,
            'timefrom' => $this->evalmonth,
            'timeto' => $monthend,
        ]);
    }
}
