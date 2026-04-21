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
 * Define for External lib.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/report/lmsace_reports/lib.php");

use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_external\external_function_parameters;
use core_external\external_api;
use report_lmsace_reports\report_helper;
use context_system;
use context_course;
use context_user;
use moodle_exception;
use context;

/**
 * External source for reports.
 */
class report_lmsace_reports_external extends external_api {

    /**
     * Validate context and capabilities based on chart type and related ID.
     *
     * @param string $chartid The chart identifier.
     * @param int $relatedid The related entity ID (course, user, or teacher).
     * @param int $contextid Optional explicit context ID (for table reports).
     * @return \context The validated context.
     */
    protected static function validate_report_access($chartid, $relatedid = 0, $contextid = 0) {
        global $USER;

        // If an explicit context ID is provided, use it directly.
        if ($contextid > 0) {
            $context = context::instance_by_id($contextid);
            self::validate_context($context);

            if ($context instanceof context_course) {
                require_capability("report/lmsace_reports:viewcoursereports", $context);
            } else if ($context instanceof context_user) {
                if ($USER->id == $context->instanceid) {
                    require_capability("report/lmsace_reports:viewuserreports", $context);
                } else {
                    require_capability("report/lmsace_reports:viewotheruserreports", $context);
                }
                self::check_admin_user_access($context->instanceid);
            } else {
                require_capability("report/lmsace_reports:viewsitereports", $context);
            }
            return $context;
        }

        // Default to system context.
        $context = context_system::instance();
        self::validate_context($context);

        if ($relatedid > 0) {
            if (strpos($chartid, 'teacher') !== false) {
                require_capability("report/lmsace_reports:viewteacherreports", $context);
            } else if (strpos($chartid, 'course') !== false) {
                $context = context_course::instance($relatedid);
                self::validate_context($context);
                require_capability("report/lmsace_reports:viewcoursereports", $context);
            } else {
                // User-related chart.
                $context = context_user::instance($relatedid);
                self::validate_context($context);
                if ($USER->id == $relatedid) {
                    require_capability("report/lmsace_reports:viewuserreports", $context);
                } else {
                    require_capability("report/lmsace_reports:viewotheruserreports", $context);
                }
                self::check_admin_user_access($relatedid);
            }
        } else {
            require_capability("report/lmsace_reports:viewsitereports", $context);
        }

        return $context;
    }

    /**
     * Prevent generating reports for admin users unless the requester has viewsitereports.
     *
     * @param int $userid The user ID to check.
     * @throws moodle_exception If the target is an admin and requester lacks viewsitereports.
     */
    protected static function check_admin_user_access($userid) {
        if (is_siteadmin($userid) && !has_capability('report/lmsace_reports:viewsitereports', context_system::instance())) {
            throw new moodle_exception('noadminreports', 'report_lmsace_reports');
        }
    }

    /**
     * Chart report parameters.
     */
    public static function get_chart_reports_parameters() {
        return new external_function_parameters(
            [
                'filter' => new external_value(PARAM_TEXT, 'Duration filter'),
                'chartid' => new external_value(PARAM_TEXT, 'chart id '),
                'relatedid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL),
            ],
        );
    }

    /**
     * Get chart report
     * @param string $filter
     * @param int $chartid
     * @param int $relatedid
     * @return array
     */
    public static function get_chart_reports($filter, $chartid, $relatedid = 0) {
        global $PAGE;

        $params = self::validate_parameters(self::get_chart_reports_parameters(),
            ['filter' => $filter, 'chartid' => $chartid, 'relatedid' => $relatedid]);

        $context = self::validate_report_access($params['chartid'], $params['relatedid']);
        $PAGE->set_context($context);

        $data = report_helper::ajax_chart_reports($params['filter'], $params['chartid'], $params['relatedid']);
        if (!isset($data['label'])) {
            $data['label'] = [];
        }
        if (!isset($data['value'])) {
            $data['value'] = [];
        }
        return $data;
    }

