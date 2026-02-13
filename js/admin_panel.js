import SuperSaver from "./SuperSaver.js";
// Needed Code Dont Delete
$("#messageBox").hide();
$("#close_messageBox").click(() => {
    $("#messageBox").hide();
});
var api = 'functionality/allFunctionality.php';
const mySuperSaver = new SuperSaver(api);
const standing_credit = $("#standing_credit").DataTable({
    columns: [
        { data: "saleman_name" },
        { data: "Credit" },
        {
            data: null,
            orderable: false,
            searchable: false,
            defaultContent: '<button class="btn btn-sm btn-primary generate-report" data-saleman-id="" data-saleman-name="">Generate Credit Report</button>',
            render: function (data, type, row) {
                try {
                    console.log('standing_credit row:', row);
                } catch (e) { }
                const id = (row && (row.saleman_id || row.salemanId || row.saleman)) || '';
                const name = (row && (row.saleman_name || row.salemanName || '')) || '';
                return `<button class="btn btn-sm btn-primary generate-report" data-saleman-id="${id}" data-saleman-name="${name}">Generate Credit Report</button>`;
            }
        }
    ]
    ,
    createdRow: function (row, data, dataIndex) {
        try {
            const id = (data && (data.saleman_id || data.salemanId || data.saleman)) || '';
            const name = (data && (data.saleman_name || data.salemanName || '')) || '';
            const btn = `<button class="btn btn-sm btn-primary generate-report" data-saleman-id="${id}" data-saleman-name="${name}">Generate Credit Report</button>`;
            // Ensure the third cell exists (if columns are fewer, append a cell)
            const $cells = $('td', row);
            if ($cells.length >= 3) {
                $cells.eq(2).html(btn);
            } else {
                $(row).append($('<td>').html(btn));
            }
        } catch (e) {
            console.error('createdRow error', e);
        }
    }
})
const today_sales = $('#today_sales').DataTable({
    columns: [
        { data: "picklist_id" },
        { data: "saleman_name" },
        { data: "picklist_amount" },
        { data: "picklist_credit" },
        { data: "picklist_sceheme_amount" },
        { data: "picklist_return" },
        { data: "picklist_recovery" }
    ],
    footerCallback: function (row, data, start, end, display) {
        var api = this.api();

        // Calculate the sum of "Amount," "Credit," and "Recived" columns
        var amountTotal = api.column(2, { page: 'current' }).data().reduce(function (a, b) {
            return parseFloat(a) + parseFloat(b);
        }, 0);

        var creditTotal = api.column(3, { page: 'current' }).data().reduce(function (a, b) {
            return parseFloat(a) + parseFloat(b);
        }, 0);

        var recivedTotal = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
            return parseFloat(a) + parseFloat(b);
        }, 0);

        // Update the footer cells with the calculated totals
        $(api.column(2).footer()).html(amountTotal.toFixed(2));
        $(api.column(3).footer()).html(creditTotal.toFixed(2));
        $(api.column(6).footer()).html(recivedTotal.toFixed(2));
    }
});
mySuperSaver.LoadDataTable(today_sales, 'today_sales')
mySuperSaver.LoadDataTable(standing_credit, 'get_credit_by_saleman')

// Delegate click for dynamically created buttons
$(document).on('click', '.generate-report', function (e) {
    const salemanId = $(this).data('saleman-id');
    const salemanName = $(this).data('saleman-name') || '';
    if (!salemanId) {
        alert('Salesman id not available');
        return;
    }
    const url = `fullCreditReport.php?saleman_id=${encodeURIComponent(salemanId)}&saleman_name=${encodeURIComponent(salemanName)}`;
    window.open(url, '_blank');
});

// Debug: log rows after table draw and ensure actions exist
standing_credit.on('draw', function () {
    try {
        const all = standing_credit.rows().data().toArray();
        console.log('standing_credit data count:', all.length, all);
        standing_credit.rows().every(function (idx) {
            const data = this.data();
            const node = this.node();
            const $cells = $('td', node);
            const id = (data && (data.saleman_id || data.salemanId || data.saleman)) || '';
            const name = (data && (data.saleman_name || data.salemanName || '')) || '';
            const btnHtml = `<button class="btn btn-sm btn-primary generate-report" data-saleman-id="${id}" data-saleman-name="${name}">Generate Credit Report</button>`;
            if ($cells.length >= 3) {
                if ($cells.eq(2).text().trim() === '') {
                    $cells.eq(2).html(btnHtml);
                }
            } else {
                $(node).append($('<td>').html(btnHtml));
            }
        });
    } catch (e) { console.error('draw handler error', e); }
});

// Also attempt injection shortly after load in case draw fired earlier
setTimeout(() => {
    try {
        standing_credit.rows().every(function () {
            const data = this.data();
            const node = this.node();
            const $cells = $('td', node);
            const id = (data && (data.saleman_id || data.salemanId || data.saleman)) || '';
            const name = (data && (data.saleman_name || data.salemanName || '')) || '';
            const btn = `<button class="btn btn-sm btn-primary generate-report" data-saleman-id="${id}" data-saleman-name="${name}">Generate Credit Report</button>`;
            if ($cells.length >= 3) {
                if ($cells.eq(2).text().trim() === '') $cells.eq(2).html(btn);
            } else {
                $(node).append($('<td>').html(btn));
            }
        });
    } catch (e) { }
}, 500);

