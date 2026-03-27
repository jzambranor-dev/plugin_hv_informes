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

            // Render bar chart for average score per question.
            var qAvgEl = document.getElementById('evaluation-question-avg-chart');
            if (qAvgEl) {
                try {
                    var qLabels = JSON.parse(qAvgEl.getAttribute('data-labels') || '[]');
                    var qValues = JSON.parse(qAvgEl.getAttribute('data-values') || '[]');

                    if (qLabels.length > 0) {
                        var colorKeys = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8'];
                        var barColorKeys = [];
                        for (var ci = 0; ci < qLabels.length; ci++) {
                            barColorKeys.push(colorKeys[ci % colorKeys.length]);
                        }
                        var barBgColors = main.getRandomColors(barColorKeys, '', false);

                        new Chart(qAvgEl.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: qLabels,
                                datasets: [{
                                    data: qValues,
                                    backgroundColor: barBgColors,
                                    borderColor: 'white',
                                    maxBarThickness: 80
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                layout: {padding: 10},
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                },
                                plugins: {
                                    legend: {display: false},
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        color: '#333',
                                        font: {weight: 'bold'},
                                        formatter: function(value) {
                                            return value > 0 ? value : '';
                                        }
                                    }
                                }
                            }
                        });
                    }
                } catch (e) {
                    window.console.warn('Quiz avg chart error:', e);
                }
            }

            // Category accordion: parent checkbox toggles all children.
            var parentCheckboxes = document.querySelectorAll('.eval-cat-parent');
            parentCheckboxes.forEach(function(parentCb) {
                parentCb.addEventListener('change', function() {
                    var parentId = parentCb.value;
                    var children = document.querySelectorAll('.eval-cat-child[data-parentid="' + parentId + '"]');
                    children.forEach(function(child) {
                        child.checked = parentCb.checked;
                    });
                });
            });

            // Accordion arrow rotation on expand/collapse.
            var accordionLinks = document.querySelectorAll('.eval-cat-accordion [data-toggle="collapse"], ' +
                '.eval-cat-accordion [data-bs-toggle="collapse"]');
            accordionLinks.forEach(function(link) {
                var target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    var observer = new MutationObserver(function() {
                        var arrow = link.querySelector('.eval-cat-arrow');
                        if (arrow) {
                            arrow.style.transform = target.classList.contains('show') ? 'rotate(90deg)' : '';
                        }
                    });
                    observer.observe(target, {attributes: true, attributeFilter: ['class']});
                    // Set initial state.
                    var arrow = link.querySelector('.eval-cat-arrow');
                    if (arrow && target.classList.contains('show')) {
                        arrow.style.transform = 'rotate(90deg)';
                    }
                }
            });

            // Initialize consolidated month filter.
            var monthApplyBtn = document.getElementById('eval-month-apply');
            var monthClearBtn = document.getElementById('eval-month-clear');

            if (monthApplyBtn) {
                monthApplyBtn.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.set('report', 'evaluationreport');
                    var monthSelect = document.getElementById('eval-month-filter');
                    var modtypeSelect = document.getElementById('eval-conmodtype-filter');

                    if (monthSelect && monthSelect.value && monthSelect.value !== '0') {
                        url.searchParams.set('evalmonth', monthSelect.value);
                    } else {
                        url.searchParams.delete('evalmonth');
                    }

                    // Collect checked category checkboxes.
                    var checkedCats = document.querySelectorAll('.eval-cat-checkbox:checked');
                    var catIds = Array.from(checkedCats).map(function(cb) {
                        return cb.value;
                    });
                    if (catIds.length > 0) {
                        url.searchParams.set('evalcategory', catIds.join(','));
                    } else {
                        url.searchParams.delete('evalcategory');
                    }

                    if (modtypeSelect && modtypeSelect.value) {
                        url.searchParams.set('evalconmodtype', modtypeSelect.value);
                    } else {
                        url.searchParams.delete('evalconmodtype');
                    }
                    url.searchParams.delete('evalteacher');
                    url.searchParams.delete('evalcourse');
                    url.searchParams.delete('evalcmid');
                    window.location.href = url.toString();
                });
            }

            if (monthClearBtn) {
                monthClearBtn.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.set('report', 'evaluationreport');
                    // Uncheck all category checkboxes.
                    document.querySelectorAll('.eval-cat-checkbox:checked').forEach(function(cb) {
                        cb.checked = false;
                    });
                    url.searchParams.delete('evalmonth');
                    url.searchParams.delete('evalcategory');
                    url.searchParams.delete('evalconmodtype');
                    url.searchParams.delete('evalteacher');
                    url.searchParams.delete('evalcourse');
                    url.searchParams.delete('evalcmid');
                    window.location.href = url.toString();
                });
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
        }
    };
});
