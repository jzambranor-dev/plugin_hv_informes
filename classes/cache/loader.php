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
 * Custom cache loader for the lmsace reports.

 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\cache;

defined('MOODLE_INTERNAL') || die();

use core_cache\application_cache;

/**
 * Custom cache loader for the lmsace reports.
 */
class loader extends application_cache {

    /**
     * Delete the cached reports for all of its users.
     *
     * Fetch the cache store, generate the keys with keyword of user cache.
     * Get the list of cached files by their filename, filenames are stored in the format of "u_ userid/c_courseid".
     *
     * Delete all the files using delete_many method.
     *
     * @param string $prefix The prefix to identify the reports.
     * @return void
     */
    public function delete_report($prefix) {
        $store = $this->get_store();

        if ($list = $store->find_by_prefix($prefix)) {
            $keys = array_map(function($key) {
                $key = current(explode('-', $key));
                return $key;
            }, $list);
            return $this->delete_many($keys) ? true : false;
        }
    }

}
