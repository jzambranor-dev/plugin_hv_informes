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
 * Site visits - chart init.
 *
 * @module     report_lmsace_reports/sitevisits
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs', 'report_lmsace_reports/chartjs-plugin-datalabels'],
    function ($, AJAX, LoadIcon) {

        /* global sitevisits */

        var visitchart = null;
        var loadiconElement = $(".site-visits-block .loadiconElement");

        /**
         * Create a chart for the site visits.
         *
         * @param {report_lmsace_reports/main} main
         */
        function init(main) {

            if (typeof sitevisits === 'undefined') {
                return null;
            }

            var label = sitevisits.label;
            var datavalue = sitevisits.value;
            showVisitChart(main, label, datavalue);

            $(".site-visits-block .dropdown-menu a").click(function () {
                var selText = $(this).text();
                var filter = $(this).attr("value");
                $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
                $(this).parents('.dropdown').find('#daterangefiltermenu').attr("data-filter", filter);
                getVisitsRecords(filter);
            });

            return true;
        }

        /**
         * Show chart.
         *
         * @param {report_lmsace_reports/main} main
         * @param {String} label
         * @param {Object} datavalue
         */
        var showVisitChart = function (main, label, datavalue) {

            let ctx = document.getElementById('site-visits-chart');

            if (ctx) {
                var bgColor = main.getRandomColors(['c7'], '.5', true); // Get bg color with opaticty.
                var borderColor = main.getRandomColors(['c7']); // Get border color without opaticty.

                var bgColor2 = main.getRandomColors(['c4'], '.5', true); // Get bg color with opaticty.
                var borderColor2 = main.getRandomColors(['c4']); // Get border color without opaticty.

                var bgColor3 = main.getRandomColors(['c6'], '.5', true); // Get bg color with opaticty.
                var borderColor3 = main.getRandomColors(['c6']); // Get border color without opaticty.

                var customConfig = {
                    options: main.getMultiDatasetOptions(),
                    data: {
                        datasets: [{
                            label: ctx.dataset.sitevisits,
                            data: datavalue.sitevisits,
                            backgroundColor: bgColor,
                            borderColor: borderColor,
                            fill: true,
                            datalabels: {
                                align: -45,
                            },
                        },
                        {
                            label: ctx.dataset.coursevisits,
                            data: datavalue.coursevisits,
                            backgroundColor: bgColor2,
                            borderColor: borderColor2,
                            fill: true,
                            datalabels: {
                                align: 'left',
                            }
                        },
                        {
                            label: ctx.dataset.modulevisits,
                            data: datavalue.modulevisits,
                            backgroundColor: bgColor3,
                            borderColor: borderColor3,
                            fill: true,
                            datalabels: {
                                align: 45,
                            },
                        }]
                    }
                };
                visitchart = main.buildChart(ctx, 'line', label, null, null, customConfig);
            }
        };

        var getVisitsRecords = function (filter) {
            if (!filter) {
                filter = 'week';
            }

            var request = {
                methodname: 'report_lmsace_reports_site_visits',
                args: {
                    filter: filter,
                    chartid: 'sitevisitswidget'
                }
            };
            var promise = AJAX.call([request])[0];
            promise.done(function (result) {
                updateChartData(result);
            });
            LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
        };

        var updateChartData = function (data) {
            visitchart.data.labels = data.label;
            visitchart.data.datasets[0].data = data.value.sitevisits;
            visitchart.data.datasets[1].data = data.value.coursevisits;
            visitchart.data.datasets[2].data = data.value.modulevisits;
            visitchart.update();
        };

        return {
            init: init
        };
    });
