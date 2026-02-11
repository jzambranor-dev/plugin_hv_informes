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
 * Table class that contains the list of users groups and cohorts.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class users groups and cohorts users.
 */
class usergroupcohortswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $context
     */
    public $context = "user";

    /**
     * Implemented the constructor.
     * @param int $userid
     * @param string $filter
     */
    public function __construct($userid, $filter = '') {
        global $DB;
        parent::__construct();
        $this->user = $DB->get_record('user', ['id' => $userid]);
        $this->filter = $filter;
        $this->get_report_data();
    }

    /**
     * Get chart type.
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
        return "u_" . $this->user->id . "_usergroupcohorts_" . $this->filter;
    }

    /**
     * Get report data.
     * @return array
     */
    private function get_report_data() {
        global $DB;

        $durationparams = report_helper::get_duration_info($this->filter, 'week');
        $cohortdurationsql = '';
        $groupdurationsql = '';

        $params = ['userid' => $this->user->id];
        if (!empty($durationparams)) {
            $cohortdurationsql .= " AND cm.timeadded BETWEEN :timestart AND :timeend";
            $groupdurationsql .= " AND gm.timeadded BETWEEN :timestart AND :timeend";
            $params = array_merge($params, $durationparams);
        }

        $value = [];
        $label = [get_string('cohorts', 'report_lmsace_reports'), get_string('groups')];
        if (!$this->cache->get($this->get_cache_key())) {
            $cohortsql = "SELECT count(*) FROM {cohort_members} cm WHERE cm.userid = :userid";
            $groupsql = "SELECT count(*) FROM {groups_members} gm WHERE gm.userid = :userid";
            $value[] = $DB->count_records_sql($cohortsql, $params);
            $value[] = $DB->count_records_sql($groupsql, $params);
            $this->cache->set($this->get_cache_key(), $value);
        }
        $value = $this->cache->get($this->get_cache_key());
        $this->reportdata['value'] = $value;
        $this->reportdata['label'] = $label;
        $this->reportdata['userid'] = $this->user->id;
    }

    /**
     * Get data.
     */
    public function get_data() {
        return $this->reportdata;
    }
}
