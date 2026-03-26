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
 * Consolidated evaluation table showing all teachers, courses and activities.
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
 * Consolidated evaluation table: one row per teacher + course + activity.
 */
class evaluationconsolidated_table extends \table_sql {

    /** @var int Month filter timestamp (first day of month). */
    protected $evalmonth;

    /** @var string Comma-separated category IDs filter. */
    protected $evalcategory;

    /** @var string Module type filter. */
    protected $evalconmodtype;

    /**
     * Constructor.
     * @param string $uniqueid
     * @param int $evalmonth Timestamp of first day of month to filter, 0 for all.
     * @param string $evalcategory Comma-separated category IDs to filter, empty for all.
     * @param string $evalconmodtype Module type to filter, empty for all.
     */
    public function __construct($uniqueid, $evalmonth = 0, $evalcategory = '', $evalconmodtype = '') {
        parent::__construct($uniqueid);
        $this->evalmonth = $evalmonth;
        $this->evalcategory = $evalcategory;
        $this->evalconmodtype = $evalconmodtype;
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
        $columns = [
            'teachername', 'categoryname', 'coursename', 'coursestartdate', 'courseenddate',
            'activityname', 'activitytype',
            'enrolled', 'completed', 'notcompleted', 'passed', 'failed', 'averagegrade',
        ];
        $headers = [
            get_string('teacher', 'report_lmsace_reports'),
            get_string('category', 'report_lmsace_reports'),
            get_string('coursename', 'report_lmsace_reports'),
            get_string('startdate', 'report_lmsace_reports'),
            get_string('enddate', 'report_lmsace_reports'),
            get_string('activityname', 'report_lmsace_reports'),
            get_string('activitytype', 'report_lmsace_reports'),
            get_string('enrolled', 'report_lmsace_reports'),
            get_string('completedcount', 'report_lmsace_reports'),
            get_string('notcompletedcount', 'report_lmsace_reports'),
            get_string('passedcount', 'report_lmsace_reports'),
            get_string('failedcount', 'report_lmsace_reports'),
            get_string('averagegrade', 'report_lmsace_reports'),
        ];
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true, 'lastname', SORT_ASC);
        $this->no_sorting('teachername');
        $this->no_sorting('categoryname');
        $this->no_sorting('coursestartdate');
        $this->no_sorting('courseenddate');
        $this->no_sorting('activityname');
        $this->no_sorting('activitytype');
        $this->no_sorting('enrolled');
        $this->no_sorting('completed');
        $this->no_sorting('notcompleted');
        $this->no_sorting('passed');
        $this->no_sorting('failed');
        $this->no_sorting('averagegrade');
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

        // One row per teacher + course + activity (course_module).
        $uniqueid = $DB->sql_concat('ra.userid', "'-'", 'cm.id');
        $select = "$uniqueid AS id,
            ra.userid AS teacherid,
            u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
            u.middlename, u.alternatename,
            c.id AS courseid, c.fullname AS coursename, c.startdate AS coursestartdate,
            c.enddate AS courseenddate, cc.name AS categoryname,
            cm.id AS cmid, cm.instance AS cminstance, m.name AS modulename";
        $from = "{role_assignments} ra
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel
            JOIN {course} c ON c.id = ctx.instanceid
            JOIN {course_categories} cc ON cc.id = c.category
            JOIN {user} u ON u.id = ra.userid AND u.deleted = 0
            JOIN {course_modules} cm ON cm.course = c.id AND cm.deletioninprogress = 0
            JOIN {modules} m ON m.id = cm.module";
        $where = "ra.roleid $rolesql";

        // Filter by categories (including all descendant subcategories).
        if (!empty($this->evalcategory)) {
            $selectedids = array_map('intval', explode(',', $this->evalcategory));
            $selectedids = array_filter($selectedids);
            if (!empty($selectedids)) {
                // Expand selected categories to include all descendants via path matching.
                $allcats = $DB->get_records('course_categories', null, '', 'id, path');
                $matchids = [];
                foreach ($allcats as $cat) {
                    foreach ($selectedids as $sid) {
                        if (preg_match('#/' . $sid . '(/|$)#', $cat->path)) {
                            $matchids[$cat->id] = true;
                            break;
                        }
                    }
                }
                if (!empty($matchids)) {
                    list($catsql, $catparams) = $DB->get_in_or_equal(
                        array_keys($matchids), SQL_PARAMS_NAMED, 'cat'
                    );
                    $where .= " AND c.category $catsql";
                    $params = array_merge($params, $catparams);
                }
            }
        }

        // Filter by month: only show courses active during the selected month.
        // A course is "active" if it started before the month ends AND
        // (has no end date OR its end date is after the month starts).
        if (!empty($this->evalmonth)) {
            $monthend = strtotime('+1 month', $this->evalmonth);
            $where .= " AND c.startdate < :monthend AND (c.enddate = 0 OR c.enddate >= :monthstart)";
            $params['monthend'] = $monthend;
            $params['monthstart'] = $this->evalmonth;
        }

