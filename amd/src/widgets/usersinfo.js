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
 * User information - chart init.
 *
 * @module     report_lmsace_reports/usersinfo
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon'], function ($, AJAX, LoadIcon) {

    var userinfochart = null;
    var loadiconElement = $(".user-info-block .loadiconElement");

    /* global siteusers */


    /**
     *
     * @param {report_lmsace_reports/main} main
     * @returns bool
     */
    function init(main) {

        if (typeof siteusers === 'undefined') {
            return null;
        }

        var label = siteusers.label;
        var datavalue = siteusers.value;

        showVisitChart(main, label, datavalue);

        $(".user-info-block .dropdown-menu a").click(function (e) {
            e.preventDefault();
            var selText = $(this).text();
            var filter = $(this).attr("data-value");
            $(this).closest('.dropdown-menu').find('.dropdown-item').removeClass('active');
            $(this).addClass('active');
            $(this).parents('.dropdown').find('.lmsace-filter-btn').html(
                '<i class="fa fa-filter"></i> ' + selText);
            getUserInfoRecords(filter);
        });

        return true;
    }

    var showVisitChart = function (main, label, datavalue) {

        let ctx = document.getElementById('user-info-chart');

        if (ctx) {

            let type = 'doughnut';

            var bgColor = main.getRandomColors(['c1', 'c2', 'c3', 'c4']);
            userinfochart = main.buildChart(ctx, type, label, datavalue, bgColor, null);
        }
    };

    var getUserInfoRecords = function (filter) {

        if (!filter) {
            filter = 'today';
        }

        var request = {
            methodname: 'report_lmsace_reports_get_chart_reports',
            args: {
                filter: filter,
                chartid: 'siteuserswidget'
            }
        };
        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            updateChartData(result);
        });
        LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
    };

    var updateChartData = function (data) {

        userinfochart.data.labels = data.label;
        userinfochart.data.datasets[0].data = data.value;
        userinfochart.update();
    };

    return {
        init: init
    };
});