// --- Dashboard Analytics ---
function loadDashboardAnalytics() {
    $.post(api, { function_to_call: 'get_dashboard_analytics' }, function (response) {
        try {
            const data = JSON.parse(response);

            // 1. Update Cards
            // Format currency (assuming PKR or generic currency)
            const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR' }).format(amount);

            $('#dash_outstanding').text(formatCurrency(data.outstanding));
            $('#dash_sales').text(formatCurrency(data.sales_month));
            $('#dash_recovery').text(formatCurrency(data.recovery_month));

            // 2. Render Chart (Sales vs Recovery)
            const salesTrend = data.sales_trend || [];
            const recoveryTrend = data.recovery_trend || [];

            // Merge dates to create a unified timeline
            const allDates = new Set([
                ...salesTrend.map(i => i.date),
                ...recoveryTrend.map(i => i.date)
            ]);
            const sortedDates = Array.from(allDates).sort();

            // Map data to the sorted timeline
            const salesData = sortedDates.map(date => {
                const found = salesTrend.find(i => i.date === date);
                return found ? found.amount : 0;
            });
            const recoveryData = sortedDates.map(date => {
                const found = recoveryTrend.find(i => i.date === date);
                return found ? found.amount : 0;
            });

            const ctx = document.getElementById('salesRecoveryChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: sortedDates,
                    datasets: [
                        {
                            label: 'Sales',
                            data: salesData,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Recovery',
                            data: recoveryData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // 3. Render Top Salesmen
            const topList = $('#top_salesmen_list');
            topList.empty();
            if (data.top_salesmen && data.top_salesmen.length > 0) {
                data.top_salesmen.forEach(salesman => {
                    const li = `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ${salesman.name}
                                <span class="badge bg-primary rounded-pill">${formatCurrency(salesman.total)}</span>
                            </li>
                        `;
                    topList.append(li);
                });
            } else {
                topList.append('<li class="list-group-item text-center">No data available</li>');
            }

            // 4. Dead Zones (Sectors)
            const deadZoneTable = $('#dead_zones_table');
            deadZoneTable.empty();
            if (data.dead_zones && data.dead_zones.length > 0) {
                data.dead_zones.forEach(zone => {
                    const row = `<tr><td>${zone.sector_name}</td><td class="text-end text-danger">${formatCurrency(zone.outstanding)}</td></tr>`;
                    deadZoneTable.append(row);
                });
            } else {
                deadZoneTable.append('<tr><td colspan="2" class="text-center">No high debt sectors</td></tr>');
            }

            // 5. Top Defaulters
            const defaulterTable = $('#top_defaulters_table');
            defaulterTable.empty();
            if (data.top_defaulters && data.top_defaulters.length > 0) {
                data.top_defaulters.forEach(defaulter => {
                    const row = `<tr><td>${defaulter.customer_name}</td><td>${defaulter.sector_name}</td><td class="text-end text-danger font-weight-bold">${formatCurrency(defaulter.outstanding)}</td></tr>`;
                    defaulterTable.append(row);
                });
            } else {
                defaulterTable.append('<tr><td colspan="3" class="text-center">No defaulters found</td></tr>');
            }

            // 6. Salesman Efficiency Chart
            if (data.salesman_efficiency && data.salesman_efficiency.length > 0) {
                const ctxEff = document.getElementById('salesmanEfficiencyChart').getContext('2d');
                new Chart(ctxEff, {
                    type: 'bar',
                    data: {
                        labels: data.salesman_efficiency.map(s => s.name),
                        datasets: [
                            {
                                label: 'Efficiency (%)',
                                data: data.salesman_efficiency.map(s => s.efficiency),
                                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                                borderColor: 'rgba(255, 159, 64, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y + '%';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 7. Debt Aging Chart
            if (data.aging_analysis && data.aging_analysis.length > 0) {
                const ctxAge = document.getElementById('agingChart').getContext('2d');
                new Chart(ctxAge, {
                    type: 'pie',
                    data: {
                        labels: data.aging_analysis.map(a => a.age_bucket),
                        datasets: [{
                            data: data.aging_analysis.map(a => a.total_outstanding),
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.6)', // 0-30 (Green-ish)
                                'rgba(255, 205, 86, 0.6)', // 31-60 (Yellow)
                                'rgba(255, 159, 64, 0.6)', // 61-90 (Orange)
                                'rgba(255, 99, 132, 0.6)'  // 90+ (Red)
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            // 8. At-Risk Customers
            const riskList = $('#at_risk_list');
            riskList.empty();
            if (data.at_risk_customers && data.at_risk_customers.length > 0) {
                data.at_risk_customers.forEach(cust => {
                    const li = `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${cust.customer_name}</strong><br>
                                    <small class="text-muted">Last Bill: ${cust.last_bill}</small>
                                </div>
                                <span class="badge bg-danger rounded-pill">${formatCurrency(cust.outstanding)}</span>
                            </li>
                        `;
                    riskList.append(li);
                });
            } else {
                riskList.append('<li class="list-group-item text-center">No at-risk customers found</li>');
            }

        } catch (e) {
            console.error('Error parsing dashboard data', e);
        }
    });
}

// Load dashboard on page ready
$(document).ready(function () {
    loadDashboardAnalytics();
});
