{{-- resources/views/admin/activity-logs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'User Activity Logs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-effect rounded-xl p-6 border border-white/10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold gradient-text">User Activity Logs</h1>
                <p class="text-gray-400 mt-1">Track changes made by Planners and Graders</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button id="exportBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
                <button id="refreshBtn" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-all">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div id="summaryCards" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Summary cards will be populated by JavaScript -->
    </div>

    <!-- Filters -->
    <div class="glass-effect rounded-xl p-6 border border-white/10">
        <h3 class="text-lg font-semibold text-white mb-4">Filters</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">User</label>
                <select id="userFilter" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">All Users</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Action</label>
                <select id="actionFilter" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">All Actions</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Student</label>
                <select id="studentFilter" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">All Students</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Subject</label>
                <select id="subjectFilter" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">All Subjects</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Date From</label>
                <input type="date" id="dateFromFilter" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Date To</label>
                <input type="date" id="dateToFilter" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary">
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button id="applyFiltersBtn" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-all">
                    Apply Filters
                </button>
                <button id="clearFiltersBtn" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all">
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="glass-effect rounded-xl border border-white/10 overflow-hidden">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Activity Logs</h3>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400">Show:</span>
                    <select id="perPageSelect" class="px-3 py-1 bg-gray-800 border border-gray-600 rounded text-white text-sm">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-400">per page</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Changes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Timestamp</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody" class="divide-y divide-gray-700">
                    <!-- Table rows will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="px-6 py-4 border-t border-white/10">
            <!-- Pagination will be populated by JavaScript -->
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loadingIndicator" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="glass-effect rounded-xl p-8 text-center">
            <div class="loading-spinner text-primary mb-4">Loading...</div>
        </div>
    </div>
</div>

<!-- Activity Detail Modal -->
<div id="activityModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-effect rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-white/10 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">Activity Details</h3>
            <button id="closeModalBtn" class="p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="modalContent" class="p-6">
            <!-- Modal content will be populated by JavaScript -->
        </div>
    </div>
</div>

@push('scripts')
<script>
class ActivityLogsManager {
    constructor() {
        this.currentPage = 1;
        this.perPage = 25;
        this.filters = {};
        this.allLogs = []; // Cache for modal details
        this.init();
    }

    init() {
        this.loadFilterOptions();
        this.loadSummary();
        this.loadLogs();
        this.bindEvents();
    }

    bindEvents() {
        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            this.applyFilters();
        });

        document.getElementById('clearFiltersBtn').addEventListener('click', () => {
            this.clearFilters();
        });

        document.getElementById('refreshBtn').addEventListener('click', () => {
            this.refreshData();
        });

        document.getElementById('exportBtn').addEventListener('click', () => {
            this.exportLogs();
        });

        document.getElementById('perPageSelect').addEventListener('change', (e) => {
            this.perPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.loadLogs();
        });

        document.getElementById('closeModalBtn').addEventListener('click', () => {
            this.closeModal();
        });

        // Close modal when clicking outside
        document.getElementById('activityModal').addEventListener('click', (e) => {
            if (e.target.id === 'activityModal') {
                this.closeModal();
            }
        });
    }

    showLoading(show = true) {
        const loading = document.getElementById('loadingIndicator');
        loading.classList.toggle('hidden', !show);
    }

    async loadFilterOptions() {
        try {
            const response = await fetch('/admin/activity-logs/filter-options');
            const result = await response.json();

            if (result.success) {
                this.populateFilterOptions(result.data);
            }
        } catch (error) {
            console.error('Error loading filter options:', error);
        }
    }

    populateFilterOptions(data) {
        // Populate user filter
        const userSelect = document.getElementById('userFilter');
        userSelect.innerHTML = '<option value="">All Users</option>';
        data.users.forEach(user => {
            userSelect.innerHTML += `<option value="${user.id}">${user.name} (${user.email})</option>`;
        });

        // Populate action filter
        const actionSelect = document.getElementById('actionFilter');
        actionSelect.innerHTML = '<option value="">All Actions</option>';
        data.actions.forEach(action => {
            const label = this.getActionLabel(action);
            actionSelect.innerHTML += `<option value="${action}">${label}</option>`;
        });

        // Populate student filter
        const studentSelect = document.getElementById('studentFilter');
        studentSelect.innerHTML = '<option value="">All Students</option>';
        data.students.forEach(student => {
            studentSelect.innerHTML += `<option value="${student}">${student}</option>`;
        });

        // Populate subject filter
        const subjectSelect = document.getElementById('subjectFilter');
        subjectSelect.innerHTML = '<option value="">All Subjects</option>';
        data.subjects.forEach(subject => {
            subjectSelect.innerHTML += `<option value="${subject}">${subject}</option>`;
        });
    }

    getActionLabel(action) {
        const labels = {
            'update_last_completed_page': 'Updated Last Completed Page',
            'update_level': 'Updated Level',
            'update_repeats': 'Updated Repeats',
            'delete_student_data': 'Deleted Student Data',
            'save_grade': 'Saved Grade',
            'save_page_override': 'Added Page Override',
            'remove_page_override': 'Removed Page Override',
            'bulk_save_grades': 'Bulk Saved Grades',
            'bulk_save_lesson_plan_changes': 'Bulk Updated Lesson Plans'
        };
        return labels[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    async loadSummary() {
        try {
            const params = new URLSearchParams(this.filters);
            const response = await fetch(`/admin/activity-logs/summary?${params}`);
            const result = await response.json();

            if (result.success) {
                this.renderSummaryCards(result.data);
            }
        } catch (error) {
            console.error('Error loading summary:', error);
        }
    }

    renderSummaryCards(data) {
        const container = document.getElementById('summaryCards');
        container.innerHTML = `
            <div class="glass-effect rounded-lg p-4 border border-white/10">
                <div class="text-2xl font-bold text-primary">${data.total_activities}</div>
                <div class="text-sm text-gray-400">Total Activities</div>
            </div>
            <div class="glass-effect rounded-lg p-4 border border-white/10">
                <div class="text-2xl font-bold text-green-400">${data.user_summary.length}</div>
                <div class="text-sm text-gray-400">Active Users</div>
            </div>
            <div class="glass-effect rounded-lg p-4 border border-white/10">
                <div class="text-2xl font-bold text-yellow-400">${data.action_summary.length}</div>
                <div class="text-sm text-gray-400">Action Types</div>
            </div>
            <div class="glass-effect rounded-lg p-4 border border-white/10">
                <div class="text-2xl font-bold text-purple-400">${data.student_activity.length}</div>
                <div class="text-sm text-gray-400">Students Affected</div>
            </div>
        `;
    }

    async loadLogs() {
        this.showLoading(true);
        try {
            const params = new URLSearchParams({
                ...this.filters,
                page: this.currentPage,
                per_page: this.perPage
            });

            const response = await fetch(`/admin/activity-logs/logs?${params}`);
            const result = await response.json();

            if (result.success) {
                this.allLogs = result.data; // Cache for modal
                this.renderLogsTable(result.data);
                this.renderPagination(result.pagination);
            } else {
                showError(result.error || 'Failed to load logs');
            }
        } catch (error) {
            console.error('Error loading logs:', error);
            showError('Failed to load activity logs');
        } finally {
            this.showLoading(false);
        }
    }

    renderLogsTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        
        if (logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                        No activity logs found
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = logs.map(log => `
            <tr class="hover:bg-white/5 transition-colors cursor-pointer" onclick="activityLogs.showActivityDetails(${log.id})">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-primary to-secondary flex items-center justify-center text-white text-sm font-bold">
                                ${log.user_name.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-white">${log.user_name}</div>
                            <div class="text-xs text-gray-400">${log.user_email}</div>
                            <div class="flex gap-1 mt-1">
                                ${log.user_roles.map(role => `
                                    <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold
                                        ${role === 'admin' ? 'bg-red-500 text-white' : 
                                          role === 'planner' ? 'bg-blue-500 text-white' : 
                                          role === 'grader' ? 'bg-green-500 text-white' : 
                                          'bg-gray-500 text-white'}">
                                        ${this.getRoleLabel(role)}
                                    </span>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/20 text-primary">
                        ${log.action_label}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-white">${log.student_name || '-'}</td>
                <td class="px-6 py-4 text-sm text-white">${log.subject || '-'}</td>
                <td class="px-6 py-4 text-sm text-white">
                    ${log.month && log.date ? `${log.month} ${log.date}` : '-'}
                </td>
                <td class="px-6 py-4 text-sm text-gray-300">
                    <div class="max-w-xs truncate">${log.description || '-'}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-400">${log.formatted_timestamp}</td>
            </tr>
        `).join('');
    }

    getRoleLabel(role) {
        const labels = {
            'admin': 'Admin',
            'planner': 'Planner',
            'grader': 'Grader',
            'viewer': 'Viewer'
        };
        return labels[role] || role;
    }

    renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        
        if (pagination.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);
        
        let paginationHTML = `
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-400">
                    Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total} results
                </div>
                <div class="flex items-center space-x-2">
        `;

        // Previous button
        if (pagination.current_page > 1) {
            paginationHTML += `
                <button onclick="activityLogs.goToPage(${pagination.current_page - 1})" 
                        class="px-3 py-2 text-sm bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Previous
                </button>
            `;
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === pagination.current_page;
            paginationHTML += `
                <button onclick="activityLogs.goToPage(${i})" 
                        class="px-3 py-2 text-sm rounded-lg transition-colors ${
                            isActive 
                                ? 'bg-primary text-white' 
                                : 'bg-gray-700 text-white hover:bg-gray-600'
                        }">
                    ${i}
                </button>
            `;
        }

        // Next button
        if (pagination.current_page < pagination.last_page) {
            paginationHTML += `
                <button onclick="activityLogs.goToPage(${pagination.current_page + 1})" 
                        class="px-3 py-2 text-sm bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Next
                </button>
            `;
        }

        paginationHTML += `
                </div>
            </div>
        `;

        container.innerHTML = paginationHTML;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadLogs();
    }

    applyFilters() {
        this.filters = {
            user_id: document.getElementById('userFilter').value,
            action: document.getElementById('actionFilter').value,
            student_name: document.getElementById('studentFilter').value,
            subject: document.getElementById('subjectFilter').value,
            date_from: document.getElementById('dateFromFilter').value,
            date_to: document.getElementById('dateToFilter').value
        };

        // Remove empty filters
        Object.keys(this.filters).forEach(key => {
            if (!this.filters[key]) {
                delete this.filters[key];
            }
        });

        this.currentPage = 1;
        this.loadLogs();
        this.loadSummary();
    }

    clearFilters() {
        this.filters = {};
        this.currentPage = 1;
        
        // Clear form
        document.getElementById('userFilter').value = '';
        document.getElementById('actionFilter').value = '';
        document.getElementById('studentFilter').value = '';
        document.getElementById('subjectFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        
        this.loadLogs();
        this.loadSummary();
    }

    refreshData() {
        this.loadFilterOptions();
        this.loadLogs();
        this.loadSummary();
        showSuccess('Data refreshed successfully');
    }

    async exportLogs() {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                ...this.filters,
                format: 'csv'
            });

            const response = await fetch(`/admin/activity-logs/export?${params}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `activity_logs_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showSuccess('Logs exported successfully');
            } else {
                showError('Failed to export logs');
            }
        } catch (error) {
            console.error('Error exporting logs:', error);
            showError('Failed to export logs');
        } finally {
            this.showLoading(false);
        }
    }

    showActivityDetails(logId) {
        const log = this.allLogs.find(l => l.id === logId);
        if (log) {
            this.renderActivityModal(log);
            document.getElementById('activityModal').classList.remove('hidden');
        }
    }

    renderActivityModal(log) {
        const modalContent = document.getElementById('modalContent');
        modalContent.innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">User</label>
                        <div class="text-white">${log.user_name} (${log.user_email})</div>
                        <div class="flex gap-1 mt-1">
                            ${log.user_roles.map(role => `
                                <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold
                                    ${role === 'admin' ? 'bg-red-500 text-white' : 
                                      role === 'planner' ? 'bg-blue-500 text-white' : 
                                      role === 'grader' ? 'bg-green-500 text-white' : 
                                      'bg-gray-500 text-white'}">
                                    ${this.getRoleLabel(role)}
                                </span>
                            `).join('')}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Action</label>
                        <div class="text-white">${log.action_label}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Student</label>
                        <div class="text-white">${log.student_name || 'N/A'}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Subject</label>
                        <div class="text-white">${log.subject || 'N/A'}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Date</label>
                        <div class="text-white">${log.month && log.date ? `${log.month} ${log.date}` : 'N/A'}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Timestamp</label>
                        <div class="text-white">${log.formatted_timestamp}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">IP Address</label>
                        <div class="text-white">${log.ip_address || 'N/A'}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <div class="text-white bg-gray-800/50 rounded p-3">${log.description || 'No description available'}</div>
                </div>

                ${log.old_values && Object.keys(log.old_values).length > 0 ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Previous Values</label>
                        <div class="text-white bg-red-900/20 border border-red-500/20 rounded p-3">
                            <pre class="text-sm overflow-x-auto">${JSON.stringify(log.old_values, null, 2)}</pre>
                        </div>
                    </div>
                ` : ''}

                ${log.new_values && Object.keys(log.new_values).length > 0 ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">New Values</label>
                        <div class="text-white bg-green-900/20 border border-green-500/20 rounded p-3">
                            <pre class="text-sm overflow-x-auto">${JSON.stringify(log.new_values, null, 2)}</pre>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    closeModal() {
        document.getElementById('activityModal').classList.add('hidden');
    }
}

// Initialize the activity logs manager
const activityLogs = new ActivityLogsManager();
</script>
@endpush

@endsection