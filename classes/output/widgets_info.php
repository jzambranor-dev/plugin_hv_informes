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
 * Define widgets.
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\output;
use cache;
use context_course;

/**
 * Widget abstract class.
 */
abstract class widgets_info {
    /**
     * @var cache_application|cache_session|cache_store $cache
     */
    public $cache;

    /**
     * @var object $students
     */
    public $students;

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * User
     *
     * @var stdClass
     */
    public $user;

    /**
     * Student params.
     *
     * @var array
     */
    public $studentparams;

    /**
     * StudentSQl
     *
     * @var string
     */
    public $studentsql;

    /**
     * Course for the report.
     *
     * @var stdClass
     */
    public $course;

    /**
     * @var array $reportdata
     */
    protected $reportdata = [];

    /**
     * Load data.
     */
    public function __construct() {
        $this->load_cache();
    }

    /**
     * Store cache.
     * @return cache_application|cache_session|cache_store.
     */
    public function load_cache() {
        $this->cache = cache::make('report_lmsace_reports', 'reportwidgets');
    }

    /**
     * Purge the caches of the report widgets.
     *
     * @param string|null $key The key to purge the cache.
     * @return bool
     */
    public static function purge_cache(?string $key=''): bool {
        // Report cache handler.
        $cache = cache::make('report_lmsace_reports', 'reportwidgets');
        if ($key == 'all') {
            $result = $cache->purge();
        } else if ($key != '') {
            $result = $cache->delete_report($key);
        }
        return $result ?? false;
    }

    /**
     * Get chart type.
     */
    abstract public function get_charttype();

    /**
     * Report is chart or not.
     */
    abstract public function is_chart();

    /**
     * Get the student role users.
     */
    public function get_students_sql() {
        global $DB;
        if ($this->context == 'course' && !empty($this->course)) {
            $coursecontext = context_course::instance($this->course->id);
            $students = get_enrolled_users($coursecontext, 'moodle/course:isincompletionreports');
            return $DB->get_in_or_equal(array_keys($students), SQL_PARAMS_NAMED, 'user');
        }
        return [];
    }

    /**
     * Get label info.
     */
    public function get_label_count() {
        switch ($this->filter) {
            case "month":
                $labelcount = 30;
                break;
            case "year":
                $labelcount = 365;
                break;
            default:
                $labelcount = 7;
                break;
        }
        return $labelcount;
    }

}
