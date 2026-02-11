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
 * Get report widgets
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\output;

/**
 * Define widget reports.
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_widgets {

    /**
     * @var array $instance
     */
    private $instance;

    /**
     * Constructor to get report widgets
     * @param array $widgets Widgets
     * @param object $output
     */
    public function __construct($widgets, $output) {
        global $CFG, $DB;

        $visiblesitewidgets = explode(",", get_config('reports_lmsace_reports', 'visiblesitereports'));
        $visiblecoursewidgets = explode(",", get_config('reports_lmsace_reports', 'visiblecoursereports'));
        $visibleuserwidgets = explode(",", get_config('reports_lmsace_reports', 'visibleuserreports'));
        $visibleteacherwidgets = explode(",", get_config('reports_lmsace_reports', 'visibleteacherreports'));
        $visibleevaluationwidgets = explode(",", get_config('reports_lmsace_reports', 'visibleevaluationreports'));
        $visiblewidgets = array_merge($visiblesitewidgets, $visiblecoursewidgets, $visibleuserwidgets,
            $visibleteacherwidgets, $visibleevaluationwidgets);

        if (empty(array_filter($visiblesitewidgets))) {
            $this->instance['nositereports'] = true;
        }

        if (empty(array_filter($visiblecoursewidgets))) {
            $this->instance['nocoursereports'] = true;
        }

        if (empty(array_filter($visibleuserwidgets))) {
            $this->instance['nouserreports'] = true;
        } else if (is_siteadmin($output->useraction)) {
            $this->instance['enableuserblock'] = false;
            $this->instance['isforadmin'] = true;
        }

        if (empty(array_filter($visibleteacherwidgets))) {
            $this->instance['noteacherreports'] = true;
        }

        if (empty(array_filter($visibleevaluationwidgets))) {
            $this->instance['noevaluationreports'] = true;
        }

        foreach ($widgets as $widget) {
            // Check the widget instance visible or not.
            if (!$widget->visible || !in_array($widget->widget, $visiblewidgets)) {
                continue;
            }
            // Check if class file exist.
            $classname = $widget->instance;
            $classfile = $CFG->dirroot . '/report/lmsace_reports/classes/local/widgets/' . $classname . '.php';
            if (!file_exists($classfile)) {
                debugging("Class file doesn't exist " . $classname);
            }
            require_once($classfile);
            $widgetinstance = null;
            $classname = '\\report_lmsace_reports\\local\\widgets\\' . $classname;
            if ($widget->context == "course") {
                if ($DB->record_exists('course', ['id' => $output->courseaction])) {
                    $widgetinstance = new $classname($output->courseaction);
                }
            } else if ($widget->context == "user") {
                if ($DB->record_exists('user', ['id' => $output->useraction])) {
                    $widgetinstance = new $classname($output->useraction);
                }
            } else if ($widget->context == "teacher") {
                $teacheraction = $output->teacheraction ?? 0;
                $teachercategory = $output->teachercategory ?? 0;
                if ($teacheraction && $DB->record_exists('user', ['id' => $teacheraction])) {
                    // Widgets with a $filter param as second argument need category as third.
                    $chartwids = ['teachergradingoverviewwidget', 'teacherstudentperformancewidget',
                        'teacheractivitycompletionwidget'];
                    if (in_array($widget->instance, $chartwids)) {
                        $widgetinstance = new $classname($teacheraction, '', $teachercategory);
                    } else {
                        $widgetinstance = new $classname($teacheraction, $teachercategory);
                    }
                }
            } else if ($widget->context == "evaluation") {
                $evalteacher = $output->evalteacher ?? 0;
                $evalcourse = $output->evalcourse ?? 0;
                $evalcmid = $output->evalcmid ?? 0;
                $evalcategory = $output->evalcategory ?? 0;
                $evalmodtype = $output->evalmodtype ?? '';
                $evalfrom = $output->evalfrom ?? 0;
                $evalto = $output->evalto ?? 0;

                if ($widget->instance == 'stackevaluationreportswidget') {
                    if ($evalteacher && $DB->record_exists('user', ['id' => $evalteacher])) {
                        $widgetinstance = new $classname($evalteacher, $evalcategory);
                    }
                } else if ($widget->instance == 'evaluationcourseswidget') {
                    if ($evalteacher && !$evalcourse && $DB->record_exists('user', ['id' => $evalteacher])) {
                        $widgetinstance = new $classname($evalteacher, $evalcategory);
                    }
                } else if ($widget->instance == 'evaluationactivitieswidget') {
                    if ($evalcourse && !$evalcmid && $DB->record_exists('course', ['id' => $evalcourse])) {
                        $widgetinstance = new $classname($evalcourse, $evalmodtype, $evalfrom, $evalto);
                    }
                } else if ($widget->instance == 'evaluationactivitydetailwidget') {
                    if ($evalcmid && $DB->record_exists('course_modules', ['id' => $evalcmid])) {
                        $widgetinstance = new $classname($evalcmid, $evalcourse);
                    }
                }
            } else {
                $widgetinstance = new $classname();
            }

            $data = $widgetinstance ? $widgetinstance->get_data() : [];
            // Data is empty not need to show the report.
            $this->instance[$widget->widget] = $data;
        }
    }

    /**
     * Get the widgets.
     */
    public function get_widgets() {
        return $this->instance;
    }
}
