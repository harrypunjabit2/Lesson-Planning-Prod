{{-- resources/views/admin/duplicate-config/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manage Duplicate Configurations')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-effect rounded-xl p-6 border border-white/10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold gradient-text">Manage Duplicate Configurations</h1>
                <p class="text-gray-400 mt-1">Find and remove duplicate student configuration entries</p>
            </div>
            <button id="scanBtn" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Scan for Duplicates
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div id="statsContainer" class="hidden grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="glass-effect rounded-lg p-4 border border-white/10">
            <div class="text-2xl font-bold text-blue-400" id="totalConfigs">0</div>
            <div class="text-sm text-gray-400">Total Configs</div>
        </div>
        <div class="glass-effect rounded-lg p-4 border border-white/10">
            <div class="text-2xl font-bold text-yellow-400" id="totalDuplicateGroups">0</div>
            <div class="text-sm text-gray-400">Duplicate Groups</div>
        </div>
        <div class="glass-effect rounded-lg p-4 border border-white/10">
            <div class="text-2xl font-bold text-red-400" id="totalDuplicateRecords">0</div>
            <div class="text-sm text-gray-400">Duplicate Records</div>
        </div>
        <div class="glass-effect rounded-lg p-4 border border-white/10">
            <div class="text-2xl font-bold text-green-400" id="uniqueAfterCleanup">0</div>
            <div class="text-sm text-gray-400">After Cleanup</div>
        </div>
    </div>

    <!-- Exact Duplicates Section -->
    <div id="exactDuplicatesSection" class="hidden glass-effect rounded-xl border border-white/10 overflow-hidden">
        <div class="p-6 border-b border-white/10 bg-green-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">Exact Duplicates</h3>
                    <p class="text-sm text-gray-400 mt-1">
                        These entries are completely identical and can be automatically removed. 
                        <span id="exactCount" class="font-semibold text-green-400">0</span> groups found.
                    </p>
                </div>
                <button id="autoDeleteBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Auto-Delete Duplicates
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Month/Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Pattern</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Duplicates</th>
                    </tr>
                </thead>
                <tbody id="exactDuplicatesBody" class="divide-y divide-gray-700">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pattern Conflicts Section -->
    <div id="patternConflictsSection" class="hidden glass-effect rounded-xl border border-white/10 overflow-hidden">
        <div class="p-6 border-b border-white/10 bg-yellow-900/20">
            <div>
                <h3 class="text-lg font-semibold text-white">Pattern Conflicts (Manual Review Required)</h3>
                <p class="text-sm text-gray-400 mt-1">
                    These entries have the same student/subject/month/year but different patterns or other fields. 
                    <span id="conflictCount" class="font-semibold text-yellow-400">0</span> conflicts found. Review and delete manually.
                </p>
            </div>
        </div>
        <div id="conflictsList" class="p-6 space-y-4">
            <!-- Populated by JavaScript -->
        </div>
    </div>

    <!-- No Duplicates Message -->
    <div id="noDuplicatesMessage" class="hidden glass-effect rounded-xl p-8 border border-white/10 text-center">
        <svg class="w-16 h-16 mx-auto text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-xl font-semibold text-white mb-2">No Duplicates Found!</h3>
        <p class="text-gray-400">Your student configurations are clean. No duplicate entries detected.</p>
    </div>

    <!-- Loading indicator -->
    <div id="loadingIndicator" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="glass-effect rounded-xl p-8 text-center">
            <div class="loading-spinner text-primary mb-4">Scanning for duplicates...</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
class DuplicateConfigManager {
    constructor() {
        this.exactDuplicates = [];
        this.patternConflicts = [];
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        document.getElementById('scanBtn').addEventListener('click', () => {
            this.scanForDuplicates();
        });

        document.getElementById('autoDeleteBtn').addEventListener('click', () => {
            this.autoDeleteExactDuplicates();
        });
    }

    showLoading(show = true) {
        document.getElementById('loadingIndicator').classList.toggle('hidden', !show);
    }

    async scanForDuplicates() {
        this.showLoading(true);
        try {
            // Get statistics
            const statsResponse = await fetch('/admin/duplicate-config/statistics');
            const statsResult = await statsResponse.json();

            if (statsResult.success) {
                this.renderStatistics(statsResult.data);
            }

            // Find duplicates
            const response = await fetch('/admin/duplicate-config/find');
            const result = await response.json();

            if (result.success) {
                this.exactDuplicates = result.data.exact_duplicates;
                this.patternConflicts = result.data.pattern_conflicts;

                if (this.exactDuplicates.length === 0 && this.patternConflicts.length === 0) {
                    this.showNoDuplicatesMessage();
                } else {
                    this.renderDuplicates();
                }

                showSuccess(`Found ${result.data.total_exact} exact duplicates and ${result.data.total_conflicts} conflicts`);
            } else {
                showError(result.error || 'Failed to scan for duplicates');
            }
        } catch (error) {
            console.error('Error scanning for duplicates:', error);
            showError('Failed to scan for duplicates');
        } finally {
            this.showLoading(false);
        }
    }

    renderStatistics(stats) {
        document.getElementById('statsContainer').classList.remove('hidden');
        document.getElementById('totalConfigs').textContent = stats.total_configs;
        document.getElementById('totalDuplicateGroups').textContent = stats.total_duplicate_groups;
        document.getElementById('totalDuplicateRecords').textContent = stats.total_duplicate_records;
        document.getElementById('uniqueAfterCleanup').textContent = stats.unique_configs_after_cleanup;
    }

    showNoDuplicatesMessage() {
        document.getElementById('noDuplicatesMessage').classList.remove('hidden');
        document.getElementById('exactDuplicatesSection').classList.add('hidden');
        document.getElementById('patternConflictsSection').classList.add('hidden');
    }

    renderDuplicates() {
        document.getElementById('noDuplicatesMessage').classList.add('hidden');

        // Render exact duplicates
        if (this.exactDuplicates.length > 0) {
            document.getElementById('exactDuplicatesSection').classList.remove('hidden');
            document.getElementById('exactCount').textContent = this.exactDuplicates.length;
            this.renderExactDuplicates();
        }

        // Render pattern conflicts
        if (this.patternConflicts.length > 0) {
            document.getElementById('patternConflictsSection').classList.remove('hidden');
            document.getElementById('conflictCount').textContent = this.patternConflicts.length;
            this.renderPatternConflicts();
        }
    }

    renderExactDuplicates() {
        const tbody = document.getElementById('exactDuplicatesBody');
        tbody.innerHTML = '';

        this.exactDuplicates.forEach(dup => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-white/5 transition-colors';
            tr.innerHTML = `
                <td class="px-6 py-4 text-sm text-white">${dup.student_name}</td>
                <td class="px-6 py-4 text-sm text-white">${dup.subject}</td>
                <td class="px-6 py-4 text-sm text-white">${dup.month} ${dup.year}</td>
                <td class="px-6 py-4 text-sm text-white">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                        ${dup.pattern}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-white">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                        ${dup.level}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-400">
                        ${dup.count} copies
                    </span>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    renderPatternConflicts() {
        const container = document.getElementById('conflictsList');
        container.innerHTML = '';

        this.patternConflicts.forEach(conflict => {
            const conflictDiv = document.createElement('div');
            conflictDiv.className = 'glass-effect rounded-lg p-4 border border-yellow-500/30';
            
            let entriesHtml = '';
            conflict.entries.forEach(entry => {
                entriesHtml += `
                    <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-2 text-sm">
                            <div>
                                <span class="text-gray-400">Pattern:</span>
                                <span class="text-white font-medium ml-2">${entry.pattern}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Level:</span>
                                <span class="text-white font-medium ml-2">${entry.level}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Class Day 1:</span>
                                <span class="text-white font-medium ml-2">${entry.class_day_1}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Class Day 2:</span>
                                <span class="text-white font-medium ml-2">${entry.class_day_2}</span>
                            </div>
                            <div class="flex items-center justify-end">
                                <button onclick="duplicateManager.deleteEntry(${entry.id})" 
                                        class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition-all">
                                    Delete This Entry
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            conflictDiv.innerHTML = `
                <div class="mb-3">
                    <h4 class="text-white font-semibold">${conflict.student_name} - ${conflict.subject}</h4>
                    <p class="text-sm text-gray-400">${conflict.month} ${conflict.year} - ${conflict.count} conflicting entries</p>
                </div>
                <div class="space-y-2">
                    ${entriesHtml}
                </div>
            `;
            
            container.appendChild(conflictDiv);
        });
    }

    async autoDeleteExactDuplicates() {
        if (this.exactDuplicates.length === 0) {
            showError('No exact duplicates to delete');
            return;
        }

        if (!confirm(`This will delete ${this.exactDuplicates.length} duplicate entries. Continue?`)) {
            return;
        }

        this.showLoading(true);
        try {
            const response = await fetch('/admin/duplicate-config/delete-exact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    duplicates: this.exactDuplicates
                })
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result.message);
                // Refresh the scan
                setTimeout(() => this.scanForDuplicates(), 1000);
            } else {
                showError(result.error || 'Failed to delete duplicates');
            }
        } catch (error) {
            console.error('Error deleting duplicates:', error);
            showError('Failed to delete duplicates');
        } finally {
            this.showLoading(false);
        }
    }

    async deleteEntry(id) {
        if (!confirm('Are you sure you want to delete this entry?')) {
            return;
        }

        this.showLoading(true);
        try {
            const response = await fetch('/admin/duplicate-config/delete-entry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result.message);
                // Refresh the scan
                setTimeout(() => this.scanForDuplicates(), 1000);
            } else {
                showError(result.error || 'Failed to delete entry');
            }
        } catch (error) {
            console.error('Error deleting entry:', error);
            showError('Failed to delete entry');
        } finally {
            this.showLoading(false);
        }
    }
}

// Initialize the duplicate config manager
const duplicateManager = new DuplicateConfigManager();
</script>
@endpush

@endsection