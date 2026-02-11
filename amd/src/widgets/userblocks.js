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
 * User blocks - chart init.
 *
 * @module     report_lmsace_reports/userblocks
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'report_lmsace_reports/widgets/user_cohorts_groups',
    'report_lmsace_reports/widgets/user_mostvisitcourses', 'core/chartjs'
], function ($, userCohortsGroups, userMostVisitsCourses, Chart) {

    /* global userscore */

    var userBlocks = {

        userCohortsGroups: {
            func: userCohortsGroups
        },
        userMostVisitsCourses: {
            func: userMostVisitsCourses
        }
    };

    /**
     * Initialize the chart of users.
     *
     * @param {Array} main
     */
    function init(main) {
        showUserscoreChart(main);

        for (let key in userBlocks) {
            userBlocks[key].func.init(main);
        }
    }

    var showUserscoreChart = function (main) {

        let ctx = document.getElementById('user-score-chart');
        if (ctx) {
            let config = {
                type: 'bar',
                data: {
                    labels: userscore.label,
                    datasets: [{
                        label: userscore.strscore,
                        data: userscore.value,
                        backgroundColor: main.getRandomColors(['c7']),
                        showTooltips: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: '',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                        x: {
                            barThickness: 2, // Number (pixels) or 'flex'
                            maxBarThickness: 3 // Number (pixels)
                        }
                    }
                },
            };
            new Chart(ctx, config);
        }
    };

    return {
        init: init
    };
});
