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
 * Group memebers table filterset.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace report_lmsace_reports\table;

use core_table\local\filter\filterset;
use core_table\local\filter\string_filter;

/**
 * Group memebers table filterset.
 */
class siteusers_table_filterset extends filterset {

    /**
     * Get the required filters.
     *
     * The only required filter is the courseid filter.
     *
     * @return array.
     */
    public function get_required_filters(): array {
        return [];
    }

    /**
     * Get the optional filters.
     *
     * These are:
     * - accesssince;
     * - enrolments;
     * - groups;
     * - keywords;
     * - country;
     * - roles; and
     * - status.
     *
     * @return array
     */
    public function get_optional_filters(): array {
        return [
            'filter' => string_filter::class,
        ];
    }
}
