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
 * Teacher blocks - composite loader for teacher chart widgets.
 * Charts are initialized lazily when the teacher tab becomes visible,
 * because Chart.js cannot render properly on hidden canvas elements.
 *
 * @module     report_lmsace_reports/widgets/teacherblocks
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'report_lmsace_reports/widgets/teacherstudentperformance',
    'report_lmsace_reports/widgets/teachergradingoverview',
    'report_lmsace_reports/widgets/teacheractivitycompletion'
], function($, teacherStudentPerformance, teacherGradingOverview, teacherActivityCompletion) {

    var initialized = false;

    /**
     * Initialize all teacher chart widgets.
     *
     * @param {Object} main The main LMSACEReports instance.
     */
    function initWidgets(main) {
        if (initialized) {
            return;
        }
        initialized = true;
        teacherStudentPerformance.init(main);
        teacherGradingOverview.init(main);
        teacherActivityCompletion.init(main);
    }

    /**
     * Set up category filter apply/clear buttons.
     */
    function initCategoryFilter() {
        var applyBtn = document.getElementById('teacher-category-apply');
        var clearBtn = document.getElementById('teacher-category-clear');
        var select = document.getElementById('teacher-category-filter');
        // Read the teacher ID from the data attribute on the filter container.
        var filterContainer = select ? select.closest('[data-teacherid]') : null;
        var teacherId = filterContainer ? filterContainer.getAttribute('data-teacherid') : '';

        if (applyBtn && select) {
            applyBtn.addEventListener('click', function() {
                var url = new URL(window.location.href);
                // Always ensure we stay on the teacher report tab.
                url.searchParams.set('report', 'teacherreport');
                if (teacherId) {
                    url.searchParams.set('teacherinfo', teacherId);
                }
                var val = select.value;
                if (val && val !== '0') {
                    url.searchParams.set('teachercategory', val);
                } else {
                    url.searchParams.delete('teachercategory');
                }
                window.location.href = url.toString();
            });
        }

        if (clearBtn && select) {
            clearBtn.addEventListener('click', function() {
                var url = new URL(window.location.href);
                url.searchParams.set('report', 'teacherreport');
                if (teacherId) {
                    url.searchParams.set('teacherinfo', teacherId);
                }
                url.searchParams.delete('teachercategory');
                window.location.href = url.toString();
            });
        }
    }

    /**
     * Initialize - set up lazy loading for teacher charts.
     *
     * @param {Object} main The main LMSACEReports instance.
     */
    function init(main) {
        // Set up category filter buttons.
        initCategoryFilter();

        // If teacher tab is already active/visible, initialize immediately.
        var teacherTab = document.getElementById('teacher-report');
        if (teacherTab && teacherTab.classList.contains('active')) {
            initWidgets(main);
            return;
        }

        // Otherwise, wait for the teacher tab to be shown.
        // Bootstrap 5 uses 'shown.bs.tab', Bootstrap 4 uses 'shown.bs.tab' too.
        $('a[id="teacher-tab"]').on('shown.bs.tab', function() {
            // Small delay to ensure the tab content is fully visible.
            setTimeout(function() {
                initWidgets(main);
            }, 200);
        });

        // Fallback: also listen for click on the teacher tab link.
        $('a[id="teacher-tab"]').on('click', function() {
            setTimeout(function() {
                initWidgets(main);
            }, 500);
        });
    }

    return {
        init: init
    };
});
