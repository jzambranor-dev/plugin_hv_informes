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
 * Table class that contains the list of enrollment method users.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class enrollment method users users.
 */
class enrolmethoduserswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var array $records
     */
    private $records;

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
        $this->filter = $filter;
        $this->param_sql();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return "pie";
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
        return "enrolmethodusers_" . $this->filter;
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
            $sql = "SELECT e.enrol, count(ue.id) AS enrolements
                FROM {enrol} e
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                GROUP BY e.enrol";
            $records = $DB->get_records_sql($sql, null);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
        $this->prepare_chartdata();
        $this->get_report_table();
    }

    /**
     * Prepare chartdata.
     */
    private function prepare_chartdata() {
        $label = [];
        $value = [];
        if (!empty($this->records)) {
            foreach ($this->records as $record) {
                $label[] = ucwords($record->enrol);
                $value[] = $record->enrolements;
            }
        }
        $labelcount = count($label);
        $this->reportdata['background'] = report_helper::get_random_back_color($labelcount);
        $this->reportdata['label'] = $label;
        $this->reportdata['value'] = $value;
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }

    /**
     * Get the report table.
     */
    public function get_report_table() {
    }
}
