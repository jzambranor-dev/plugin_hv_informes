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
 * Table class that contains the list of sitvisits.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'report_lmsace_reports_get_chart_reports' => [
        'classname'   => 'report_lmsace_reports_external',
        'methodname'  => 'get_chart_reports',
        'classpath'   => 'report/lmsace_reports/externallib.php',
        'description' => 'Get list of chart reports',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'report_lmsace_reports_activity_progress_reports' => [
        'classname'   => 'report_lmsace_reports_external',
        'methodname'  => 'get_activity_progress_reports',
        'classpath'   => 'report/lmsace_reports/externallib.php',
        'description' => 'Get list of activity progress status',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'report_lmsace_reports_table_reports' => [
        'classname'   => 'report_lmsace_reports_external',
        'methodname'  => 'get_table_reports',
        'classpath'   => 'report/lmsace_reports/externallib.php',
        'description' => 'Get list of site info reports',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'report_lmsace_reports_enrollment_completion_month' => [
        'classname'   => 'report_lmsace_reports_external',
        'methodname'  => 'get_enrollment_completion_bymonths',
        'classpath'   => 'report/lmsace_reports/externallib.php',
        'description' => 'Get list of enrollment and completion reports by month',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'report_lmsace_reports_site_visits' => [
        'classname'   => 'report_lmsace_reports_external',
        'methodname'  => 'get_site_visits',
        'classpath'   => 'report/lmsace_reports/externallib.php',
        'description' => 'Get list of site visits',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'report_lmsace_reports_get_moodle_used_size' => [
        'classname'   => 'report_lmsace_reports_external',
        'methodname'  => 'get_moodle_used_size',
        'classpath'   => 'report/lmsace_reports/externallib.php',
        'description' => 'Get the moodle source and data size',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
