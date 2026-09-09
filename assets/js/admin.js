/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Panel & Analytics JavaScript (Chart.js Integration)
 */

function initAdminCharts(chartData) {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded');
        return;
    }

    // 1. Monthly Sales Line Chart
    const salesCtx = document.getElementById('monthlySalesChart');
    if (salesCtx && chartData.monthlySales) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: chartData.monthlySales.labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: chartData.monthlySales.data,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#1d4ed8',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Revenue: ₹' + Number(context.raw).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + (value >= 100000 ? (value / 100000).toFixed(1) + 'L' : (value / 1000) + 'k');
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Orders by Status Doughnut Chart
    const orderCtx = document.getElementById('orderStatusChart');
    if (orderCtx && chartData.orderStatus) {
        new Chart(orderCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.orderStatus.labels,
                datasets: [{
                    data: chartData.orderStatus.data,
                    backgroundColor: [
                        '#10b981', // Delivered (Green)
                        '#3b82f6', // Shipped / In Transit (Blue)
                        '#f59e0b', // Processing (Yellow)
                        '#ef4444', // Cancelled (Red)
                        '#64748b'  // Pending (Gray)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 3. Top Brands Bar Chart
    const brandCtx = document.getElementById('brandSalesChart');
    if (brandCtx && chartData.brandSales) {
        new Chart(brandCtx, {
            type: 'bar',
            data: {
                labels: chartData.brandSales.labels,
                datasets: [{
                    label: 'Units Sold',
                    data: chartData.brandSales.data,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}
