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
 * Teacher overview state widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Teacher overview state widget showing total students, courses, completion rate and activities.
 */
class stackteacherreportswidget extends widgets_info {

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
        global $DB;
        parent::__construct();
        $this->teacherid = $teacherid;
        $this->categoryid = $categoryid;
        $this->user = $DB->get_record('user', ['id' => $teacherid]);
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

        $teachercourses = report_helper::get_teacher_courses($this->teacherid, $this->categoryid);
        $courseids = array_column((array) $teachercourses, 'courseid');

        $this->reportdata['teachername'] = $this->user ? fullname($this->user) : '';
        $this->reportdata['totalcourses'] = count($courseids);
        $this->reportdata['totalstudents'] = report_helper::get_teacher_total_students($this->teacherid);

        $totalrate = 0;
        $coursecount = 0;
        $totalactivities = 0;

        foreach ($courseids as $courseid) {
            if (!$DB->record_exists('course', ['id' => $courseid])) {
                continue;
            }
            $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($courseid, true);
            $completed = \report_lmsace_reports\widgets::get_course_completion_users($courseid, [], true);
            if ($enrolled > 0) {
                $totalrate += ($completed / $enrolled) * 100;
                $coursecount++;
            }
            $totalactivities += $DB->count_records('course_modules', [
                'course' => $courseid, 'deletioninprogress' => 0,
            ]);
        }

        $this->reportdata['avgcompletionrate'] = $coursecount > 0
            ? round($totalrate / $coursecount, 1) : 0;
        $this->reportdata['totalactivities'] = $totalactivities;
        $this->reportdata['teacherid'] = $this->teacherid;
    }

    /**
     * Get data.
     * @return array
     */
    public function get_data() {
        return $this->reportdata;
    }
}
