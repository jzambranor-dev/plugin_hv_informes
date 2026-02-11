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
 * Evaluation blocks widget.
 *
 * @module     report_lmsace_reports/widgets/evaluationblocks
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        init: function(main) {

            // Render pie chart if detail view is active.
            var chartEl = document.getElementById('evaluation-passfail-chart');
            if (chartEl) {
                // Read chart data from data attributes on canvas element.
                var passed = parseInt(chartEl.getAttribute('data-passed')) || 0;
                var failed = parseInt(chartEl.getAttribute('data-failed')) || 0;
                var notcompleted = parseInt(chartEl.getAttribute('data-notcompleted')) || 0;

                if (passed > 0 || failed > 0 || notcompleted > 0) {
                    var bgColors = main.getRandomColors(['c6', 'c3', 'c5'], '', false);
                    main.buildChart(
                        chartEl.getContext('2d'),
                        'doughnut',
                        ['Passed', 'Failed', 'No attempt'],
                        [passed, failed, notcompleted],
                        bgColors
                    );
                }
            }

            // Initialize evaluation filters.
            var modtypeFilter = document.getElementById('eval-modtype-filter');
            var applyBtn = document.getElementById('eval-apply-filter');
            var clearBtn = document.getElementById('eval-clear-filter');

            if (applyBtn) {
                applyBtn.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    if (modtypeFilter) {
                        if (modtypeFilter.value) {
                            url.searchParams.set('evalmodtype', modtypeFilter.value);
                        } else {
                            url.searchParams.delete('evalmodtype');
                        }
                    }
                    var datefrom = document.getElementById('eval-datefrom');
                    var dateto = document.getElementById('eval-dateto');
                    if (datefrom && datefrom.value) {
                        url.searchParams.set('evalfrom', Math.floor(new Date(datefrom.value).getTime() / 1000));
                    } else {
                        url.searchParams.delete('evalfrom');
                    }
                    if (dateto && dateto.value) {
                        url.searchParams.set('evalto', Math.floor(new Date(dateto.value + 'T23:59:59').getTime() / 1000));
                    } else {
                        url.searchParams.delete('evalto');
                    }
                    // Remove evalcmid when filtering activities list.
                    url.searchParams.delete('evalcmid');
                    window.location.href = url.toString();
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('evalmodtype');
                    url.searchParams.delete('evalfrom');
                    url.searchParams.delete('evalto');
                    url.searchParams.delete('evalcmid');
                    window.location.href = url.toString();
                });
            }

            // Category filter for evaluation courses list.
            var catSelect = document.getElementById('eval-category-filter');
            var catApplyBtn = document.getElementById('eval-category-apply');
            var catClearBtn = document.getElementById('eval-category-clear');
            var catContainer = catSelect ? catSelect.closest('[data-evalteacher]') : null;
            var evalTeacherId = catContainer ? catContainer.getAttribute('data-evalteacher') : '';

            if (catApplyBtn && catSelect) {
                catApplyBtn.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.set('report', 'evaluationreport');
                    if (evalTeacherId) {
                        url.searchParams.set('evalteacher', evalTeacherId);
                    }
                    // Remove course/activity params when filtering by category.
                    url.searchParams.delete('evalcourse');
                    url.searchParams.delete('evalcmid');
                    var val = catSelect.value;
                    if (val && val !== '0') {
                        url.searchParams.set('evalcategory', val);
                    } else {
                        url.searchParams.delete('evalcategory');
                    }
                    window.location.href = url.toString();
                });
            }

            if (catClearBtn && catSelect) {
                catClearBtn.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.set('report', 'evaluationreport');
                    if (evalTeacherId) {
                        url.searchParams.set('evalteacher', evalTeacherId);
                    }
                    url.searchParams.delete('evalcategory');
                    url.searchParams.delete('evalcourse');
                    url.searchParams.delete('evalcmid');
                    window.location.href = url.toString();
                });
            }
        }
    };
});
