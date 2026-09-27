/**
 * Dashboard charts (resources/views/dashboard/index.blade.php). Chart.js is
 * loaded only on the dashboard.
 */
const data = document.getElementById('dashboard-data');

if (data) {
    const { trend, mix, money, currency } = JSON.parse(data.textContent);
    const style = getComputedStyle(document.documentElement);
    const color = (name, fallback) => style.getPropertyValue(name).trim() || fallback;
    const primary = color('--bs-primary', '#0d6efd');
    const success = color('--bs-success', '#198754');
    const info = color('--bs-info', '#0dcaf0');
    const warning = color('--bs-warning', '#ffc107');
    const money0 = (value) => currency + Number(value).toLocaleString('en-US', { maximumFractionDigits: 0 });

    import('chart.js/auto').then(({ default: Chart }) => {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = color('--bs-secondary-color', '#6c757d');

        const trendCanvas = document.getElementById('dashboard-trend');
        if (trendCanvas) {
            const datasets = [{
                type: 'line',
                label: 'Consultations',
                data: trend.visits,
                borderColor: warning,
                backgroundColor: warning,
                tension: 0.3,
                yAxisID: 'visits',
                order: 0,
            }];
            if (money) {
                datasets.push({ type: 'bar', label: 'Revenue', data: trend.revenue, backgroundColor: `${primary}cc`, borderRadius: 4, yAxisID: 'revenue', order: 1 });
            }

            new Chart(trendCanvas, {
                data: { labels: trend.labels, datasets },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: (item) => `${item.dataset.label}: ${item.dataset.yAxisID === 'revenue' ? money0(item.raw) : item.raw}` } },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        visits: { position: money ? 'right' : 'left', beginAtZero: true, grid: { display: !money }, ticks: { precision: 0 } },
                        ...(money ? { revenue: { position: 'left', beginAtZero: true, ticks: { callback: (value) => money0(value) } } } : {}),
                    },
                },
            });
        }

        const mixCanvas = document.getElementById('dashboard-mix');
        if (mixCanvas) {
            new Chart(mixCanvas, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(mix).map((type) => type || 'Other'),
                    datasets: [{ data: Object.values(mix), backgroundColor: [success, info, warning, primary], borderWidth: 0 }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (item) => `${item.label}: ${money0(item.raw)}` } } },
                },
            });
        }
    });
}
