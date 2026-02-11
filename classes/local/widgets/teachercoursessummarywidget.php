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
 * Teacher courses summary widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Teacher courses summary table widget.
 */
class teachercoursessummarywidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "teacher";

    /** @var int Teacher user ID. */
    protected $teacherid;

    /** @var int Category filter ID. */
    protected $categoryid;

    /**
     * Constructor.
     * @param int $teacherid
     * @param int $categoryid
     */
    public function __construct($teacherid, $categoryid = 0) {
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->categoryid = $categoryid;
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
        global $OUTPUT;

        $courses = report_helper::get_teacher_courses($this->teacherid, $this->categoryid);
        $data = [];
        $data['hascourses'] = !empty($courses);
        $data['courses'] = [];

        foreach ($courses as $course) {
            $courseid = $course->courseid;
            $courserecord = get_course($courseid);
            $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($courseid, true);
            $completed = \report_lmsace_reports\widgets::get_course_completion_users($courseid, [], true);
            $rate = $enrolled > 0 ? round(($completed / $enrolled) * 100, 1) : 0;

            // Get average grade.
            global $DB;
            $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 100) as avggrade
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gi.courseid = :courseid AND gi.itemtype = 'course'
                    AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
            $graderesult = $DB->get_record_sql($sql, ['courseid' => $courseid]);
            $avggrade = ($graderesult && $graderesult->avggrade !== null) ? round($graderesult->avggrade, 1) : 0;

            $activitiescount = $DB->count_records('course_modules', [
                'course' => $courseid, 'deletioninprogress' => 0,
            ]);

            $data['courses'][] = [
                'coursename' => $courserecord->fullname,
                'enrolled' => $enrolled,
                'completionrate' => $rate,
                'averagegrade' => $avggrade,
                'activitiescount' => $activitiescount,
            ];
        }

        $this->reportdata = $OUTPUT->render_from_template('report_lmsace_reports/widgets/teachercoursessummary', $data);
    }

    /**
     * Get data.
     * @return string
     */
    public function get_data() {
        return $this->reportdata;
    }
}
