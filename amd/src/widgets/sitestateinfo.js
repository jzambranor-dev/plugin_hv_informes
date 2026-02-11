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
 * Site current state information - chart init.
 *
 * @module     report_lmsace_reports/sitestateinfo
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/loadingicon'], function ($, AJAX, LoadIcon) {

    /**
     * Trigger the intialHandler
     */
    function init() {
        intialHandler();
    }

    var intialHandler = function () {
        var moodlesrcElement = $(".site-state-reports .moodlesrc-size");
        var moodledataElement = $(".site-state-reports .moodledata-size");

        var request = {
            methodname: 'report_lmsace_reports_get_moodle_used_size',
            args: {
                chartid: 'courseenrolcompletionwidget'
            }
        };

        var promise = AJAX.call([request])[0];
        promise.done(function (result) {
            $(moodlesrcElement).text(result.moodlesrc);
            $(moodledataElement).text(result.moodledata);
        });

        LoadIcon.addIconToContainerRemoveOnCompletion(moodlesrcElement, promise);
        LoadIcon.addIconToContainerRemoveOnCompletion(moodledataElement, promise);

    };

    return {
        init: init
    };
});
