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
     * Enable column sorting on a table.
     * @param {string} selector CSS selector for the table.
     */
    function enableTableSort(selector) {
        var $table = $(selector);
        if (!$table.length) {
            return;
        }
        $table.find('thead th').each(function(colIndex) {
            var $th = $(this);
            $th.css('cursor', 'pointer').attr('title', 'Click to sort');
            $th.append(' <i class="fa fa-sort small text-muted"></i>');
            $th.on('click', function() {
                var $tbody = $table.find('tbody');
                var rows = $tbody.find('tr').get();
                var asc = !$th.data('asc');
                $th.data('asc', asc);

                // Update sort icons.
                $table.find('thead th i.fa').attr('class', 'fa fa-sort small text-muted');
                $th.find('i.fa').attr('class', 'fa fa-sort-' + (asc ? 'asc' : 'desc') + ' small');

                rows.sort(function(a, b) {
                    var aText = $(a).find('td').eq(colIndex).text().trim();
                    var bText = $(b).find('td').eq(colIndex).text().trim();
                    // Try numeric comparison (remove %, /10 suffix).
                    var aNum = parseFloat(aText.replace('%', '').replace('/10', ''));
                    var bNum = parseFloat(bText.replace('%', '').replace('/10', ''));
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return asc ? aNum - bNum : bNum - aNum;
                    }
                    // Fallback to string comparison.
                    return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
                });
                $.each(rows, function(i, row) {
                    $tbody.append(row);
                });
            });
        });
    }

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
     * Navigate to the teacher report URL with updated filter parameters.
     */
    function applyFilters() {
        var url = new URL(window.location.href);
        url.searchParams.set('report', 'teacherreport');

        // Month filter.
        var monthSelect = document.getElementById('teacher-month-select');
        if (monthSelect) {
            url.searchParams.set('teachermonth', monthSelect.value);
        }

        // Category filter (collect all checked checkboxes).
        var checked = document.querySelectorAll('.teacher-cat-checkbox:checked');
        var catIds = [];
        checked.forEach(function(cb) {
            catIds.push(cb.value);
        });
        url.searchParams.set('teachercategory', catIds.join(','));

        window.location.href = url.toString();
    }

    /**
     * Bind filter event handlers.
     */
    function bindFilters() {
        // Month filter auto-submit on change.
        var monthSelect = document.getElementById('teacher-month-select');
        if (monthSelect) {
            monthSelect.addEventListener('change', applyFilters);
        }

        // Category checkbox change.
        $(document).on('change', '.teacher-cat-checkbox', applyFilters);

        // Category tree toggle (expand/collapse).
        $(document).on('click', '.teacher-cat-toggle', function(e) {
            e.stopPropagation();
            var catId = $(this).data('catid');
            // Toggle direct children and courses containers for this specific category.
            $('[data-parent-cat="' + catId + '"], [data-courses-cat="' + catId + '"]').toggleClass('d-none');
            $(this).toggleClass('fa-caret-right fa-caret-down');
        });
    }

    /**
     * Initialize - set up lazy loading for teacher charts.
     *
     * @param {Object} main The main LMSACEReports instance.
     */
    function init(main) {
        // Bind filter controls and enable table sorting.
        bindFilters();
        enableTableSort('.teacher-courses-summary-block table');

        // If teacher tab is already active/visible, initialize immediately.
        var teacherTab = document.getElementById('teacher-report');
        if (teacherTab && teacherTab.classList.contains('active')) {
            initWidgets(main);
            return;
        }

        // Otherwise, wait for the teacher tab to be shown.
        $('a[id="teacher-tab"]').on('shown.bs.tab', function() {
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
