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
 * Get Reports widgets.
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports;

use stdClass;
use core_course_list_element;
use core_plugin_manager;

/**
 * Define widgets.
 */
class report_helper {

    /**
     * Get courses.
     * @param int $selectid
     * @param bool $getcourse
     * @return array list of courses.
     */
    public static function get_course($selectid = 0, $getcourse = false) {
        $data = [];
        $courses = \core_course_category::get(0)->get_courses(['recursive' => true]);
        if ($getcourse) {
            return $courses;
        }
        if (!empty($courses)) {
            foreach ($courses as $course) {
                $list['id'] = $course->id;
                $list['coursename'] = $course->get_formatted_name();
                if ($selectid == $course->id) {
                    $list['selected'] = "selected";
                } else {
                    $list['selected'] = '';
                }
                $data[] = $list;
            }
        }
        return $data;
    }

    /**
     * Get courses.
     * @param int $courseids
     * @param array $currentcourse
     * @return array list of courses.
     */
    public static function generate_course_chooser_data($courseids, $currentcourse) {
        global $DB;
        $data = [];
        if (!empty($courseids)) {
            // Batch load all courses in a single query instead of N+1.
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $courses = $DB->get_records_select('course', "id $insql", $params);
            foreach ($courseids as $courseid) {
                if (!isset($courses[$courseid])) {
                    continue;
                }
                $course = new core_course_list_element($courses[$courseid]);
                $list['id'] = $course->id;
                $list['coursename'] = $course->get_formatted_name();
                $list['selected'] = ($currentcourse == $course->id) ? "selected" : '';
                $data[] = $list;
            }
        }
        return $data;
    }

    /**
     * Get users.
     * @param int $selectid
     * @param bool $getusers
     */
    public static function get_users($selectid = 0, $getusers = false) {
        global $OUTPUT;
        $data = [];
        $context = \context_system::instance();
        $users = get_users_listing();
        if (!empty($users)) {
            foreach ($users as $user) {

                $list['id'] = $user->id;
                $report['fullname'] = $user->firstname.$user->lastname;
                $report['email'] = $user->email;
                $list['usertext'] = $OUTPUT->render_from_template('report_lmsace_reports/lmsace_reports_select_user', $report);
                if ($selectid == $user->id) {
                    $list['selected'] = "selected";
                } else {
                    $list['selected'] = '';
                }
                $data[] = $list;
            }
        }

        return $data;
    }

    /**
     * Get default display course in course blocks.
     * @return int courseid
     */
    public static function get_first_course() {
        global $DB;
        $sql = "SELECT * FROM {course} WHERE category != 0 ORDER BY id";
        $courses = $DB->get_records_sql($sql, null, 0, 1);
        $course = reset($courses);
        return !empty($course) ? $course->id : 0;
    }

    /**
     * Get default display user in user blocks.
     * @return int courseid
     */
    public static function get_first_user() {
        global $DB;
        $sql = "SELECT * FROM {user} WHERE id > 2 AND deleted = 0 ORDER BY id";
        $users = $DB->get_records_sql($sql, null, 0, 1);
        $user = reset($users);
        return !empty($user) ? $user->id : 0;
    }

    /**
     * Get the site admin
     * @return object user object
     */
    public static function site_admin_user() {
        global $CFG;
        $adminuser = '';
        $siteadmins = explode(',', $CFG->siteadmins);
        if (!empty($siteadmins) && isset($siteadmins[0])) {
            $adminuser = \core_user::get_user($siteadmins[0]);
        }
        return $adminuser;
    }

    /**
     * get list of activities info details
     * @return array activities info.
     *
     */
    public static function activities_info_details() {
        $adminuser = self::site_admin_user();
        $contentitemservice = \core_course\local\factory\content_item_service_factory::get_content_item_service();
        $meta = $contentitemservice->get_all_content_items($adminuser);
        $activityinfos = [];
        foreach ($meta as $mod) {
            $activityinfos[$mod->name] = $mod;
        }
        return $activityinfos;
    }

    /**
     * Load js data
     * @param array $reports
     */
    public static function load_js_data($reports) {
        global $PAGE;
        if (!empty($reports)) {
            foreach ($reports as $var => $data) {
                $PAGE->requires->data_for_js($var, $data);
            }
        }
    }

