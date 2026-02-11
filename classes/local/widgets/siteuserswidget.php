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
 * Table class that contains the list of site users report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\local\table\siteusers_table_filterset;
use report_lmsace_reports\local\table\siteusers_table;
use html_writer;
use report_lmsace_reports\report_helper;

/**
 * Class site users report. site registration users status.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class siteuserswidget extends widgets_info {

    /**
     * @var object $records
     */
    private $records;

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $duration
     */
    private $duration;

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
        if (!$filter) {
            $filter = 'all';
        }
        $this->filter = $filter;
        $this->duration = report_helper::get_duration_info($filter);
        $this->param_sql();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return 'doughnut';
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
        return "siteusers_" . $this->filter;
    }

    /**
     * Set the report data.
     * @return void
     */
    private function param_sql() {
        global $DB;

        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $confirmdurationsql = '';
            $durationsql = '';
            $params = [];
            if (!empty($this->duration)) {
                $params = [
                    'timestart' => $this->duration['timestart'],
                    'timeend' => $this->duration['timeend'],
                ];
                $confirmdurationsql .= "AND timecreated BETWEEN :timestart AND :timeend";
                $durationsql .= "AND timemodified BETWEEN :timestart AND :timeend";
            }
            $confirmsql = "SELECT
                    sum(CASE
                        WHEN confirmed = 1 THEN 1
                        ELSE 0
                    END) AS confirmed,

                    sum(CASE
                        WHEN confirmed = 0 THEN 1
                        ELSE 0
                    END) AS notconfirmed

                FROM {user}
                WHERE id > 2 $confirmdurationsql";
            $sql = "SELECT
                        sum(CASE
                            WHEN deleted = 1 THEN 1
                            ELSE 0
                        END) AS deleted,

                        sum(CASE
                            WHEN suspended = 1 THEN 1
                            ELSE 0
                        END) AS suspended
                FROM {user}
                WHERE id > 2 $durationsql";
            $records = (array) $DB->get_record_sql($sql, $params);
            $confirmrecords = (array) $DB->get_record_sql($confirmsql, $params);
            $records = array_merge($records, $confirmrecords);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get the report records.
     */
    public function get_records() {
        $this->records;
    }

    /**
     * Get the report data.
     */
    public function get_data() {

        return report_helper::chart_values($this->records);
    }

    /**
     * Get the report table.
     */
    public function get_report_table() {

        $filterset = new siteusers_table_filterset('reports-siteusers-filter');
        $filter = new \core_table\local\filter\string_filter('filter');
        $filter->add_filter_value($this->filter);
        $filterset->add_filter($filter);

        $table = new siteusers_table('reports-siteusers-widget');
        $table->set_filterset($filterset);

        ob_start();
        echo html_writer::start_div('siteusers-widget-table');
        $table->out(10, true);
        echo html_writer::end_div();
        $tablehtml = ob_get_contents();
        ob_end_clean();
        return $tablehtml;
    }
}
