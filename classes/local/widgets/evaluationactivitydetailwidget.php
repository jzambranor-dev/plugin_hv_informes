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
use report_lmsace_reports\report_helper;

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

        $dates = $this->get_module_dates($cm, $module);
        $completion = $this->get_completion_stats($cm, $module);
        $avggrade = $this->get_average_grade($cm, $module);
        $notattemptedlist = $this->get_not_attempted_users();
        $quizdata = $this->get_quiz_detail_data($cm, $module);

        $this->reportdata = array_merge([
            'activityname' => format_string($activityname),
            'activitytype' => get_string('modulename', $module->name),
            'modulename' => $module->name,
            'avggrade' => $avggrade,
            'notattempted' => $notattemptedlist,
            'notattemptedcount' => count($notattemptedlist),
            'hasnotattempted' => !empty($notattemptedlist),
            'label' => [
                get_string('passed', 'report_lmsace_reports'),
                get_string('failed', 'report_lmsace_reports'),
                get_string('noattempt', 'report_lmsace_reports'),
            ],
            'value' => [$completion['passed'], $completion['failed'], $completion['notcompleted']],
        ], $dates, $completion, $quizdata);
    }

    /**
     * Get activity dates and average time based on module type.
     *
     * @param object $cm Course module record.
     * @param object $module Module record.
     * @return array Keys: startdate, enddate, avgtime.
     */
    private function get_module_dates($cm, $module) {
        global $DB;

        $startdate = '';
        $enddate = '';
        $avgtime = get_string('notavailable', 'report_lmsace_reports');

        if ($module->name === 'quiz') {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
            if ($quiz) {
                $startdate = $quiz->timeopen > 0 ? userdate($quiz->timeopen) : '';
                $enddate = $quiz->timeclose > 0 ? userdate($quiz->timeclose) : '';

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
            $startdate = userdate($cm->added);
        }

        return ['startdate' => $startdate, 'enddate' => $enddate, 'avgtime' => $avgtime];
    }

    /**
     * Get completion statistics for this course module.
     *
     * @param object $cm Course module record.
     * @param object $module Module record (unused, kept for signature consistency).
     * @return array Keys: enrolled, completed, notcompleted, passed, failed.
     */
    private function get_completion_stats($cm, $module) {
        global $DB;

        $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($this->courseid, true);

        // Single query instead of 3 separate COUNT queries.
        $sql = "SELECT
                SUM(CASE WHEN completionstate IN (1,2,3) THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN completionstate = 2 THEN 1 ELSE 0 END) AS passed,
                SUM(CASE WHEN completionstate = 3 THEN 1 ELSE 0 END) AS failed
            FROM {course_modules_completion}
            WHERE coursemoduleid = :cmid";
        $stats = $DB->get_record_sql($sql, ['cmid' => $this->cmid]);

        $completedcount = (int) ($stats->completed ?? 0);
        $passedcount = (int) ($stats->passed ?? 0);
        $failedcount = (int) ($stats->failed ?? 0);
        $notcompleted = max(0, $enrolled - $completedcount);

        return [
            'enrolled' => $enrolled,
            'completed' => $completedcount,
            'notcompleted' => $notcompleted,
            'passed' => $passedcount,
            'failed' => $failedcount,
        ];
    }

    /**
     * Get average grade for this activity, trying module-specific tables first, then gradebook.
     *
     * @param object $cm Course module record.
     * @param object $module Module record.
     * @return float Average grade scaled to /10.
     */
    private function get_average_grade($cm, $module) {
        global $DB;

        $avggrade = 0;

        if ($module->name === 'quiz') {
            $sql = "SELECT AVG(qg.grade / q.grade * 10) as avggrade
                FROM {quiz_grades} qg
                JOIN {quiz} q ON q.id = qg.quiz
                WHERE qg.quiz = :quizid AND q.grade > 0";
            $result = $DB->get_record_sql($sql, ['quizid' => $cm->instance]);
            if ($result && $result->avggrade !== null) {
                $avggrade = round($result->avggrade, 1);
            }
        } else if ($module->name === 'assign') {
            $sql = "SELECT AVG(ag.grade / a.grade * 10) as avggrade
                FROM {assign_grades} ag
                JOIN {assign} a ON a.id = ag.assignment
                WHERE ag.assignment = :assignid AND ag.grade >= 0 AND a.grade > 0";
            $result = $DB->get_record_sql($sql, ['assignid' => $cm->instance]);
            if ($result && $result->avggrade !== null) {
                $avggrade = round($result->avggrade, 1);
            }
        }

        // Fallback to gradebook if module-specific query returned 0.
        if ($avggrade == 0) {
            $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 10) as avggrade
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gi.itemtype = 'mod' AND gi.itemmodule = :modulename AND gi.iteminstance = :instanceid
                AND gi.courseid = :courseid AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
            $result = $DB->get_record_sql($sql, [
                'modulename' => $module->name,
                'instanceid' => $cm->instance,
                'courseid' => $this->courseid,
            ]);
            if ($result && $result->avggrade !== null) {
                $avggrade = round($result->avggrade, 1);
            }
        }

        return $avggrade;
    }

    /**
     * Get list of enrolled users who have not attempted this activity.
     *
     * @return array List of user records with rownum, fullname, email.
     */
    private function get_not_attempted_users() {
        global $DB;

        $sql = "SELECT u.id, u.email, u.firstname, u.lastname,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
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

        $list = [];
        $index = 1;
        foreach ($notattempted as $user) {
            $list[] = [
                'rownum' => $index++,
                'fullname' => fullname($user),
                'email' => $user->email,
            ];
        }
        return $list;
    }

    /**
     * Get quiz-specific detail data (questions, attempts, averages).
     *
     * @param object $cm Course module record.
     * @param object $module Module record.
     * @return array Quiz detail data or defaults if not a quiz.
     */
    private function get_quiz_detail_data($cm, $module) {
        $defaults = [
            'isquiz' => false,
            'quizquestions' => [],
            'quizattempts' => [],
            'hasquizattempts' => false,
            'quizattemptscount' => 0,
            'quizdetaildownloadurl' => '',
            'quizquestionlabelsjson' => '[]',
            'quizquestionavgsjson' => '[]',
            'quizquestionmaxjson' => '[]',
        ];

        if ($module->name !== 'quiz') {
            return $defaults;
        }

        $slots = report_helper::get_quiz_slots($cm->instance);
        $attdata = report_helper::get_quiz_attempts_with_questions($cm->instance, $this->courseid);

        if (empty($slots) || empty($attdata['attempts'])) {
            $defaults['isquiz'] = true;
            return $defaults;
        }

        $quizquestions = [];
        $quizquestionlabels = [];
        $quizquestionmax = [];
        foreach ($slots as $s) {
            $quizquestions[] = [
                'slot' => $s->slot,
                'label' => 'P' . $s->slot,
                'questionname' => format_string($s->questionname),
                'maxmark' => round($s->maxmark, 2),
            ];
            $quizquestionlabels[] = format_string($s->questionname);
            $quizquestionmax[] = round($s->maxmark, 2);
        }

        $quizattempts = [];
        $rownum = 1;
        foreach ($attdata['attempts'] as $att) {
            $marks = [];
            foreach ($slots as $s) {
                $mark = $att->questionmarks[$s->slot] ?? null;
                $marks[] = [
                    'slot' => $s->slot,
                    'mark' => ($mark !== null) ? $mark : '-',
                ];
            }
            $quizattempts[] = [
                'rownum' => $rownum++,
                'fullname' => $att->fullname,
                'email' => $att->email,
                'idnumber' => $att->idnumber,
                'questionmarks' => $marks,
                'grade' => $att->grade,
            ];
        }

        // Compute average score per question for the chart.
        $quizquestionavgs = [];
        foreach ($slots as $s) {
            $total = 0;
            $count = 0;
            foreach ($attdata['attempts'] as $att) {
                if (isset($att->questionmarks[$s->slot]) && $att->questionmarks[$s->slot] !== null) {
                    $total += $att->questionmarks[$s->slot];
                    $count++;
                }
            }
            $quizquestionavgs[] = ($count > 0) ? round($total / $count, 2) : 0;
        }

        $downloadurl = new \moodle_url('/report/lmsace_reports/index.php', [
            'report' => 'evaluationreport',
            'evalcmid' => $this->cmid,
            'evalcourse' => $this->courseid,
            'download' => 'excel',
        ]);

        return [
            'isquiz' => true,
            'quizquestions' => $quizquestions,
            'quizattempts' => $quizattempts,
            'hasquizattempts' => !empty($quizattempts),
            'quizattemptscount' => count($quizattempts),
            'quizdetaildownloadurl' => $downloadurl->out(false),
            'quizquestionlabelsjson' => json_encode($quizquestionlabels),
            'quizquestionavgsjson' => json_encode($quizquestionavgs),
            'quizquestionmaxjson' => json_encode($quizquestionmax),
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