    /**
     * Get site visits.
     * @param string $filter
     * @param bool $details
     * @param int $userid
     * @return array list of visits reports
     *
     */
    public static function get_site_visits($filter = '', $details = false, $userid = 0) {
        global $DB;

        if (!$filter) {
            $filter = 'week';
        }

        if ($filter == 'month') {
            $labelcount = 30;
            $groupby = 604800;
        } else if ($filter == 'year') {
            $labelcount = 365;
            $groupby = 86400 * 30;
        } else {
            $labelcount = 7;
            $groupby = 86400;
        }

        if ($filter == 'today') {
            $timestart = strtotime('today');
        } else {
            $timestart = strtotime('-1 ' . $filter);
        }
        $timeend = time();
        $usersql = '';
        $userparams = [];
        if ($userid) {
            $usersql = 'AND ls.userid = :userid';
            $userparams = ['userid' => $userid];
        }

        $sql = "SELECT FLOOR(ls.timecreated / 86400) AS userdate, count(ls.id) AS visits
        FROM {logstore_standard_log} ls
        WHERE ls.action = 'loggedin' AND ls.target = 'user' AND ls.userid > 2 $usersql
        AND ls.timecreated BETWEEN :timestart AND :timeend
        GROUP BY FLOOR(ls.timecreated / 86400)";

        $params = [
            'timestart' => $timestart,
            'timeend' => $timeend,
        ];
        $params = array_merge($params, $userparams);
        $records = $DB->get_records_sql($sql, $params);

        if ($details) {
            return $records;
        }

        $labels = [];
        $values = [];

        for ($i = 0; $i < $labelcount; $i++) {
            $time = time() - $i * 24 * 60 * 60;
            $values[floor($time / (24 * 60 * 60))] = 0;
            $labels[] = date("d M y", $time);
        }
        if (!empty($records)) {
            foreach (array_keys($values) as $key) {
                if (!isset($records[$key])) {
                    continue;
                }
                $values[$key] = $records[$key]->visits;
            }
        }
        $data['label'] = array_reverse($labels);
        $data['value'] = array_reverse($values);
        return $data;
    }

    /**
     * spareate the chart values using chart
     * @param array $reports
     * @return array chart labels,values
     */
    public static function chart_values($reports) {
        $data = [];

        if (!empty($reports)) {
            foreach ($reports as $key => $value) {
                if ($key == 'id') {
                    continue;
                }
                $data['label'][] = get_string($key, 'report_lmsace_reports');
                $data['value'][] = !empty($value) ? $value : 0;
            }
        }
        // Create flag for the empty data.
        $data['noresult'] = empty(array_filter((array) $reports));

        return $data;
    }

    /**
     * Get chart reports values
     * @param string $filter
     * @param string $classname
     * @param int $relatedid
     * @param string $function
     */
    public static function ajax_chart_reports($filter, $classname, $relatedid = 0, $function = '') {
        global $CFG;

        // Validate classname against registered widgets whitelist.
        $widgets = self::get_default_widgets();
        $allowedclasses = array_column($widgets, 'instance');
        if (!in_array($classname, $allowedclasses)) {
            throw new \moodle_exception('invalidwidgetclass', 'report_lmsace_reports');
        }

        $classfile = $CFG->dirroot . '/report/lmsace_reports/classes/local/widgets/' . $classname . '.php';
        if (!file_exists($classfile)) {
            debugging("Class file doesn't exist " . $classname);
        }
        require_once($classfile);
        $classname = '\\report_lmsace_reports\\local\\widgets\\' . $classname;
        if ($relatedid) {
            $widgetinstance = new $classname($relatedid, $filter);
        } else {
            $widgetinstance = new $classname($filter);
        }

        if (empty($function)) {
            return $widgetinstance->get_data();
        } else {
            return $widgetinstance->{$function}();
        }
    }

    /**
     * get duration info
     * @param string $filter
     * @param string $default
     * @return array duration.
     */
    public static function get_duration_info($filter, $default = 'all') {
        $duration = [];
        if (!$filter) {
            $filter = $default;
        }
        if ($filter != 'all') {
            if ($filter == 'today') {
                $duration['timestart'] = strtotime('today');
            } else {
                $duration['timestart'] = strtotime('-1 ' . $filter);
            }
            $duration['timeend'] = time();
        }
        return $duration;
    }

    /**
     * get random colors
     * @param int $count
     * @return array list of colors
     */
    public static function get_random_back_color($count) {
        // Fixed accessible color palette matching the JS-side palette in main.js.
        $palette = [
            'rgb(27, 58, 95)',      // Dark blue.
            'rgb(48, 190, 207)',    // Light blue.
            'rgb(239, 77, 97)',     // Rose.
            'rgb(251, 178, 24)',    // Dark yellow.
            'rgb(165, 165, 165)',   // Gray.
            'rgb(12, 203, 150)',    // Green.
            'rgb(153, 102, 255)',   // Purple.
            'rgb(57, 155, 226)',    // Blue.
        ];

        $colors = [];
        $palettesize = count($palette);
        if (!empty($count)) {
            for ($i = 0; $i <= $count; $i++) {
                $colors[] = $palette[$i % $palettesize];
            }
        }
        return $colors;
    }

