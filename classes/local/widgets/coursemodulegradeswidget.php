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
 * Table class that contains the list of learners overview report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\local\widgets;

use report_lmsace_reports\output\widgets_info;
use report_lmsace_reports\report_helper;

/**
 * Class modules grades report.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodulegradeswidget extends widgets_info {

    /**
     * @var array $records
     */
    private $records;

    /**
     * @var string $context
     */
    public $context = "course";

    /**
     * @var array $quizlist
     */
    public $quizlist = [];

    /**
     * @var array $assignmentlist
     */
    private $assignmentlist = [];

    /**
     * Average of grades for quiz and assignments
     *
     * @var array
     */
    private $avg = [];

    /**
     * Implemented the constructor.
     *
     * @param int $courseid course ID
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
     * Get the cache key.
     * @return string
     */
    public function get_cache_key() {
        return "c_" . $this->course->id . "_modulesattempts_" . $this->filter;
    }

    /**
     * Get the report data.
     * @return void
     */
    public function get_data() {
        global $OUTPUT;

        if (empty(array_filter($this->reportdata))) {
            $this->reportdata['noresult'] = true;
        }

        return $OUTPUT->render_from_template('report_lmsace_reports/widgets/coursemodulesgrade', $this->reportdata);
    }

    /**
     * Prepare report data.
     */
    private function get_report_data() {

        $data = $this->module_activities();
        $data['courseid'] = $this->course->id;
        $data['avg'] = $this->avg;
        $this->cache->set($this->get_cache_key(), $data);
        $this->reportdata = $this->cache->get($this->get_cache_key());
    }

    /**
     * Return activities grade data in the course module.
     *
     * @return array
     */
    public function module_activities() {
        global $DB;

        $daterange = report_helper::get_duration_info($this->filter, 'all');
        $quizattempts = $this->getquizattempts($daterange);
        $submission = $this->assignment_submissions($daterange);
        $grades = $this->grades($daterange);
        $result = ['attempts' => $quizattempts, 'submission' => $submission];

        return [
            'label' => [
                get_string('pluginname', 'mod_quiz'),
                get_string('pluginname', 'mod_assign'),
            ],
            'value' => $result,
            'grades' => $grades,
            'counts' => [
                'assign' => $DB->count_records_sql(
                    "SELECT count(id) FROM {course_modules}
                    WHERE course=:courseid AND module
                    IN (SELECT md.id FROM {modules} md WHERE md.name='assign')", ['courseid' => $this->course->id]),
                'quiz' => $DB->count_records_sql(
                    "SELECT count(id) FROM {course_modules}
                    WHERE course=:courseid AND module
                    IN (SELECT md.id FROM {modules} md WHERE md.name='quiz')", ['courseid' => $this->course->id]),
            ],
        ];
    }

    /**
     * Fetch the quiz module attemptes in the records.
     *
     * @param array $daterange Date range
     * @return array
     */
    public function getquizattempts($daterange) {
        global $DB;

        $report = [];
        $daterange = []; // For duration based quiz attempts add timestart and timeend in params.
        $durationsql = isset($daterange['timestart']) ? " AND timemodified BETWEEN :timestart AND :timeend " : '';
        $sql = " SELECT qa.quiz, count(*) AS count FROM {quiz_attempts} qa
            WHERE qa.quiz IN (
                SELECT cm.instance FROM {course_modules} cm  WHERE cm.course=:courseid AND cm.module IN (
                    SELECT m.id FROM {modules} m WHERE m.name = 'quiz'
                )
            ) $durationsql GROUP BY qa.quiz";
        $daterange['courseid'] = $this->course->id;

        $records = $DB->get_records_sql($sql, $daterange);
        foreach ($records as $id => $record) {
            $report[$record->quiz] = $record->count;
            $this->quizlist[] = $record->quiz;
        }
        return array_sum(array_values($report));
    }

    /**
     * Fetch the assignment submission data form the record.
     *
     * @param array $daterange Date range.
     * @return void
     */
    public function assignment_submissions($daterange) {
        global $DB;

        $sql = "SELECT assignment, COUNT(*) AS count FROM {assign_submission} WHERE status='submitted'";
        if ($daterange) {
            $sql .= ' AND timemodified BETWEEN :timestart AND :timeend ';
        }
        $sql .= " AND assignment IN ( select instance from {course_modules} WHERE course=:courseid AND module
                IN (select id from {modules} where name = 'assign') )";
        $daterange['courseid'] = $this->course->id;
        $sql .= ' GROUP BY assignment';
        $records = $DB->get_records_sql($sql, $daterange);
        if ($records) {
            foreach ($records as $id => $record) {
                $report[$record->assignment] = $record->count;
                $this->assignmentlist[] = $record->assignment;
            }
            return array_sum(array_values($report));
        }
        return 0;
    }

    /**
     * Get the grades form the date range of the course modules.
     *
     * @param array $daterange Date range option.
     * @return array
     */
    public function grades($daterange) {
        $assigngrades = $this->get_assign_grades($daterange);
        $quizgrades = $this->get_quiz_grades($daterange);
        return ['quiz' => $quizgrades, 'assign' => $assigngrades];
    }

    /**
     * Fetch the assignment grade form the records.
     *
     * @param array $daterange Date range.
     * @return void
     */
    public function get_assign_grades($daterange) {
        global $DB;
        $sql = " SELECT assignment, SUM(grade) as sumgrade, COUNT(*) AS count FROM {assign_grades} WHERE grader >= '1' ";
        // Daterange selections query contiditon.
        $sql .= isset($daterange['timestart']) ? " AND ( timemodified BETWEEN :timestart AND :timeend )  " : '';

        if (!empty($this->course)) {
            list($insql, $inparams) = $DB->get_in_or_equal($this->assignmentlist, SQL_PARAMS_NAMED, 'asg', true, '0');
            $sql .= ' AND assignment '.$insql;
            $daterange = array_merge($daterange, $inparams);
        }
        $sql .= ' GROUP BY assignment ';

        $records = $DB->get_records_sql($sql, $daterange);

        if ($records) {
            foreach ($records as $id => $record) {
                $report[$record->assignment] = $record->count;
            }

            $count = array_sum(array_column($records, 'count'));
            $this->avg['assign'] = $count ? number_format(array_sum(array_column($records, 'sumgrade')) / $count, 2) : '0';

            return array_sum(array_values($report));
        }
        return 0;
    }

    /**
     * Get the quiz module grade data from the records.
     *
     * @param array $daterange
     * @return void
     */
    public function get_quiz_grades($daterange) {
        global $DB;

        $sql = "SELECT quiz, SUM(grade) as sumgrade, COUNT(*) AS count FROM {quiz_grades}";

        list($quizlistsql, $quizlistparams) = $DB->get_in_or_equal($this->quizlist, SQL_PARAMS_NAMED, 'qz', true, '0');
        $sql .= " WHERE quiz $quizlistsql";
        $daterange += $quizlistparams;

        $sql .= isset($daterange['timestart']) ? " AND timemodified BETWEEN :timestart AND :timeend " : '';

        $sql .= ' GROUP BY quiz ';
        $records = $DB->get_records_sql($sql, $daterange);
        return $this->get_count_records($records, 'quiz');
    }

    /**
     * Get the counts of the given table records.
     *
     * @param array $records records.
     * @param string $key Module name.
     * @return void
     */
    public function get_count_records($records, $key) {
        if ($records) {
            foreach ($records as $id => $record) {
                $report[$record->{$key}] = $record->count;
            }

            $count = array_sum(array_column($records, 'count'));
            $this->avg['quiz'] = $count ? number_format(array_sum(array_column($records, 'sumgrade')) / $count, 2) : '0';
            return array_sum(array_values($report));
        }
        return 0;
    }
}
