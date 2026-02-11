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
 * Table class that contains the list of evaluation activities.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\table;

defined('MOODLE_INTERNAL') || die('No direct access');

use core_table\dynamic as dynamic_table;

require_once($CFG->dirroot.'/lib/tablelib.php');
require_once($CFG->dirroot. '/report/lmsace_reports/lib.php');

/**
 * List of evaluation activities table.
 */
class evaluationactivities_table extends \table_sql implements dynamic_table {

    /** @var int Course ID. */
    protected $courseid;

    /** @var string Module type filter. */
    protected $modtype;

    /** @var int Date from filter (timestamp). */
    protected $datefrom;

    /** @var int Date to filter (timestamp). */
    protected $dateto;

    /**
     * Constructor.
     * @param string $uniqueid
     * @param int $courseid
     * @param string $modtype
     * @param int $datefrom
     * @param int $dateto
     */
    public function __construct($uniqueid, $courseid = 0, $modtype = '', $datefrom = 0, $dateto = 0) {
        parent::__construct($uniqueid);
        $this->courseid = $courseid;
        $this->modtype = $modtype;
        $this->datefrom = $datefrom;
        $this->dateto = $dateto;
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
        $columns = ['activityname', 'activitytype', 'enrolled', 'completed', 'notcompleted', 'passed', 'failed', 'averagegrade'];
        $headers = [
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
        $this->no_sorting('enrolled');
        $this->no_sorting('completed');
        $this->no_sorting('notcompleted');
        $this->no_sorting('passed');
        $this->no_sorting('failed');
        $this->no_sorting('averagegrade');
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
     * Set the sql query to fetch evaluation activities.
     *
     * @param int $pagesize
     * @param boolean $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        if (empty($this->courseid)) {
            $this->rawdata = [];
            return;
        }

        $params = ['courseid' => $this->courseid];
        $modtypewhere = '';
        if (!empty($this->modtype)) {
            $modtypewhere = ' AND m.name = :modtype';
            $params['modtype'] = $this->modtype;
        }

        $select = "cm.id, cm.instance, cm.course, m.name AS modulename, cm.added";
        $from = "{course_modules} cm
            JOIN {modules} m ON m.id = cm.module";
        $where = "cm.course = :courseid AND cm.deletioninprogress = 0" . $modtypewhere;

        $this->set_sql($select, $from, $where, $params);
        parent::query_db($pagesize, false);
    }

    /**
     * Generate the activityname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_activityname($row) {
        global $DB, $PAGE;
        // Get the activity name from its module table.
        $name = $DB->get_field($row->modulename, 'name', ['id' => $row->instance]);
        if (!$name) {
            $name = get_string('notavailable', 'report_lmsace_reports');
        }
        // Link to detail view.
        $currentparams = $PAGE->url->params();
        $url = new \moodle_url('/report/lmsace_reports/index.php', [
            'report' => 'evaluationreport',
            'evalteacher' => $currentparams['evalteacher'] ?? 0,
            'evalcourse' => $this->courseid,
            'evalcmid' => $row->id,
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
        return \report_lmsace_reports\widgets::get_course_progress_status($row->course, true);
    }

    /**
     * Generate the completed column.
     *
     * @param \stdClass $row
     * @return int
     */
    public function col_completed($row) {
        global $DB;
        $params = ['cmid' => $row->id];
        $datewhere = '';
        if ($this->datefrom > 0) {
            $datewhere .= ' AND cmc.timemodified >= :datefrom';
            $params['datefrom'] = $this->datefrom;
        }
        if ($this->dateto > 0) {
            $datewhere .= ' AND cmc.timemodified <= :dateto';
            $params['dateto'] = $this->dateto;
        }
        $sql = "SELECT COUNT(*) FROM {course_modules_completion} cmc
                WHERE cmc.coursemoduleid = :cmid AND cmc.completionstate IN (1,2,3)" . $datewhere;
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Generate the notcompleted column.
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
        $params = ['cmid' => $row->id];
        $datewhere = '';
        if ($this->datefrom > 0) {
            $datewhere .= ' AND cmc.timemodified >= :datefrom';
            $params['datefrom'] = $this->datefrom;
        }
        if ($this->dateto > 0) {
            $datewhere .= ' AND cmc.timemodified <= :dateto';
            $params['dateto'] = $this->dateto;
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
        $params = ['cmid' => $row->id];
        $datewhere = '';
        if ($this->datefrom > 0) {
            $datewhere .= ' AND cmc.timemodified >= :datefrom';
            $params['datefrom'] = $this->datefrom;
        }
        if ($this->dateto > 0) {
            $datewhere .= ' AND cmc.timemodified <= :dateto';
            $params['dateto'] = $this->dateto;
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
        $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 100) as avggrade
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gi.id = gg.itemid
            WHERE gi.itemtype = 'mod' AND gi.itemmodule = :modulename AND gi.iteminstance = :instanceid
            AND gi.courseid = :courseid AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
        $result = $DB->get_record_sql($sql, [
            'modulename' => $row->modulename,
            'instanceid' => $row->instance,
            'courseid' => $row->course,
        ]);
        $avg = ($result && $result->avggrade !== null) ? round($result->avggrade, 1) : 0;
        return $avg . '%';
    }
}
