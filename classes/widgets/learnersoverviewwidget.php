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
 * Table class that contains the list of learners overview report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;


/**
 * Class learners overview report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learnersoverviewwidget extends widgets_info {

    /**
     * @var object $records
     */
    private $records;

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var array $reportdata
     */
    protected $reportdata;

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
            $filter = 'today';
        }
        $this->filter = $filter;
        $this->param_sql();
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
        return "learnersoverview_" . $this->filter;
    }


    /**
     * Load the data.
     */
    private function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $timestart = strtotime("-1". $this->filter);
            $timeend = time();
            $sql = "SELECT gg.id AS itemid, u.id AS userid, u.username AS user,
                u.timecreated AS usercreation, u.lastaccess,
                ROUND((sum(gg.finalgrade) / sum(gg.rawgrademax)) * 100) AS score,
                (SELECT count(*) FROM {course_completions} cp WHERE cp.userid = u.id AND timecompleted IS NOT NULL)
                    AS completedcourses
                FROM {grade_items} gt
                JOIN {course} c ON c.id = gt.courseid
                JOIN {grade_grades} gg ON gg.itemid = gt.id
                JOIN {user} u ON u.id = gg.userid
            WHERE gt.itemtype = 'course' GROUP BY gg.userid";
            $params = [];
            $records = $DB->get_records_sql($sql, $params);
            foreach ($records as $record) {
                $record->overallcourseprogress = report_helper::get_user_overall_courseinfo($record->userid);
            }
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->reportdata = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get chart data.
     */
    public function get_data() {
        global $OUTPUT, $PAGE;
        $context = \context_system::instance();
        return $this->records = $OUTPUT->render_from_template('report_lmsace_reports/learnersoverviewinfo',
            array_values($this->reportdata));
    }
}
