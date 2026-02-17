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
 * Reports view page.
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot. '/report/lmsace_reports/lib.php');

// Set external page admin.

use report_lmsace_reports\report_helper;

$defaultcourse = report_helper::get_first_course();
$defaultuser = report_helper::get_first_user();
$defaultteacher = report_helper::get_first_teacher();
$courseaction = optional_param('courseinfo', $defaultcourse, PARAM_INT);
$useraction = optional_param('userinfo', $defaultuser, PARAM_INT);
$teacheraction = optional_param('teacherinfo', $defaultteacher, PARAM_INT);
$report = optional_param('report', '', PARAM_TEXT);
$evalteacher = optional_param('evalteacher', $defaultteacher, PARAM_INT);
$evalcourse = optional_param('evalcourse', 0, PARAM_INT);
$evalcmid = optional_param('evalcmid', 0, PARAM_INT);
$evalmodtype = optional_param('evalmodtype', '', PARAM_TEXT);
$evalfrom = optional_param('evalfrom', 0, PARAM_INT);
$evalto = optional_param('evalto', 0, PARAM_INT);

// Page URL.
$pageurl = new moodle_url($CFG->wwwroot."/report/lmsace_reports/index.php");

if ($report == 'coursereport') {
    $context = context_course::instance($courseaction);
    require_capability("report/lmsace_reports:viewcoursereports", $context);
    $pageurl->param('courseinfo', $courseaction);

} else if ($report == 'userreport') {
    if ($USER->id == $useraction) {
        $context = context_user::instance($useraction);
        require_capability("report/lmsace_reports:viewuserreports", $context);
    } else {
        $context = context_user::instance($useraction);
        require_capability("report/lmsace_reports:viewotheruserreports", $context);
    }

    // Prevent generating the reports for admin users.
    if (is_siteadmin($useraction)) {
        core\notification::info(get_string('noadminreports', 'report_lmsace_reports'));
    }
    $pageurl->param('userinfo', $useraction);
} else if ($report == 'teacherreport') {
    $context = context_system::instance();
    require_capability("report/lmsace_reports:viewteacherreports", $context);
    $pageurl->param('teacherinfo', $teacheraction);
} else if ($report == 'evaluationreport') {
    $context = context_system::instance();
    require_capability("report/lmsace_reports:viewevaluationreports", $context);
    // Add evaluation params to page URL so table pagination/sorting preserves filters.
    $pageurl->param('evalteacher', $evalteacher);
    if ($evalcourse) {
        $pageurl->param('evalcourse', $evalcourse);
    }
    if ($evalcmid) {
        $pageurl->param('evalcmid', $evalcmid);
    }
    if ($evalmodtype !== '') {
        $pageurl->param('evalmodtype', $evalmodtype);
    }
    if ($evalfrom) {
        $pageurl->param('evalfrom', $evalfrom);
    }
    if ($evalto) {
        $pageurl->param('evalto', $evalto);
    }
} else {
    $context = context_system::instance();
    require_capability("report/lmsace_reports:viewsitereports", $context);
}

list($context, $course, $cm) = get_context_info_array($context->id);
require_login($course, false, $cm);

if ($report) {
    $pageurl->param('report', $report);
}

// Set page context.
$PAGE->set_context($context);

// Set page URL.
$PAGE->set_url($pageurl);

// Set Page layout.
$PAGE->set_pagelayout('standard');

// Set page heading.
if ($context->id == SYSCONTEXTID) {
    admin_externalpage_setup('lmsacesitereports', '', $PAGE->url->params(),
        $PAGE->url->out(false), ['pagelayout' => 'standard']);
    $PAGE->set_heading(get_string('reports', 'report_lmsace_reports'));
} else {
    $PAGE->set_heading($context->get_context_name(false));
}

$PAGE->set_title($SITE->shortname.": ".get_string("lmsacereports", "report_lmsace_reports"));

$PAGE->add_body_class('lmsace-reports-body');
$output = $PAGE->get_renderer('report_lmsace_reports');
$output->courseaction = $courseaction;
$output->useraction = $useraction;
$output->teacheraction = $teacheraction;
$output->report = $report;
$output->evalteacher = $evalteacher;
$output->evalcourse = $evalcourse;
$output->evalcmid = $evalcmid;
$output->evalmodtype = $evalmodtype;
$output->evalfrom = $evalfrom;
$output->evalto = $evalto;

// Print output in page.
echo $output->header();
$renderable = new \report_lmsace_reports\output\lmsace_reports();

// Load js.
$PAGE->requires->js_call_amd('report_lmsace_reports/main', 'init');
echo $output->render($renderable);
echo $output->footer();
