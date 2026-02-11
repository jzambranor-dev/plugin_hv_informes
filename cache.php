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
 * Cache option view page.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use report_lmsace_reports\output\widgets_info;

$courseid = optional_param('courseinfo', 0, PARAM_INT);
$userid = optional_param('userinfo', 0, PARAM_INT);
$action = optional_param('purge', 'all', PARAM_ALPHA);

require_login();

$PAGE->set_url(new moodle_url('/report/lmsace_reports/index.php'));

$purgekey = '';
if ($action == 'all' && has_capability('report/lmsace_reports:viewsitereports', \context_system::instance())) {
    $purgekey = 'all';
} else if ($courseid && $action == 'coursereport'
    && has_capability('report/lmsace_reports:viewcoursereports', \context_course::instance($courseid))) {
    $purgekey = 'c_'.$courseid.'_';
} else if ($action == 'user' && $USER->id != $userid
    && has_capability('report/lmsace_reports:viewotheruserreports', \context_user::instance($USER->id))) {
    // User with capability when view the other users reports. then reloads the data.
    $purgekey = 'u_'.$userid.'_';
} else if ($action == 'userreport' && $USER->id == $userid
    && has_capability('report/lmsace_reports:viewuserreports', \context_user::instance($USER->id))) {
    $purgekey = 'u_'.$userid.'_';
}

$returnurl = optional_param('returnurl', '/admin/purgecaches.php', PARAM_LOCALURL);
$returnurl = new moodle_url($returnurl);

// Purge the cache using the widget cache handler. if not cache purged then use the purge caches method to purge all.
$message = (widgets_info::purge_cache($purgekey)) ? get_string('reportcachepurge', 'report_lmsace_reports')
    : get_string('notcachepurge', 'report_lmsace_reports');

redirect($returnurl, $message);
