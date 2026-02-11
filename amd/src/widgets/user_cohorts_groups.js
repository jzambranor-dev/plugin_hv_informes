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
 * User cohorts groups - chart init.
 *
 * @module     report_lmsace_reports/user_cohorts_groups
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon) {

    /* global usergroupcohorts */
    var chartReport = null;
    var loadiconElement = $(".user-cohort-group-block .loadiconElement");

    /**
     * Initialize the user chart.
     *
     * @param {report_lmsace_reports/main} main
     */
    function init(main) {
        showUserGroupCohortChart(main);
        $(".user-cohort-group-block .dropdown-menu a").click(function () {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            getUserGroupCohortrecords(filter);
        });
    }

    var showUserGroupCohortChart = function (main) {

        let ctx = document.getElementById('user-cohort-group-chart');
        if (ctx) {
            let type = 'doughnut';
            var bgColor = main.getRandomColors(['c1', 'c2']);
            chartReport = main.buildChart(ctx, type, usergroupcohorts.label, usergroupcohorts.value, bgColor, null);
        }
    };

    var getUserGroupCohortrecords = function (filter) {

        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'usergroupcohortswidget',
                relatedid: usergroupcohorts.userid
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
