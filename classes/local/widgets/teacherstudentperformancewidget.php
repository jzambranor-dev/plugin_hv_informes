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
 * Teacher student performance widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Teacher student performance doughnut chart widget.
 */
class teacherstudentperformancewidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "teacher";

    /** @var string Duration filter. */
    public $filter;

    /** @var int Teacher user ID. */
    protected $teacherid;

    /** @var int Month filter timestamp. */
    protected $month;

    /** @var string Comma-separated category IDs. */
    protected $categoryids;

    /**
     * Constructor.
     * @param int $teacherid
     * @param int $month Month filter timestamp (0 = all).
     * @param string $categoryids Comma-separated category IDs (empty = all).
     */
    public function __construct($teacherid, $month = 0, $categoryids = '') {
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->month = $month;
        $this->categoryids = $categoryids;
        $this->filter = 'all';
        $this->prepare_chartdata();
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
     * Get cache key.
     * @return string
     */
    public function get_cache_key() {
        return "t_" . $this->teacherid . "_studentperformance_" . $this->month . "_" . md5($this->categoryids);
    }

    /**
     * Prepare chart data.
     */
    private function prepare_chartdata() {
        if (!$this->cache->get($this->get_cache_key())) {
            $courseids = report_helper::get_teacher_courses_filtered(
                $this->teacherid, $this->month, $this->categoryids
            );

            $completed = 0;
            $inprogress = 0;
            $notstarted = 0;

            foreach ($courseids as $courseid) {
                $status = \report_lmsace_reports\widgets::get_course_progress_status($courseid);
                if (!is_array($status)) {
                    continue;
                }
                $completed += $status['completed'] ?? 0;
                $inprogress += $status['incompleted'] ?? 0;
                $notstarted += $status['notstarted'] ?? 0;
            }

            $data = [
                'label' => [
                    get_string('studentscompleted', 'report_lmsace_reports'),
                    get_string('studentsinprogress', 'report_lmsace_reports'),
                    get_string('studentsnotstarted', 'report_lmsace_reports'),
                ],
                'value' => [$completed, $inprogress, $notstarted],
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
