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
 * Teacher grading overview widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Teacher grading overview bar chart widget.
 */
class teachergradingoverviewwidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "teacher";

    /** @var string Duration filter. */
    public $filter;

    /** @var int Teacher user ID. */
    protected $teacherid;

    /**
     * Constructor.
     * @param int $teacherid
     * @param string $filter
     */
    public function __construct($teacherid, $filter = '') {
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->filter = $filter;
        $this->prepare_chartdata();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return 'bar';
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return true;
    }

    /**
     * Get cache key.
     * @return string
     */
    public function get_cache_key() {
        return "t_" . $this->teacherid . "_gradingoverview";
    }

    /**
     * Prepare chart data.
     */
    private function prepare_chartdata() {
        global $DB;
        if (!$this->cache->get($this->get_cache_key())) {
            $teachercourses = report_helper::get_teacher_courses($this->teacherid);
            $courseids = array_column((array) $teachercourses, 'courseid');
            $labels = [];
            $values = [];

            foreach ($courseids as $courseid) {
                if (!$DB->record_exists('course', ['id' => $courseid])) {
                    continue;
                }
                $course = get_course($courseid);
                $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 100) as avggrade
                    FROM {grade_grades} gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    WHERE gi.courseid = :courseid AND gi.itemtype = 'course'
                        AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
                $result = $DB->get_record_sql($sql, ['courseid' => $courseid]);
                $labels[] = format_string($course->shortname);
                $values[] = ($result && $result->avggrade !== null) ? (int) round($result->avggrade, 0) : 0;
            }

            $data = [
                'label' => $labels,
                'value' => $values,
                'teacherid' => $this->teacherid,
            ];
            $this->cache->set($this->get_cache_key(), $data);
        }
        $this->reportdata = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get data.
     * @return array
     */
    public function get_data() {
        return $this->reportdata;
    }
}
