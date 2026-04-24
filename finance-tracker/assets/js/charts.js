/**
 * File: assets/js/charts.js
 * Purpose: Initialize Chart.js charts for dashboard and reports
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── Pie Chart: Expense by Category ───────────────────
    const pieCanvas = document.getElementById('expensePieChart');
    if (pieCanvas) {
        const month = window.FT_CHART_MONTH || new Date().getMonth() + 1;
        const year  = window.FT_CHART_YEAR  || new Date().getFullYear();
        const apiBase = window.FT_API_BASE  || '/finance-tracker/api';

        fetch(`${apiBase}/get_chart_data.php?type=pie_expense&month=${month}&year=${year}`)
            .then(res => res.json())
            .then(data => {
                if (data.labels && data.labels.length > 0) {
                    new Chart(pieCanvas, {
                        type: 'pie',
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { padding: 12, font: { size: 11 } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const label = ctx.label || '';
                                            const value = ctx.parsed || 0;
                                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                            const pct   = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return `${label}: $${value.toFixed(2)} (${pct}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    pieCanvas.parentElement.innerHTML = '<p class="text-muted text-center py-4">No expense data</p>';
                }
            })
            .catch(err => {
                console.error('Pie chart error:', err);
                pieCanvas.parentElement.innerHTML = '<p class="text-danger text-center py-4">Failed to load chart</p>';
            });
    }

    // ── Bar Chart: Monthly Income vs Expense ─────────────
    const barCanvas = document.getElementById('monthlyBarChart');
    if (barCanvas) {
        const apiBase = window.FT_API_BASE || '/finance-tracker/api';

        fetch(`${apiBase}/get_chart_data.php?type=bar_trend&months=6`)
            .then(res => res.json())
            .then(data => {
                new Chart(barCanvas, {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (val) => '$' + val.toLocaleString()
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { padding: 12, font: { size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => {
                                        return `${ctx.dataset.label}: $${ctx.parsed.y.toFixed(2)}`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => {
                console.error('Bar chart error:', err);
                barCanvas.parentElement.innerHTML = '<p class="text-danger text-center py-4">Failed to load chart</p>';
            });
    }

    // ── Yearly Bar Chart ──────────────────────────────────
    const yearlyCanvas = document.getElementById('yearlyBarChart');
    if (yearlyCanvas && window.FT_YEARLY_DATA) {
        const data = window.FT_YEARLY_DATA;
        new Chart(yearlyCanvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Income',
                        data: data.income,
                        backgroundColor: 'rgba(39,174,96,0.7)',
                        borderColor: '#27ae60',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Expenses',
                        data: data.expense,
                        backgroundColor: 'rgba(231,76,60,0.7)',
                        borderColor: '#e74c3c',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (val) => '$' + val.toLocaleString()
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { padding: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: $${ctx.parsed.y.toFixed(2)}`
                        }
                    }
                }
            }
        });
    }

});
