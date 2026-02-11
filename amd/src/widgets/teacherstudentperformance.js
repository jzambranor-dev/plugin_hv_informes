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
 * Teacher student performance doughnut chart widget.
 *
 * @module     report_lmsace_reports/widgets/teacherstudentperformance
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/chartjs'], function() {

    /* global teacherstudentperformance */

    /**
     * Initialize the student performance doughnut chart.
     *
     * @param {Object} main The main LMSACEReports instance.
     */
    function init(main) {

        if (typeof teacherstudentperformance === 'undefined') {
            return null;
        }

        var ctx = document.getElementById('teacher-studentperformance-chart');
        if (!ctx) {
            return null;
        }

        var labels = teacherstudentperformance.label;
        var datavalue = teacherstudentperformance.value;
        var bgColors = main.getRandomColors(['c6', 'c4', 'c3']);

        main.buildChart(ctx, 'doughnut', labels, datavalue, bgColors);

        return true;
    }

    return {
        init: init
    };
});
