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
 * Teacher activity completion line chart widget with AJAX filter.
 *
 * @module     report_lmsace_reports/widgets/teacheractivitycompletion
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function($, AJAX, LoadIcon) {

    /* global teacheractivitycompletion */

    var completionChart = null;
    var loadiconElement = $(".teacher-activitycompletion-block .loadiconElement");

    /**
     * Initialize the activity completion line chart and filter.
     *
     * @param {Object} main The main LMSACEReports instance.
     */
    function init(main) {

        if (typeof teacheractivitycompletion === 'undefined') {
            return null;
        }

        var labels = teacheractivitycompletion.label;
        var datavalue = teacheractivitycompletion.value;
        showCompletionChart(main, labels, datavalue);

        $(".teacher-activitycompletion-block .dropdown-menu a").click(function() {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            getCompletionRecords(filter);
        });

        return true;
    }

    /**
     * Show the line chart.
     *
     * @param {Object} main The main LMSACEReports instance.
     * @param {Array} labels Chart labels.
     * @param {Array} datavalue Chart data values.
     */
    var showCompletionChart = function(main, labels, datavalue) {

        var ctx = document.getElementById('teacher-activitycompletion-chart');
        if (ctx) {
            var bgColor = main.getRandomColors(['c6'], '0.5', true);
            var borderColor = main.getRandomColors(['c6']);
            var customConfig = {data: {datasets: [{label: 'Activity completions'}]}};

            completionChart = main.buildChart(ctx, 'line', labels, datavalue, bgColor, customConfig, borderColor);
        }
    };

    /**
     * AJAX call to get updated data.
     *
     * @param {String} filter The duration filter.
     */
    var getCompletionRecords = function(filter) {
        if (!filter) {
            filter = 'year';
        }

        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'teacheractivitycompletionwidget',
                relatedid: teacheractivitycompletion.teacherid
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function(result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    /**
     * Update chart with new data.
     *
     * @param {Object} data The new chart data.
     */
    var updateChartData = function(data) {
        completionChart.data.labels = data.label;
        completionChart.data.datasets[0].data = data.value;
        completionChart.update();
    };

    return {
        init: init
    };
});
