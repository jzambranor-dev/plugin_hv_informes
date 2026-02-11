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
 * Table class that contains the list of site info.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\report_helper;
use report_lmsace_reports\output\widgets_info;

/**
 * Class Most visit course.
 */
class overallsiteinfowidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var array $report
     */
    private $report = [];

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * Instance.
     *
     * @var \report_lmsace_reports\widgets
     */
    public $instance;

    /**
     * Implemented the constructor.
     * @param string $filter
     */
    public function __construct($filter = '') {
        parent::__construct();
        $this->filter = $filter;
        $this->instance = new \report_lmsace_reports\widgets();
        $this->get_report_data();
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
     * Get chart data.
     */
    public function get_data() {
        return $this->report;
    }

    /**
     * Set the report data.
     * @return void
     */
    private function get_report_data() {
        global $OUTPUT, $PAGE;
        $data = [];
        $courses = report_helper::get_course(0, true);
        $durationparams = report_helper::get_duration_info($this->filter);
        $enrolments = 0;
        $activitycompletions = 0;
        $coursecompletions = 0;
        $newregistrations = 0;
        $visits = 0;

        if (!empty($courses)) {
            foreach ($courses as $course) {
                $enrolments += \report_lmsace_reports\widgets::get_enroluser_incourse($course->id, $durationparams);
                $mods = \report_lmsace_reports\widgets::get_course_activity_completion($course->id, $durationparams);
                if ($mods) {
                    foreach ($mods as $mod) {
                        $activitycompletions += $mod->completions;
                    }
                }
                $coursecompletions += \report_lmsace_reports\widgets::get_course_completion_users($course->id,
                    $durationparams, true);
            }
        }
        $newregistrations = \report_lmsace_reports\widgets::get_timebased_siteusers($durationparams);
        $visits = \report_lmsace_reports\widgets::get_timebased_visits($durationparams);
        $data['enrolments'] = $enrolments;
        $data['activity_completions'] = $activitycompletions;
        $data['course_completions'] = $coursecompletions;
        $data['newregistrations'] = $newregistrations;
        $data['visits'] = $visits;
        $this->report = $OUTPUT->render_from_template('report_lmsace_reports/siteinfo_reports', $data);
    }
}
