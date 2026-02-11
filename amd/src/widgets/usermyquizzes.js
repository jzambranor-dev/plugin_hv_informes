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
 * User quizzes - chart init.
 *
 * @module     report_lmsace_reports/usermyquizzes
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon', 'core/chartjs'], function ($, AJAX, LoadIcon) {

    var loadiconElement = $(".user-myquizzes-block .loadiconElement");

    /**
     * Initialize the chart.
     */
    function init() {

        $(".user-myquizzes-block .dropdown-menu a").click(function () {
            var selText = $(this).text();
            var filter = $(this).attr("value");
            $(this).parents('.dropdown').find('#daterangefiltermenu').html(selText + ' <span class="caret"></span>');
            var userid = $(".user-report-block #actionuser").val();
            getSiteinfoRecords(filter, userid);
        });

        var getSiteinfoRecords = function (filter, userid) {

            if (!filter) {
                filter = 'today';
            }
            var request = {
                methodname: 'report_lmsace_reports_table_reports',
                args: {
                    filter: filter,
                    chartid: 'usermyquizzeswidget',
                    relatedid: userid
                }
            };
            var promise = AJAX.call([request])[0];
            promise.done(function (result) {
                $(".user-myquizzes-block").find(".user-myquizzes-reports").empty().append(result);
            });
            LoadIcon.addIconToContainerRemoveOnCompletion(loadiconElement, promise);
        };
    }
    return {
        init: init
    };
});
