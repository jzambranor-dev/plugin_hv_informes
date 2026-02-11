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
 * Renderer config.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lmsace_reports\output;

use plugin_renderer_base;

/**
 * Renderer.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Course action.
     *
     * @var int
     */
    public $courseaction;

    /**
     * User action.
     *
     * @var int
     */
    public $useraction;

    /**
     * Report.
     *
     * @var string
     */
    public $report;

    /**
     * Teacher action.
     *
     * @var int
     */
    public $teacheraction;

    /**
     * Evaluation teacher ID.
     *
     * @var int
     */
    public $evalteacher;

    /**
     * Evaluation course ID.
     *
     * @var int
     */
    public $evalcourse;

    /**
     * Evaluation course module ID.
     *
     * @var int
     */
    public $evalcmid;

    /**
     * Evaluation module type filter.
     *
     * @var string
     */
    public $evalmodtype;

    /**
     * Evaluation date from filter.
     *
     * @var int
     */
    public $evalfrom;

    /**
     * Evaluation date to filter.
     *
     * @var int
     */
    public $evalto;

    /**
     * Defer to template.
     *
     * @param lmsace_reports $genesisreports
     *
     * @return string html for the page
     */
    public function render_lmsace_reports(\report_lmsace_reports\output\lmsace_reports $genesisreports) {
        $data = $genesisreports->export_for_template($this);
        return parent::render_from_template('report_lmsace_reports/lmsace_reports', $data);
    }
}
