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
 * @module     report_lmsace_reports/siteactiveusers
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon, Chart) {

    /* global siteactiveusers */

    var activeuserschart = null;
    var loadiconElement = $(".site-activeusers-block .loadiconElement");

    /**
     * Initialize the active users chart and add event listener for the filter to udpate the users list.
     */
    function init() {

        if (typeof siteactiveusers === 'undefined') {
            return null;
        }

        var label = siteactiveusers.label;
        var datavalue = siteactiveusers.value;

        showActiveusersChart(label, datavalue);

        $(".site-activeusers-block .dropdown-menu a").click(function () {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            getVisitsRecords(filter);
        });

        return true;
    }

    /**
     * Show chart.
     *
     * @param {String} label
     * @param {Object} datavalue
     */
    var showActiveusersChart = function (label, datavalue) {

        let ctx = document.getElementById('site-activeusers-chart');
        if (ctx) {
            let config = {
                type: 'line',
                data: {
                    labels: label,
                    datasets: [
                        {
                            label: ctx.dataset.uservisitslabel,
                            data: datavalue,
                            backgroundColor: ['rgba(153, 102, 255, 1)'],
                            showTooltips: false,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: '',
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
            activeuserschart = new Chart(ctx, config);
        }
    };

    var getVisitsRecords = function (filter) {
        if (!filter) {
            filter = 'week';
        }

        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'siteactiveuserswidget'
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    var updateChartData = function (data) {
        activeuserschart.data.labels = data.label;
        activeuserschart.data.datasets[0].data = data.value;
        activeuserschart.update();
    };

    return {
        init: init
    };
});
