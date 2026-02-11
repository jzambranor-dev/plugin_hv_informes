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
 * Table class that contains the list of sitvisits.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace report_lmsace_reports\table;

defined('MOODLE_INTERNAL') || die('No direct access');

use core_table\dynamic as dynamic_table;
use html_writer;
use core_user;

require_once($CFG->dirroot.'/lib/tablelib.php');

/**
 * List of group memebers table.
 */
class sitevisits_table extends \table_sql implements dynamic_table {

    /**
     * Define table field definitions and filter data
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        $columns = ['fullname', 'sitevisits', 'coursevisits', 'modulevisits'];
        $headers = [
            get_string('fullname'),
            get_string('strsitevisits', 'report_lmsace_reports'),
            get_string('strcoursevisits', 'report_lmsace_reports'),
            get_string('strmodulevisits', 'report_lmsace_reports'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        if ($this->filterset->has_filter('filter')) {
            $this->filter = $this->filterset->get_filter('filter')->get_filter_values();
            $this->filter = current($this->filter);
        }
        $countsql = "SELECT COUNT(DISTINCT ls.userid) FROM
            {logstore_standard_log} ls
            JOIN {user} u ON u.id = ls.userid
            WHERE ls.userid > 2 AND ls.timecreated
            BETWEEN :timestart AND :timeend";
        $timestart = strtotime("-1". $this->filter);
        $timeend = time();
        $this->set_count_sql($countsql, ['timestart' => $timestart, 'timeend' => $timeend]);

        $this->collapsible(false);
        $this->no_sorting('sitevisits');
        $this->no_sorting('coursevisits');
        $this->no_sorting('modulevisits');
        $this->guess_base_url();
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Set the context of the current block.
     *
     * @return void
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
        return has_capability('report/lmsace_reports:viewsitereports', $this->get_context());
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
     * Set the sql query to fetch same user groups.
     *
     * @param int $pagesize
     * @param boolean $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $USER;

        $select = "ls.userid,
            count(case when ls.target = 'user' then 1 else NULL end) As sitevisits,
            count(case when ls.target = 'course' then 1 else NULL end) As coursevisits,
            count(case when ls.target = 'course_module' then 1 else NULL end) As modulevisits
        ";
        $timestart = strtotime("-1". $this->filter);
        $timeend = time();
        $from = "{logstore_standard_log} ls JOIN {user} u ON u.id = ls.userid";
        $where = "ls.userid > 2 AND ls.timecreated
            BETWEEN :timestart AND :timeend GROUP BY ls.userid";
        $this->set_sql($select, $from, $where, ['timestart' => $timestart, 'timeend' => $timeend]);
        parent::query_db($pagesize, false);
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        global $OUTPUT;
        $user = core_user::get_user($row->userid);
        return $OUTPUT->user_picture($user, ['size' => 35, 'includefullname' => true]);
    }

    /**
     * Generate the sitevisits column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_sitevisits($row) {
        return $row->sitevisits;
    }

    /**
     * Generate the coursevisits column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursevisits($row) {
        return $row->coursevisits;
    }


    /**
     * Generate the modulevisits column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_modulevisits($row) {
        return $row->modulevisits;
    }
}
