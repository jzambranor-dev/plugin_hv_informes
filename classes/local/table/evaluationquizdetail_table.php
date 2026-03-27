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
 * Quiz detail table with per-question grades for Excel download.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\table;

defined('MOODLE_INTERNAL') || die('No direct access');

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/report/lmsace_reports/lib.php');

use report_lmsace_reports\report_helper;

/**
 * Quiz detail table: one row per student with per-question marks.
 */
class evaluationquizdetail_table extends \table_sql {

    /** @var int Course module ID. */
    protected $cmid;

    /** @var int Course ID. */
    protected $courseid;

    /** @var array Quiz slot info. */
    protected $slots;

    /** @var array Attempt data keyed by userid. */
    protected $attemptdata;

    /** @var object Quiz record. */
    protected $quiz;

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param int $cmid
     * @param int $courseid
     */
    public function __construct($uniqueid, $cmid, $courseid) {
        parent::__construct($uniqueid);
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->slots = [];
        $this->attemptdata = [];
    }

    /**
     * Define table columns and output.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $DB;

        // Get quiz instance from cmid.
        $cm = $DB->get_record('course_modules', ['id' => $this->cmid]);
        if (!$cm) {
            return;
        }
        $this->slots = report_helper::get_quiz_slots($cm->instance);
        $attdata = report_helper::get_quiz_attempts_with_questions($cm->instance, $this->courseid);
        $this->quiz = $attdata['quiz'];
        $this->attemptdata = [];
        foreach ($attdata['attempts'] as $att) {
            $this->attemptdata[$att->userid] = $att;
        }

        // Build dynamic columns.
        $columns = ['rownum', 'fullname', 'email', 'idnumber'];
        $headers = [
            '#',
            get_string('name'),
            get_string('email'),
            get_string('idnumber'),
        ];

        foreach ($this->slots as $s) {
            $columns[] = 'q_' . $s->slot;
            $headers[] = format_string($s->questionname) . ' (' . round($s->maxmark, 2) . ')';
        }

        $columns[] = 'grade';
        $headers[] = get_string('grade', 'report_lmsace_reports');

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(false);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);
        $this->guess_base_url();
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Get the context for this table.
     *
     * @return \context
     */
    public function get_context(): \context {
        return \context_system::instance();
    }

    /**
     * Set the base URL.
     *
     * @return void
     */
    public function guess_base_url(): void {
        global $PAGE;
        $this->baseurl = $PAGE->url;
    }

    /**
     * Query the database for attempt data.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        if (empty($this->attemptdata)) {
            $this->rawdata = [];
            return;
        }

        $rownum = 1;
        $rows = [];
        foreach ($this->attemptdata as $userid => $att) {
            $row = new \stdClass();
            $row->id = $userid;
            $row->rownum = $rownum++;
            $row->fullname = $att->fullname;
            $row->email = $att->email;
            $row->idnumber = $att->idnumber;
            $row->grade = $att->grade;
            $row->questionmarks = $att->questionmarks;
            $rows[] = $row;
        }
        $this->rawdata = $rows;
    }

    /**
     * Generate dynamic question column values.
     *
     * @param string $colname
     * @param \stdClass $row
     * @return string
     */
    public function other_cols($colname, $row) {
        if (strpos($colname, 'q_') === 0) {
            $slot = (int)substr($colname, 2);
            if (isset($row->questionmarks[$slot]) && $row->questionmarks[$slot] !== null) {
                return $row->questionmarks[$slot];
            }
            return '-';
        }
        return null;
    }
}
