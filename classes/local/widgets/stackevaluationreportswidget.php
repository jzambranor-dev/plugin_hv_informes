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
 * Evaluation overview state widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Evaluation overview state widget showing total students, courses, completion rate and activities.
 */
class stackevaluationreportswidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "evaluation";

    /** @var int Teacher user ID. */
    protected $teacherid;

    /**
     * Constructor.
     * @param int $teacherid
     */
    public function __construct($teacherid) {
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->get_report_data();
    }

    /**
     * Get chart type.
     * @return null
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
     * Prepare report data.
     */
    private function get_report_data() {
        global $DB;
        $courses = report_helper::get_teacher_courses($this->teacherid);
        $totalstudents = 0;
        $totalactivities = 0;
        $totalcompletionrate = 0;

        foreach ($courses as $course) {
            $courseid = $course->courseid;
            $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($courseid, true);
            $completed = \report_lmsace_reports\widgets::get_course_completion_users($courseid, [], true);
            $totalstudents += $enrolled;
            $rate = $enrolled > 0 ? round(($completed / $enrolled) * 100, 1) : 0;
            $totalcompletionrate += $rate;
            $totalactivities += $DB->count_records('course_modules', [
                'course' => $courseid, 'deletioninprogress' => 0,
            ]);
        }

        $coursecount = count($courses);
        $this->reportdata = [
            'totalstudents' => $totalstudents,
            'totalcourses' => $coursecount,
            'avgcompletionrate' => $coursecount > 0 ? round($totalcompletionrate / $coursecount, 1) : 0,
            'totalactivities' => $totalactivities,
        ];
    }

    /**
     * Get data.
     * @return array
     */
    public function get_data() {
        return $this->reportdata;
    }
}
