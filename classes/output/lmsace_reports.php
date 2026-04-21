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
 * Config report renderer.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\output;

use report_lmsace_reports\report_helper;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;

require_once($CFG->dirroot.'/report/lmsace_reports/lib.php');

/**
 * Config report renderer.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lmsace_reports implements renderable, templatable {

    /**
     * Report.
     *
     * @var [type]
     */
    public $report;

    /**
     * Function to export the renderer data in a format that is suitable for a
     * edit mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $DB, $PAGE, $USER;

        $data = new stdClass();

        $data->enablecourseblock = !empty(report_helper::get_course()) ? true : false;
        $data->lastmonthsinfo = report_helper::get_current_last_12months();
        $data->currentmonth = $data->lastmonthsinfo[0]['month'];
        $data->courses = report_helper::get_course($output->courseaction);

        // Get the widgets list.
        $widgets = \report_lmsace_reports\widgets::get_widgets();
        $reportwidgets = new \report_lmsace_reports\output\report_widgets($widgets, $output);
        $widgetslist = $reportwidgets->get_widgets();

        // Load the reports js data variables.
        report_helper::load_js_data($widgetslist);
        $data->widgets = $widgetslist;

        $purgeurl = new moodle_url('/report/lmsace_reports/cache.php', ['confirm' => 1,
                'sesskey' => sesskey(),
                'returnurl' => $PAGE->url->out_as_local_url(false),
            ]);

        $data->contextid = $PAGE->context->id;
        $data->users = report_helper::get_users($output->useraction);

        $data->enableuserblock = !empty(report_helper::get_users()) ? true : false;
        $data->pageurl = new \moodle_url('/report/lmsace_reports/index.php');
        if (isset($output->report) && !empty($output->report)) {
            $data->{$output->report} = true;
            $data->reportbase = true;
            $purgeurl->param('purge', $output->report);
            $purgeurl->param('courseinfo', $output->courseaction);
            $purgeurl->param('userinfo', $output->useraction);
        }
        $data->purgeurl = $purgeurl->out(false);

        $availableteachercourses = false;
        if ($output->report == 'coursereport' && !has_capability("report/lmsace_reports:viewsitereports",
            \context_system::instance())) {
            if (report_helper::is_currentuser_has_teacherrole()) {
                $teachercourses = report_helper::get_teacher_courses($USER->id);
                $teachercourses = array_map(function($c) { return $c->courseid; }, $teachercourses);
                $data->courses = report_helper::generate_course_chooser_data($teachercourses, $output->courseaction);
                $availableteachercourses = true;
            }
        }

        $data->availableteachercourses = $availableteachercourses;
        $data->reporttype = $output->report;
        $data->courseaction = $output->courseaction;

        // Chooser form.
        require_once($CFG->dirroot . '/report/lmsace_reports/form/chooser_form.php');

        if (has_capability("report/lmsace_reports:viewsitereports", \context_system::instance()) && $data->enablecourseblock) {

            if ($PAGE->context->contextlevel == CONTEXT_SYSTEM) {
                // Course selectors form.
                $courseform = new \course_selector_form(null, ['courseinfo' => $output->courseaction]);
                if ($courseform->get_data()) {
                    $data->showcoursereport = true;
                }
                $courseform->set_data(['courseinfo' => $output->courseaction]);
                // Only render when the form will be in the DOM (not in reportbase mode).
                $data->courseform = empty($data->reportbase) ? $courseform->render() : '';
            } else {
                $data->courseform = '';
            }
        }

        if ($PAGE->context->contextlevel == CONTEXT_SYSTEM) {
            // Users selectors form.
            $form = (new \user_selector_form(null, ['userinfo' => $output->useraction]));
            if ($form->get_data()) {
                $data->showuserreport = true;
            }
            $form->set_data(['userinfo' => $output->useraction]);
            // Only render when the form will be in the DOM (not in reportbase mode).
            $data->userform = empty($data->reportbase) ? $form->render() : '';
        } else {
            $data->userform = '';
        }

        // Teacher report data.
        $data->teacheraction = $output->teacheraction ?? report_helper::get_first_teacher();
        $data->teachermonth = $output->teachermonth ?? 0;
        $data->teachercategory = $output->teachercategory ?? '';
        $data->teachers = report_helper::get_teachers($data->teacheraction);
        $data->enableteacherblock = !empty($data->teachers);

        if (has_capability("report/lmsace_reports:viewteacherreports", \context_system::instance())
                && $data->enableteacherblock) {
            if ($PAGE->context->contextlevel == CONTEXT_SYSTEM) {
                $teacherform = new \teacher_selector_form(null, ['teacherinfo' => $data->teacheraction]);
                if ($teacherform->get_data()) {
                    $data->showteacherreport = true;
                }
                $teacherform->set_data(['teacherinfo' => $data->teacheraction]);
                // Only render when the form will be in the DOM (not in reportbase mode).
                $data->teacherform = empty($data->reportbase) ? $teacherform->render() : '';

                // Generate month filter for teacher reports (last 12 months).
                $data->teachermonthfilter = [];
                $data->teachermonthfilter[] = [
                    'value' => 0,
                    'label' => get_string('allmonths', 'report_lmsace_reports'),
                    'selected' => empty($data->teachermonth) ? 'selected' : '',
                ];
                for ($i = 0; $i < 12; $i++) {
                    $ts = strtotime("-$i months", strtotime(date('Y-m-01')));
                    $data->teachermonthfilter[] = [
                        'value' => $ts,
                        'label' => userdate($ts, '%B %Y'),
                        'selected' => ($data->teachermonth == $ts) ? 'selected' : '',
                    ];
                }

                // Build teacher category filter (hierarchical, only teacher's categories).
                $data->teachercategoryfilter = [];
                if ($data->teacheraction) {
                    $data->teachercategoryfilter = report_helper::get_teacher_category_tree(
                        $data->teacheraction, $data->teachercategory
                    );
                }
            } else {
                $data->teacherform = '';
            }
        }

        // Evaluation report data.
        $data->enableevaluationblock = $data->enableteacherblock;
        $data->evalteacher = $output->evalteacher ?? 0;
        $data->evalcourse = $output->evalcourse ?? 0;
        $data->evalcmid = $output->evalcmid ?? 0;
        $data->evalmodtype = $output->evalmodtype ?? '';
        $data->evalfrom = $output->evalfrom ?? 0;
        $data->evalto = $output->evalto ?? 0;
        $data->evalmonth = $output->evalmonth ?? 0;
        $data->evalcategory = $output->evalcategory ?? '';
        $data->evalconmodtype = $output->evalconmodtype ?? '';
        // Convert timestamps to date strings for HTML date inputs.
        if ($data->evalfrom) {
            $data->evalfromdate = date('Y-m-d', $data->evalfrom);
        }
        if ($data->evalto) {
            $data->evaltodate = date('Y-m-d', $data->evalto);
        }

        if (has_capability("report/lmsace_reports:viewevaluationreports", \context_system::instance())
                && $data->enableevaluationblock) {
            if ($PAGE->context->contextlevel == CONTEXT_SYSTEM) {
                $evalform = new \evaluation_teacher_selector_form(null, ['evalteacher' => $data->evalteacher]);
                if ($evalform->get_data()) {
                    $data->showevaluationreport = true;
                }
                $evalform->set_data(['evalteacher' => $data->evalteacher]);
                // Only render when the form will be in the DOM (not in reportbase mode).
                $data->evalform = empty($data->reportbase) ? $evalform->render() : '';
            } else {
                $data->evalform = '';
            }

            // Build breadcrumb.
            $data->evalbreadcrumb = [];
            $baseurl = new \moodle_url('/report/lmsace_reports/index.php', ['report' => 'evaluationreport']);
            $data->evalbreadcrumb[] = [
                'label' => get_string('evaluationreports', 'report_lmsace_reports'),
                'url' => $baseurl->out(false),
                'active' => empty($data->evalteacher),
            ];

            if ($data->evalteacher) {
                $teacheruser = $DB->get_record('user', ['id' => $data->evalteacher]);
                $teachername = $teacheruser ? fullname($teacheruser) : '';
                $teacherurl = new \moodle_url('/report/lmsace_reports/index.php', [
                    'report' => 'evaluationreport',
                    'evalteacher' => $data->evalteacher,
                ]);
                $data->evalbreadcrumb[] = [
                    'label' => $teachername,
                    'url' => $teacherurl->out(false),
                    'active' => empty($data->evalcourse),
                ];
            }

            if ($data->evalcourse) {
                $coursename = $DB->get_field('course', 'fullname', ['id' => $data->evalcourse]);
                $courseurl = new \moodle_url('/report/lmsace_reports/index.php', [
                    'report' => 'evaluationreport',
                    'evalteacher' => $data->evalteacher,
                    'evalcourse' => $data->evalcourse,
                ]);
                $data->evalbreadcrumb[] = [
                    'label' => format_string($coursename),
                    'url' => $courseurl->out(false),
                    'active' => empty($data->evalcmid),
                ];

                // Prepare course summary data.
                $data->coursesummarydata = new stdClass();
                $data->coursesummarydata->totalenrolled = \report_lmsace_reports\widgets::get_course_progress_status($data->evalcourse, true);
                $data->coursesummarydata->totalcompleted = \report_lmsace_reports\widgets::get_course_completion_users($data->evalcourse, [], true);

                // Get module types for filter.
                $modtypes = $DB->get_records_sql(
                    "SELECT DISTINCT m.name FROM {course_modules} cm
                     JOIN {modules} m ON m.id = cm.module
                     WHERE cm.course = :courseid AND cm.deletioninprogress = 0
                     ORDER BY m.name",
                    ['courseid' => $data->evalcourse]
                );
                $data->modtypes = [];
                $data->modtypes[] = ['value' => '', 'label' => get_string('alltypes', 'report_lmsace_reports'),
                    'selected' => empty($data->evalmodtype) ? 'selected' : ''];
                foreach ($modtypes as $mod) {
                    $data->modtypes[] = [
                        'value' => $mod->name,
                        'label' => get_string('modulename', $mod->name),
                        'selected' => ($data->evalmodtype === $mod->name) ? 'selected' : '',
                    ];
                }
            }

            if ($data->evalcmid) {
                $cmname = '';
                $cm = $DB->get_record('course_modules', ['id' => $data->evalcmid]);
                if ($cm) {
                    $mod = $DB->get_record('modules', ['id' => $cm->module]);
                    $cmname = $DB->get_field($mod->name, 'name', ['id' => $cm->instance]);
                }
                $data->evalbreadcrumb[] = [
                    'label' => format_string($cmname),
                    'url' => '',
                    'active' => true,
                ];
            }

            $data->hasevalbreadcrumb = count($data->evalbreadcrumb) > 1;

            // Build consolidated view when no teacher is selected.
            if (empty($data->evalteacher)) {
                $data->evalmonthfilter = [];
                $data->evalmonthfilter[] = [
                    'value' => 0,
                    'label' => get_string('allmonths', 'report_lmsace_reports'),
                    'selected' => empty($data->evalmonth) ? 'selected' : '',
                ];
                // Generate last 12 months.
                for ($i = 0; $i < 12; $i++) {
                    $ts = strtotime("-$i months", strtotime(date('Y-m-01')));
                    $data->evalmonthfilter[] = [
                        'value' => $ts,
                        'label' => userdate($ts, '%B %Y'),
                        'selected' => ($data->evalmonth == $ts) ? 'selected' : '',
                    ];
                }

                // Build accordion category filter (parent categories with child checkboxes).
                $catswithcourses = $DB->get_records_sql(
                    "SELECT DISTINCT cc.id
                     FROM {course_categories} cc
                     JOIN {course} c ON c.category = cc.id
                     JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = " . CONTEXT_COURSE . "
                     JOIN {role_assignments} ra ON ra.contextid = ctx.id"
                );
                $catidswithcourses = array_flip(array_keys($catswithcourses));

                $allcats = $DB->get_records('course_categories', null, 'sortorder', 'id, name, parent, depth, path');

                // Parse selected category IDs.
                $selectedcatids = [];
                if (!empty($data->evalcategory)) {
                    $selectedcatids = array_flip(array_map('intval', explode(',', $data->evalcategory)));
                }

                // Build tree: top-level parents with their children (recursive flattened).
                $toplevel = [];
                $childrenof = [];
                foreach ($allcats as $cat) {
                    if ($cat->parent == 0) {
                        $toplevel[$cat->id] = $cat;
                    } else {
                        $childrenof[$cat->parent][] = $cat;
                    }
                }

                // Recursive function to collect all descendants.
                $getdescendants = function($parentid) use (&$getdescendants, $childrenof, $catidswithcourses, $selectedcatids) {
                    $result = [];
                    if (!empty($childrenof[$parentid])) {
                        foreach ($childrenof[$parentid] as $child) {
                            $hascourses = isset($catidswithcourses[$child->id]);
                            $grandchildren = $getdescendants($child->id);
                            // Show this child if it has courses or has descendants with courses.
                            if ($hascourses || !empty($grandchildren)) {
                                $checked = isset($selectedcatids[$child->id]) ? 'checked' : '';
                                $indent = max(0, $child->depth - 2);
                                $result[] = [
                                    'id' => $child->id,
                                    'name' => format_string($child->name),
                                    'checked' => $checked,
                                    'indent' => $indent * 20,
                                ];
                                $result = array_merge($result, $grandchildren);
                            }
                        }
                    }
                    return $result;
                };

                $data->evalcategoryaccordion = [];
                $idx = 0;
                foreach ($toplevel as $parent) {
                    $children = $getdescendants($parent->id);
                    $parenthascourses = isset($catidswithcourses[$parent->id]);
                    if (!$parenthascourses && empty($children)) {
                        continue;
                    }
                    $parentchecked = isset($selectedcatids[$parent->id]) ? 'checked' : '';
                    // Check if any child is selected to auto-expand.
                    $hasselected = !empty($parentchecked);
                    if (!$hasselected) {
                        foreach ($children as $ch) {
                            if (!empty($ch['checked'])) {
                                $hasselected = true;
                                break;
                            }
                        }
                    }
                    $data->evalcategoryaccordion[] = [
                        'parentid' => $parent->id,
                        'parentname' => format_string($parent->name),
                        'parentchecked' => $parentchecked,
                        'children' => $children,
                        'haschildren' => !empty($children),
                        'expanded' => $hasselected ? 'true' : 'false',
                        'showclass' => $hasselected ? 'show' : '',
                        'collapsedclass' => $hasselected ? '' : 'collapsed',
                        'idx' => $idx,
                    ];
                    $idx++;
                }
                $data->hascategoryaccordion = !empty($data->evalcategoryaccordion);

                // Build module type filter options.
                $allmods = $DB->get_records_sql(
                    "SELECT DISTINCT m.name FROM {modules} m
                     JOIN {course_modules} cm ON cm.module = m.id AND cm.deletioninprogress = 0
                     ORDER BY m.name"
                );
                $data->evalconmodtypefilter = [];
                $data->evalconmodtypefilter[] = [
                    'value' => '',
                    'label' => get_string('alltypes', 'report_lmsace_reports'),
                    'selected' => empty($data->evalconmodtype) ? 'selected' : '',
                ];
                foreach ($allmods as $mod) {
                    $data->evalconmodtypefilter[] = [
                        'value' => $mod->name,
                        'label' => get_string('modulename', $mod->name),
                        'selected' => ($data->evalconmodtype === $mod->name) ? 'selected' : '',
                    ];
                }

                // Render the consolidated table directly (bypasses widget visibility settings).
                require_once($CFG->dirroot . '/report/lmsace_reports/classes/local/table/evaluationconsolidated_table.php');
                $contable = new \report_lmsace_reports\local\table\evaluationconsolidated_table(
                    'evaluation-consolidated-table', $data->evalmonth, $data->evalcategory, $data->evalconmodtype
                );
                ob_start();
                $contable->out(20, true);
                $data->consolidatedtablehtml = ob_get_contents();
                ob_end_clean();

                $data->showconsolidated = true;
            }
        }

        // Moodle 5.0 uses Bootstrap 5, so we need to set the correct data attribute for tab toggling.
        $data->datatabtoggle = $CFG->branch >= 500 ? 'data-bs-toggle=tab' : 'data-toggle=tab';
        $data->datatargetsitereport = $CFG->branch >= 500 ? 'data-bs-target=#site-report' : 'href=#site-report';
        $data->datatargetcoursereport = $CFG->branch >= 500 ? 'data-bs-target=#course-report' : 'href=#course-report';
        $data->datatargetuserreport = $CFG->branch >= 500 ? 'data-bs-target=#user-report' : 'href=#user-report';
        $data->datatargetteacherreport = $CFG->branch >= 500
            ? 'data-bs-target=#teacher-report' : 'href=#teacher-report';
        $data->datatargetevaluationreport = $CFG->branch >= 500
            ? 'data-bs-target=#evaluation-report' : 'href=#evaluation-report';
        $data->datatoggle = $CFG->branch >= 500 ? 'data-bs-toggle=dropdown' : 'data-toggle=dropdown';
        $data->showsitereport = !isset($data->showcoursereport) && !isset($data->showuserreport)
            && !isset($data->showteacherreport) && !isset($data->showevaluationreport);

        return $data;
    }
}
