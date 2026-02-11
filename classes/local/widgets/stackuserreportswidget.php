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
 * Table class that contains the list of stack user reports.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Class stack user reports.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stackuserreportswidget extends widgets_info {

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
     */
    public function __construct($userid) {
        global $DB;
        parent::__construct();
        $this->user = $DB->get_record('user', ['id' => $userid]);
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
     * Set the report data.
     * @return void
     */
    private function get_report_data() {
        global $OUTPUT, $DB, $CFG;
        // Include badges lib.
        require_once($CFG->dirroot.'/lib/badgeslib.php');

        // User basic information.
        $this->reportdata['username'] = fullname($this->user);
        $this->reportdata['email'] = $this->user->email;

        // Not access string.
        $notaccess = get_string('notaccess', 'report_lmsace_reports');
        // User access time details.
        $this->reportdata['timecreated'] = !empty($this->user->timecreated) ?
            userdate($this->user->timecreated, get_string('strftimedatetime'), '', false) : $notaccess;
        $this->reportdata['firstaccess'] = !empty($this->user->firstaccess) ?
            userdate($this->user->firstaccess, get_string('strftimedatetime'), '', false) : $notaccess;
        $this->reportdata['lastlogin'] = !empty($this->user->lastaccess) ?
            userdate($this->user->lastaccess, get_string('strftimedatetime'), '', false) : $notaccess;

        // User image.
        $this->reportdata['userimage'] = $OUTPUT->user_picture($this->user);

        // User enroled courses.
        $courses  = enrol_get_users_courses($this->user->id);
        // User completed courses.
        $completedcourses = $DB->count_records_sql("SELECT count(*) FROM {course_completions} WHERE
            userid = :userid AND timecompleted IS NOT NULL", ['userid' => $this->user->id]);
        $this->reportdata['courses'] = count($courses);
        $this->reportdata['completed'] = $completedcourses;

        // User assigned badges.
        $badges = badges_get_user_badges($this->user->id);
        $this->reportdata['badges'] = count($badges);
        // Users available points.
        $this->reportdata['points'] = $this->user_points();
        $this->reportdata['showpoints'] = is_numeric($this->user_points()) ? true : false;
        // User submissions for assignment.
        $this->reportdata['assignsubmission'] = count($DB->get_records('assign_submission', [
            'userid' => $this->user->id, 'status' => 'submitted',
        ]));
        // Quiz attempts list.
        $this->reportdata['quiz_attempts'] = count($DB->get_records('quiz_attempts', ['userid' => $this->user->id]));
    }

    /**
     * Get user points
     */
    public function user_points() {
        global $PAGE;
        if (class_exists('\block_xp\di')) {
            $xpworld = \block_xp\di::get('course_world_factory')->get_world($PAGE->course->id);
            $state = $xpworld->get_store()->get_state($this->user->id);
            $points = $state->get_xp();
            $xprend = $PAGE->get_renderer('block_xp');
            return $xprend->xp($points);
        }
        return '';
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return $this->reportdata;
    }
}
