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
 * Course visits widget.
 *
 * @module     report_lmsace_reports/coursevisits
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon) {

    /* global coursevisits, courseresources */

    var visitchart = null;
    var loadiconElement = $(".course-visits-block .loadiconElement");

    /**
     * Initialize the visits chart and add event listener for the filter.
     *
     * @param {report_lmsace_reports/main} main
     */
    function init(main) {

        if (typeof coursevisits === 'undefined') {
            return null;
        }

        var label = coursevisits.label;
        var datavalue = coursevisits.value;
        showVisitChart(main, label, datavalue);

        $(".course-visits-block .dropdown-menu a").click(function (e) {
            e.preventDefault();
            var selText = $(this).text();
            var filter = $(this).attr("data-value");
            $(this).closest('.dropdown-menu').find('.dropdown-item').removeClass('active');
            $(this).addClass('active');
            $(this).parents('.dropdown').find('.lmsace-filter-btn').html(
                '<i class="fa fa-filter"></i> ' + selText);
            getCoursevisitsRecords(filter);
        });

        return true;
    }

    /**
     * Show chart
     *
     * @param {report_lmsace_reports/main} main
     * @param {String} label
     * @param {Object} datavalue
     */
    var showVisitChart = function (main, label, datavalue) {

        let ctx = document.getElementById('course-visits-chart');
        if (ctx) {
            let type = 'line';
            var bgColor = main.getRandomColors(['c8'], '0.5', true); // Get bg color with opaticty.
            var borderColor = main.getRandomColors(['c8']); // Get border color without opaticty.
            var customConfig = { data: { datasets: [{ label: 'coursevisits' }] } };

            visitchart = main.buildChart(ctx, type, label, datavalue, bgColor, customConfig, borderColor);
        }
    };

    var getCoursevisitsRecords = function (filter) {
        if (!filter) {
            filter = 'week';
        }

        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'coursevisitswidget',
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

        visitchart.data.labels = data.label;
        visitchart.data.datasets[0].data = data.value;
        visitchart.update();
    };

    return {
        init: init
    };
});