    /**
     * Return chart reports.
     */
    public static function get_chart_reports_returns() {

        return new external_single_structure(
            [
                'label' => new external_multiple_structure(
                    new external_value(PARAM_RAW, 'chart label')
                ),
                'value' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'chart value')
                ),
            ],
        );
    }

    /**
     * Get the site visits parameters.
     */
    public static function get_site_visits_parameters() {
        return new external_function_parameters(
            [
                'filter' => new external_value(PARAM_TEXT, 'Duration filter'),
                'chartid' => new external_value(PARAM_TEXT, 'chart id '),
                'relatedid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL),
            ],
        );
    }

    /**
     * Get site visits
     * @param string $filter
     * @param int $chartid
     * @param int $relatedid
     * @return array list of visits
     */
    public static function get_site_visits($filter, $chartid, $relatedid = 0) {
        $params = self::validate_parameters(self::get_site_visits_parameters(),
            ['filter' => $filter, 'chartid' => $chartid, 'relatedid' => $relatedid]);

        self::validate_report_access($params['chartid'], $params['relatedid']);

        $data = report_helper::ajax_chart_reports($params['filter'], $params['chartid'], $params['relatedid']);
        return $data;
    }

    /**
     * Return the site visits.
     */
    public static function get_site_visits_returns() {

        return new external_single_structure(
            [
                'label' => new external_multiple_structure(
                    new external_value(PARAM_RAW, 'chart label')
                ),
                'value' => new external_single_structure(
                    [
                        'sitevisits' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'chart value')
                        ),
                        'coursevisits' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'chart value')
                        ),
                        'modulevisits' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'chart value')
                        ),
                    ],
                ),
            ],
        );
    }

    /**
     * Get the activity progress paramters.
     */
    public static function get_activity_progress_reports_parameters() {

        return new external_function_parameters(
            [
                'filter' => new external_value(PARAM_TEXT, 'Duration filter'),
                'chartid' => new external_value(PARAM_TEXT, 'chart id '),
            ]
        );
    }

    /**
     * Get the activity progress reports.
     * @param string $filter
     * @param int $chartid
     * @return array $data.
     */
    public static function get_activity_progress_reports($filter, $chartid) {
        // Validate parameters.
        $params = self::validate_parameters(self::get_activity_progress_reports_parameters(),
            ['filter' => $filter, 'chartid' => $chartid]);

        // Validate site context and capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability("report/lmsace_reports:viewsitereports", $context);

        $data = report_helper::ajax_chart_reports($params['filter'], $params['chartid']);
        return $data;
    }

    /**
     * Return the activity progress.
     */
    public static function get_activity_progress_reports_returns() {

        return new external_single_structure(

            [
                'label' => new external_multiple_structure(
                    new external_value(PARAM_RAW, 'chart label')
                ),
                'completiondata' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'chart value')
                ),
                'enrolmentsdata' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'chart value')
                ),
                'completionlabel' => new external_value(PARAM_TEXT, 'Completions label'),
                'enrolmentlabel' => new external_value(PARAM_TEXT, 'Enrolments label'),
            ]
        );
    }

    /**
     * Get the show report table parameters.
     */
    public static function get_table_reports_parameters() {

        return new external_function_parameters(
            [
                'filter' => new external_value(PARAM_TEXT, 'Duration filter'),
                'chartid' => new external_value(PARAM_TEXT, 'chart id '),
                'contextid' => new external_value(PARAM_INT, 'context id'),
                'relatedid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * Get the show report table.
     * @param string $filter
     * @param int $chartid
     * @param int $contextid
     * @param int $relatedid
     */
    public static function get_table_reports($filter, $chartid, $contextid, $relatedid = 0) {
        global $PAGE;

        $params = self::validate_parameters(self::get_table_reports_parameters(),
            ['filter' => $filter, 'chartid' => $chartid, 'contextid' => $contextid, 'relatedid' => $relatedid]);

        $context = self::validate_report_access($params['chartid'], $params['relatedid'], $params['contextid']);
        $PAGE->set_context($context);

        $data = report_helper::ajax_chart_reports($params['filter'],
            $params['chartid'], $params['relatedid'], '');
        return $data;
    }

    /**
     * Return the table report.
     */
    public static function get_table_reports_returns() {

        return new external_value(PARAM_RAW, 'Site info content');
    }

    /**
     * Get the enrollment compltion by months parameters.
     */
    public static function get_enrollment_completion_bymonths_parameters() {

        return new external_function_parameters(
            [
                'filter' => new external_value(PARAM_TEXT, 'Duration filter'),
                'chartid' => new external_value(PARAM_TEXT, 'chart id '),
            ]
        );
    }

    /**
     * Get the enrolment and completion by months.
     * @param string $filter
     * @param int $chartid
     * @return array $data
     */
    public static function get_enrollment_completion_bymonths($filter, $chartid) {
        // Validate parameters.
        $params = self::validate_parameters(self::get_enrollment_completion_bymonths_parameters(),
            ['filter' => $filter, 'chartid' => $chartid]);

        // Validate site context and capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability("report/lmsace_reports:viewsitereports", $context);

        $data = report_helper::ajax_chart_reports($params['filter'], $params['chartid']);
        return $data;
    }

    /**
     * Return enrollment and completion by month.
     */
    public static function get_enrollment_completion_bymonths_returns() {

        return new external_single_structure(

            [
                'label' => new external_multiple_structure(
                    new external_value(PARAM_RAW, 'chart label')
                ),
                'enrolment' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'chart value')
                ),
                'completion' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'chart value')
                ),
            ]
        );
    }

    /**
     * Paramented defined for the moodle data and source directories used sizes.
     *
     * @return \external_function_paramters
     */
    public static function get_moodle_used_size_parameters() {
        return new external_function_parameters(
            [
                'chartid' => new external_value(PARAM_TEXT, 'chart id '),
            ]
        );
    }

    /**
     * Fetch the moodle data and source directories used sizes.
     *
     * @param int $chartid
     * @return array
     */
    public static function get_moodle_used_size($chartid) {
        // Validate parameters.
        $params = self::validate_parameters(self::get_moodle_used_size_parameters(),
            ['chartid' => $chartid]);

        // Validate context and capability - only site admins/managers should see disk usage.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability("report/lmsace_reports:viewsitereports", $context);

        return report_helper::get_moodle_spaces();
    }

    /**
     * Return data strcture defined for webservice the moodle data and source directories used sizes.
     *
     * @return \external_single_structure
     */
    public static function get_moodle_used_size_returns() {
        return new external_single_structure(
            [
                'moodlesrc' => new external_value(PARAM_TEXT, 'Moodle source size'),
                'moodledata' => new external_value(PARAM_TEXT, 'Moodle data size'),
            ]
        );
    }
}
