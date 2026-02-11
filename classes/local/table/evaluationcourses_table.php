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
 * Table class that contains the list of evaluation courses.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\table;

defined('MOODLE_INTERNAL') || die('No direct access');

use core_table\dynamic as dynamic_table;
use report_lmsace_reports\report_helper;

require_once($CFG->dirroot.'/lib/tablelib.php');
require_once($CFG->dirroot. '/report/lmsace_reports/lib.php');

/**
 * List of evaluation courses table.
 */
class evaluationcourses_table extends \table_sql implements dynamic_table {

    /** @var int Teacher user ID. */
    protected $teacherid;

    /**
     * Constructor.
     * @param string $uniqueid
     * @param int $teacherid
     */
    public function __construct($uniqueid, $teacherid = 0) {
        parent::__construct($uniqueid);
        $this->teacherid = $teacherid;
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
        $columns = ['coursename', 'categoryname', 'enrolled', 'completed', 'withoutevaluations', 'certificates'];
        $headers = [
            get_string('coursename', 'report_lmsace_reports'),
            get_string('category', 'report_lmsace_reports'),
            get_string('enrolled', 'report_lmsace_reports'),
            get_string('coursecompletions', 'report_lmsace_reports'),
            get_string('withoutevaluations', 'report_lmsace_reports'),
            get_string('certificates', 'report_lmsace_reports'),
        ];
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->no_sorting('enrolled');
        $this->no_sorting('completed');
        $this->no_sorting('withoutevaluations');
        $this->no_sorting('certificates');
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
     * Set the sql query to fetch evaluation courses.
     *
     * @param int $pagesize
     * @param boolean $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        if (empty($this->teacherid)) {
            $this->rawdata = [];
            return;
        }

        $roles = get_roles_with_capability("report/lmsace_reports:viewcoursereports");
        $roleids = array_keys($roles);
        if (empty($roleids)) {
            $this->rawdata = [];
            return;
        }

        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        $params = [
            'teacherid' => $this->teacherid,
            'contextlevel' => CONTEXT_COURSE,
        ];
        $params = array_merge($params, $roleparams);

        $select = "c.id, c.fullname AS coursename, cc.name AS categoryname, c.category AS categoryid";
        $from = "{course} c
            JOIN {course_categories} cc ON cc.id = c.category
            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
            JOIN {role_assignments} ra ON ra.contextid = ctx.id";
        $where = "ra.userid = :teacherid AND ra.roleid $rolesql";

        $this->set_sql($select, $from, $where, $params);
        parent::query_db($pagesize, false);
    }

    /**
     * Generate the coursename column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursename($row) {
        global $PAGE;
        $url = new \moodle_url($PAGE->url, [
            'report' => 'evaluationreport',
            'evalteacher' => $this->teacherid,
            'evalcourse' => $row->id,
        ]);
        return \html_writer::link($url, format_string($row->coursename));
    }

    /**
     * Generate the categoryname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_categoryname($row) {
        return format_string($row->categoryname);
    }

    /**
     * Generate the enrolled column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_enrolled($row) {
        return \report_lmsace_reports\widgets::get_course_progress_status($row->id, true);
    }

    /**
     * Generate the completed column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_completed($row) {
        return \report_lmsace_reports\widgets::get_course_completion_users($row->id, [], true);
    }

    /**
     * Generate the withoutevaluations column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_withoutevaluations($row) {
        global $DB;
        // Count enrolled students with NO completion records for any activity in this course.
        $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($row->id, true);

        $sql = "SELECT COUNT(DISTINCT cmc.userid)
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                WHERE cm.course = :courseid AND cm.deletioninprogress = 0";
        $witheval = $DB->count_records_sql($sql, ['courseid' => $row->id]);
        $without = $enrolled - $witheval;
        return $without > 0 ? $without : 0;
    }

    /**
     * Generate the certificates column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_certificates($row) {
        // Certificates = course completions count.
        return \report_lmsace_reports\widgets::get_course_completion_users($row->id, [], true);
    }
}
