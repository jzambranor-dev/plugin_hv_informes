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
 * Table class that contains the list of course resources.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class that contains the list of course resources.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseresourceswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var string $context
     */
    public $context = "course";

    /**
     * Implemented the constructor.
     * @param int $courseid
     * @param string $filter
     */
    public function __construct($courseid, $filter = '') {
        parent::__construct();

        if (!$filter) {
            $filter = 'all';
        }

        $this->course = get_course($courseid);
        $this->filter = $filter;
        $this->get_report_data();
    }

    /**
     * Get chart type.
     * @return string
     */
    public function get_charttype() {
        return 'pie';
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
        return "c_" . $this->course->id . "_courseresources_" . $this->filter;
    }

    /**
     * Prepare report data.
     */
    private function get_report_data() {
        if (!$this->cache->get($this->get_cache_key())) {
            $data = \report_lmsace_reports\widgets::get_course_progress_status($this->course->id, false, $this->filter);
            $data = report_helper::chart_values($data);
            $data['courseid'] = $this->course->id;
            $this->cache->set($this->get_cache_key(), $data);
        }
        $this->reportdata = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get chart data.
     */
    public function get_data() {
        return $this->reportdata;
    }

}
