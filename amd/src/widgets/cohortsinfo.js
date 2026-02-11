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
 * Cohorts Info widgets.
 *
 * @module     report_lmsace_reports/cohortsinfo
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/chartjs'], function () {

    /* global cohortsinfo */

    /**
     * Initialize the cohorts chart.
     *
     * @param {report_lmsace_reports/main} main
     */
    function init(main) {

        let ctx = document.getElementById('cohorts-info-chart');
        if (ctx) {
            let type = 'pie';
            var bgColor = main.getRandomColors(main.getRandomColorpattern(cohortsinfo.label.length));
            main.buildChart(ctx, type, cohortsinfo.label, cohortsinfo.value, bgColor, null);
        }
    }

    return {
        init: init
    };
});
