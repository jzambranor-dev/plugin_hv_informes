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
 * User most visits courses - chart init.
 *
 * @module     report_lmsace_reports/user_mostvisitcourses
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon'], function ($, AJAX, LoadIcon) {

    /* global mostvisitcourse */

    var chartReport = null;
    var loadiconElement = $(".user-visits-course-block .loadiconElement");

    /**
     * Initialize the courses visits chart.
     *
     * @param {report_lmsace_reports/main} main
     */
    function init(main) {
        showMostVisitsCourses(main);
        $(".user-visits-course-block .dropdown-menu a").click(function (e) {
            e.preventDefault();
            var selText = $(this).text();
            var filter = $(this).attr("data-value");
            $(this).closest('.dropdown-menu').find('.dropdown-item').removeClass('active');
            $(this).addClass('active');
            $(this).parents('.dropdown').find('.lmsace-filter-btn').html(
                '<i class="fa fa-filter"></i> ' + selText);
            getUserMostvisitsrecords(filter);
        });
    }

    var showMostVisitsCourses = function (main) {
        let ctx = document.getElementById('user-visits-course-chart');
        if (ctx) {

            let type = 'line';
            var bgColor = main.getRandomColors(['c6'], '0.5', true); // Get bg color with opaticty.
            var borderColor = main.getRandomColors(['c6']); // Get border color without opaticty.
            var customConfig = {
                options: {
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: !0,
                            min: 0,
                            suggestedMin: 0,
                            ticks: {
                                stepSize: 1
                            }
                        },
                    }
                },
                data: {
                    datasets: [{
                        label: mostvisitcourse.visits
                    }]
                }
            };

            chartReport = main.buildChart(
                ctx, type, mostvisitcourse.label, mostvisitcourse.value, bgColor, customConfig, borderColor);
        }
    };

    var getUserMostvisitsrecords = function (filter) {
        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'mostvisitcoursewidget',
                relatedid: mostvisitcourse.userid
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    var updateChartData = function (result) {
        chartReport.data.labels = result.label;
        chartReport.data.datasets[0].data = result.value;
        chartReport.update();
    };

    return {
        init: init
    };

});
