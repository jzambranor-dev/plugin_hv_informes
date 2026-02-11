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
 * User logins - chart init.
 *
 * @module     report_lmsace_reports/userlogins
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs', 'report_lmsace_reports/main'],
    function ($, AJAX, LoadIcon) {

        var visitchart = null;
        var loadiconElement = $(".user-login-block .loadiconElement");

        /* global userlogins */

        /**
         * Inititalize the user logins report related charts and event handlers defined.
         * @param {report_lmsace_reports/main} main
         */
        function init(main) {
            if (typeof userlogins === 'undefined') {
                return null;
            }

            var label = userlogins.label;
            var datavalue = userlogins.value;
            var userid = userlogins.userid;
            showloginChart(main, label, datavalue);

            $(".user-login-block .dropdown-menu a").click(function () {
                var selText = $(this).text();
                var filter = $(this).attr("value");
                $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
                getUserloginRecords(filter, userid);
            });

            return true;
        }

        /**
         * Show chart
         *
         * @param {Array} main
         * @param {String} label
         * @param {Object} datavalue
         */
        var showloginChart = function (main, label, datavalue) {

            let ctx = document.getElementById('user-login-chart');
            if (ctx) {
                let type = 'line';
                var bgColor = main.getRandomColors(['c4'], '0.5', true); // Get bg color with opaticty.
                var borderColor = main.getRandomColors(['c4']); // Get border color without opaticty.
                var customConfig = {
                    data: {
                        datasets: [{
                            label: userlogins.userlabel
                        }]
                    }
                };

                visitchart = main.buildChart(ctx, type, label, datavalue, bgColor, customConfig, borderColor);
            }
        };

        var getUserloginRecords = function (filter, userid) {

            if (!filter) {
                filter = 'week';
            }

            var request = {
                methodname: 'report_lmsace_reports_get_chart_reports',
                args: {
                    filter: filter,
                    chartid: 'userloginswidget',
                    relatedid: userid
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
            visitchart.data.datasets[0].data = data.value;
            visitchart.update();
        };

        return {
            init: init
        };
    });
