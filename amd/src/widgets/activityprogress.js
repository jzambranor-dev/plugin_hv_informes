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
 * Activity progress widgets.
 *
 * @module     report_lmsace_reports/activityprogress
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon, Chart) {

    /* global courseactivityinfo */

    var activityprochart = null;
    var loadiconElement = $(".activity-progress-block .loadiconElement");

    /**
     * Initialize the progress chart.
     *
     * @returns bool
     */
    function init() {

        if (typeof courseactivityinfo === 'undefined') {
            return null;
        }

        var label = courseactivityinfo.label;
        var completiondata = courseactivityinfo.completiondata;
        var enrolmentdata = courseactivityinfo.enrolmentsdata;

        showActivityProgressChart(label, completiondata, enrolmentdata);

        $(".activity-progress-block .dropdown-menu a").click(function () {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            getActivityProgressReports(filter);
        });

        return null;
    }

    /**
     * Build the activity progress chart.
     *
     * @param {String} label
     * @param {Array} completiondata
     * @param {Array} enrolmentdata
     */
    var showActivityProgressChart = function (label, completiondata, enrolmentdata) {

        let ctx = document.getElementById('activity-progress-chart');
        if (ctx) {
            let config = {
                type: 'bar',
                data: {
                    labels: label,
                    datasets: [{
                        label: courseactivityinfo.completionlabel,
                        data: completiondata,
                        backgroundColor: 'rgba(153, 102, 255, 1)',
                        showTooltips: false,
                    },
                    {
                        label: courseactivityinfo.enrolmentlabel,
                        data: enrolmentdata,
                        backgroundColor: 'rgba(255, 102, 48, 1)',
                        showTooltips: false,
                    }]
                },
                options: {
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
            activityprochart = new Chart(ctx, config);
        }
    };

    var getActivityProgressReports = function (filter) {

        if (!filter) {
            filter = 'today';
        }
        var request = {
            methodname: 'report_lmsace_reports_activity_progress_reports',
            args: {
                filter: filter,
                chartid: 'activityprogressinfo'
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    var updateChartData = function (data) {

        activityprochart.data.labels = data.label;
        activityprochart.data.datasets[0].data = data.completiondata;
        activityprochart.data.datasets[1].data = data.enrolmentsdata;
        activityprochart.update();
    };

    return {
        init: init
    };
});
