{{-- resources/views/admin/monthly-report/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Monthly Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-effect rounded-xl p-6 border border-white/10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold gradient-text">Monthly Report</h1>
                <p class="text-gray-400 mt-1">Generate comprehensive reports for all students</p>
            </div>
        </div>
    </div>

    <!-- Report Selection -->
    <div class="glass-effect rounded-xl p-6 border border-white/10">
        <h3 class="text-lg font-semibold text-white mb-4">Select Month & Year</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Month</label>
                <select id="monthSelect" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">Select Month</option>
                    <option value="January">January</option>
                    <option value="February">February</option>
                    <option value="March">March</option>
                    <option value="April">April</option>
                    <option value="May">May</option>
                    <option value="June">June</option>
                    <option value="July">July</option>
                    <option value="August">August</option>
                    <option value="September">September</option>
                    <option value="October">October</option>
                    <option value="November">November</option>
                    <option value="December">December</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Year</label>
                <select id="yearSelect" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">Select Year</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button id="generateBtn" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-all flex-1">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Generate Report
                </button>
                <button id="exportBtn" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all hidden">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Report Summary -->
    <div id="reportSummary" class="hidden glass-effect rounded-xl p-6 border border-white/10">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Report Summary</h3>
            <div class="text-sm text-gray-400">
                <span id="reportMonth"></span> | <span id="totalStudents"></span> students
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div id="reportTableContainer" class="hidden glass-effect rounded-xl border border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Student Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Highest Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Highest Worksheet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pages Completed</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody" class="divide-y divide-gray-700">
                    <!-- Table rows will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loadingIndicator" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="glass-effect rounded-xl p-8 text-center">
            <div class="loading-spinner text-primary mb-4">Generating Report...</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
class MonthlyReportManager {
    constructor() {
        this.currentMonth = '';
        this.currentYear = '';
        this.reportData = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.setDefaultMonthYear();
    }

    bindEvents() {
        document.getElementById('generateBtn').addEventListener('click', () => {
            this.generateReport();
        });

        document.getElementById('exportBtn').addEventListener('click', () => {
            this.exportReport();
        });
    }

    setDefaultMonthYear() {
        const now = new Date();
        const currentMonth = now.toLocaleString('default', { month: 'long' });
        const currentYear = now.getFullYear();

        document.getElementById('monthSelect').value = currentMonth;
        document.getElementById('yearSelect').value = currentYear;
    }

    showLoading(show = true) {
        const loading = document.getElementById('loadingIndicator');
        loading.classList.toggle('hidden', !show);
    }

    async generateReport() {
        try {
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;

            if (!month || !year) {
                showError('Please select both month and year');
                return;
            }

            this.currentMonth = month;
            this.currentYear = year;

            this.showLoading(true);

            const response = await fetch('/admin/monthly-report/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ month, year })
            });

            const result = await response.json();

            if (result.success) {
                this.reportData = result.data;
                this.renderReport(result.data);
                showSuccess(`Report generated successfully for ${month} ${year}`);
            } else {
                showError(result.error || 'Failed to generate report');
            }

        } catch (error) {
            console.error('Error generating report:', error);
            showError('Failed to generate report');
        } finally {
            this.showLoading(false);
        }
    }

    renderReport(data) {
        // Show summary
        document.getElementById('reportSummary').classList.remove('hidden');
        document.getElementById('reportMonth').textContent = `${data.month} ${data.year}`;
        document.getElementById('totalStudents').textContent = data.total_students;

        // Show export button
        document.getElementById('exportBtn').classList.remove('hidden');

        // Render table
        const tbody = document.getElementById('reportTableBody');
        tbody.innerHTML = '';

        if (data.report.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                        No data available for this month
                    </td>
                </tr>
            `;
        } else {
            data.report.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-white/5 transition-colors';
                tr.innerHTML = `
                    <td class="px-6 py-4 text-sm text-white">${row.student_name}</td>
                    <td class="px-6 py-4 text-sm text-white">${row.subject}</td>
                    <td class="px-6 py-4 text-sm text-white">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                            ${row.highest_level}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-white">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                            ${row.highest_worksheet}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-white">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                            ${row.pages_completed}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Show table container
        document.getElementById('reportTableContainer').classList.remove('hidden');
    }

    async exportReport() {
        try {
            if (!this.currentMonth || !this.currentYear) {
                showError('Please generate a report first');
                return;
            }

            this.showLoading(true);

            const params = new URLSearchParams({
                month: this.currentMonth,
                year: this.currentYear
            });

            const response = await fetch(`/admin/monthly-report/export?${params}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `monthly_report_${this.currentMonth}_${this.currentYear}_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showSuccess('Report exported successfully');
            } else {
                showError('Failed to export report');
            }

        } catch (error) {
            console.error('Error exporting report:', error);
            showError('Failed to export report');
        } finally {
            this.showLoading(false);
        }
    }
}

// Initialize the monthly report manager
const monthlyReport = new MonthlyReportManager();
</script>
@endpush

@endsection