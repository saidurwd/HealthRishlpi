@extends('layouts.app')

@section('title', 'Dashboard - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-home" title="Dashboard" :breadcrumbs="['Dashboard']" />
@endsection

@push('head')
    {{-- Chart.js from the CDN, as the legacy dashboard loaded it --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
@php
    $dashboardData = compact('trendWeek', 'trendMonth', 'trendYear', 'demographics', 'serviceRevenue', 'departments', 'diseases', 'referralSources', 'geographicData', 'staffPerformance', 'heatmap');
@endphp
<div class="health-dashboard">
<div class="dashboard-header">
    <div class="header-content">
        <div class="header-title">
            <div class="icon">&#x1F3E5;</div>
            <div>
                <h1>Health Management Dashboard</h1>
            </div>
        </div>
        <div class="header-meta">
            <span id="currentDateTime">{{ date('F j, Y g:i A') }}</span>
            <span class="live-indicator">Live Data</span>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Start Date</label>
        <input type="date" id="startDate" value="{{ date('Y-m-01') }}">
    </div>
    <div class="filter-group">
        <label>End Date</label>
        <input type="date" id="endDate" value="{{ date('Y-m-t') }}">
    </div>
    <div class="filter-group">
        <label>Category</label>
        <select id="categoryFilter">
            <option value="all">All Categories</option>
            @foreach ($categories as $id => $title)
                <option value="{{ $id }}">{{ $title }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-group">
        <label>Department</label>
        <select id="departmentFilter">
            <option value="all">All Departments</option>
            @foreach ($departmentOptions as $id => $title)
                <option value="{{ $id }}">{{ $title }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
    <form id="exportForm" method="get" action="{{ route('dashboard.export') }}" style="display:none;"></form>
    <button class="btn btn-outline" onclick="document.getElementById('exportForm').submit();">Export</button>
</div>

<div class="dashboard-container">
    <div class="kpi-grid">
        <div class="kpi-card primary">
            <div class="kpi-header">
                <span class="kpi-label">Total Patients</span>
                <div class="kpi-icon">&#x1F464;</div>
            </div>
            <div class="kpi-value" id="kpiTotalPatients">{{ number_format($totalPatients) }}</div>
            <div class="kpi-trend {{ $patientTrend >= 0 ? 'up' : 'down' }}">
                {!! $patientTrend >= 0 ? '&#x2191;' : '&#x2193;' !!} {{ abs($patientTrend) }}% <span>vs last month</span>
            </div>
        </div>
        <div class="kpi-card accent">
            <div class="kpi-header">
                <span class="kpi-label">Monthly Revenue</span>
                <div class="kpi-icon">&#x1F4B0;</div>
            </div>
            <div class="kpi-value" id="kpiRevenue">৳{{ number_format($monthlyRevenue) }}</div>
            <div class="kpi-trend {{ $revenueTrend >= 0 ? 'up' : 'down' }}">
                {!! $revenueTrend >= 0 ? '&#x2191;' : '&#x2193;' !!} {{ abs($revenueTrend) }}% <span>vs last month</span>
            </div>
        </div>
        <div class="kpi-card success">
            <div class="kpi-header">
                <span class="kpi-label">Prescriptions Today</span>
                <div class="kpi-icon">&#x1F4EA;</div>
            </div>
            <div class="kpi-value" id="kpiPrescriptions">{{ number_format($prescriptionsToday) }}</div>
            <div class="kpi-trend {{ $prescriptionTrend >= 0 ? 'up' : 'down' }}">
                {!! $prescriptionTrend >= 0 ? '&#x2191;' : '&#x2193;' !!} {{ abs($prescriptionTrend) }}% <span>vs yesterday</span>
            </div>
        </div>
        <div class="kpi-card warning">
            <div class="kpi-header">
                <span class="kpi-label">Admissions</span>
                <div class="kpi-icon">&#x1F3E5;</div>
            </div>
            <div class="kpi-value" id="kpiAdmissions">{{ number_format($admissions) }}</div>
            <div class="kpi-trend {{ $admissionTrend >= 0 ? 'up' : 'down' }}">
                {!! $admissionTrend >= 0 ? '&#x2191;' : '&#x2193;' !!} {{ abs($admissionTrend) }}% <span>vs last week</span>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card two-third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Patient Attendance Trend</div>
                    <div class="chart-subtitle">Daily patient visits over time</div>
                </div>
                <div class="chart-actions">
                    <button class="active" onclick="updateTrendChart('week', this)">Week</button>
                    <button onclick="updateTrendChart('month', this)">Month</button>
                    <button onclick="updateTrendChart('year', this)">Year</button>
                </div>
            </div>
            <div class="chart-container tall">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>
        <div class="chart-card third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Patient Demographics</div>
                    <div class="chart-subtitle">Age & Sex distribution</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="demographicsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card half">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Service Revenue Breakdown</div>
                    <div class="chart-subtitle">Revenue by service type</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="serviceRevenueChart"></canvas>
            </div>
        </div>
        <div class="chart-card half">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Department Performance</div>
                    <div class="chart-subtitle">Patient count by department</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Disease Categories</div>
                    <div class="chart-subtitle">Common diagnoses</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="diseaseChart"></canvas>
            </div>
        </div>
        <div class="chart-card two-third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Patient Volume Heatmap</div>
                    <div class="chart-subtitle">Hourly distribution for current week</div>
                </div>
            </div>
            <div id="heatmapContainer">
                <div class="heatmap-labels">
                    <span></span>
                    @for ($h = 8; $h <= 19; $h++)
                        <span>{{ $h }}</span>
                    @endfor
                </div>
                <div class="heatmap-container" id="heatmapGrid"></div>
                <div class="heatmap-legend">
                    <span>Less</span>
                    <div class="heatmap-legend-item" style="background:#f1f3f5;"></div>
                    <div class="heatmap-legend-item" style="background:#bbdefb;"></div>
                    <div class="heatmap-legend-item" style="background:#64b5f6;"></div>
                    <div class="heatmap-legend-item" style="background:#1976d2;"></div>
                    <div class="heatmap-legend-item" style="background:#0d47a1;"></div>
                    <span>More</span>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card full">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Recent Patient Activity</div>
                    <div class="chart-subtitle">Latest registrations and invoices</div>
                </div>
                    <button class="btn btn-outline" onclick="window.location.href='{{ route('patient.admin') }}'">View All</button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody id="recentActivityTable">
                    @foreach ($recentActivity as $row)
                        <tr>
                            <td><strong>{{ $row['id'] }}</strong></td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['dept'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td><strong>৳{{ number_format($row['amount'], 2) }}</strong></td>
                            <td><span class="status-badge {{ $row['status'] }}">{{ ucfirst($row['status']) }}</span></td>
                            <td style="min-width: 120px;">
                                <div class="progress-bar">
                                    <div class="progress-bar-fill" style="width: {{ $row['progress'] }}%; background: {{ $row['progress'] == 100 ? 'var(--success)' : ($row['progress'] > 50 ? 'var(--warning)' : 'var(--danger)') }}"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Referral Sources</div>
                    <div class="chart-subtitle">Patient origin analysis</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="referralChart"></canvas>
            </div>
        </div>
        <div class="chart-card third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Geographic Distribution</div>
                    <div class="chart-subtitle">Patients by district</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="geographicChart"></canvas>
            </div>
        </div>
        <div class="chart-card third">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Staff Performance</div>
                    <div class="chart-subtitle">Revenue by invoice_by</div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="staffChart"></canvas>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card full">
            <div class="chart-header">
                <div>
                    <div class="chart-title">Stock Alerts</div>
                    <div class="chart-subtitle">Low inventory items requiring attention</div>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Store</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Rate (৳)</th>
                        <th>Value (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockAlerts as $row)
                        <tr>
                            <td>{{ $row['store'] }}</td>
                            <td>{{ $row['product'] }}</td>
                            <td>{{ $row['quantity'] }}</td>
                            <td>৳{{ number_format($row['rate'], 2) }}</td>
                            <td><strong>৳{{ number_format($row['quantity'] * $row['rate'], 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    const sampleData = @json($dashboardData);

    Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = '#6c757d';
    Chart.defaults.plugins.tooltip.backgroundColor = '#2c3e50';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { weight: '600', size: 13 };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };

    const primaryColor = '#3b5998';
    const accentColor = '#00bcd4';
    const colors = ['#3b5998', '#00bcd4', '#4caf50', '#ff9800', '#f44336', '#9c27b0', '#795548', '#607d8b'];

    function initTrendChart(data) {
        const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Patients', data: data.patients,
                        borderColor: primaryColor,
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const {ctx, chartArea} = chart;
                            if (!chartArea) return primaryColor + '40';
                            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            gradient.addColorStop(0, primaryColor + '40');
                            gradient.addColorStop(1, primaryColor + '00');
                            return gradient;
                        },
                        borderWidth: 3, fill: true, tension: 0.4,
                        pointRadius: 5, pointHoverRadius: 7,
                        pointBackgroundColor: primaryColor, pointBorderColor: '#fff', pointBorderWidth: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (৳)', data: data.revenue,
                        borderColor: accentColor, backgroundColor: 'transparent',
                        borderWidth: 3, borderDash: [5, 5], tension: 0.4,
                        pointRadius: 5, pointHoverRadius: 7,
                        pointBackgroundColor: accentColor, pointBorderColor: '#fff', pointBorderWidth: 2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'circle', padding: 20 } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { weight: '500' } } },
                    y: {
                        type: 'linear', display: true, position: 'left',
                        grid: { color: '#f1f3f5' },
                        title: { display: true, text: 'Patients', color: primaryColor, font: { weight: '600' } },
                        ticks: { color: primaryColor }
                    },
                    y1: {
                        type: 'linear', display: true, position: 'right',
                        grid: { display: false },
                            title: { display: true, text: 'Revenue (৳)', color: accentColor, font: { weight: '600' } },
                            ticks: { color: accentColor, callback: v => '৳' + (v/1000) + 'k' }
                    }
                }
            }
        });
    }

    function updateTrendChart(period, btn) {
        const data = sampleData['trend' + period.charAt(0).toUpperCase() + period.slice(1)];
        window.trendChart.data.labels = data.labels;
        window.trendChart.data.datasets[0].data = data.patients;
        window.trendChart.data.datasets[1].data = data.revenue;
        window.trendChart.update('active');
        document.querySelectorAll('.chart-actions button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function initDemographicsChart() {
        const ctx = document.getElementById('demographicsChart').getContext('2d');
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: sampleData.demographics.labels,
                datasets: [{ data: sampleData.demographics.values, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 12, boxWidth: 8 } } }
            }
        });
    }

    function initServiceRevenueChart() {
        const ctx = document.getElementById('serviceRevenueChart').getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sampleData.serviceRevenue.labels,
                datasets: [{
                    label: 'Revenue', data: sampleData.serviceRevenue.values,
                    backgroundColor: colors.map(c => c + 'cc'), borderColor: colors,
                    borderWidth: 2, borderRadius: 8, borderSkipped: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: { legend: { display: false } },
                        scales: { x: { grid: { color: '#f1f3f5' }, ticks: { callback: v => '৳' + (v/1000) + 'k' } }, y: { grid: { display: false } } }
            }
        });
    }

    function initDepartmentChart() {
        const ctx = document.getElementById('departmentChart').getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sampleData.departments.labels,
                datasets: [{
                    label: 'Patients', data: sampleData.departments.values,
                    backgroundColor: primaryColor + 'cc', borderColor: primaryColor,
                    borderWidth: 2, borderRadius: 8, borderSkipped: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f3f5' } } }
            }
        });
    }

    function initDiseaseChart() {
        const ctx = document.getElementById('diseaseChart').getContext('2d');
        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: sampleData.diseases.labels,
                datasets: [{ data: sampleData.diseases.values, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, boxWidth: 8, font: { size: 11 } } } }
            }
        });
    }

    function initReferralChart() {
        const ctx = document.getElementById('referralChart').getContext('2d');
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: sampleData.referralSources.labels,
                datasets: [{ data: sampleData.referralSources.values, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, boxWidth: 8, font: { size: 10 } } } }
            }
        });
    }

    function initGeographicChart() {
        const ctx = document.getElementById('geographicChart').getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sampleData.geographicData.labels,
                datasets: [{
                    label: 'Patients', data: sampleData.geographicData.values,
                    backgroundColor: primaryColor + 'cc', borderColor: primaryColor,
                    borderWidth: 2, borderRadius: 8, borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { color: '#f1f3f5' } }, y: { grid: { display: false } } }
            }
        });
    }

    function initStaffChart() {
        const ctx = document.getElementById('staffChart').getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sampleData.staffPerformance.labels,
                datasets: [{
                    label: 'Revenue (৳)', data: sampleData.staffPerformance.values,
                    backgroundColor: accentColor + 'cc', borderColor: accentColor,
                    borderWidth: 2, borderRadius: 8, borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { color: '#f1f3f5' }, ticks: { callback: v => '৳' + (v/1000) + 'k' } }, y: { grid: { display: false } } }
            }
        });
    }

    function initHeatmap() {
        const container = document.getElementById('heatmapGrid');
        container.innerHTML = '';
        const heatmap = window.heatmapData || sampleData.heatmap;
        heatmap.forEach((level, i) => {
            const cell = document.createElement('div');
            cell.className = 'heatmap-cell';
            cell.setAttribute('data-level', Math.min(level, 4));
            // The Yii tooltip made up a patient number; this shows the invoice count
            const day = Math.floor(i / 12);
            const hour = (i % 12) + 8;
            cell.title = 'Day ' + (day + 1) + ', Hour ' + hour + ':00\nInvoices: ' + level;
            container.appendChild(cell);
        });
    }

    function applyFilters() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const category = document.getElementById('categoryFilter').value;
        const department = document.getElementById('departmentFilter').value;

        const btn = document.querySelector('.health-dashboard .filter-bar .btn-primary');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';
        btn.disabled = true;

        fetch(@js(route('dashboard.ajaxFilter')), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: new URLSearchParams({ start_date: startDate, end_date: endDate, category: category, department: department }),
        })
            .then((response) => { if (!response.ok) { throw new Error(); } return response.json(); })
            .then((data) => updateDashboard(data))
            .catch(() => alert('Failed to load filtered data. Please try again.'))
            .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }

    function updateDashboard(data) {
        const primaryColor = '#3b5998';
        const accentColor = '#00bcd4';

        document.getElementById('kpiTotalPatients').textContent = Number(data.totalPatients).toLocaleString();
        document.getElementById('kpiRevenue').textContent = '৳' + Number(data.monthlyRevenue).toLocaleString();
        document.getElementById('kpiPrescriptions').textContent = Number(data.prescriptionsToday).toLocaleString();
        document.getElementById('kpiAdmissions').textContent = Number(data.admissions).toLocaleString();

        if (window.trendChart) {
            window.trendChart.data.labels = data.trendWeek.labels;
            window.trendChart.data.datasets[0].data = data.trendWeek.patients;
            window.trendChart.data.datasets[1].data = data.trendWeek.revenue;
            window.trendChart.update('active');
        }

        if (window.demographicsChart) {
            window.demographicsChart.data.labels = data.demographics.labels;
            window.demographicsChart.data.datasets[0].data = data.demographics.values;
            window.demographicsChart.update();
        }

        if (window.serviceRevenueChart) {
            window.serviceRevenueChart.data.labels = data.serviceRevenue.labels;
            window.serviceRevenueChart.data.datasets[0].data = data.serviceRevenue.values;
            window.serviceRevenueChart.update();
        }

        if (window.departmentChart) {
            window.departmentChart.data.labels = data.departments.labels;
            window.departmentChart.data.datasets[0].data = data.departments.values;
            window.departmentChart.update();
        }

        if (window.diseaseChart) {
            window.diseaseChart.data.labels = data.diseases.labels;
            window.diseaseChart.data.datasets[0].data = data.diseases.values;
            window.diseaseChart.update();
        }

        if (window.referralChart) {
            window.referralChart.data.labels = data.referralSources.labels;
            window.referralChart.data.datasets[0].data = data.referralSources.values;
            window.referralChart.update();
        }

        if (window.geographicChart) {
            window.geographicChart.data.labels = data.geographicData.labels;
            window.geographicChart.data.datasets[0].data = data.geographicData.values;
            window.geographicChart.update();
        }

        if (window.staffChart) {
            window.staffChart.data.labels = data.staffPerformance.labels;
            window.staffChart.data.datasets[0].data = data.staffPerformance.values;
            window.staffChart.update();
        }

        if (window.heatmapData) {
            window.heatmapData = data.heatmap;
            initHeatmap();
        }

        const tbody = document.getElementById('recentActivityTable');
        if (tbody && data.recentActivity) {
            tbody.innerHTML = data.recentActivity.map(row => {
                const amount = parseFloat(row.amount).toFixed(2);
                const progressColor = row.progress == 100 ? 'var(--success)' : (row.progress > 50 ? 'var(--warning)' : 'var(--danger)');
                return '<tr>' +
                    '<td><strong>' + escapeHtml(row.id) + '</strong></td>' +
                    '<td>' + escapeHtml(row.name) + '</td>' +
                    '<td>' + escapeHtml(row.dept) + '</td>' +
                    '<td>' + escapeHtml(row.date) + '</td>' +
                    '<td>' + escapeHtml(row.service) + '</td>' +
                    '<td><strong>৳' + Number(amount).toLocaleString() + '</strong></td>' +
                    '<td><span class="status-badge ' + row.status + '">' + row.status.charAt(0).toUpperCase() + row.status.slice(1) + '</span></td>' +
                    '<td style="min-width: 120px;"><div class="progress-bar"><div class="progress-bar-fill" style="width: ' + row.progress + '%; background: ' + progressColor + '"></div></div></td>' +
                '</tr>';
            }).join('');
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function exportDashboard() {
        window.location.href = @js(route('dashboard.export'));
    }

    document.getElementById('currentDateTime').textContent = new Date().toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });

    window.addEventListener('DOMContentLoaded', () => {
        window.trendChart = initTrendChart(sampleData.trendWeek);
        window.demographicsChart = initDemographicsChart();
        window.serviceRevenueChart = initServiceRevenueChart();
        window.departmentChart = initDepartmentChart();
        window.diseaseChart = initDiseaseChart();
        window.referralChart = initReferralChart();
        window.geographicChart = initGeographicChart();
        window.staffChart = initStaffChart();
        window.heatmapData = sampleData.heatmap;
        initHeatmap();
    });
</script>
@endpush
