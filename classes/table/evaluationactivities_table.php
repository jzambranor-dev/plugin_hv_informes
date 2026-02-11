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
 * Table handler class for evaluation activities (required by Moodle dynamic table system).
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\table;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once(__DIR__ . '/../local/table/evaluationactivities_table.php');

/**
 * Evaluation activities table in the namespace expected by Moodle's dynamic table API.
 */
class evaluationactivities_table extends \report_lmsace_reports\local\table\evaluationactivities_table {
}
