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
 * Course status widget.
 *
 * @module     report_lmsace_reports/coursestatus
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon'], function ($, AJAX, LoadIcon) {

    /* global courseresources */
    var courseStatus = null;
    var loadiconElement = $(".course-status-block .loadiconElement");

    /**
     * Initialize the course status chart.
     *
     * @param {report_lmsace_reports/main} main
     */
    function init(main) {
        showCoursestatusChart(main);
        $(".course-status-block .dropdown-menu a").click(function (e) {
            e.preventDefault();
            var selText = $(this).text();
            var filter = $(this).attr("data-value");
            $(this).closest('.dropdown-menu').find('.dropdown-item').removeClass('active');
            $(this).addClass('active');
            $(this).parents('.dropdown').find('.lmsace-filter-btn').html(
                '<i class="fa fa-filter"></i> ' + selText);
            getCourseactivityRecords(filter);
        });
    }

    var getCourseactivityRecords = function (filter) {

        if (!filter) {
            filter = 'week';
        }

        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'courseresourceswidget',
                relatedid: courseresources.courseid
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    var updateChartData = function (data) {
        courseStatus.data.labels = data.label;
        courseStatus.data.datasets[0].data = data.value;
        courseStatus.update();
    };

    var showCoursestatusChart = function (main) {

        var ctx = document.getElementById('course-status-chart');
        if (ctx) {
            let type = 'pie';
            var bgColor = main.getRandomColors(['c1', 'c3', 'c4', 'c6']);
            courseStatus = main.buildChart(ctx, type, courseresources.label, courseresources.value, bgColor, null);
        }
    };

    return {
        init: init
    };
});
