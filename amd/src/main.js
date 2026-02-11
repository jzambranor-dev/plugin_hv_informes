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
 * JS code which shows all fontawesome icons in a popover.
 *
 * @module     report_lmsace_reports/main
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @copyright  based on code from theme_boost\footer-popover by Bas Brands.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/chartjs',
    'core/str',
    'report_lmsace_reports/widgets/sitevisits',
    'report_lmsace_reports/widgets/siteactiveusers',
    'report_lmsace_reports/widgets/courseblocks',
    'report_lmsace_reports/widgets/coursevisits',
    'report_lmsace_reports/widgets/usersinfo',
    'report_lmsace_reports/widgets/siteinfo',
    'report_lmsace_reports/widgets/topcourseenrolment',
    'report_lmsace_reports/widgets/topcoursecompletion',
    'report_lmsace_reports/widgets/courseactivity',
    'report_lmsace_reports/widgets/enrolmethod',
    'report_lmsace_reports/widgets/userblocks',
    'report_lmsace_reports/widgets/usermyactivities',
    'report_lmsace_reports/widgets/usermyquizzes',
    'report_lmsace_reports/widgets/userassignments',
    'report_lmsace_reports/widgets/userlogins',
    'report_lmsace_reports/widgets/cohortsinfo',
    'report_lmsace_reports/widgets/sitestateinfo',
    'report_lmsace_reports/widgets/teacherblocks',
    'report_lmsace_reports/widgets/evaluationblocks',
    'report_lmsace_reports/chartjs-plugin-datalabels',
    'core/notification'
],
    function (
        chartjs,
        Str,
        siteVistis,
        siteactiveUsers,
        courseBlocks,
        courseVisits,
        usersInfo,
        siteInfo,
        topcourseEnrolment,
        topcourseCompletion,
        courseactivity,
        enrolmethod,
        userBlocks,
        userMyactivities,
        userMyquizzes,
        userMyassignments,
        userLogins,
        cohortsInfo,
        sitestateInfo,
        teacherBlocks,
        evaluationBlocks,
        chartDataLabels,
        Notification
    ) {

        /* global Chart */

        var blocks = {

            siteVistis: {
                func: siteVistis
            },
            /* SiteactiveUsers : {
                func : siteactiveUsers
            }, */
            courseBlocks: {
                func: courseBlocks
            },
            courseVisits: {
                func: courseVisits
            },
            usersInfo: {
                func: usersInfo
            },
            siteInfo: {
                func: siteInfo
            },
            topcourseEnrolment: {
                func: topcourseEnrolment
            },
            topcourseCompletion: {
                func: topcourseCompletion
            },
            courseactivity: {
                func: courseactivity
            },
            enrolmethod: {
                func: enrolmethod
            },
            userBlocks: {
                func: userBlocks
            },
            userMyactivities: {
                func: userMyactivities
            },
            userMyquizzes: {
                func: userMyquizzes
            },
            userMyassignments: {
                func: userMyassignments
            },
            userLogins: {
                func: userLogins
            },
            cohortsInfo: {
                func: cohortsInfo
            },
            sitestateInfo: {
                func: sitestateInfo
            },
            teacherBlocks: {
                func: teacherBlocks
            },
            evaluationBlocks: {
                func: evaluationBlocks
            }
        };

        class LMSACEReports {

            getStrings() {
                return Str.get_string('nodatatodisplay', 'report_lmsace_reports');
            }

            /**
             * Verify the charts are have data to render the chart, if not then hide the chart
             * and display no data available message.
             *
             * @param {String} str No data string
             */
            registerChartNoData(str) {

                Chart.defaults.datasets.bar.maxBarThickness = 10;

                Chart.register({
                    id: 'nodatatodisplay',
                    afterRender: function (chart) {

                        if (chart.data.datasets[0].data.every(item => item === 0)) {
                            const { ctx } = chart;
                            let width = chart.width;
                            chart.clear();
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.font = `.9375rem ${window.getComputedStyle(document.body).fontFamily}`;
                            ctx.fillText(str, width / 2, 50);
                            ctx.restore();
                        }
                    },

                });

            }

            generateRandomColor() {
                const randomColor = Math.floor(Math.random() * 16777215).toString(16);
                return "#" + randomColor;
            }

            getRandomColorpattern(length) {
                return Array.from({ length: length }, () => {
                    var val = Math.floor(Math.random() * (8 - 1 + 1)) + 1;
                    return "c" + val;
                });
            }

            getRandomColors(rcolors = ['c1'], op = '', bg = false) {
                var opacity = op ? op : '1';
                var colors = {
                    c1: {
                        bo: `rgba(27, 58, 95, ${opacity})`,
                        bg: `rgba(27, 58, 95, ${opacity})`
                    }, // Dark blue.
                    c2: {
                        bo: `rgba(48, 190, 207, ${opacity})`,
                        bg: `rgba(48, 190, 207, ${opacity})`
                    }, // Light blue.
                    c3: {
                        bo: `rgba(239, 77, 97, ${opacity})`,
                        bg: `rgba(239, 77, 97, ${opacity})`
                    }, // Rose.
                    c4: {
                        bo: `rgba(251, 178, 24, ${opacity})`,
                        bg: `rgba(251, 178, 24, ${opacity})`
                    }, // Dark Yellow
                    c5: {
                        bo: `rgba(165, 165, 165, ${opacity})`,
                        bg: `rgba(165, 165, 165, ${opacity})`
                    }, // Gray.
                    c6: {
                        bo: `rgba(12, 203, 150, ${opacity})`,
                        bg: `rgba(12, 203, 150, ${opacity})`
                    }, // Green
                    c7: {
                        bo: `rgba(153, 102, 255, ${opacity})`,
                        bg: `rgba(153, 102, 255, ${opacity})`
                    }, // Purple
                    c8: {
                        bo: `rgba(57, 155, 226, ${opacity})`,
                        bg: `rgba(57, 155, 226, ${opacity})`
                    }, // Blue.
                };

                // Bg colors are with opacity, bo colors are withou opacity.
                var method = bg ? 'bg' : 'bo';

                var list = [];
                rcolors.forEach((key) => {
                    if (key in colors) {
                        list.push(colors[key][method] ?? colors.c1);
                    } else {
                        list.push(this.generateRandomColor());
                    }
                });

                return list;
            }

            buildChart(ctx, type, labels, dataValue, bgColors = null, customConfig = null, borderColor = null) {

                let config = {
                    type: type,
                    data: {
                        labels: labels,
                        datasets: [{
                            data: dataValue,
                            backgroundColor: bgColors,
                            borderColor: borderColor || 'white',
                            showTooltips: false,
                            fill: true,
                            maxBarThickness: 80,
                            datalabels: {
                                anchor: 'end',
                                align: (type == 'line' || type == 'bar') ? 'top' : 'center'
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: !(type == 'line'),
                        layout: {
                            padding: 20
                        },
                        plugins: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: false
                            },
                            datalabels: {
                                backgroundColor: function (context) {
                                    return borderColor || context.dataset.backgroundColor;
                                },
                                borderColor: 'white',
                                borderRadius: 25,
                                borderWidth: 2,
                                color: 'white',
                                display: function (ctx) {
                                    return ctx.dataset.data[ctx.dataIndex] > 0;
                                },
                                font: {
                                    weight: 'bold'
                                },
                                padding: 6,
                                formatter: Math.round
                            }
                        }
                    },
                    plugins: [chartDataLabels],
                };


                if (customConfig !== null) {
                    config = this.merge(config, customConfig);
                }
                return new Chart(ctx, config);
            }

            merge(config, customConfig) {

                for (let key of Object.keys(customConfig)) {

                    if (!config.hasOwnProperty(key) || typeof customConfig[key] !== 'object') {
                        config[key] = customConfig[key];
                    } else if (typeof customConfig[key] === 'object' && typeof config[key] !== 'object') {
                        config[key] = customConfig[key];
                    } else if (!config[key]) {
                        config[key] = customConfig[key];
                    } else {
                        this.merge(config[key], customConfig[key]);
                    }
                }
                return config;
            }

            getMultiDatasetOptions() {
                return {
                    maintainAspectRatio: true,
                    layout: {
                        padding: {
                            left: 10,
                            right: 20,
                        }
                    },
                    plugins: {
                        datalabels: {
                            display: 'auto',
                            backgroundColor: function (context) {
                                return context.dataset.borderColor;
                            },
                            padding: 4,
                            borderWidth: 0,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        },
                        x: {
                            ticks: {
                                padding: 20
                            }
                        }
                    }
                };
            }
        }

        return {
            init: function () {
                var main = new LMSACEReports();
                main.getStrings().then((str) => {
                    setTimeout(function () {
                        for (let key in blocks) {
                            blocks[key].func.init(main);
                        }
                        main.registerChartNoData(str);
                    }, 1000);

                    return true;
                }).fail(Notification.exception);
            },
        };
    });
