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
 * Plugin functions.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Site reports - site information stack report.
define("REPORT_LMSACE_REPORTS_SITESTATE", "stacksitereports");

// Site reports - site user visits report.
define("REPORT_LMSACE_REPORTS_SITEVISITS", "sitevisits");

// Site report - Users registrations status counts.
define("REPORT_LMSACE_REPORTS_SITEUSERS", "siteusers");

// Site report - Site overview reports.
define("REPORT_LMSACE_REPORTS_SITEACTIVITIES", "overallsiteinfo");

// Site report - Unique active users report.
define("REPORT_LMSACE_REPORTS_SITEACTIVEUSERS", "siteactiveusers");

// Site report - Registered users enrolment status.
define("REPORT_LMSACE_REPORTS_SITEENROLLMENTSTATUS", "siteresourceofcourses");

// Site report - Enrolments completion by month.
define("REPORT_LMSACE_REPORTS_SITEENROLLMENTSCOMPLETIONMONTH", "enrolcompletionmonth");

// Site report - Enrolment methods usages.
define("REPORT_LMSACE_REPORTS_SITEENROLLMENTMETHOD", "enrolmethodusers");

// Site report - Top courses by enrollments.
define("REPORT_LMSACE_REPORTS_SITETOPCOURSEBYENROLLMENT", "topcourseenrolment");

// Site report - Top courses by completion.
define("REPORT_LMSACE_REPORTS_SITETOPCOURSEBYCOMPLETION", "topcoursecompletion");

// Site report - Cohorts information.
define("REPORT_LMSACE_REPORTS_SITECOHORTSINFO", "cohortsinfo");

// Site report - Overview stats information.
define("REPORT_LMSACE_REPORTS_SITESTATEINFO", "sitestateinfo");

// Course report - Course stats information.
define("REPORT_LMSACE_REPORTS_COURSESTATE", "stackcoursereports");

// Course report - Course enrol completion.
define("REPORT_LMSACE_REPORTS_COURSEENROLMENTCOMPLETION", "courseenrolcompletion");

// Course report - Course active users and inactive users.
define("REPORT_LMSACE_REPORTS_COURSEACTIVEINACTIVEUSERS", "courseactiveinactiveusers");

// Course report - Course resources.
define("REPORT_LMSACE_REPORTS_COURSEACTIVITY", "courseresources");

// Course report - Course visits.
define("REPORT_LMSACE_REPORTS_COURSEVISTITS", "coursevisits");

// Course report - Course high scores.
define("REPORT_LMSACE_REPORTS_COURSEHIGHSCORE", "coursehighscore");

// Course report - Course module grades.
define("REPORT_LMSACE_REPORTS_COURSEMODULEGRADES", 'coursemodulegrades');

// User report - User stats imformation.
define("REPORT_LMSACE_REPORTS_USERSTATE", "stackuserreports");

// User report - User my activities information.
define("REPORT_LMSACE_REPORTS_USERMYACTIVITIES", "usermyactivities");

// User report - User my quiz information.
define("REPORT_LMSACE_REPORTS_USERMYQUIZZES", "usermyquizzes");

// User report - User my assignments information.
define("REPORT_LMSACE_REPORTS_USERMYASSIGNMENTS", "usermyassignments");

// User report - User groups and cohorts.
define("REPORT_LMSACE_REPORTS_USERCOHORTSGROUPS", "usergroupcohorts");

// User report - User login logs.
define("REPORT_LMSACE_REPORTS_USERLOGINS", "userlogins");

// User report - Users most visited course.
define("REPORT_LMSACE_REPORTS_USERMOSTVISITCOURSES", "mostvisitcourse");

// User report - User Score.
define("REPORT_LMSACE_REPORTS_USERSCORES", "userscore");

// Teacher report - Teacher stats information.
define("REPORT_LMSACE_REPORTS_TEACHERSTATE", "stackteacherreports");

// Teacher report - Teacher courses summary.
define("REPORT_LMSACE_REPORTS_TEACHERCOURSESSUMMARY", "teachercoursessummary");

// Teacher report - Teacher student performance.
define("REPORT_LMSACE_REPORTS_TEACHERSTUDENTPERFORMANCE", "teacherstudentperformance");

// Teacher report - Teacher grading overview.
define("REPORT_LMSACE_REPORTS_TEACHERGRADINGOVERVIEW", "teachergradingoverview");

