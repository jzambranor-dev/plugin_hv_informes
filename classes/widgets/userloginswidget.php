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
 * Table class that contains the list of user logins.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Class user logins.
 */
class userloginswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var $context
     */
    public string $context = "user";

    /**
     * Implemented the constructor.
     * @param int $userid
     * @param string $filter
     */
    public function __construct($userid, $filter = '') {
        global $DB;
        parent::__construct();
        if (!$filter) {
            $filter = 'year';
        }
        $this->filter = $filter;
        $this->user = $DB->get_record('user', ['id' => $userid]);
        $this->get_report_data();
    }

    /**
     * Get chart type.
     */
    public function get_charttype() {
        return 'doughnut';
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return true;
    }

    /**
     * Get enroll users in course
     * @return array Get enrol users count each month
     */
    private function get_report_data() {
        $sitevisits = new \report_lmsace_reports\widgets\sitevisitswidget($this->filter, $this->user->id);
        $records = $sitevisits->get_data(false);
        $this->reportdata['label'] = $records['label'];
        $this->reportdata['value'] = $records['value']['sitevisits'];
        $this->reportdata['userid'] = $this->user->id;
        $this->reportdata['userlabel'] = get_string('userlogins', 'report_lmsace_reports');
    }

    /**
     * Get data.
     */
    public function get_data() {
        return $this->reportdata;
    }
}
