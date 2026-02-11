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
 * Table class that contains the list of site active users.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class site active users.
 */
class siteactiveuserswidget extends widgets_info {

    /**
     * @var array $records
     */
    private $records;

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * Data of the chart.
     *
     * @var array
     */
    public $chartdata;

    /**
     * Implemented the constructor.
     * @param string $filter
     */
    public function __construct($filter = '') {
        parent::__construct();
        if (!$filter) {
            $filter = 'year';
        }
        $this->filter = $filter;
        $this->param_sql();
        $this->prepare_chartdata();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return "line";
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
        return "siteactiveusers_" . $this->filter;
    }

    /**
     * Set the report data.
     * @return void
     */
    public function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $durationparams = report_helper::get_duration_info($this->filter);
            $durationsql = '';
            $params = [];
            if (!empty($durationparams)) {
                $durationsql = " AND ls.timecreated BETWEEN :timestart AND :timeend";
                $params = array_merge($params, $durationparams);
            }
            $sql = "SELECT FLOOR(ls.timecreated / 86400) AS userdate, count(DISTINCT ls.userid) AS activeusers
                FROM {logstore_standard_log} ls
                WHERE ls.action = 'viewed' AND ls.userid > 2
                $durationsql
                GROUP BY FLOOR(ls.timecreated / 86400)";
            $records = (array) $DB->get_records_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get chart data.
     * @return array
     */
    public function get_data() {
        return $this->chartdata;
    }

    /**
     * Prepare chartdata.
     */
    public function prepare_chartdata() {
        $labels = [];
        $values = [];
        $labelcount = $this->get_label_count();
        for ($i = 0; $i < $labelcount; $i++) {
            $time = time() - $i * 24 * 60 * 60;
            $values[floor($time / (24 * 60 * 60))] = 0;
            $labels[] = date("d M y", $time);
        }
        if (!empty($this->records)) {
            foreach (array_keys($values) as $key) {
                if (!isset($this->records[$key])) {
                    continue;
                }
                $values[$key] = $this->records[$key]->activeusers;
            }
        }
        $this->chartdata['label'] = array_reverse($labels);
        $this->chartdata['value'] = array_reverse($values);
    }

    /**
     * Get the report table.
     */
    public function get_report_table() {
    }
}
