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
 * Table class that contains the list of site resources.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;
use html_writer;
use report_lmsace_reports\report_helper;

/**
 * Class site resources.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class siteresourceofcourseswidget extends widgets_info {

    /**
     * @var string $filter
     */
    public $filter;

    /**
     * @var array $reportdata
     */
    protected $reportdata = [];

    /**
     * @var object $records
     */
    private $records;

    /**
     * @var string $context
     */
    public $context = "site";

    /**
     * Instance.
     *
     * @var \report_lmsace_reports\widgets
     */
    public $instance;

    /**
     * Implemented the constructor.
     * @param string $filter
     */
    public function __construct($filter = '') {
        parent::__construct();
        $this->filter = '';
        $this->instance = new \report_lmsace_reports\widgets();
        $this->param_sql();
    }

    /**
     * Get chart type.
     * @return string
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
     * Get the cache key.
     * @return string
     */
    public function get_cache_key() {
        return "siteresourceofcourses_" . $this->filter;
    }

    /**
     * Set the report data.
     * @return void
     */
    private function param_sql() {
        global $DB, $SITE;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $sql = "
            SELECT
                (SELECT COUNT(*) FROM {user} u WHERE deleted = 0 AND NOT EXISTS (
                    SELECT * FROM {user_enrolments} ue WHERE ue.userid = u.id
                ) AND u.id > 2) AS notenrolled,
                (SELECT COUNT(*) FROM (
                    SELECT userid, COUNT(userid) FROM {user_enrolments} WHERE userid > 2 AND status = 0
                    AND timestart < :now1 AND (timeend = 0 OR timeend > :now2) GROUP BY userid HAVING COUNT(*) > 1
                ) subquery) AS moreonecourse,
                (SELECT COUNT(*) FROM {user} u WHERE deleted = 0 AND NOT EXISTS (
                    SELECT * FROM {logstore_standard_log} l WHERE l.userid = u.id AND l.timecreated >= :lastweek
                ) AND u.id > 2) AS notvisits
            ";
            $params = [
                'lastweek' => strtotime('-1weeks'),
                'now1' => time(),
                'now2' => time(),
            ];
            $records = $DB->get_record_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get the report data.
     */
    public function get_data() {
        return report_helper::chart_values((array) $this->records);
    }

    /**
     * Get the report records table.
     */
    public function get_report_table() {
        global $OUTPUT;

        $morethencourse =
            new \report_lmsace_reports\table\siteresourceofmorecourse_table('reports-siteresourceofcourse-morecourse');
        $notenroll =
            new \report_lmsace_reports\table\siteresourceofnotenroll_table('reports-siteresourceofcourse-notenroll');
        $notvisits =
            new \report_lmsace_reports\table\siteresourceofnotvisit_table('reports-siteresourceofcourse-notvisit');

        $morethencoursefilterset =
        new \report_lmsace_reports\table\siteresourceofmorecourse_table_filterset('reports-siteresourceofcourse-morecourse-filter');
        $morethencourse->set_filterset($morethencoursefilterset);

        $notenrollfilterset =
        new \report_lmsace_reports\table\siteresourceofnotenroll_table_filterset('reports-siteresourceofcourse-notenroll-filter');
        $notenroll->set_filterset($notenrollfilterset);

        $notvisitsfilterset =
            new \report_lmsace_reports\table\siteresourceofnotvisit_table_filterset('reports-siteresourceofcourse-notvisit-filter');
        $notvisits->set_filterset($notvisitsfilterset);

        ob_start();
        $morethencourse->out(10, true);
        $morethencoursetablehtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        $notenroll->out(10, true);
        $notenrolltablehtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        $notvisits->out(10, true);
        $notvisitstablehtml = ob_get_contents();
        ob_end_clean();

        $content = [];
        $content['morethencoursetablehtml'] = $morethencoursetablehtml;
        $content['notenrolltablehtml'] = $notenrolltablehtml;
        $content['notvisitstablehtml'] = $notvisitstablehtml;
        $tablehtml = $OUTPUT->render_from_template('report_lmsace_reports/widgetstable/siteresourceofcourses',
            $content);
        return $tablehtml;
    }
}
