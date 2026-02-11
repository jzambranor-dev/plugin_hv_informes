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
 * Report plugin "lmsace_reports" - settings.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot. "/report/lmsace_reports/lib.php");

// Just a link to site report.
$ADMIN->add('reports', new admin_externalpage('lmsacesitereports', get_string('pluginname', 'report_lmsace_reports'),
    $CFG->wwwroot . "/report/lmsace_reports/index.php"));

// Enable / Disable available layouts.
$choices = [
    REPORT_LMSACE_REPORTS_SITESTATE => get_string('sitestatewidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITEACTIVITIES => get_string('siteactivitieswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITESTATEINFO => get_string('sitestateinfowidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITEUSERS => get_string('siteuserswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITEENROLLMENTSTATUS => get_string('siteenrolmentstatuswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITEENROLLMENTMETHOD => get_string('siteenrolmentmethodwidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITECOHORTSINFO => get_string('sitecohortsinfowidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITETOPCOURSEBYENROLLMENT => get_string('sitetopcoursebyenrolmentwidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITETOPCOURSEBYCOMPLETION => get_string('sitetopcoursebycompletionwidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_SITEVISITS => get_string('sitevisitswidget', 'report_lmsace_reports'),
];

$settings->add(new admin_setting_configmulticheckbox(
    'reports_lmsace_reports/visiblesitereports',
    get_string('sitereportswidgets', 'report_lmsace_reports'),
    get_string('sitereportswidgets_help', 'report_lmsace_reports'),
    $choices, $choices)
);

unset($choices);

$choices = [
    REPORT_LMSACE_REPORTS_COURSESTATE => get_string('coursestatewidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_COURSEMODULEGRADES => get_string('coursemodulegradeswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_COURSEACTIVEINACTIVEUSERS => get_string('courseactiveinactiveuserswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_COURSEACTIVITY => get_string('courseactivitywidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_COURSEVISTITS => get_string('coursevistitswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_COURSEHIGHSCORE => get_string('coursehighscorewidget', 'report_lmsace_reports'),
];

$settings->add(new admin_setting_configmulticheckbox(
    'reports_lmsace_reports/visiblecoursereports',
    get_string('coursereportswidgets', 'report_lmsace_reports'),
    get_string('coursereportswidgets_help', 'report_lmsace_reports'),
    $choices, $choices)
);

unset($choices);

$choices = [
    REPORT_LMSACE_REPORTS_USERSTATE => get_string('userstatewidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERMYACTIVITIES => get_string('usermyactivitieswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERMYQUIZZES => get_string('usermyquizzeswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERMYASSIGNMENTS => get_string('usermyassignmentswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERLOGINS => get_string('userloginswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERSCORES => get_string('userscoreswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERCOHORTSGROUPS => get_string('usercohortsgroupswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_USERMOSTVISITCOURSES => get_string('usermostvisitcourseswidget', 'report_lmsace_reports'),
];

$settings->add(
    new admin_setting_configmulticheckbox(
        'reports_lmsace_reports/visibleuserreports',
        get_string('usersreportswidgets', 'report_lmsace_reports'),
        get_string('usersreportswidgets_help', 'report_lmsace_reports'),
        $choices, $choices
    )
);

unset($choices);

$choices = [
    REPORT_LMSACE_REPORTS_TEACHERSTATE => get_string('teacherstatewidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_TEACHERCOURSESSUMMARY => get_string('teachercoursessummarywidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_TEACHERSTUDENTPERFORMANCE => get_string('teacherstudentperformancewidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_TEACHERGRADINGOVERVIEW => get_string('teachergradingoverviewwidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_TEACHERACTIVITYCOMPLETION => get_string('teacheractivitycompletionwidget', 'report_lmsace_reports'),
];

$settings->add(
    new admin_setting_configmulticheckbox(
        'reports_lmsace_reports/visibleteacherreports',
        get_string('teacherreportswidgets', 'report_lmsace_reports'),
        get_string('teacherreportswidgets_help', 'report_lmsace_reports'),
        $choices, $choices
    )
);

unset($choices);

// Evaluation report widgets.
$choices = [
    REPORT_LMSACE_REPORTS_EVALUATIONSTATE => get_string('evaluationstatewidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_EVALUATIONCOURSES => get_string('evaluationcourseswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_EVALUATIONACTIVITIES => get_string('evaluationactivitieswidget', 'report_lmsace_reports'),
    REPORT_LMSACE_REPORTS_EVALUATIONACTIVITYDETAIL => get_string('evaluationactivitydetailwidget', 'report_lmsace_reports'),
];

$settings->add(
    new admin_setting_configmulticheckbox(
        'reports_lmsace_reports/visibleevaluationreports',
        get_string('evaluationreportswidgets', 'report_lmsace_reports'),
        get_string('evaluationreportswidgets_help', 'report_lmsace_reports'),
        $choices, $choices
    )
);
// Just a link to global report.
