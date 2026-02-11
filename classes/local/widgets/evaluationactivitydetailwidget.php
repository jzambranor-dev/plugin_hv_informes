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
 * Evaluation activity detail widget.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Evaluation activity detail widget showing completion stats, grades and a doughnut chart.
 */
class evaluationactivitydetailwidget extends widgets_info {

    /** @var string Widget context. */
    public $context = "evaluation";

    /** @var int Course module ID. */
    protected $cmid;

    /** @var int Course ID. */
    protected $courseid;

    /**
     * Constructor.
     * @param int $cmid
     * @param int $courseid
     */
    public function __construct($cmid, $courseid) {
        parent::__construct();
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->get_report_data();
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
     * Prepare report data.
     */
    private function get_report_data() {
        global $DB;

        $cm = $DB->get_record('course_modules', ['id' => $this->cmid]);
        if (!$cm) {
            $this->reportdata = ['error' => true];
            return;
        }

        $module = $DB->get_record('modules', ['id' => $cm->module]);
        $activityname = $DB->get_field($module->name, 'name', ['id' => $cm->instance]);

        // Get dates based on module type.
        $startdate = '';
        $enddate = '';
        $avgtime = get_string('notavailable', 'report_lmsace_reports');

        if ($module->name === 'quiz') {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
            if ($quiz) {
                $startdate = $quiz->timeopen > 0 ? userdate($quiz->timeopen) : '';
                $enddate = $quiz->timeclose > 0 ? userdate($quiz->timeclose) : '';

                // Average time for quiz.
                $sql = "SELECT AVG(qa.timefinish - qa.timestart) as avgtime
                    FROM {quiz_attempts} qa
                    WHERE qa.quiz = :quizid AND qa.state = 'finished'
                    AND qa.timefinish > qa.timestart";
                $timeresult = $DB->get_record_sql($sql, ['quizid' => $cm->instance]);
                if ($timeresult && $timeresult->avgtime > 0) {
                    $mins = round($timeresult->avgtime / 60, 1);
                    $avgtime = $mins . ' ' . get_string('minutes', 'report_lmsace_reports');
                }
            }
        } else if ($module->name === 'assign') {
            $assign = $DB->get_record('assign', ['id' => $cm->instance]);
            if ($assign) {
                $startdate = $assign->allowsubmissionsfromdate > 0 ? userdate($assign->allowsubmissionsfromdate) : '';
                $enddate = $assign->duedate > 0 ? userdate($assign->duedate) : '';
            }
        } else {
            // Generic: use course module added date.
            $startdate = userdate($cm->added);
        }

        // Count completion stats.
        $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($this->courseid, true);

        $completedcount = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course_modules_completion} WHERE coursemoduleid = :cmid AND completionstate IN (1,2,3)",
            ['cmid' => $this->cmid]
        );
        $passedcount = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course_modules_completion} WHERE coursemoduleid = :cmid AND completionstate = 2",
            ['cmid' => $this->cmid]
        );
        $failedcount = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course_modules_completion} WHERE coursemoduleid = :cmid AND completionstate = 3",
            ['cmid' => $this->cmid]
        );
        $notcompleted = $enrolled - $completedcount;
        if ($notcompleted < 0) {
            $notcompleted = 0;
        }

        // Average grade.
        $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 100) as avggrade
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gi.id = gg.itemid
            WHERE gi.itemtype = 'mod' AND gi.itemmodule = :modulename AND gi.iteminstance = :instanceid
            AND gi.courseid = :courseid AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
        $graderesult = $DB->get_record_sql($sql, [
            'modulename' => $module->name,
            'instanceid' => $cm->instance,
            'courseid' => $this->courseid,
        ]);
        $avggrade = ($graderesult && $graderesult->avggrade !== null) ? round($graderesult->avggrade, 1) : 0;

        // Students who have NOT attempted.
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
            WHERE u.deleted = 0 AND ue.status = 0
            AND u.id NOT IN (
                SELECT cmc.userid FROM {course_modules_completion} cmc
                WHERE cmc.coursemoduleid = :cmid
            )
            ORDER BY u.lastname, u.firstname";
        $notattempted = $DB->get_records_sql($sql, [
            'courseid' => $this->courseid,
            'cmid' => $this->cmid,
        ]);

        $notattemptedlist = [];
        $index = 1;
        foreach ($notattempted as $user) {
            $notattemptedlist[] = [
                'rownum' => $index++,
                'fullname' => fullname($user),
                'email' => $user->email,
            ];
        }

        $this->reportdata = [
            'activityname' => format_string($activityname),
            'activitytype' => get_string('modulename', $module->name),
            'modulename' => $module->name,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'avgtime' => $avgtime,
            'enrolled' => $enrolled,
            'completed' => $completedcount,
            'notcompleted' => $notcompleted,
            'passed' => $passedcount,
            'failed' => $failedcount,
            'avggrade' => $avggrade,
            'notattempted' => $notattemptedlist,
            'notattemptedcount' => count($notattemptedlist),
            'hasnotattempted' => !empty($notattemptedlist),
            // Chart data for pie.
            'label' => [
                get_string('passed', 'report_lmsace_reports'),
                get_string('failed', 'report_lmsace_reports'),
                get_string('noattempt', 'report_lmsace_reports'),
            ],
            'value' => [$passedcount, $failedcount, $notcompleted],
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
