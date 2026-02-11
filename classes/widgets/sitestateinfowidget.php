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
 * Table class that contains the list of cohorts and assign users.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use core\task\manager;
use report_lmsace_reports\report_helper;



/**
 * Class cohorts info widget.
 */
class sitestateinfowidget extends widgets_info {

    /**
     * @var array $records
     */
    private $records;

    /**
     * @var array $report
     */
    private $report = [];

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * Implemented the constructor.
     * @param string $filter
     */
    public function __construct($filter = '') {
        parent::__construct();
        $this->get_report_block();
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
        return "sitestateinfo";
    }

    /**
     * Prepare report data
     */
    private function get_report_block() {
        global $CFG, $OUTPUT;
        $data = [];
        $logtask = manager::get_scheduled_task('logstore_standard\task\cleanup_task');
        if ($logtask->get_last_run_time()) {
            $data['lastlogclear'] = userdate($logtask->get_last_run_time());
        } else {
            $data['lastlogclear'] = get_string('never');
        }
        $data['themedesignermode'] = $CFG->themedesignermode ? get_string('enable') : get_string('disable');
        $data['themedesignermoderbadge'] = $CFG->themedesignermode ? "success" : "danger";
        $data['debugging'] = $CFG->debug ? get_string('enable') : get_string('disable');
        $data['debuggingbadge'] = $CFG->debug ? "success" : "danger";
        $data['addtionalplugins'] = report_helper::get_addtional_plugins();
        $this->report = $OUTPUT->render_from_template('report_lmsace_reports/sitestate_reports', $data);
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->report;
    }
}
