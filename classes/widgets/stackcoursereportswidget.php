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
 * Table class that contains the list of stack course report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;


/**
 * Class stack course report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stackcoursereportswidget extends widgets_info {

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
     */
    public function __construct($courseid) {
        parent::__construct();
        $this->course = get_course($courseid);
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
     * get report data
     */
    private function get_report_data() {
        global $DB;
        $module = 0;
        $resources = 0;
        $quiz = 0;
        $assign = 0;
        $lesson = 0;
        $enrolled = 0;
        $activities = \course_modinfo::get_array_of_activities($this->course);
        $activityresources = [];
        $activitymodules = [];
        $activitiesinfo = report_helper::activities_info_details();
        foreach ($activities as $activity) {
            if ($DB->record_exists('course_modules', ['id' => $activity->cm, 'deletioninprogress' => 0])) {
                if ($activitiesinfo[$activity->mod]->archetype == 0) {
                    $activitymodules[] = $activity;
                } else if ($activitiesinfo[$activity->mod]->archetype == 1) {
                    $activityresources[] = $activity;
                }
            }
        }

        $this->reportdata['modules'] = count($activitymodules);
        $this->reportdata['resources'] = count($activityresources);
        $icons = ['fa fa-question', 'fa fa-file', 'fa fa-file-text-o'];
        $i = 0;
        $mostactivities = [];
        foreach (report_helper::get_most_activities_in_course($this->course->id) as $values) {
            $values->name = get_string('pluginname', $values->name);
            $values->icon = $icons[$i];
            $mostactivities[] = $values;
            $i++;
        }

        $this->reportdata['mostactivities'] = $mostactivities;
        $this->reportdata['enrolled'] = \report_lmsace_reports\widgets::get_course_progress_status($this->course->id, true);
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }
}
