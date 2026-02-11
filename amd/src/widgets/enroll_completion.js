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
 * Enroll completion.
 *
 * @module     report_lmsace_reports/enroll_completion
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon, Chart) {

    /* global courseenrolcompletion */

    var chartreport = null;
    var loadiconElement = $(".enrol-completion-block  .loadiconElement");

    /**
     * Display the completion chart and fetch the completion records when the event listener for the filter triggered.
     */
    function init() {
        showEnrollCompletionChart();
        $(".enrol-completion-block .dropdown-menu a").click(function () {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            getEnrollCompletionRecords(filter);
        });
    }

    var showEnrollCompletionChart = function () {
        let ctx = document.getElementById('enrolment-completion-chart');
        if (ctx) {
            let config = {
                type: 'doughnut',
                data: {
                    labels: courseenrolcompletion.label,
                    datasets: [{
                        data: courseenrolcompletion.value,
                        backgroundColor: ['rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'],
                        showTooltips: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: '',
                        }
                    }
                },
            };
            chartreport = new Chart(ctx, config);
        }
    };

    var getEnrollCompletionRecords = function (filter) {

        if (!filter) {
            filter = 'week';
        }
        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'courseenrolcompletionwidget',
                relatedid: courseenrolcompletion.courseid
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    /**
     * Update the chart data from the fetched dataset.
     *
     * @param {Array} result
     */
    function updateChartData(result) {
        chartreport.data.labels = result.label;
        chartreport.data.datasets[0].data = result.value;
        chartreport.update();
    }

    return {
        init: init
    };
});