    /**
     * Get course info
     * @param int $courseid
     * @return object course info object
     *
     */
    public static function get_course_info($courseid) {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid]);
        $course = new core_course_list_element($course);
        return $course;
    }

    /**
     * Get the last 12 months.
     */
    public static function get_current_last_12months() {

        $months = [];
        for ($i = 0; $i <= 12; $i++) {
            $record = [];
            $seconds = strtotime( date( 'Y-m-01' )." -$i months");
            $record['month'] = date("F Y", $seconds);
            $months[] = $record;
        }
        return $months;
    }

    /**
     * Get the report widgets.
     */
    public static function get_default_widgets() {

        $sort = 0;
        $widgets = [
            'sitevisits' => [
                'instance' => 'sitevisitswidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'siteusers' => [
                'instance' => 'siteuserswidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'overallsiteinfo' => [
                'instance' => 'overallsiteinfowidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'siteresourceofcourses' => [
                'instance' => 'siteresourceofcourseswidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'siteactiveusers' => [
                'instance' => 'siteactiveuserswidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'enrolcompletionmonth' => [
                'instance' => 'enrolcompletionmonthwidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'enrolmethodusers' => [
                'instance' => 'enrolmethoduserswidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'topcourseenrolment' => [
                'instance' => 'topcourseenrolmentwidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'topcoursecompletion' => [
                'instance' => 'topcoursecompletionwidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'stacksitereports' => [
                'instance' => 'stacksitereportswidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'cohortsinfo' => [
                'instance' => 'cohortsinfowidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'sitestateinfo' => [
                'instance' => 'sitestateinfowidget',
                'context' => 'site',
                'sort' => $sort++,
                'visible' => true,
            ],
            'stackcoursereports' => [
                'instance' => 'stackcoursereportswidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'coursemodulegrades' => [
                'instance' => 'coursemodulegradeswidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'courseenrolcompletion' => [
                'instance' => 'courseenrolcompletionwidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'courseactiveinactiveusers' => [
                'instance' => 'courseactiveinactiveuserswidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'courseresources' => [
                'instance' => 'courseresourceswidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'coursevisits' => [
                'instance' => 'coursevisitswidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'coursehighscore' => [
                'instance' => 'coursehighscorewidget',
                'context' => 'course',
                'sort' => $sort++,
                'visible' => true,
            ],
            'stackuserreports' => [
                'instance' => 'stackuserreportswidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'usermyactivities' => [
                'instance' => 'usermyactivitieswidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'usermyquizzes' => [
                'instance' => 'usermyquizzeswidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'usermyassignments' => [
                'instance' => 'usermyassignmentswidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'usergroupcohorts' => [
                'instance' => 'usergroupcohortswidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'userlogins' => [
                'instance' => 'userloginswidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'mostvisitcourse' => [
                'instance' => 'mostvisitcoursewidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'userscore' => [
                'instance' => 'userscorewidget',
                'context' => 'user',
                'sort' => $sort++,
                'visible' => true,
            ],
            'stackteacherreports' => [
                'instance' => 'stackteacherreportswidget',
                'context' => 'teacher',
                'sort' => $sort++,
                'visible' => true,
            ],
            'teachercoursessummary' => [
                'instance' => 'teachercoursessummarywidget',
                'context' => 'teacher',
                'sort' => $sort++,
                'visible' => true,
            ],
            'teacherstudentperformance' => [
                'instance' => 'teacherstudentperformancewidget',
                'context' => 'teacher',
                'sort' => $sort++,
                'visible' => true,
            ],
            'teachergradingoverview' => [
                'instance' => 'teachergradingoverviewwidget',
                'context' => 'teacher',
                'sort' => $sort++,
                'visible' => true,
            ],
            'teacheractivitycompletion' => [
                'instance' => 'teacheractivitycompletionwidget',
                'context' => 'teacher',
                'sort' => $sort++,
                'visible' => true,
            ],
            // Evaluation widgets.
            'evaluationconsolidated' => [
                'instance' => 'evaluationconsolidatedwidget',
                'context' => 'evaluation',
                'sort' => $sort++,
                'visible' => true,
            ],
            'stackevaluationreports' => [
                'instance' => 'stackevaluationreportswidget',
                'context' => 'evaluation',
                'sort' => $sort++,
                'visible' => true,
            ],
            'evaluationcourses' => [
                'instance' => 'evaluationcourseswidget',
                'context' => 'evaluation',
                'sort' => $sort++,
                'visible' => true,
            ],
            'evaluationactivities' => [
                'instance' => 'evaluationactivitieswidget',
                'context' => 'evaluation',
                'sort' => $sort++,
                'visible' => true,
            ],
            'evaluationactivitydetail' => [
                'instance' => 'evaluationactivitydetailwidget',
                'context' => 'evaluation',
                'sort' => $sort++,
                'visible' => true,
            ],
        ];
        return $widgets;
    }

    /**
     * Intialize the reports widgets.
     */
    public static function load_widgets() {

        $widgets = self::get_default_widgets();
        $widgetlist = [];
        foreach ($widgets as $report => $widget) {
            $widgetdata = new stdClass();
            $widgetdata->widget = $report;
            $widgetdata->instance = $widget['instance'];
            $widgetdata->context = $widget['context'];
            $widgetdata->sort = $widget['sort'];
            $widgetdata->visible = $widget['visible'];
            $widgetdata->timecreated = time();
            $widgetlist[] = $widgetdata;
        }

        return $widgetlist;
    }

    /**
     * Get the user overall courseinfo.
     * @param int $userid
     * @return int
     */
    public static function get_user_overall_courseinfo($userid) {
        $progress = 0;
        $target = 0;
        $courses = enrol_get_users_courses($userid, true, '*');
        foreach ($courses as $course) {
            if (completion_can_view_data($userid, $course)) {
                $progress += \core_completion\progress::get_course_progress_percentage($course, $userid);
                $target ++;
            }
        }
        if ($target == 0) {
            return 0;
        }
        return round($progress / ($target * 100) * 100);
    }

    /**
     * Get the most activities in course.
     *
     * @param int $courseid
     * @return int
     */
    public static function get_most_activities_in_course($courseid) {
        global $DB;
        $sql = "SELECT cm.module, count(cm.id) AS count, m.name  FROM {course_modules} cm
            LEFT JOIN {modules} m ON  m.id = cm.module
            WHERE cm.course = :courseid AND cm.deletioninprogress = 0
            GROUP BY cm.module, m.name ORDER BY COUNT(cm.id) DESC";

        $data = $DB->get_records_sql($sql, ['courseid' => $courseid], 0, 3);
        return $data;
    }

    /**
     * Check the current user has teacher role.
     *
     * @return void
     */
    public static function is_currentuser_has_teacherrole() {
        global $DB, $USER;

        $roles = get_roles_with_capability("report/lmsace_reports:viewcoursereports");
        $roleids = array_keys($roles);

        $params = ['userid' => $USER->id, 'contextlevel' => CONTEXT_COURSE];
        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

        $params = array_merge($params, $roleparams);
        $sql = "SELECT * FROM {role_assignments} WHERE userid = :userid AND roleid $rolesql";
        if ($DB->record_exists_sql($sql, $params)) {
            return true;
        }
        return false;
    }

    /**
     * Get the courses form the  current user has teacher role.
     *
     * @param int $userid Current user ID
     * @return array
     */
    public static function get_teacher_courses($userid) {
        global $DB;

        $roles = get_roles_with_capability("report/lmsace_reports:viewcoursereports");
        $roleids = array_keys($roles);

        $params = ['userid' => $userid, 'contextlevel' => CONTEXT_COURSE];
        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params = array_merge($params, $roleparams);
        $sql = "
            SELECT c.id, c.instanceid as courseid
            FROM {role_assignments} ra
            JOIN {context} c ON c.id = ra.contextid
            WHERE c.contextlevel = :contextlevel AND ra.userid = :userid AND ra.roleid $rolesql";
        $record = $DB->get_records_sql($sql, $params);

        return $record;
    }

    /**
     * Get teacher's courses filtered by month and category.
     *
     * @param int $teacherid Teacher user ID.
     * @param int $month Unix timestamp of month start (0 = all months).
     * @param string $categoryids Comma-separated category IDs (empty = all categories).
     * @return array Filtered course IDs.
     */
    public static function get_teacher_courses_filtered($teacherid, $month = 0, $categoryids = '') {
        global $DB;

        if ($teacherid == 0) {
            // All teachers mode: get courses for all valid teachers.
            $teachers = self::get_teachers();
            $courseids = [];
            foreach ($teachers as $teacher) {
                $tcourses = self::get_teacher_courses($teacher['id']);
                foreach ($tcourses as $tc) {
                    $courseids[] = $tc->courseid;
                }
            }
            $courseids = array_unique($courseids);
        } else {
            $courses = self::get_teacher_courses($teacherid);
            $courseids = array_column((array) $courses, 'courseid');
        }
        if (empty($courseids)) {
            return [];
        }

        // Filter by category (expand to include descendants via path matching).
        if (!empty($categoryids)) {
            $selectedids = array_map('intval', explode(',', $categoryids));
            $selectedids = array_filter($selectedids);
            if (!empty($selectedids)) {
                $allcats = $DB->get_records('course_categories', null, '', 'id, path');
                $matchids = [];
                foreach ($allcats as $cat) {
                    foreach ($selectedids as $sid) {
                        if (preg_match('#/' . $sid . '(/|$)#', $cat->path)) {
                            $matchids[$cat->id] = true;
                            break;
                        }
                    }
                }
                if (!empty($matchids)) {
                    // Filter courseids to only those in matching categories.
                    list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
                    list($catsql, $catparams) = $DB->get_in_or_equal(array_keys($matchids), SQL_PARAMS_NAMED, 'cat');
                    $filtered = $DB->get_fieldset_sql(
                        "SELECT id FROM {course} WHERE id $insql AND category $catsql",
                        array_merge($inparams, $catparams)
                    );
                    $courseids = $filtered;
                } else {
                    $courseids = [];
                }
            }
        }

        // Filter by month (courses active during the selected month).
        if (!empty($month) && !empty($courseids)) {
            $monthend = strtotime('+1 month', $month);
            list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
            $courseids = $DB->get_fieldset_sql(
                "SELECT id FROM {course}
                 WHERE id $insql AND startdate < :monthend AND (enddate = 0 OR enddate >= :monthstart)",
                array_merge($inparams, ['monthstart' => $month, 'monthend' => $monthend])
            );
        }

        return $courseids;
    }

    /**
     * Build a hierarchical category tree for a teacher's courses.
     *
     * Returns only categories where the teacher has courses, organized
     * as parent → children with course lists.
     *
     * @param int $teacherid Teacher user ID.
     * @param string $selectedcategory Comma-separated selected category IDs.
     * @return array Hierarchical category tree for template rendering.
     */
    public static function get_teacher_category_tree($teacherid, $selectedcategory = '') {
        global $DB;

        $courses = self::get_teacher_courses($teacherid);
        $courseids = array_column((array) $courses, 'courseid');
        if (empty($courseids)) {
            return [];
        }

        // Load courses with their category info.
        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $coursesdata = $DB->get_records_sql(
            "SELECT c.id, c.fullname, c.category, cc.name as categoryname, cc.path as categorypath, cc.parent
             FROM {course} c
             JOIN {course_categories} cc ON cc.id = c.category
             WHERE c.id $insql
             ORDER BY cc.path, c.fullname",
            $inparams
        );

        // Collect all category IDs involved (including ancestors).
        $catids = [];
        foreach ($coursesdata as $course) {
            $pathparts = array_filter(explode('/', $course->categorypath));
            foreach ($pathparts as $catid) {
                $catids[(int) $catid] = true;
            }
        }
        if (empty($catids)) {
            return [];
        }

        // Load all involved categories.
        list($catsql, $catparams) = $DB->get_in_or_equal(array_keys($catids), SQL_PARAMS_NAMED);
        $allcats = $DB->get_records_sql(
            "SELECT id, name, parent, path, depth FROM {course_categories} WHERE id $catsql ORDER BY path",
            $catparams
        );

        // Parse selected categories.
        $selectedids = [];
        if (!empty($selectedcategory)) {
            $selectedids = array_flip(array_map('intval', explode(',', $selectedcategory)));
        }

        // Build tree: find top-level categories and nest children.
        $tree = [];
        $catmap = [];
        foreach ($allcats as $cat) {
            $catmap[$cat->id] = $cat;
        }

        // Group courses by direct category.
        $coursesbycategory = [];
        foreach ($coursesdata as $course) {
            $coursesbycategory[$course->category][] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
            ];
        }

        // Find root categories (parent not in our set).
        foreach ($allcats as $cat) {
            if (!isset($catmap[$cat->parent])) {
                // This is a root for our tree.
                $tree[] = self::build_category_node($cat, $catmap, $coursesbycategory, $selectedids);
            }
        }

        return $tree;
    }

    /**
     * Recursively build a category node for the tree.
     *
     * @param object $cat Category record.
     * @param array $catmap All categories indexed by ID.
     * @param array $coursesbycategory Courses grouped by category ID.
     * @param array $selectedids Selected category IDs (as keys).
     * @return array Category node with children and courses.
     */
    private static function build_category_node($cat, $catmap, $coursesbycategory, $selectedids) {
        $courselist = $coursesbycategory[$cat->id] ?? [];
        $node = [
            'id' => $cat->id,
            'name' => format_string($cat->name),
            'selected' => isset($selectedids[$cat->id]),
            'courses' => $courselist,
            'hascourses' => !empty($courselist),
            'coursecount' => count($courselist),
            'children' => [],
            'haschildren' => false,
            'expanded' => true,
        ];

        // Find direct children in our category map.
        foreach ($catmap as $childcat) {
            if ($childcat->parent == $cat->id) {
                $childnode = self::build_category_node($childcat, $catmap, $coursesbycategory, $selectedids);
                $node['children'][] = $childnode;
                // Expand parent if any child is selected or expanded.
                if ($childnode['selected'] || $childnode['expanded']) {
                    $node['expanded'] = true;
                }
            }
        }
        $node['haschildren'] = !empty($node['children']);
        if ($node['selected']) {
            $node['expanded'] = true;
        }

        return $node;
    }

    /**
     * Get the number of additional plugins install in the plugin manager.
     *
     * @return int $numextension
     */
    public static function get_addtional_plugins() {
        $pluginman = core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugins();
        $numextension = 0;
        foreach ($plugininfo as $type => $plugins) {
            foreach ($plugins as $name => $plugin) {
                if (!$plugin->is_standard() && $plugin->get_status() !== core_plugin_manager::PLUGIN_STATUS_MISSING) {
                    $numextension++;
                }
            }
        }
        return $numextension;
    }

    /**
     * Get the report lmsace plugin folder size in the given path.
     *
     * @param string $path Folder path.
     * @return array
     */
    public static function get_foldersize($path) {
        $totalsize = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalsize += $file->getSize();
            }
        }
        return self::get_formatsize($totalsize);
    }

    /**
     * Get the moodle spaces.
     *
     * @return array
     */
    public static function get_moodle_spaces() {
        global $CFG;

        $cache = \cache::make('report_lmsace_reports', 'reportwidgets');
        // Get total size and free space of the folder.
        if (!$cache->get('sizemoodlesrc')) {
            $cache->set('sizemoodlesrc', self::get_foldersize($CFG->dirroot));
        }

        $moodlesrc = $cache->get('sizemoodlesrc');

        if (!$cache->get('sizemoodledata')) {
            $cache->set('sizemoodledata', self::get_foldersize($CFG->dataroot));
        }

        $moodledata = $cache->get('sizemoodledata');
        return ['moodlesrc' => $moodlesrc, 'moodledata' => $moodledata];
    }

    /**
     * Get the plugin format size
     *
     * @param string $size Size of the format.
     * @return string
     */
    public static function get_formatsize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i] . ' ' . get_string('used', 'core');
    }

    /**
     * Get role IDs for valid teacher/manager roles.
     *
     * Includes: manager, coursecreator, editingteacher, teacher.
     * @return array Role IDs.
     */
    public static function get_teacher_role_ids() {
        global $DB;

        // Get roles with the course reports capability (editingteacher, teacher).
        $caproles = get_roles_with_capability("report/lmsace_reports:viewcoursereports");
        $roleids = array_keys($caproles);

        // Also include manager and coursecreator archetypes.
        $archetypes = ['manager', 'coursecreator'];
        foreach ($archetypes as $archetype) {
            $archetyperoles = get_archetype_roles($archetype);
            foreach ($archetyperoles as $role) {
                $roleids[] = $role->id;
            }
        }

        return array_unique($roleids);
    }

    /**
     * Usernames to exclude from teacher lists.
     * @return array
     */
    private static function get_excluded_usernames() {
        return ['guest', 'frontpage', 'wsapiuser', 'asistentedeseleccion', 'userapi', 'user_api', 'apiuser'];
    }

    /**
     * Build SQL conditions to exclude system/service accounts from teacher queries.
     *
     * @return array [sql_fragment, params] for use in WHERE clause.
     */
    private static function get_excluded_users_sql() {
        global $DB;
        $excluded = self::get_excluded_usernames();
        list($excl_sql, $excl_params) = $DB->get_in_or_equal($excluded, SQL_PARAMS_NAMED, 'excl', false);
        // Also exclude by LIKE patterns for variations.
        $likeconditions = " AND u.username NOT LIKE :likeapi AND u.username NOT LIKE :likews";
        $excl_params['likeapi'] = '%api%';
        $excl_params['likews'] = '%wsuser%';
        return ["u.username $excl_sql $likeconditions", $excl_params];
    }

    /**
     * Get the first available teacher (for default selection).
     * @return int teacherid or 0
     */
    public static function get_first_teacher() {
        global $DB;
        $roleids = self::get_teacher_role_ids();
        if (empty($roleids)) {
            return 0;
        }
        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        list($excl_fragment, $excl_params) = self::get_excluded_users_sql();
        $sql = "SELECT DISTINCT ra.userid
            FROM {role_assignments} ra
            JOIN {context} c ON c.id = ra.contextid
            JOIN {user} u ON u.id = ra.userid AND u.deleted = 0
            WHERE c.contextlevel = :contextlevel AND ra.roleid $rolesql
            AND $excl_fragment
            ORDER BY ra.userid";
        $params = array_merge(['contextlevel' => CONTEXT_COURSE], $roleparams, $excl_params);
        $records = $DB->get_records_sql($sql, $params, 0, 1);
        $record = reset($records);
        return !empty($record) ? $record->userid : 0;
    }

    /**
     * Get quiz slot info (slot number, question name, max mark).
     *
     * @param int $quizid The quiz instance ID.
     * @return array Array of objects with slot, questionname, maxmark.
     */
    public static function get_quiz_slots($quizid) {
        global $DB;

        $sql = "SELECT qs.slot, qs.maxmark, q.name AS questionname
            FROM {quiz_slots} qs
            JOIN {question_references} qr ON qr.itemid = qs.id
                AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
            JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
            JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
            JOIN {question} q ON q.id = qv.questionid
            WHERE qs.quizid = :quizid
            ORDER BY qs.slot";

        $records = $DB->get_records_sql($sql, ['quizid' => $quizid]);

        // Deduplicate by slot (multiple versions may exist, keep highest questionid).
        $slots = [];
        foreach ($records as $rec) {
            if (!isset($slots[$rec->slot]) || $rec->slot > 0) {
                $slots[$rec->slot] = $rec;
            }
        }
        ksort($slots);
        return array_values($slots);
    }

    /**
     * Get quiz attempts with per-question marks for the best attempt per student.
     *
     * @param int $quizid The quiz instance ID.
     * @param int $courseid The course ID.
     * @return array Array with 'attempts' (per-student data) and 'quiz' (quiz record).
     */
    public static function get_quiz_attempts_with_questions($quizid, $courseid) {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        if (!$quiz) {
            return ['attempts' => [], 'quiz' => null];
        }

        // Get the best finished attempt per student (highest sumgrades, then highest id as tiebreaker).
        $sql = "SELECT qa.id, qa.userid, qa.uniqueid, qa.sumgrades
            FROM {quiz_attempts} qa
            WHERE qa.quiz = :quizid AND qa.state = 'finished'
            AND qa.id = (
                SELECT MAX(qa2.id) FROM {quiz_attempts} qa2
                WHERE qa2.quiz = qa.quiz AND qa2.userid = qa.userid
                AND qa2.state = 'finished'
                AND qa2.sumgrades = (
                    SELECT MAX(qa3.sumgrades) FROM {quiz_attempts} qa3
                    WHERE qa3.quiz = qa.quiz AND qa3.userid = qa.userid
                    AND qa3.state = 'finished'
                )
            )
            ORDER BY qa.userid";
        $attempts = $DB->get_records_sql($sql, ['quizid' => $quizid]);

        if (empty($attempts)) {
            return ['attempts' => [], 'quiz' => $quiz];
        }

        // Get user info for all attempt users.
        $userids = array_column((array) $attempts, 'userid');
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
        $users = $DB->get_records_select('user', "id $usersql", $userparams);

        // Get per-question marks for all attempts in bulk.
        $attemptids = array_keys($attempts);
        list($attsql, $attparams) = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'att');
        $sql = "SELECT " . $DB->sql_concat('qa.userid', "'-'", 'qatt.slot') . " AS id,
                qa.userid, qatt.slot, qatt.maxmark, qatt.id AS qattid,
                (SELECT qas.fraction FROM {question_attempt_steps} qas
                 WHERE qas.questionattemptid = qatt.id
                 AND qas.sequencenumber = (
                    SELECT MAX(qas2.sequencenumber)
                    FROM {question_attempt_steps} qas2
                    WHERE qas2.questionattemptid = qatt.id
                 )
                ) AS fraction
            FROM {quiz_attempts} qa
            JOIN {question_attempts} qatt ON qatt.questionusageid = qa.uniqueid
            WHERE qa.id $attsql
            ORDER BY qa.userid, qatt.slot";
        $questionmarks = $DB->get_records_sql($sql, $attparams);

        // Organize by userid => slot => mark.
        $markmap = [];
        foreach ($questionmarks as $qm) {
            if (!isset($markmap[$qm->userid])) {
                $markmap[$qm->userid] = [];
            }
            if ($qm->fraction !== null) {
                $markmap[$qm->userid][$qm->slot] = round($qm->maxmark * $qm->fraction, 2);
            } else {
                $markmap[$qm->userid][$qm->slot] = null;
            }
        }

        // Build result array.
        $result = [];
        foreach ($attempts as $att) {
            $user = isset($users[$att->userid]) ? $users[$att->userid] : null;
            if (!$user) {
                continue;
            }
            $row = new stdClass();
            $row->userid = $att->userid;
            $row->fullname = fullname($user);
            $row->email = $user->email;
            $row->idnumber = $user->idnumber ?? '';
            $row->sumgrades = $att->sumgrades;
            $row->grade = ($quiz->grade > 0 && $quiz->sumgrades > 0)
                ? round($att->sumgrades / $quiz->sumgrades * $quiz->grade, 2) : 0;
            $row->questionmarks = isset($markmap[$att->userid]) ? $markmap[$att->userid] : [];
            $result[] = $row;
        }

        return ['attempts' => $result, 'quiz' => $quiz];
    }

    /**
     * Get list of teachers for the dropdown selector.
     *
     * Only includes users with roles: manager, coursecreator, editingteacher, teacher.
     * Excludes: guest, frontpage, wsapiuser, asistentedeseleccion.
     *
     * @param int $selectid The currently selected teacher ID.
     * @return array
     */
    public static function get_teachers($selectid = 0) {
        global $DB;
        $roleids = self::get_teacher_role_ids();
        if (empty($roleids)) {
            return [];
        }
        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        list($excl_fragment, $excl_params) = self::get_excluded_users_sql();
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email,
                u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
            FROM {role_assignments} ra
            JOIN {context} c ON c.id = ra.contextid
            JOIN {user} u ON u.id = ra.userid AND u.deleted = 0
            WHERE c.contextlevel = :contextlevel AND ra.roleid $rolesql
            AND $excl_fragment
            ORDER BY u.lastname, u.firstname";
        $params = array_merge(['contextlevel' => CONTEXT_COURSE], $roleparams, $excl_params);
        $users = $DB->get_records_sql($sql, $params);
        $data = [];
        foreach ($users as $user) {
            $list = [];
            $list['id'] = $user->id;
            $list['teachername'] = fullname($user);
            $list['selected'] = ($selectid == $user->id) ? 'selected' : '';
            $data[] = $list;
        }
        return $data;
    }

    /**
     * Get total enrolled students across all of a teacher's courses.
     * @param int $teacherid
     * @return int
     */
    public static function get_teacher_total_students($teacherid) {
        global $DB;
        $courses = self::get_teacher_courses($teacherid);
        if (empty($courses)) {
            return 0;
        }
        $courseids = array_column((array) $courses, 'courseid');
        if (empty($courseids)) {
            return 0;
        }
        $studentroles = \report_lmsace_reports\widgets::get_student_roles();
        if (empty($studentroles)) {
            return 0;
        }
        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        list($rolesql, $roleparams) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED, 'role');
        $sql = "SELECT COUNT(DISTINCT ue.userid)
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = ue.userid
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :ctxlevel
                AND ctx.instanceid = e.courseid
            WHERE e.courseid $coursesql AND ra.roleid $rolesql
                AND ue.status = 0 AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)";
        $params = array_merge(
            $courseparams, $roleparams,
            ['ctxlevel' => CONTEXT_COURSE, 'now1' => time(), 'now2' => time()]
        );
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Download teacher courses report as Excel or CSV.
     *
     * @param int $teacherid Teacher user ID.
     * @param int $month Month filter timestamp.
     * @param string $categoryids Comma-separated category IDs.
     * @param string $format 'excel' or 'csv'.
     */
    public static function download_teacher_report($teacherid, $month, $categoryids, $format) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $teacherid]);
        $teachername = $user ? fullname($user) : 'teacher';
        $filename = clean_filename('teacher_report_' . $teachername . '_' . date('Y-m-d'));

        $courseids = self::get_teacher_courses_filtered($teacherid, $month, $categoryids);

        // Build data rows.
        $rows = [];
        $totalenrolled = 0;
        $totalcompleted = 0;
        $totalactivities = 0;
        $gradesum = 0;
        $gradecount = 0;

        foreach ($courseids as $courseid) {
            if (!$DB->record_exists('course', ['id' => $courseid])) {
                continue;
            }
            $course = get_course($courseid);
            $cat = $DB->get_record('course_categories', ['id' => $course->category], 'name');
            $enrolled = \report_lmsace_reports\widgets::get_course_progress_status($courseid, true);
            $completed = \report_lmsace_reports\widgets::get_course_completion_users($courseid, [], true);
            $rate = $enrolled > 0 ? round(($completed / $enrolled) * 100, 1) : 0;

            $sql = "SELECT AVG(gg.finalgrade / gi.grademax * 10) as avggrade
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gi.courseid = :courseid AND gi.itemtype = 'course'
                    AND gg.finalgrade IS NOT NULL AND gi.grademax > 0";
            $graderesult = $DB->get_record_sql($sql, ['courseid' => $courseid]);
            $avggrade = ($graderesult && $graderesult->avggrade !== null) ? round($graderesult->avggrade, 1) : 0;

            $activitiescount = $DB->count_records('course_modules', [
                'course' => $courseid, 'deletioninprogress' => 0,
            ]);

            $rows[] = [
                format_string($course->fullname),
                $cat ? format_string($cat->name) : '',
                $enrolled,
                $rate . '%',
                $avggrade . '/10',
                $activitiescount,
            ];

            $totalenrolled += $enrolled;
            $totalcompleted += $completed;
            $totalactivities += $activitiescount;
            if ($avggrade > 0) {
                $gradesum += $avggrade;
                $gradecount++;
            }
        }

        // Totals row.
        $totalrate = $totalenrolled > 0 ? round(($totalcompleted / $totalenrolled) * 100, 1) : 0;
        $totalavg = $gradecount > 0 ? round($gradesum / $gradecount, 1) : 0;
        $rows[] = [
            get_string('total'),
            '',
            $totalenrolled,
            $totalrate . '%',
            $totalavg . '/10',
            $totalactivities,
        ];

        $headers = [
            get_string('course'),
            get_string('category', 'report_lmsace_reports'),
            get_string('enrolled', 'report_lmsace_reports'),
            get_string('completionrate', 'report_lmsace_reports'),
            get_string('averagegrade', 'report_lmsace_reports'),
            get_string('activities', 'report_lmsace_reports'),
        ];

        if ($format === 'excel') {
            self::download_excel($filename, $headers, $rows, $teachername);
        } else {
            self::download_csv($filename, $headers, $rows);
        }
    }

    /**
     * Generate Excel download.
     *
     * @param string $filename File name without extension.
     * @param array $headers Column headers.
     * @param array $rows Data rows.
     * @param string $teachername Teacher name for the sheet title.
     */
    private static function download_excel($filename, $headers, $rows, $teachername = '') {
        global $CFG;
        require_once($CFG->libdir . '/excellib.class.php');

        $workbook = new \MoodleExcelWorkbook($filename);
        $sheet = $workbook->add_worksheet($teachername ?: 'Report');

        // Header format.
        $headerformat = $workbook->add_format(['bold' => 1, 'bg_color' => '#1b3a5f', 'color' => 'white']);

        // Write headers.
        foreach ($headers as $col => $header) {
            $sheet->write_string(0, $col, $header, $headerformat);
        }

        // Write data.
        $rownum = 1;
        foreach ($rows as $row) {
            foreach ($row as $col => $value) {
                if (is_numeric($value)) {
                    $sheet->write_number($rownum, $col, $value);
                } else {
                    $sheet->write_string($rownum, $col, $value);
                }
            }
            $rownum++;
        }

        $workbook->close();
    }

    /**
     * Generate CSV download.
     *
     * @param string $filename File name without extension.
     * @param array $headers Column headers.
     * @param array $rows Data rows.
     */
    private static function download_csv($filename, $headers, $rows) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $csvexport = new \csv_export_writer('comma');
        $csvexport->set_filename($filename);
        $csvexport->add_data($headers);
        foreach ($rows as $row) {
            $csvexport->add_data($row);
        }
        $csvexport->download_file();
    }
}