        // Filter by module type.
        if (!empty($this->evalconmodtype)) {
            $where .= " AND m.name = :modtype";
            $params['modtype'] = $this->evalconmodtype;
        }

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
        $url = new \moodle_url('/report/lmsace_reports/index.php', [
            'report' => 'evaluationreport',
            'evalteacher' => $row->teacherid,
        ]);
        return \html_writer::link($url, $fullname);
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
     * Generate the coursestartdate column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursestartdate($row) {
        if (empty($row->coursestartdate)) {
            return '-';
        }
        return userdate($row->coursestartdate, '%d/%m/%Y');
    }

    /**
     * Generate the courseenddate column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_courseenddate($row) {
        if (empty($row->courseenddate)) {
            return '-';
        }
        return userdate($row->courseenddate, '%d/%m/%Y');
    }

    /**
     * Generate the coursename column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursename($row) {
        if ($this->is_downloading()) {
            return format_string($row->coursename);
        }
        $url = new \moodle_url('/report/lmsace_reports/index.php', [
            'report' => 'evaluationreport',
            'evalteacher' => $row->teacherid,
            'evalcourse' => $row->courseid,
        ]);
        return \html_writer::link($url, format_string($row->coursename));
    }

    /**
     * Generate the activityname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_activityname($row) {
        global $DB;
        $name = $DB->get_field($row->modulename, 'name', ['id' => $row->cminstance]);
        if (!$name) {
            $name = get_string('notavailable', 'report_lmsace_reports');
        }
        if ($this->is_downloading()) {
            return format_string($name);
        }
        $url = new \moodle_url('/report/lmsace_reports/index.php', [
            'report' => 'evaluationreport',
            'evalteacher' => $row->teacherid,
            'evalcourse' => $row->courseid,
            'evalcmid' => $row->cmid,
        ]);
        return \html_writer::link($url, format_string($name));
    }

    /**
     * Generate the activitytype column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_activitytype($row) {
        return get_string('modulename', $row->modulename);
    }

    /**
     * Generate the enrolled column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_enrolled($row) {
        return \report_lmsace_reports\widgets::get_course_progress_status($row->courseid, true);
    }

    /**
     * Generate the completed column (students who completed the activity).
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_completed($row) {
        global $DB;
        $params = ['cmid' => $row->cmid];
        $datewhere = '';
        if ($this->evalmonth) {
            $monthend = strtotime('+1 month', $this->evalmonth);
            $datewhere = ' AND cmc.timemodified >= :timefrom AND cmc.timemodified < :timeto';
            $params['timefrom'] = $this->evalmonth;
            $params['timeto'] = $monthend;
        }
        $sql = "SELECT COUNT(*) FROM {course_modules_completion} cmc
                WHERE cmc.coursemoduleid = :cmid AND cmc.completionstate IN (1,2,3)" . $datewhere;
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Generate the notcompleted column (students who did NOT do the activity).
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_notcompleted($row) {
        $enrolled = $this->col_enrolled($row);
        $completed = $this->col_completed($row);
        $result = $enrolled - $completed;
        return $result > 0 ? $result : 0;
    }

    /**
     * Generate the passed column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_passed($row) {
        global $DB;
        $params = ['cmid' => $row->cmid];
        $datewhere = '';
        if ($this->evalmonth) {
            $monthend = strtotime('+1 month', $this->evalmonth);
            $datewhere = ' AND cmc.timemodified >= :timefrom AND cmc.timemodified < :timeto';
            $params['timefrom'] = $this->evalmonth;
            $params['timeto'] = $monthend;
        }
        $sql = "SELECT COUNT(*) FROM {course_modules_completion} cmc
                WHERE cmc.coursemoduleid = :cmid AND cmc.completionstate = 2" . $datewhere;
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Generate the failed column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_failed($row) {
        global $DB;
        $params = ['cmid' => $row->cmid];
        $datewhere = '';
        if ($this->evalmonth) {
            $monthend = strtotime('+1 month', $this->evalmonth);
            $datewhere = ' AND cmc.timemodified >= :timefrom AND cmc.timemodified < :timeto';
            $params['timefrom'] = $this->evalmonth;
            $params['timeto'] = $monthend;
        }
        $sql = "SELECT COUNT(*) FROM {course_modules_completion} cmc
                WHERE cmc.coursemoduleid = :cmid AND cmc.completionstate = 3" . $datewhere;
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Generate the averagegrade column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_averagegrade($row) {
        global $DB;
        $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 10) as avggrade
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gi.id = gg.itemid
            WHERE gi.itemtype = 'mod' AND gi.itemmodule = :modulename AND gi.iteminstance = :instanceid
            AND gi.courseid = :courseid AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
        $params = [
            'modulename' => $row->modulename,
            'instanceid' => $row->cminstance,
            'courseid' => $row->courseid,
        ];

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
}
