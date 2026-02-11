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
 * Enroll completion month.
 *
 * @module     report_lmsace_reports/enroll_completion_month
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon, Chart) {

    /* global enrolcompletionmonth */

    var chartReport = null;
    var loadiconElement = $(".enrol-completion-month-block  .loadiconElement");

    /**
     * Create the completion chart by month and add event listener for the filter.
     */
    function init() {
        showEnrollCompletionMonthChart();
        $(".enrol-completion-month-block .dropdown-menu a").click(function () {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            getEnrollCompletionMonthRecords(filter);
        });
    }

    /**
     * Display the completion and enrolment chart based on month.
     */
    function showEnrollCompletionMonthChart() {

        let ctx = document.getElementById('enrolment-completion-month-chart');
        if (ctx) {
            let config = {
                type: 'bar',
                data: {
                    labels: enrolcompletionmonth.label,
                    datasets: [
                        {
                            label: enrolcompletionmonth.strenrolment,
                            data: enrolcompletionmonth.enrolment,
                            backgroundColor: 'rgba(153, 102, 255, 1)',
                            showTooltips: false,
                        },
                        {
                            label: enrolcompletionmonth.strcompletion,
                            data: enrolcompletionmonth.completion,
                            backgroundColor: 'rgba(255, 102, 48, 1)',
                            showTooltips: false,
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: '',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
            };
            chartReport = new Chart(ctx, config);
        }
    }

    /**
     * Fetch the completion dataset.
     *
     * @param {String} filter
     */
    function getEnrollCompletionMonthRecords(filter) {

        var request = {
            methodname: 'report_lmsace_reports_enrollment_completion_month',
            args: {
                filter: filter,
                chartid: 'enrolcompletionmonthwidget',
            }
        };

        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    }

    /**
     * Update the dataset to chart data format.
     *
     * @param {Array} result
     */
    function updateChartData(result) {
        chartReport.data.labels = result.label;
        chartReport.data.datasets[0].data = result.enrolment;
        chartReport.data.datasets[1].data = result.completion;
        chartReport.update();
    }

    return {
        init: init
    };
});
