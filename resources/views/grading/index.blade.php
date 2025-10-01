@extends('layouts.app')

@section('title', 'Multi-Day Grading System')

@section('content')
<style>
.glass-effect {
    background: rgba(31, 41, 55, 0.7);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.gradient-text {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

:root {
    --primary: #667eea;
    --secondary: #764ba2;
}

/* Autocomplete Styles */
.autocomplete-container {
    position: relative;
}

.autocomplete-input {
    width: 100%;
    padding: 0.25rem 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.25rem;
    color: white;
    font-size: 0.75rem;
    transition: all 0.2s ease;
    position: relative;
}

.autocomplete-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.15);
    border-color: var(--primary);
}

.autocomplete-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.autocomplete-dropdown {
    position: fixed;
    max-height: 200px;
    overflow-y: auto;
    background: rgba(31, 41, 55, 0.98);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.25rem;
    z-index: 99999;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
    display: none;
}

.autocomplete-dropdown.show {
    display: block;
}

.autocomplete-item {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.15s ease;
}

.autocomplete-item:hover,
.autocomplete-item.selected {
    background: rgba(102, 126, 234, 0.2);
    color: white;
}

.autocomplete-item.selected {
    background: rgba(102, 126, 234, 0.3);
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-highlight {
    color: #667eea;
    font-weight: bold;
}

.autocomplete-no-results {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
    text-align: center;
}

.clear-button {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    padding: 2px;
    display: none;
    transition: color 0.2s;
    z-index: 10;
}

.clear-button:hover {
    color: rgba(255, 255, 255, 0.8);
}

.autocomplete-container.has-value .clear-button {
    display: block;
}

/* Multi-day grid styles */
.multi-day-grid {
    overflow-x: auto;
    max-height: 70vh;
}

.worksheet-cell {
    min-width: 100px;
    position: sticky;
    left: 0;
    background: rgba(31, 41, 55, 0.95);
    backdrop-filter: blur(10px);
    z-index: 10;
}

/* Compact grid cells for more pages */
.grade-cell {
    width: 50px;
    height: 28px;
    font-size: 0.7rem;
    text-align: center;
    padding: 0.2rem;
}

.grade-cell:focus {
    transform: scale(1.05);
    z-index: 15;
    position: relative;
}

/* Updated status colors for 3-tier system */
.status-excellent { 
    background-color: rgba(34, 197, 94, 0.2) !important; 
    border-color: #22c55e !important;
}
.status-progress { 
    background-color: rgba(59, 130, 246, 0.2) !important; 
    border-color: #3b82f6 !important;
}
.status-needs-work { 
    background-color: rgba(251, 191, 36, 0.2) !important; 
    border-color: #f59e0b !important;
}
.status-empty { 
    background-color: rgba(107, 114, 128, 0.2) !important; 
    border-color: #6b7280 !important;
}

/* Page override indicators */
.page-override {
    border-color: #f97316 !important;
    background-color: rgba(249, 115, 22, 0.2) !important;
}

.page-calculated {
    border-color: rgba(59, 130, 246, 0.3) !important;
    background-color: rgba(59, 130, 246, 0.1) !important;
}

/* Quick date ranges */
.quick-range {
    transition: all 0.2s ease;
}

.quick-range:hover {
    background: rgba(102, 126, 234, 0.2);
    transform: translateY(-1px);
}

.quick-range.active {
    background: var(--primary);
    color: white;
}

/* Compact table headers */
.page-header {
    min-width: 85px;
    max-width: 85px;
    font-size: 0.65rem;
}
</style>

<!-- Header -->
<div class="glass-effect rounded-lg p-3 mb-4">
    <h1 class="text-xl font-bold gradient-text">Multi-Day Grading System</h1>
</div>

<!-- Multi-Day Controls -->
<div class="glass-effect rounded-lg p-3 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <!-- Date Range -->
        <div>
            <label for="startDate" class="block text-xs text-gray-300 mb-1 font-medium">START DATE</label>
            <input type="date" id="startDate" class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs focus:bg-white/15 focus:border-primary transition-all">
        </div>
        <div>
            <label for="endDate" class="block text-xs text-gray-300 mb-1 font-medium">END DATE</label>
            <input type="date" id="endDate" class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs focus:bg-white/15 focus:border-primary transition-all">
        </div>

        <!-- Student Selection -->
        <div>
            <label for="studentSearch" class="block text-xs text-gray-300 mb-1 font-medium">STUDENT</label>
            <div class="autocomplete-container" id="studentAutocomplete">
                <input type="text" 
                       id="studentSearch" 
                       class="autocomplete-input" 
                       placeholder="Type to search students..."
                       autocomplete="off">
                <button type="button" class="clear-button" id="clearStudent">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <input type="hidden" id="studentSelect" value="">
        </div>

        <!-- Subject Selection -->
        <div>
            <label for="subjectSelect" class="block text-xs text-gray-300 mb-1 font-medium">SUBJECT</label>
            <select id="subjectSelect" disabled class="w-full px-2 py-1 bg-gray-700 text-white border border-gray-600 rounded text-xs focus:outline-none focus:border-primary disabled:bg-gray-800 disabled:cursor-not-allowed">
                <option value="">Select Subject</option>
            </select>
        </div>

        <!-- Load Button -->
        <div class="flex items-end">
            <button type="button" id="loadData" disabled class="w-full bg-gradient-to-r from-primary to-secondary text-white px-3 py-1 rounded font-semibold text-xs hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed">
                Load Data
            </button>
        </div>
    </div>

    <!-- Quick Date Ranges -->
    <div class="mt-3 flex flex-wrap gap-2">
        <button type="button" class="quick-range px-2 py-1 bg-white/10 hover:bg-white/20 rounded text-xs transition-all" data-range="7">Last 7 Days</button>
        <button type="button" class="quick-range px-2 py-1 bg-white/10 hover:bg-white/20 rounded text-xs transition-all" data-range="14">Last 14 Days</button>
        <button type="button" class="quick-range px-2 py-1 bg-white/10 hover:bg-white/20 rounded text-xs transition-all" data-range="30">Last 30 Days</button>
        <button type="button" class="quick-range px-2 py-1 bg-white/10 hover:bg-white/20 rounded text-xs transition-all" data-range="current-week">This Week</button>
        <button type="button" class="quick-range px-2 py-1 bg-white/10 hover:bg-white/20 rounded text-xs transition-all" data-range="current-month">This Month</button>
    </div>
</div>

<!-- Student Info Section -->
<div id="studentInfoSection" class="hidden mb-2">
    <div class="glass-effect rounded-lg p-2">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-center">
            <div>
                <div class="text-xs text-gray-400 mb-1">STUDENT</div>
                <div id="studentName" class="gradient-text font-bold text-sm">-</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">SUBJECT</div>
                <div id="studentSubject" class="text-gray-200 font-semibold text-sm">-</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">LEVEL</div>
                <div id="studentLevel" class="text-gray-200 font-semibold text-sm">-</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">DATE RANGE</div>
                <div id="dateDisplay" class="text-gray-200 font-semibold text-sm">-</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">COMPLETION</div>
                <div id="completionRate" class="text-green-400 font-semibold text-sm">-</div>
            </div>
        </div>
    </div>
</div>

<!-- Multi-Day Grading Grid -->
<div id="gradingGrid" class="hidden">
    <div class="glass-effect rounded-lg overflow-hidden">
        <div class="p-3 border-b border-white/10">
            <div class="flex justify-between items-center">
                <div class="gradient-text font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Multi-Day Grading Grid
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="text-gray-400">Auto-save:</span>
                        <span id="autoSaveStatus" class="text-green-400">Enabled</span>
                    </div>
                    <button type="button" id="exportData" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-1 rounded font-semibold text-xs transition-all">
                        Export CSV
                    </button>
                    <button type="button" id="saveAllChanges" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-3 py-1 rounded font-semibold text-xs transition-all">
                        Save All Changes
                    </button>
                </div>
            </div>
        </div>

        <div class="multi-day-grid">
            <table class="w-full text-sm">
                <thead>
                    <tr id="headerRow">
                        <th class="worksheet-cell p-2 text-left border-r border-white/10 sticky top-0 bg-gray-800/95 backdrop-blur-sm">
                            Date
                        </th>
                        <!-- Dynamic headers will be inserted here -->
                    </tr>
                </thead>
                <tbody id="gridBody">
                    <!-- Dynamic content will be inserted here -->
                </tbody>
            </table>
        </div>

        <!-- Updated Legend -->
        <div class="p-3 border-t border-white/10 bg-white/5">
            <div class="flex items-center justify-center space-x-8 text-xs">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded status-excellent"></div>
                    <span class="text-gray-300">Excellent (10)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded status-progress"></div>
                    <span class="text-gray-300">Progress (7-9)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded status-needs-work"></div>
                    <span class="text-gray-300">Needs Work (1-6)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded status-empty"></div>
                    <span class="text-gray-300">Not Graded</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded page-override"></div>
                    <span class="text-gray-300">Page Override</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loadingIndicator" class="hidden text-center py-8">
    <div class="inline-flex flex-col items-center">
        <div class="loading-spinner w-8 h-8 border-4 border-primary border-t-transparent rounded-full mb-3"></div>
        <span class="text-gray-400 text-sm">Loading data...</span>
    </div>
</div>

<!-- Success/Error Messages -->
<div id="messageContainer" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

<!-- Autocomplete Dropdown Portal (rendered at body level) -->
<div id="autocompleteDropdown" class="autocomplete-dropdown"></div>

<script>

class MultiDayGradingManager {
    constructor() {
        this.data = null;
        this.unsavedChanges = new Map();
        this.autoSaveEnabled = true;
        this.saveTimeout = null;
        this.tabOrder = [];
        
        this.studentAutocomplete = new StudentAutocomplete(this);
        
        this.initializeEventListeners();
        this.setDefaultDates();
        this.loadStudents();
    }

    initializeEventListeners() {
        // Date range validation
        document.getElementById('startDate').addEventListener('change', () => this.validateDateRange());
        document.getElementById('endDate').addEventListener('change', () => this.validateDateRange());
        
        // Subject and load button updates
        document.getElementById('subjectSelect').addEventListener('change', () => this.updateLoadButton());
        document.getElementById('loadData').addEventListener('click', () => this.loadData());
        
        // Save and export
        document.getElementById('saveAllChanges').addEventListener('click', () => this.saveAllChanges());
        document.getElementById('exportData').addEventListener('click', () => this.exportData());
        
        // Quick date range buttons
        document.querySelectorAll('.quick-range').forEach(button => {
            button.addEventListener('click', (e) => {
                this.setQuickRange(e.target.dataset.range);
            });
        });
        
        // Global input handlers
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('grade-input')) {
                this.handleGradeInput(e.target);
            } else if (e.target.classList.contains('page-input')) {
                this.handlePageInput(e.target);
            } else if (e.target.classList.contains('time-input')) {
                this.handleTimeInput(e.target);
            }
        });

        // Tab navigation handler
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && e.target.classList.contains('grade-input')) {
                e.preventDefault();
                this.handleTabNavigation(e.target, e.shiftKey);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cw-hw-select')) {
                this.handleCwHwChange(e.target);
            } else if (e.target.classList.contains('hw-completed-select')) {
                this.handleHwCompletedChange(e.target);
            }
        });
    }

    handleTabNavigation(currentInput, shiftKey) {
        const currentIndex = this.tabOrder.findIndex(input => input === currentInput);
        
        if (currentIndex === -1) return;
        
        let nextIndex;
        if (shiftKey) {
            nextIndex = currentIndex > 0 ? currentIndex - 1 : this.tabOrder.length - 1;
        } else {
            nextIndex = currentIndex < this.tabOrder.length - 1 ? currentIndex + 1 : 0;
        }
        
        const nextInput = this.tabOrder[nextIndex];
        if (nextInput) {
            nextInput.focus();
            nextInput.select();
        }
    }

    buildTabOrder() {
        this.tabOrder = Array.from(document.querySelectorAll('.grade-input')).sort((a, b) => {
            const aRect = a.getBoundingClientRect();
            const bRect = b.getBoundingClientRect();
            
            // Sort by row first (top to bottom), then by column (left to right)
            if (Math.abs(aRect.top - bRect.top) > 5) {
                return aRect.top - bRect.top;
            }
            return aRect.left - bRect.left;
        });
    }

    setDefaultDates() {
        const today = new Date();
        const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
        
        document.getElementById('endDate').value = today.toISOString().split('T')[0];
        document.getElementById('startDate').value = weekAgo.toISOString().split('T')[0];
    }

    setQuickRange(range) {
        const today = new Date();
        let startDate, endDate = today;

        switch(range) {
            case '7':
                startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                break;
            case '14':
                startDate = new Date(today.getTime() - 14 * 24 * 60 * 60 * 1000);
                break;
            case '30':
                startDate = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                break;
            case 'current-week':
                const dayOfWeek = today.getDay();
                startDate = new Date(today.getTime() - dayOfWeek * 24 * 60 * 60 * 1000);
                break;
            case 'current-month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                break;
        }

        document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
        document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
        this.updateLoadButton();
        
        // Visual feedback
        document.querySelectorAll('.quick-range').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-range="${range}"]`).classList.add('active');
    }

    validateDateRange() {
        const startDate = new Date(document.getElementById('startDate').value);
        const endDate = new Date(document.getElementById('endDate').value);
        
        if (startDate > endDate) {
            document.getElementById('endDate').value = document.getElementById('startDate').value;
        }
        this.updateLoadButton();
    }

    async loadStudents() {
        try {
            const response = await fetch('/api/grading/students');
            const students = await response.json();
            this.studentAutocomplete.setStudents(students);
        } catch (error) {
            this.showError('Failed to load students');
        }
    }

    async onStudentChange() {
        const studentName = document.getElementById('studentSelect').value;
        const subjectSelect = document.getElementById('subjectSelect');
        const loadBtn = document.getElementById('loadData');
        
        subjectSelect.disabled = !studentName;
        subjectSelect.innerHTML = '<option value="">Select Subject</option>';
        loadBtn.disabled = true;

        if (studentName) {
            try {
                const response = await fetch(`/api/grading/subjects?student_name=${encodeURIComponent(studentName)}`);
                const subjects = await response.json();
                
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject;
                    option.textContent = subject;
                    subjectSelect.appendChild(option);
                });
                subjectSelect.disabled = false;
                this.updateLoadButton();
            } catch (error) {
                this.showError('Failed to load subjects');
            }
        }
    }

    updateLoadButton() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const student = document.getElementById('studentSelect').value;
        const subject = document.getElementById('subjectSelect').value;
        
        document.getElementById('loadData').disabled = !(startDate && endDate && student && subject);
    }

    async loadData() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const student = document.getElementById('studentSelect').value;
        const subject = document.getElementById('subjectSelect').value;

        if (!startDate || !endDate || !student || !subject) {
            this.showError('Please fill in all fields');
            return;
        }

        this.showLoading(true);
        
        try {
            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate,
                student_name: student,
                subject: subject
            });

            const response = await fetch(`/api/grading/multi-day-data?${params}`);
            const result = await response.json();

            if (result.success) {
                this.data = result.data;
                this.displayData();
            } else {
                this.showError(result.error || 'Failed to load data');
            }
            
        } catch (error) {
            this.showError('Failed to load data: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    displayData() {
        if (!this.data) return;

        // Update info section
        document.getElementById('studentName').textContent = this.data.student || '-';
        document.getElementById('studentSubject').textContent = this.data.subject || '-';
        document.getElementById('studentLevel').textContent = 'Multi-Level';
        document.getElementById('dateDisplay').textContent = this.data.date_range || '-';
        
        this.buildGrid();
        this.updateCompletionRate();
        this.buildTabOrder();

        document.getElementById('studentInfoSection').classList.remove('hidden');
        document.getElementById('gradingGrid').classList.remove('hidden');
    }

    buildGrid() {
        const headerRow = document.getElementById('headerRow');
        const gridBody = document.getElementById('gridBody');

        // Clear existing content
        headerRow.innerHTML = '';
        gridBody.innerHTML = '';

        if (!this.data || !this.data.dates) {
            console.error('No data available');
            return;
        }

        const lessonPlans = this.data.lesson_plans || {};
        
        // Increased max pages to 10 for better visibility
        let maxPages = 10;
        Object.values(lessonPlans).forEach(lessonPlan => {
            if (lessonPlan && lessonPlan.expected_pages) {
                maxPages = Math.max(maxPages, lessonPlan.expected_pages.length);
            }
        });
        
        // Cap at 10 for UI purposes
        maxPages = Math.min(maxPages, 10);

        // Build headers
        headerRow.innerHTML = `
            <th class="p-2 border-r border-white/10 text-center sticky top-0 bg-gray-800/95 backdrop-blur-sm text-xs font-medium min-w-[100px]">Date</th>
            <th class="p-2 border-r border-white/10 text-center sticky top-0 bg-gray-800/95 backdrop-blur-sm text-xs font-medium min-w-[60px]">CW/HW</th>
            <th class="p-2 border-r border-white/10 text-center sticky top-0 bg-gray-800/95 backdrop-blur-sm text-xs font-medium min-w-[60px]">Time</th>
            <th class="p-2 border-r border-white/10 text-center sticky top-0 bg-gray-800/95 backdrop-blur-sm text-xs font-medium min-w-[70px]">HW Done</th>
        `;

        // Add compact page headers
        for (let i = 1; i <= maxPages; i++) {
            const th = document.createElement('th');
            th.className = 'page-header p-1 border-r border-white/10 text-center sticky top-0 bg-gray-800/95 backdrop-blur-sm';
            th.innerHTML = `
                <div class="font-semibold text-xs">Page ${i}</div>
                <div class="text-xs text-blue-300">Page #</div>
                <div class="text-xs text-green-300">Grade</div>
            `;
            headerRow.appendChild(th);
        }

        // Build rows
        this.data.dates.forEach(dateInfo => {
            const lessonPlan = lessonPlans[dateInfo.formatted];
            
            const row = document.createElement('tr');
            row.className = 'border-b border-white/10 hover:bg-white/5';

            // Compact date cell
            row.innerHTML = `
                <td class="p-2 border-r border-white/10 text-center bg-gray-700/30">
                    <div class="font-bold text-white text-xs">${dateInfo.short_date}</div>
                    <div class="text-xs text-gray-400">${dateInfo.day_of_week}</div>
                </td>
            `;

            if (!lessonPlan) {
                // No lesson plan
                row.innerHTML += `
                    <td class="p-1 border-r border-white/10 text-center text-gray-500" colspan="${3 + maxPages}">No lesson plan</td>
                `;
            } else {
                // Compact CW/HW cell
                const cwHwCell = document.createElement('td');
                cwHwCell.className = 'p-1 border-r border-white/10 text-center';
                cwHwCell.innerHTML = `
                    <select class="w-full px-2 py-1 bg-gray-700 text-white border border-gray-600 rounded text-xs focus:outline-none focus:border-primary cw-hw-select" 
                            data-lesson-plan="${lessonPlan.id}">
                        <option value="Y" ${lessonPlan.is_class_day === 'Y' ? 'selected' : ''}>CW</option>
                        <option value="N" ${lessonPlan.is_class_day === 'N' ? 'selected' : ''}>HW</option>
                    </select>
                `;
                row.appendChild(cwHwCell);

                // Compact time cell
                const timeCell = document.createElement('td');
                timeCell.className = 'p-1 border-r border-white/10 text-center';
                timeCell.innerHTML = `
                    <input type="number" class="w-full bg-white/10 border border-white/20 rounded text-white text-xs p-1 text-center time-input"
                           value="${lessonPlan.time || 10}" min="1" max="60"
                           data-lesson-plan="${lessonPlan.id}">
                `;
                row.appendChild(timeCell);

                // Compact HW completed cell
                const hwCell = document.createElement('td');
                hwCell.className = 'p-1 border-r border-white/10 text-center';
                hwCell.innerHTML = `
                    <select class="w-full px-2 py-1 bg-gray-700 text-white border border-gray-600 rounded text-xs focus:outline-none focus:border-primary hw-completed-select"
                            data-lesson-plan="${lessonPlan.id}">
                        <option value="Y" ${lessonPlan.hw_completed === 'Y' ? 'selected' : ''}>Y</option>
                        <option value="N" ${lessonPlan.hw_completed === 'N' ? 'selected' : ''}>N</option>
                    </select>
                `;
                row.appendChild(hwCell);

                // Compact page cells
                for (let i = 1; i <= maxPages; i++) {
                    const pageOverrides = lessonPlan.page_overrides || {};
                    const pageGrades = lessonPlan.page_grades || {};
                    const expectedPages = lessonPlan.expected_pages || [];
                    
                    const pageCell = document.createElement('td');
                    pageCell.className = 'p-1 border-r border-white/10 text-center';
                    
                    if (i <= expectedPages.length) {
                        const expectedPage = expectedPages[i - 1];
                        const override = pageOverrides[i];
                        const existing = pageGrades[i];
                        
                        // Determine actual page to display
                        let actualPage = expectedPage;
                        let isOverride = false;
                        
                        if (override) {
                            actualPage = override.custom_page;
                            isOverride = true;
                        }
                        
                        const gradeValue = existing ? existing.grade : '';
                        const gradeStatus = this.getGradeStatus(gradeValue);

                        const pageInputClass = isOverride ? 'page-override' : 'page-calculated';

                        pageCell.innerHTML = `
                            <div class="space-y-1">
                                <input type="number" 
                                       class="w-full bg-blue-900/20 border rounded text-white text-xs p-1 text-center page-input ${pageInputClass}"
                                       value="${actualPage}" min="1" max="200" placeholder="Page"
                                       title="${isOverride ? 'Custom page (overridden)' : 'Expected page'}"
                                       data-lesson-plan="${lessonPlan.id}" 
                                       data-page-position="${i}"
                                       data-expected-page="${expectedPage}">
                                <input type="number" 
                                       class="w-full bg-green-900/20 border rounded text-white text-xs p-1 text-center grade-input ${gradeStatus}"
                                       value="${parseInt(gradeValue)}" min="0" max="10" step="1" placeholder="Grade"
                                       data-lesson-plan="${lessonPlan.id}" 
                                       data-page-position="${i}">
                            </div>
                        `;
                    } else {
                        pageCell.innerHTML = `<div class="text-xs text-gray-500">-</div>`;
                    }
                    
                    row.appendChild(pageCell);
                }
            }

            gridBody.appendChild(row);
        });
    }

    getGradeStatus(gradeValue) {
        if (gradeValue === '' || gradeValue === null || gradeValue === undefined || isNaN(parseInt(gradeValue))) {
            return 'status-empty';
        }
        
        const grade = parseInt(gradeValue);
        if (grade === 10) {
            return 'status-excellent';
        } else if (grade >= 7 && grade <= 9) {
            return 'status-progress';
        } else if (grade >= 1 && grade <= 6) {
            return 'status-needs-work';
        } else {
            return 'status-empty';
        }
    }

    handlePageInput(input) {
        const lessonPlanId = input.dataset.lessonPlan;
        const pagePosition = input.dataset.pagePosition;
        const expectedPage = parseInt(input.dataset.expectedPage);
        const customPage = parseInt(input.value) || 0;

        if (customPage !== expectedPage && customPage > 0) {
            // Save page override
            this.savePageOverride(lessonPlanId, pagePosition, expectedPage, customPage);
            input.classList.remove('page-calculated');
            input.classList.add('page-override');
            input.title = 'Custom page (overridden)';
        } else if (customPage === expectedPage) {
            // Remove override
            this.removePageOverride(lessonPlanId, pagePosition);
            input.classList.remove('page-override');
            input.classList.add('page-calculated');
            input.title = 'Expected page';
        }
    }

    handleGradeInput(input) {
        const lessonPlanId = input.dataset.lessonPlan;
        const pagePosition = input.dataset.pagePosition;
        const value = input.value;
        
        // Update visual feedback with new 3-tier system - ONLY for grade input
        input.classList.remove('status-excellent', 'status-progress', 'status-needs-work', 'status-empty');
        input.classList.add(this.getGradeStatus(value));

        // Save grade to page_overrides table
        this.saveGradeToPageOverride(lessonPlanId, pagePosition, value);

        // Auto-save
        if (this.autoSaveEnabled) {
            this.scheduleAutoSave();
        }

        this.updateCompletionRate();
    }

    handleTimeInput(input) {
        const lessonPlanId = input.dataset.lessonPlan;
        const value = input.value;
        
        const changeKey = `time-${lessonPlanId}`;
        this.unsavedChanges.set(changeKey, {
            lesson_plan_id: parseInt(lessonPlanId),
            field: 'time',
            value: parseInt(value) || 0
        });
        
        if (this.autoSaveEnabled) {
            this.scheduleAutoSave();
        }
    }

    handleCwHwChange(select) {
        const lessonPlanId = select.dataset.lessonPlan;
        const value = select.value;
        
        const changeKey = `cw-hw-${lessonPlanId}`;
        this.unsavedChanges.set(changeKey, {
            lesson_plan_id: parseInt(lessonPlanId),
            field: 'is_class_day',
            value: value
        });
        
        if (this.autoSaveEnabled) {
            this.scheduleAutoSave();
        }
    }

    handleHwCompletedChange(select) {
        const lessonPlanId = select.dataset.lessonPlan;
        const value = select.value;
        
        const changeKey = `hw-completed-${lessonPlanId}`;
        this.unsavedChanges.set(changeKey, {
            lesson_plan_id: parseInt(lessonPlanId),
            field: 'hw_completed',
            value: value
        });
        
        if (this.autoSaveEnabled) {
            this.scheduleAutoSave();
        }
    }

    async savePageOverride(lessonPlanId, pagePosition, expectedPage, customPage) {
        try {
            const response = await fetch('/api/grading/save-page-override', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    lesson_plan_id: parseInt(lessonPlanId),
                    page_position: parseInt(pagePosition),
                    original_page: expectedPage,
                    custom_page: customPage
                })
            });

            const result = await response.json();

            if (!result.success) {
                console.error('Failed to save page override:', result.error);
                this.showError('Failed to save page override');
            }

        } catch (error) {
            console.error('Error saving page override:', error);
            this.showError('Error saving page override');
        }
    }

    async removePageOverride(lessonPlanId, pagePosition) {
        try {
            const response = await fetch('/api/grading/remove-page-override', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    lesson_plan_id: parseInt(lessonPlanId),
                    page_position: parseInt(pagePosition)
                })
            });

            const result = await response.json();

            if (!result.success) {
                console.error('Failed to remove page override:', result.error);
            }

        } catch (error) {
            console.error('Error removing page override:', error);
        }
    }

    async saveGradeToPageOverride(lessonPlanId, pagePosition, grade) {
        try {
            const response = await fetch('/api/grading/save-grade-to-override', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    lesson_plan_id: parseInt(lessonPlanId),
                    page_position: parseInt(pagePosition),
                    grade: grade ? parseInt(grade) : null
                })
            });

            const result = await response.json();

            if (!result.success) {
                console.error('Failed to save grade:', result.error);
            }

        } catch (error) {
            console.error('Error saving grade:', error);
        }
    }

    scheduleAutoSave() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveTimeout = setTimeout(() => {
            this.autoSave();
        }, 2000);
    }

    async autoSave() {
        if (this.unsavedChanges.size === 0) return;
        
        document.getElementById('autoSaveStatus').textContent = 'Saving...';
        document.getElementById('autoSaveStatus').className = 'text-yellow-400';
        
        try {
            const changes = Array.from(this.unsavedChanges.values());
            
            const response = await fetch('/api/grading/bulk-save-lesson-plan-changes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    changes: changes
                })
            });

            const result = await response.json();

            if (result.success) {
                this.unsavedChanges.clear();
                document.getElementById('autoSaveStatus').textContent = 'Saved';
                document.getElementById('autoSaveStatus').className = 'text-green-400';
                
                setTimeout(() => {
                    document.getElementById('autoSaveStatus').textContent = 'Enabled';
                }, 2000);
            } else {
                throw new Error(result.error || 'Failed to auto-save');
            }
            
        } catch (error) {
            document.getElementById('autoSaveStatus').textContent = 'Error';
            document.getElementById('autoSaveStatus').className = 'text-red-400';
        }
    }

    async saveAllChanges() {
        const saveBtn = document.getElementById('saveAllChanges');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            // Save lesson plan changes
            if (this.unsavedChanges.size > 0) {
                await this.autoSave();
            }
            
            this.showSuccess('All changes saved successfully');
            
        } catch (error) {
            this.showError('Failed to save changes: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save All Changes';
        }
    }

    async exportData() {
        if (!this.data) {
            this.showError('No data to export');
            return;
        }

        try {
            const params = new URLSearchParams({
                start_date: document.getElementById('startDate').value,
                end_date: document.getElementById('endDate').value,
                student_name: this.data.student,
                subject: this.data.subject
            });

            const response = await fetch(`/api/grading/export-multi-day-grades?${params}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `${this.data.student}_${this.data.subject}_grades.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showSuccess('Data exported successfully');
            } else {
                throw new Error('Failed to export data');
            }
            
        } catch (error) {
            this.showError('Failed to export data: ' + error.message);
        }
    }

    updateCompletionRate() {
        if (!this.data) return;
        
        let totalCells = 0;
        let filledCells = 0;
        
        Object.values(this.data.lesson_plans || {}).forEach(lessonPlan => {
            if (lessonPlan && lessonPlan.expected_pages) {
                totalCells += lessonPlan.expected_pages.length;
                
                const pageGrades = lessonPlan.page_grades || {};
                filledCells += Object.values(pageGrades).filter(grade => 
                    grade && grade.grade !== null && grade.grade !== undefined && grade.grade !== ''
                ).length;
            }
        });
        
        const completionRate = totalCells > 0 ? Math.round((filledCells / totalCells) * 100) : 0;
        document.getElementById('completionRate').textContent = `${completionRate}%`;
    }

    showLoading(show) {
        document.getElementById('loadingIndicator').classList.toggle('hidden', !show);
        
        if (show) {
            document.getElementById('gradingGrid').classList.add('hidden');
            document.getElementById('studentInfoSection').classList.add('hidden');
        }
    }

    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    showError(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        const container = document.getElementById('messageContainer');
        const messageDiv = document.createElement('div');
        
        const bgColor = type === 'success' ? 'bg-green-500/20 border-green-500/30 text-green-300' : 'bg-red-500/20 border-red-500/30 text-red-300';
        
        messageDiv.className = `glass-effect rounded-lg p-3 border ${bgColor} text-sm font-medium`;
        messageDiv.textContent = message;
        
        container.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 4000);
    }
}

// StudentAutocomplete class with fixed positioning
class StudentAutocomplete {
    constructor(manager) {
        this.manager = manager;
        this.input = document.getElementById('studentSearch');
        this.hiddenInput = document.getElementById('studentSelect');
        this.dropdown = document.getElementById('autocompleteDropdown');
        this.container = document.getElementById('studentAutocomplete');
        this.clearButton = document.getElementById('clearStudent');
        this.students = [];
        this.filteredStudents = [];
        this.selectedIndex = -1;
        this.selectedValue = '';
        
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('focus', () => this.handleFocus());
        this.input.addEventListener('blur', (e) => this.handleBlur(e));
        this.input.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        this.clearButton.addEventListener('click', () => this.clearSelection());
        
        this.dropdown.addEventListener('mousedown', (e) => {
            e.preventDefault();
        });

        window.addEventListener('scroll', () => {
            if (this.dropdown.classList.contains('show')) {
                this.positionDropdown();
            }
        });
        
        window.addEventListener('resize', () => {
            if (this.dropdown.classList.contains('show')) {
                this.positionDropdown();
            }
        });

        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.hideDropdown();
            }
        });
    }

    setStudents(students) {
        this.students = students;
    }

    handleInput(e) {
        const query = e.target.value.toLowerCase().trim();
        
        if (query.length === 0) {
            this.filteredStudents = [...this.students];
            this.container.classList.remove('has-value');
        } else {
            this.filteredStudents = this.students.filter(student => 
                student.toLowerCase().includes(query)
            );
            this.container.classList.add('has-value');
        }
        
        if (this.selectedValue !== e.target.value) {
            this.hiddenInput.value = '';
            this.selectedValue = '';
            this.manager.onStudentChange();
        }
        
        this.selectedIndex = -1;
        this.renderDropdown(query);
    }

    handleFocus() {
        if (this.input.value.length === 0) {
            this.filteredStudents = [...this.students];
        } else {
            const query = this.input.value.toLowerCase().trim();
            this.filteredStudents = this.students.filter(student => 
                student.toLowerCase().includes(query)
            );
        }
        this.renderDropdown(this.input.value.toLowerCase().trim());
    }

    handleBlur(e) {
        setTimeout(() => {
            if (!this.dropdown.matches(':hover')) {
                this.hideDropdown();
            }
        }, 200);
    }

    handleKeyDown(e) {
        if (this.dropdown.classList.contains('show')) {
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.selectNext();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.selectPrevious();
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (this.selectedIndex >= 0 && this.selectedIndex < this.filteredStudents.length) {
                        this.selectStudent(this.filteredStudents[this.selectedIndex]);
                    }
                    break;
                case 'Escape':
                    e.preventDefault();
                    this.hideDropdown();
                    break;
            }
        }
    }

    selectNext() {
        if (this.selectedIndex < this.filteredStudents.length - 1) {
            this.selectedIndex++;
            this.updateSelection();
        }
    }

    selectPrevious() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
            this.updateSelection();
        } else if (this.selectedIndex === 0) {
            this.selectedIndex = -1;
            this.updateSelection();
        }
    }

    updateSelection() {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
        
        if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
            items[this.selectedIndex].scrollIntoView({
                block: 'nearest'
            });
        }
    }

    positionDropdown() {
        const inputRect = this.input.getBoundingClientRect();
        const dropdownHeight = Math.min(200, this.filteredStudents.length * 40);
        const spaceBelow = window.innerHeight - inputRect.bottom;
        const spaceAbove = inputRect.top;
        
        let top = inputRect.bottom + window.scrollY;
        
        if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
            top = inputRect.top + window.scrollY - dropdownHeight;
        }
        
        this.dropdown.style.top = top + 'px';
        this.dropdown.style.left = inputRect.left + window.scrollX + 'px';
        this.dropdown.style.width = inputRect.width + 'px';
    }

    renderDropdown(query = '') {
        if (this.filteredStudents.length === 0 && query.length > 0) {
            this.dropdown.innerHTML = '<div class="autocomplete-no-results">No students found</div>';
            this.dropdown.classList.add('show');
            this.positionDropdown();
            return;
        }
        
        if (this.filteredStudents.length === 0) {
            this.hideDropdown();
            return;
        }
        
        const html = this.filteredStudents.map((student, index) => {
            const highlighted = query ? this.highlightMatch(student, query) : student;
            return `
                <div class="autocomplete-item ${index === this.selectedIndex ? 'selected' : ''}" 
                     data-value="${student}">
                    ${highlighted}
                </div>
            `;
        }).join('');
        
        this.dropdown.innerHTML = html;
        this.dropdown.classList.add('show');
        this.positionDropdown();
        
        this.dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectStudent(item.dataset.value);
            });
        });
    }

    highlightMatch(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<span class="autocomplete-highlight">$1</span>');
    }

    selectStudent(student) {
        this.input.value = student;
        this.hiddenInput.value = student;
        this.selectedValue = student;
        this.container.classList.add('has-value');
        this.hideDropdown();
        this.manager.onStudentChange();
    }

    clearSelection() {
        this.input.value = '';
        this.hiddenInput.value = '';
        this.selectedValue = '';
        this.container.classList.remove('has-value');
        this.hideDropdown();
        this.manager.onStudentChange();
    }

    hideDropdown() {
        this.dropdown.classList.remove('show');
        this.selectedIndex = -1;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new MultiDayGradingManager();
});
</script>
@endsection