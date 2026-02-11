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
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\table\sitevisits_table_filterset;
use report_lmsace_reports\table\sitevisits_table;
use html_writer;

/**
 * Class sitvisits.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sitevisitswidget extends widgets_info {

    /**
     * @var array $records
     */
    private $records;

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $usersql
     */
    private $usersql;

    /**
     * @var array $userparams
     */
    private $userparams = [];

    /**
     * @var array $chartdata
     */
    private $chartdata;

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * @var array $tabledata
     */
    private $tabledata;

    /**
     * User id.
     *
     * @var int
     */
    public $userid;

    /**
     * Implemented the constructor.
     * @param string $filter
     * @param int $userid
     */
    public function __construct($filter = '', $userid = 0) {
        parent::__construct();
        if (!$filter) {
            $filter = 'year';
        }
        $this->filter = $filter;
        $this->userid = $userid;
        if ($this->userid) {
            $this->usersql = 'AND ls.userid = :userid';
            $this->userparams = ['userid' => $this->userid];
        }
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
     * @param string $type
     * @return string
     */
    public function get_cache_key($type) {
        return "u_" . $this->userid . "_sitvisits_" . $type . "_" . $this->filter;
    }

    /**
     * Load the data.
     */
    private function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }

        if (!$this->cache->get($this->get_cache_key('main'))) {
            $timestart = strtotime("-1". $this->filter);
            $timeend = time();
            $sql = "SELECT FLOOR(ls.timecreated / 86400) AS userdate,
                count(case when ls.target = 'user' AND ls.action = 'loggedin' then 1 else NULL end) AS sitevisits,
                count(case when ls.target = 'course' AND ls.action = 'viewed' then 1 else NULL end) AS coursevisits,
                count(case when ls.target = 'course_module' AND ls.action = 'viewed' then 1 else NULL end) AS modulevisits
                FROM {logstore_standard_log} ls
                WHERE ls.userid > 2 $this->usersql
                AND ls.timecreated BETWEEN :timestart AND :timeend
                GROUP BY FLOOR(ls.timecreated / 86400)";
            $params = [
                'timestart' => $timestart,
                'timeend' => $timeend,
            ];
            $params = array_merge($params, $this->userparams);
            $records = (array) $DB->get_records_sql($sql, $params);
            $this->cache->set($this->get_cache_key('main'), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key('main'));
    }

    /**
     * Get the table data.
     */
    private function get_table_info() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key('table'))) {
            $timestart = strtotime("-1". $this->filter);
            $timeend = time();
            $sql = "SELECT ls.userid,
                count(case when ls.target = 'user' AND ls.action = 'loggedin' then 1 else NULL end) As sitevisits,
                count(case when ls.target = 'course' AND ls.action = 'viewed' then 1 else NULL end) As coursevisits,
                count(case when ls.target = 'course_module' AND ls.action = 'viewed' then 1 else NULL end) As modulevisits
                FROM {logstore_standard_log} ls
                WHERE ls.action = 'loggedin' AND ls.userid > 2 $this->usersql
                AND ls.timecreated BETWEEN :timestart AND :timeend
                GROUP BY ls.userid";
            $params = [
                'timestart' => $timestart,
                'timeend' => $timeend,
            ];
            $params = array_merge($params, $this->userparams);
            $records = (array) $DB->get_records_sql($sql, $params);
        }
        $this->tabledata = $this->cache->get($this->get_cache_key('table'));
    }


    /**
     * Get chart data.
     * @param bool $info
     * @return array
     */
    public function get_data($info = false) {
        $this->param_sql();
        $this->prepare_chartdata();
        if ($info) {
            return $this->records;
        }
        return $this->chartdata;
    }

    /**
     * Prepare chart data.
     */
    public function prepare_chartdata() {
        $labels = [];
        $values = [];
        $labelcount = $this->get_label_count();
        for ($i = 0; $i < $labelcount; $i++) {
            $time = time() - $i * 24 * 60 * 60;
            $values['sitevisits'][floor($time / (24 * 60 * 60))] = 0;
            $values['coursevisits'][floor($time / (24 * 60 * 60))] = 0;
            $values['modulevisits'][floor($time / (24 * 60 * 60))] = 0;
            $labels[] = date("d M y", $time);
        }
        foreach (array_keys($values) as $report) {
            foreach (array_keys($values[$report]) as $key) {
                if (!isset($this->records[$key])) {
                    continue;
                }

                $values[$report][$key] = $this->records[$key]->{$report};
            }
            $values[$report] = array_reverse($values[$report]);
        }

        // Current is filtered by year then grouped by month.
        if ($this->filter == 'year') {

            $groupedbymonth = [];
            foreach ($values as $visitmethod => $report) {
                $combined = array_combine(array_values($labels), array_values($report));
                foreach ($combined as $date => $value) {
                    $datetime = explode(' ', $date);
                    // Remove the day from the label date.
                    array_shift($datetime);
                    // Create month with year of the data.
                    $month = trim(implode(' ', $datetime));
                    // Increase the visits by month.
                    if (array_key_exists($month, $groupedbymonth)) {
                        $groupedbymonth[$month] += $value;
                    } else {
                        $groupedbymonth[$month] = $value;
                    }
                }
                // Use the grouped month data as data for year report.
                $values[$visitmethod] = array_values($groupedbymonth);
            }
            // Use the grouped months.
            $labels = array_keys($groupedbymonth);
        }

        $this->chartdata['label'] = array_reverse($labels);
        $this->chartdata['value'] = $values;
    }

    /**
     * Get the report table.
     */
    public function get_report_table() {
        $filterset = new sitevisits_table_filterset('reports-sitivisits-filter');
        $filter = new \core_table\local\filter\string_filter('filter');
        $filter->add_filter_value($this->filter);
        $filterset->add_filter($filter);

        $table = new sitevisits_table('reports-sitivisits-widget');
        $table->set_filterset($filterset);

        ob_start();
        echo html_writer::start_div('sitevisits-widget-table');
        $table->out(10, true);
        echo html_writer::end_div();
        $tablehtml = ob_get_contents();
        ob_end_clean();

        return $tablehtml;
    }
}
