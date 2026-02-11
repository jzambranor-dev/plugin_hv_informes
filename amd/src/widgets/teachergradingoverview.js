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
 * Teacher grading overview bar chart widget.
 *
 * @module     report_lmsace_reports/widgets/teachergradingoverview
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/chartjs'], function() {

    /* global teachergradingoverview */

    /**
     * Initialize the grading overview bar chart.
     *
     * @param {Object} main The main LMSACEReports instance.
     */
    function init(main) {

        if (typeof teachergradingoverview === 'undefined') {
            return null;
        }

        var ctx = document.getElementById('teacher-gradingoverview-chart');
        if (!ctx) {
            return null;
        }

        var labels = teachergradingoverview.label;
        var datavalue = teachergradingoverview.value;
        var colorPattern = main.getRandomColorpattern(labels.length);
        var bgColors = main.getRandomColors(colorPattern);

        main.buildChart(ctx, 'bar', labels, datavalue, bgColors);

        return true;
    }

    return {
        init: init
    };
});
