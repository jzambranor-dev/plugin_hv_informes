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
 * Enrolmethod - chart init.
 *
 * @module     report_lmsace_reports/enrolmethod
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function () {

    /* global enrolmethodusers */

    return {

        init: function init(main) {
            let ctx = document.getElementById('enroll-method-chart');
            if (ctx) {
                var type = 'pie';
                var bgColor = main.getRandomColors(Array.from({ length: enrolmethodusers.label.length }, (_, t) => 'c' + (t + 1)));
                main.buildChart(ctx, type, enrolmethodusers.label, enrolmethodusers.value, bgColor, null);
            }
        }
    };
});
