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
 * @module     report_lmsace_reports/courseblocks
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'report_lmsace_reports/widgets/enroll_completion',
    'report_lmsace_reports/widgets/enroll_completion_month',
    'report_lmsace_reports/widgets/coursestatus',
    'core/chartjs',
], function (enrollCompletion, enrollCompletionMonth, courseStatus) {

    /* global courseactiveinactiveusers, coursehighscore */

    var courseBlocks = {

        enrollCompletion: {
            func: enrollCompletion
        },
        enrollCompletionMonth: {
            func: enrollCompletionMonth
        },
        courseStatus: {
            func: courseStatus
        },
    };

    var loadCharts = function (main) {
        showActiveInactiveUsersChart(main);
        showHighScoreCourse(main);
        for (var key in courseBlocks) {
            courseBlocks[key].func.init(main);
        }
    };

    var showHighScoreCourse = function (main) {
        let ctx = document.getElementById('high-score-course-chart');
        if (ctx) {

            let type = 'bar';
            var bgColor = main.getRandomColors(['c2']); // Get bg color with opaticty.
            var customConfig = {
                data: { datasets: [{ label: coursehighscore.strscore }] },
                options: {
                    plugins: {
                        datalabels: {
                            backgroundColor: false,
                            borderWidth: 2,
                            borderColor: function (context) {
                                return context.dataset.backgroundColor;
                            },
                            color: function (context) {
                                return context.dataset.backgroundColor;
                            }
                        }
                    }
                }
            };

            main.buildChart(ctx, type, coursehighscore.label, coursehighscore.score, bgColor, customConfig, null);

        }
    };

    var showActiveInactiveUsersChart = function (main) {

        let ctx = document.getElementById('active-inactive-users-chart');
        if (ctx) {

            let type = 'line';
            var bgColor = main.getRandomColors(['c7'], '.5', true); // Get bg color with opaticty.
            var borderColor = main.getRandomColors(['c7']); // Get border color without opaticty.

            var bgColor2 = main.getRandomColors(['c4'], '.5', true); // Get bg color with opaticty.
            var borderColor2 = main.getRandomColors(['c4']); // Get border color without opaticty.

            var options = main.getMultiDatasetOptions();
            options.maintainAspectRatio = false;

            var customConfig = {
                options: options,
                data: {
                    datasets: [{
                        label: courseactiveinactiveusers.activeusers,
                        data: courseactiveinactiveusers.activeusers_data,
                        backgroundColor: bgColor,
                        borderColor: borderColor,
                        datalabels: {
                            align: 240,
                        }
                    },
                    {
                        label: courseactiveinactiveusers.inactiveusers,
                        data: courseactiveinactiveusers.inactiveusers_data,
                        backgroundColor: bgColor2,
                        borderColor: borderColor2,
                        fill: true,
                        datalabels: {
                            align: -45,
                        }
                    }]
                }
            };

            main.buildChart(ctx, type, courseactiveinactiveusers.label, null, null, customConfig);
        }

    };

    /**
     * Set up course category filter apply/clear buttons.
     */
    var initCategoryFilter = function() {
        var applyBtn = document.getElementById('course-category-apply');
        var clearBtn = document.getElementById('course-category-clear');
        var select = document.getElementById('course-category-filter');

        if (applyBtn && select) {
            applyBtn.addEventListener('click', function() {
                var url = new URL(window.location.href);
                url.searchParams.set('report', 'coursereport');
                var val = select.value;
                if (val && val !== '0') {
                    url.searchParams.set('coursecategory', val);
                } else {
                    url.searchParams.delete('coursecategory');
                }
                window.location.href = url.toString();
            });
        }

        if (clearBtn && select) {
            clearBtn.addEventListener('click', function() {
                var url = new URL(window.location.href);
                url.searchParams.set('report', 'coursereport');
                url.searchParams.delete('coursecategory');
                window.location.href = url.toString();
            });
        }
    };

    return {
        init: function (main) {
            initCategoryFilter();
            loadCharts(main);
        }
    };
});