// Teacher report - Teacher activity completion.
define("REPORT_LMSACE_REPORTS_TEACHERACTIVITYCOMPLETION", "teacheractivitycompletion");

define("REPORT_LMSACE_REPORTS_EVALUATIONSTATE", "stackevaluationreports");
define("REPORT_LMSACE_REPORTS_EVALUATIONCOURSES", "evaluationcourses");
define("REPORT_LMSACE_REPORTS_EVALUATIONACTIVITIES", "evaluationactivities");
define("REPORT_LMSACE_REPORTS_EVALUATIONACTIVITYDETAIL", "evaluationactivitydetail");

require_once($CFG->dirroot. '/report/lmsace_reports/classes/report_helper.php');

use report_lmsace_reports\report_helper;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass        $course     The course to object for the report
 * @param context         $context    The context of the course
 */
function report_lmsace_reports_extend_navigation_course($navigation, $course, $context) {
    $url = new moodle_url('/report/lmsace_reports/index.php', ['report' => 'coursereport', 'courseinfo' => $course->id]);
    $navigation->add(get_string('lmsacecoursereports', 'report_lmsace_reports'), $url, navigation_node::TYPE_SETTING, null, null,
            new pix_icon('i/report', ''));
}

/**
 * This function extends the course navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $user
 * @param stdClass $course The course to object for the report
 */
function report_lmsace_reports_extend_navigation_user($navigation, $user, $course) {
    global $USER;
    $url = new moodle_url('/report/lmsace_reports/index.php', ['report' => 'userreport', 'userinfo' => $user->id]);
    $navigation->add(get_string('lmsaceuserreports', 'report_lmsace_reports'), $url);
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function report_lmsace_reports_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;
    $url = new moodle_url('/report/lmsace_reports/index.php',
        ['report' => 'userreport', 'userinfo' => $user->id]);
    $node = new core_user\output\myprofile\node('reports', 'userlmsacereport',
        get_string('lmsaceuserreports', 'report_lmsace_reports'), null, $url);
    $tree->add_node($node);
    return true;
}

/**
 * Get the block reports template data.
 *
 * @return array
 */
function report_lmsace_get_block_report() {
    global $USER, $OUTPUT, $PAGE, $USER;
    // Site related show the report.
    if (has_capability("report/lmsace_reports:viewsitereports", context_system::instance())) {
        $classname = '\\report_lmsace_reports\\local\\widgets\\stacksitereportswidget';
        $widgetinstance = new $classname();
        $templatename = "report_lmsace_reports/siteblocks";
        $reporturl = new moodle_url('/report/lmsace_reports/index.php');
    } else if ($PAGE->context->contextlevel == CONTEXT_COURSE) {
        $classname = '\\report_lmsace_reports\\local\\widgets\\stackcoursereportswidget';
        $widgetinstance = new $classname($PAGE->course->id);
        $templatename = "report_lmsace_reports/courseblocks";
        $reporturl = new moodle_url('/report/lmsace_reports/index.php', ['report' => 'coursereport',
            'courseinfo' => $PAGE->course->id,
        ]);
    } else if (report_helper::is_currentuser_has_teacherrole()) {
        $classname = '\\report_lmsace_reports\\local\\widgets\\stackcoursereportswidget';
        $teachercourse = report_helper::get_teacher_courses($USER->id);
        $courseid = current($teachercourse)->courseid;
        $widgetinstance = new $classname($courseid);
        $templatename = "report_lmsace_reports/courseblocks";
        $reporturl = new moodle_url('/report/lmsace_reports/index.php', ['report' => 'coursereport', 'courseinfo' => $courseid]);
    } else { // User related the report.
        $classname = '\\report_lmsace_reports\\local\\widgets\\stackuserreportswidget';
        $widgetinstance = new $classname($USER->id);
        $templatename = "report_lmsace_reports/userblocks";
        $reporturl = new moodle_url('/report/lmsace_reports/index.php', ['report' => 'userreport', 'userinfo' => $USER->id]);
    }
    $template = $widgetinstance->get_data();
    $template['stacksitereports'] = true;
    $template['enableuserblock'] = true;
    $template['enablecourseblock'] = true;
    $template['stackcoursereports'] = true;
    $template['stackuserreports'] = true;
    $template['reportbase'] = true;
    $header = $OUTPUT->render_from_template($templatename, $template);
    $footer = html_writer::link($reporturl->out(false), get_string('viewreport', 'block_lmsace_reports'));
    return [$header, $footer];
}
