<!-- resources/views/student-progress/index.blade.php - Complete Updated with Pages and Repeats System -->
@extends('layouts.app')

@section('title', 'Lesson Plan Tracker')

@section('content')
<style>
/* Custom styles to fix dropdown z-index issues */
.dropdown-container {
    position: relative;
    z-index: auto;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: rgba(31, 41, 55, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 0.375rem;
    margin-top: 0.25rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    z-index: 9999;
    max-height: 15rem;
    overflow-y: auto;
}

.dropdown-menu.hidden {
    display: none;
}

.results-container {
    position: relative;
    z-index: 1;
}

.table-container {
    position: relative;
    z-index: 1;
}

/* Ensure fixed dropdowns appear above everything */
.dropdown-fixed {
    position: fixed !important;
    z-index: 99999 !important;
}

/* Role-based styling for read-only users */
.readonly-mode {
    opacity: 0.6;
    cursor: not-allowed;
}

.readonly-mode input,
.readonly-mode button {
    pointer-events: none;
}

/* Styles for the repeat section */
.repeat-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.repeat-input-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.repeat-input-group label {
    font-size: 10px;
    color: #9ca3af;
    font-weight: 500;
}

.repeat-input-group input {
    width: 40px;
}

@media (max-width: 768px) {
    .repeat-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    
    .repeat-input-group {
        flex-direction: row;
        justify-content: space-between;
    }
    
    .repeat-input-group input {
        width: 50px;
    }
    
}
</style>

<!-- User Role Info -->
@if(auth()->user()->isViewer())
<div class="glass-effect rounded-lg p-3 mb-4 border border-yellow-500/30 bg-yellow-500/5">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <span class="text-yellow-200 text-sm font-medium">
            Read-Only Access: You can view data but cannot make changes. Contact an administrator for edit permissions.
        </span>
    </div>
</div>
@endif

<!-- Main Controls -->
<div class="glass-effect rounded-lg p-4 mb-4" style="position: relative; z-index: 10;">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        <!-- Date Range Filter -->
        <div class="dropdown-container">
            <label class="block text-xs font-semibold text-gray-300 mb-1 uppercase tracking-wide">Date Range Filter</label>
            <button id="dateRangeTrigger" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all flex justify-between items-center min-h-[32px]">
                <span id="dateRangeText" class="flex-1 text-left truncate">Select date range...</span>
                <button id="dateRangeClear" class="hidden ml-2 bg-red-500/60 hover:bg-red-500/80 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">&times;</button>
            </button>
        </div>

        <!-- Multi-select Month Filter -->
        <div class="dropdown-container" id="monthMultiSelect">
            <label class="block text-xs font-semibold text-gray-300 mb-1 uppercase tracking-wide">Months (if no date range)</label>
            <div id="monthDisplay" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm cursor-pointer focus:bg-white/15 focus:border-primary transition-all flex justify-between items-center min-h-[32px]">
                <span id="monthSelectedText" class="flex-1 truncate">Select months...</span>
                <span class="text-xs text-gray-400">▼</span>
            </div>
            <div id="monthDropdown" class="dropdown-menu hidden">
                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                <div class="multiselect-option flex items-center gap-2 px-3 py-2 hover:bg-primary/20 cursor-pointer text-sm border-b border-white/5 last:border-b-0">
                    <input type="checkbox" id="month_{{ strtolower($month) }}" value="{{ $month }}" class="accent-primary">
                    <label for="month_{{ strtolower($month) }}" class="cursor-pointer">{{ $month }}</label>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Year Input -->
        <div>
            <label for="yearInput" class="block text-xs font-semibold text-gray-300 mb-1 uppercase tracking-wide">Year</label>
            <input type="number" id="yearInput" min="2020" max="2030" value="{{ date('Y') }}" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
        </div>

        <!-- Student Name Search -->
        <div class="dropdown-container">
            <label for="studentNameSearch" class="block text-xs font-semibold text-gray-300 mb-1 uppercase tracking-wide">Search Student Name</label>
            <input type="text" id="studentNameSearch" placeholder="Type student name..." class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all" autocomplete="off">
            <div id="studentNameDropdown" class="dropdown-menu hidden"></div>
        </div>

        <!-- Subject Filter -->
        <div>
            <label for="subjectFilter" class="block text-xs font-semibold text-gray-300 mb-1 uppercase tracking-wide">Subject</label>
            <select id="subjectFilter" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                <option value="">All Subjects</option>
            </select>
        </div>

        <!-- Student & Subject Filter -->
        <div>
            <label for="studentFilter" class="block text-xs font-semibold text-gray-300 mb-1 uppercase tracking-wide">Student & Subject</label>
            <select id="studentFilter" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                <option value="">All Students</option>
            </select>
        </div>
    </div>
    
    <div class="flex justify-between items-center mt-4">
        <button id="loadLessonPlan" class="bg-gradient-to-r from-primary to-secondary text-white px-6 py-2 rounded font-semibold text-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
            Load Lesson Plan
        </button>
        
        @if(auth()->user()->canEdit())
        <div class="flex gap-2">
            <a href="{{ route('admin.setup') }}" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded font-medium text-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
                Setup Data
            </a>
            
            <a href="{{ route('admin.delete') }}" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded font-medium text-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
                Delete Data
            </a>
            <a href="{{ route('admin.activity-logs.index') }}" 
   class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white transition-all {{ request()->routeIs('admin.activity-logs.*') ? 'bg-primary/20 text-primary' : '' }}">
    Activity Logs
</a>

<a href="{{ route('admin.monthly-report.index') }}" 
   class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white transition-all {{ request()->routeIs('admin.monthly-report.*') ? 'bg-primary/20 text-primary' : '' }}">
    Monthly Report
</a>

        </div>
        @endif



    </div>
</div>

<!-- Date Range Popup -->
<div id="popupOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden"></div>
<div id="dateRangePopup" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-800/98 backdrop-blur-lg border border-white/30 rounded-lg p-6 z-50 hidden min-w-80 shadow-2xl">
    <div class="flex justify-between items-center mb-4 pb-2 border-b border-white/10">
        <h3 class="text-lg font-semibold text-gray-200">Select Date Range</h3>
        <button id="popupClose" class="text-gray-400 hover:text-gray-200 text-xl p-1 rounded hover:bg-white/10 transition-all">&times;</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label for="startDate" class="block text-sm text-gray-300 font-medium mb-2">Start Date</label>
            <input type="date" id="startDate" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
        </div>
        <div>
            <label for="endDate" class="block text-sm text-gray-300 font-medium mb-2">End Date</label>
            <input type="date" id="endDate" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
        </div>
    </div>
    <div class="flex gap-3 justify-end">
        <button id="popupCancel" class="px-4 py-2 bg-white/10 border border-white/20 rounded text-sm hover:bg-white/15 transition-all">Cancel</button>
        <button id="popupApply" class="px-4 py-2 bg-gradient-to-r from-primary to-secondary text-white rounded text-sm font-medium hover:shadow-lg transition-all">Apply</button>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loading" class="hidden text-center py-4 text-gray-400 text-sm">
    <span class="loading-spinner">Loading data...</span>
</div>

<!-- Results Container -->
<div id="results" class="results-container"></div>
@endsection

@push('scripts')
<script>
// Global variables
let currentData = [];
let allStudentNames = [];
let allSubjects = [];
let selectedMonths = [];
let allStudentSubjects = [];
let selectedDateRange = null;

// User permissions
const canEdit = {{ auth()->user()->canEdit() ? 'true' : 'false' }};
const userRole = '{{ auth()->user()->role }}';


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

async function initializeApp() {
    initializeMultiSelect();
    initializeDateRangeFilter();
    await loadStudents();
    await loadSubjects();
    setupAutocomplete();
    
    // Auto-load lesson plan for current month
  /*  setTimeout(() => {
      //  loadLessonPlan();
    }, 1000);
    */
}

function initializeDateRangeFilter() {
    document.getElementById('dateRangeTrigger').addEventListener('click', openDateRangePopup);
    document.getElementById('dateRangeClear').addEventListener('click', clearDateRange);
    document.getElementById('popupClose').addEventListener('click', closeDateRangePopup);
    document.getElementById('popupCancel').addEventListener('click', closeDateRangePopup);
    document.getElementById('popupApply').addEventListener('click', applyDateRange);
    document.getElementById('popupOverlay').addEventListener('click', closeDateRangePopup);
}

function openDateRangePopup() {
    const popup = document.getElementById('dateRangePopup');
    const overlay = document.getElementById('popupOverlay');
    closeAllDropdowns();
    popup.classList.remove('hidden');
    overlay.classList.remove('hidden');
    if (selectedDateRange) {
        document.getElementById('startDate').value = selectedDateRange.start;
        document.getElementById('endDate').value = selectedDateRange.end;
    }
}

function closeDateRangePopup() {
    document.getElementById('dateRangePopup').classList.add('hidden');
    document.getElementById('popupOverlay').classList.add('hidden');
}

async function applyDateRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    if (!startDate || !endDate) {
        showError('Please select both start and end dates');
        return;
    }
    if (new Date(startDate) > new Date(endDate)) {
        showError('Start date must be before end date');
        return;
    }
    selectedDateRange = { start: startDate, end: endDate };
    updateDateRangeDisplay();
    updateMonthSelectState();
    closeDateRangePopup();
    setTimeout(() => { loadLessonPlan(); }, 300);
}

function clearDateRange(event) {
    if (event) event.stopPropagation();
    selectedDateRange = null;
    updateDateRangeDisplay();
    updateMonthSelectState();
    setTimeout(() => { loadLessonPlan(); }, 300);
}

function updateDateRangeDisplay() {
    const trigger = document.getElementById('dateRangeTrigger');
    const text = document.getElementById('dateRangeText');
    const clearBtn = document.getElementById('dateRangeClear');
    if (selectedDateRange) {
        const startFormatted = new Date(selectedDateRange.start).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const endFormatted = new Date(selectedDateRange.end).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        text.textContent = `${startFormatted} - ${endFormatted}`;
        trigger.classList.add('border-green-500', 'bg-green-500/10');
        clearBtn.classList.remove('hidden');
    } else {
        text.textContent = 'Select date range...';
        trigger.classList.remove('border-green-500', 'bg-green-500/10');
        clearBtn.classList.add('hidden');
    }
}

function updateMonthSelectState() {
    const monthContainer = document.getElementById('monthMultiSelect');
    const monthDisplay = document.getElementById('monthDisplay');
    if (selectedDateRange) {
        monthDisplay.classList.add('opacity-50', 'cursor-not-allowed');
        monthContainer.style.pointerEvents = 'none';
    } else {
        monthDisplay.classList.remove('opacity-50', 'cursor-not-allowed');
        monthContainer.style.pointerEvents = 'auto';
    }
}

function closeAllDropdowns() {
    document.getElementById('monthDropdown').classList.add('hidden');
    document.getElementById('studentNameDropdown').classList.add('hidden');
    const monthDropdown = document.getElementById('monthDropdown');
    const studentDropdown = document.getElementById('studentNameDropdown');
    monthDropdown.classList.remove('dropdown-fixed');
    studentDropdown.classList.remove('dropdown-fixed');
    monthDropdown.style.position = '';
    monthDropdown.style.top = '';
    monthDropdown.style.left = '';
    monthDropdown.style.width = '';
    studentDropdown.style.position = '';
    studentDropdown.style.top = '';
    studentDropdown.style.left = '';
    studentDropdown.style.width = '';
}

function initializeMultiSelect() {
    const container = document.getElementById('monthMultiSelect');
    const display = document.getElementById('monthDisplay');
    const dropdown = document.getElementById('monthDropdown');
    const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
    
    display.addEventListener('click', function(e) {
        e.stopPropagation();
        if (display.classList.contains('cursor-not-allowed')) {
            return;
        }
        const isOpen = !dropdown.classList.contains('hidden');
        document.getElementById('studentNameDropdown').classList.add('hidden');
        
        if (!isOpen) {
            const rect = display.getBoundingClientRect();
            const dropdownHeight = 240;
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - rect.bottom;
            
            if (spaceBelow < dropdownHeight) {
                dropdown.classList.add('dropdown-fixed');
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 4) + 'px';
                dropdown.style.left = rect.left + 'px';
                dropdown.style.width = rect.width + 'px';
                dropdown.style.zIndex = '99999';
            } else {
                dropdown.classList.remove('dropdown-fixed');
                dropdown.style.position = '';
                dropdown.style.top = '';
                dropdown.style.left = '';
                dropdown.style.width = '';
            }
        }
        dropdown.classList.toggle('hidden', isOpen);
    });
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedMonths();
            updateMonthDisplay();
            if (selectedMonths.length > 0 && !selectedDateRange) {
                setTimeout(() => { loadLessonPlan(); }, 300);
            }
        });
    });
    
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    const currentMonth = new Date().toLocaleString('default', { month: 'long' });
    const currentMonthCheckbox = document.getElementById(`month_${currentMonth.toLowerCase()}`);
    if (currentMonthCheckbox) {
        currentMonthCheckbox.checked = true;
        updateSelectedMonths();
        updateMonthDisplay();
    }
}

function updateSelectedMonths() {
    const checkboxes = document.querySelectorAll('#monthDropdown input[type="checkbox"]:checked');
    selectedMonths = Array.from(checkboxes).map(cb => cb.value);
}

function updateMonthDisplay() {
    const selectedText = document.getElementById('monthSelectedText');
    if (selectedMonths.length === 0) {
        selectedText.innerHTML = 'Select months...';
    } else if (selectedMonths.length === 1) {
        selectedText.innerHTML = `${selectedMonths[0]} <span class="ml-2 bg-gradient-to-r from-primary to-secondary text-white px-2 py-0.5 rounded-full text-xs font-semibold">1</span>`;
    } else if (selectedMonths.length <= 3) {
        selectedText.innerHTML = `${selectedMonths.join(', ')} <span class="ml-2 bg-gradient-to-r from-primary to-secondary text-white px-2 py-0.5 rounded-full text-xs font-semibold">${selectedMonths.length}</span>`;
    } else {
        selectedText.innerHTML = `${selectedMonths.length} months selected <span class="ml-2 bg-gradient-to-r from-primary to-secondary text-white px-2 py-0.5 rounded-full text-xs font-semibold">${selectedMonths.length}</span>`;
    }
}

async function loadStudents() {
    try {
        const response = await fetch('/student-progress/students');
        const students = await response.json();
        const select = document.getElementById('studentFilter');
        select.innerHTML = '<option value="">All Students</option>';
        allStudentSubjects = students;
        const studentNames = new Set();
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student;
            option.textContent = student;
            select.appendChild(option);
            const dashIndex = student.lastIndexOf(' - ');
            if (dashIndex !== -1) {
                const studentName = student.substring(0, dashIndex);
                studentNames.add(studentName);
            }
        });
        allStudentNames = Array.from(studentNames).sort();
    } catch (error) {
        showError('Failed to load students: ' + error.message);
    }
}

async function loadSubjects() {
    try {
        const response = await fetch('/student-progress/subjects');
        const subjects = await response.json();
        const select = document.getElementById('subjectFilter');
        select.innerHTML = '<option value="">All Subjects</option>';
        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject;
            option.textContent = subject;
            select.appendChild(option);
        });
        allSubjects = subjects;
    } catch (error) {
        showError('Failed to load subjects: ' + error.message);
    }
}

function setupAutocomplete() {
    const searchInput = document.getElementById('studentNameSearch');
    const dropdown = document.getElementById('studentNameDropdown');
    let selectedIndex = -1;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query.length === 0) {
            hideDropdown();
            return;
        }
        const filtered = allStudentNames.filter(name => name.toLowerCase().includes(query));
        if (filtered.length === 0) {
            hideDropdown();
            return;
        }
        showDropdown(filtered, this);
        selectedIndex = -1;
    });
    
    searchInput.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                selectStudent(items[selectedIndex].textContent);
            }
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    });
    
    function showDropdown(items, inputElement) {
        document.getElementById('monthDropdown').classList.add('hidden');
        dropdown.innerHTML = '';
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item px-3 py-2 hover:bg-primary/20 cursor-pointer text-sm border-b border-white/5 last:border-b-0';
            div.textContent = item;
            div.addEventListener('click', () => selectStudent(item));
            dropdown.appendChild(div);
        });
        const rect = inputElement.getBoundingClientRect();
        const dropdownHeight = Math.min(items.length * 40, 192);
        const viewportHeight = window.innerHeight;
        const spaceBelow = viewportHeight - rect.bottom;
        if (spaceBelow < dropdownHeight) {
            dropdown.classList.add('dropdown-fixed');
            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 4) + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.width = rect.width + 'px';
            dropdown.style.zIndex = '99999';
        } else {
            dropdown.classList.remove('dropdown-fixed');
            dropdown.style.position = '';
            dropdown.style.top = '';
            dropdown.style.left = '';
            dropdown.style.width = '';
        }
        dropdown.classList.remove('hidden');
    }
    
    function hideDropdown() {
        dropdown.classList.add('hidden');
        dropdown.classList.remove('dropdown-fixed');
        dropdown.style.position = '';
        dropdown.style.top = '';
        dropdown.style.left = '';
        dropdown.style.width = '';
        selectedIndex = -1;
    }
    
    function updateSelection(items) {
        items.forEach((item, index) => {
            item.classList.toggle('bg-primary/30', index === selectedIndex);
        });
    }
    
    function selectStudent(studentName) {
        searchInput.value = studentName;
        hideDropdown();
        loadLessonPlan();
    }
}

async function loadLessonPlan() {
    if (selectedDateRange) {
        await loadLessonPlanByDateRange();
    } else {
        await loadLessonPlanByMonths();
    }
}

async function loadLessonPlanByDateRange() {
    const year = parseInt(document.getElementById('yearInput').value);
    const studentName = document.getElementById('studentFilter').value;
    let name = '';
    let subject = '';
    if (studentName !== 'All Students' && studentName !== '') {
        const parts = studentName.split(' - ');
        name = parts[0]?.trim() || '';
        subject = parts[1]?.trim() || '';
    }
    showLoading(true, `Loading lesson plan for date range...`);
    try {
        const response = await fetch('/student-progress/lesson-plan-data-by-date-range', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                start_date: selectedDateRange.start,
                end_date: selectedDateRange.end,
                student_name: name,
                subject: subject
            })
        });
        const result = await response.json();
        showLoading(false);
        if (result.success) {
            currentData = result.data;
            displayLessonPlan(result.data, `${selectedDateRange.start} to ${selectedDateRange.end}`, year);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showLoading(false);
        showError('Failed to load lesson plan: ' + error.message);
    }
}

async function loadLessonPlanByMonths() {
    const months = selectedMonths.length > 0 ? selectedMonths : [new Date().toLocaleString('default', { month: 'long' })];
    const year = parseInt(document.getElementById('yearInput').value);
    const studentName = document.getElementById('studentFilter').value;
    let name = '';
    let subject = '';
    if (studentName !== 'All Students' && studentName !== '') {
        const parts = studentName.split(' - ');
        name = parts[0]?.trim() || '';
        subject = parts[1]?.trim() || '';
    }
    showLoading(true, `Loading lesson plan for ${months.length} month(s)...`);
    try {
        const response = await fetch('/student-progress/lesson-plan-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                months: months,
                year: year,
                student_name: name,
                subject: subject
            })
        });
        const result = await response.json();
        showLoading(false);
        if (result.success) {
            currentData = result.data;
            displayLessonPlan(result.data, months.join(', '), year);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showLoading(false);
        showError('Failed to load lesson plan: ' + error.message);
    }
}

function displayLessonPlan(data, period, year) {
    const results = document.getElementById('results');
    
    if (!data || data.length === 0) {
        results.innerHTML = '<div class="bg-red-500/10 border border-red-500/20 rounded p-4 text-red-200 text-center">No lesson plan data found.</div>';
        return;
    }
    
    let filteredData = applyClientFilters(data);
    
    if (filteredData.length === 0) {
        results.innerHTML = '<div class="bg-red-500/10 border border-red-500/20 rounded p-4 text-red-200 text-center">No data found for the selected filters.</div>';
        return;
    }
    
    filteredData = sortLessonData(filteredData);
    
    let html = `
        <div class="glass-effect rounded-lg overflow-hidden shadow-2xl table-container">
            <div class="p-4 border-b border-white/10 flex justify-between items-center">
                <div class="gradient-text font-semibold">${period} ${year}</div>
                <div class="text-sm text-gray-400">${filteredData.length} entries</div>
            </div>
            
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-xs">
<thead class="bg-gray-800/90 sticky top-0 z-10">
    <tr>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Month</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Date</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Type</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Level</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Worksheet</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">New</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Test Day</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Completed</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Pages/Repeats</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Student</th>
        <th class="px-3 py-3 text-left font-semibold text-gray-300 uppercase">Subject</th>
    </tr>
</thead>
                    <tbody>
    `;
    
    filteredData.forEach(row => {
        const newConceptClass = row.newConcept === 'Y' 
            ? 'bg-green-500 text-white px-2 py-1 rounded text-xs font-semibold' 
            : 'bg-gray-500 text-white px-2 py-1 rounded text-xs';
        
        const rowClass = row.isClassDay === 'Y' ? 'class-day-row' : '';
        const isPastDate = isDateInPast(row.date, row.month, year);
        const isReadOnly = !canEdit || isPastDate;
        
        const fullName = createFullName(row.studentName, row.studentLastName);
        const assignmentType = row.isClassDay === 'Y' ? "CW" : "HW";
        const studentKey = fullName.replace(/\s+/g, '_');

html += `
    <tr class="${rowClass} hover:bg-primary/10 transition-colors border-b border-white/5 ${row.isTestDay === 'Y' ? 'bg-yellow-500/10' : ''}">
        <td class="px-3 py-3 font-medium">${row.month}</td>
        <td class="px-3 py-3 font-medium">${row.date}</td>
        <td class="px-3 py-3">${assignmentType}</td>
        <td class="px-3 py-3">
            <div class="flex items-center gap-2 ${isReadOnly ? 'readonly-mode' : ''}">
                <input type="text" 
                       class="w-20 px-2 py-1 bg-white/10 border border-white/20 rounded text-xs" 
                       value="${row.level || ''}"
                       id="level_${studentKey}_${row.subject}_${row.month}_${row.date}"
                       ${isReadOnly ? 'disabled readonly' : ''}>
                ${!isReadOnly ? `<button class="px-2 py-1 bg-secondary hover:bg-secondary/80 text-white rounded text-xs transition-all" 
                        onclick="updateLevel('${fullName}', '${row.subject}', '${row.month}', ${row.date})">
                    Update
                </button>` : ''}
            </div>
        </td>
        <td class="px-3 py-3 font-medium">${row.worksheet}</td>
        <td class="px-3 py-3"><span class="${newConceptClass}">${row.newConcept}</span></td>
        <td class="px-3 py-3">
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-1 cursor-pointer ${isReadOnly ? 'readonly-mode' : ''}">
                    <input type="checkbox" 
                           id="test_${studentKey}_${row.subject}_${row.month}_${row.date}"
                           ${row.isTestDay === 'Y' ? 'checked' : ''}
                           ${isReadOnly ? 'disabled' : ''}
                           onchange="toggleTestDay('${fullName}', '${row.subject}', '${row.month}', ${row.date}, this.checked)"
                           class="w-4 h-4 accent-yellow-500">
                    <span class="text-xs ${row.isTestDay === 'Y' ? 'text-yellow-400 font-semibold' : 'text-gray-400'}">Test</span>
                </label>
            </div>
        </td>
        <td class="px-3 py-3">
            <div class="flex items-center gap-2 ${isReadOnly ? 'readonly-mode' : ''}">
                <input type="number" 
                       class="w-16 px-2 py-1 bg-white/10 border border-white/20 rounded text-xs" 
                       value="${row.lastCompletedPage || ''}"
                       id="lcp_${studentKey}_${row.subject}_${row.month}_${row.date}"
                       ${isReadOnly ? 'disabled readonly' : ''}>
                ${!isReadOnly ? `<button class="px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs transition-all" 
                        onclick="updateLastCompleted('${fullName}', '${row.subject}', '${row.month}', ${row.date})">
                    Update
                </button>` : ''}
            </div>
        </td>
        <td class="px-3 py-3">
            <div class="repeat-controls ${isReadOnly ? 'readonly-mode' : ''}">
                <div class="repeat-input-group">
                    <label>Pages</label>
                    <input type="number" 
                           class="px-2 py-1 bg-white/10 border border-white/20 rounded text-xs" 
                           value="${row.repeatPages || ''}"
                           id="pages_${studentKey}_${row.subject}_${row.month}_${row.date}"
                           min="0"
                           ${isReadOnly ? 'disabled readonly' : ''}>
                </div>
                <div class="repeat-input-group">
                    <label>Repeats</label>
                    <input type="number" 
                           class="px-2 py-1 bg-white/10 border border-white/20 rounded text-xs" 
                           value="${row.repeats || ''}"
                           id="repeats_${studentKey}_${row.subject}_${row.month}_${row.date}"
                           min="0"
                           ${isReadOnly ? 'disabled readonly' : ''}>
                </div>
                ${!isReadOnly ? `<button class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs transition-all" 
                        onclick="updatePagesAndRepeats('${fullName}', '${row.subject}', '${row.month}', ${row.date})">
                    Update
                </button>` : ''}
            </div>
        </td>
        <td class="px-3 py-3 font-medium">${fullName}</td>
        <td class="px-3 py-3 font-medium">${row.subject}</td>
    </tr>
`;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    results.innerHTML = html;
}

// Updated function to handle pages and repeats together
async function updatePagesAndRepeats(fullStudentName, subject, month, date) {
    if (!canEdit) {
        showError('You do not have permission to edit data.');
        return;
    }
    
    const studentKey = fullStudentName.replace(/\s+/g, '_');
    const pagesInputId = `pages_${studentKey}_${subject}_${month}_${date}`;
    const repeatsInputId = `repeats_${studentKey}_${subject}_${month}_${date}`;
    const pagesElement = document.getElementById(pagesInputId);
    const repeatsElement = document.getElementById(repeatsInputId);
    
    const pages = pagesElement.value;
    const repeats = repeatsElement.value;
    
    if (pages === '' || isNaN(pages) || parseInt(pages) < 0) {
        showError('Please enter a valid number of pages (0 or higher)');
        return;
    }
    
    if (repeats === '' || isNaN(repeats) || parseInt(repeats) < 0) {
        showError('Please enter a valid number of repeats (0 or higher)');
        return;
    }
    
    showLoading(true, 'Updating pages and repeats...');
    
    try {
        const response = await fetch('/student-progress/update-repeats', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                student_name: fullStudentName,
                subject: subject,
                month: month,
                date: parseInt(date),
                pages: parseInt(pages),
                repeats: parseInt(repeats)
            })
        });
        
        const result = await response.json();
        showLoading(false);
        
        if (result.success) {
            showSuccess(result.message);
            setTimeout(() => {
                loadLessonPlan();
            }, 1000);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showLoading(false);
        showError('Failed to update pages and repeats: ' + error.message);
    }
}

async function updateLastCompleted(fullStudentName, subject, month, date) {
    if (!canEdit) {
        showError('You do not have permission to edit data.');
        return;
    }
    
    const studentKey = fullStudentName.replace(/\s+/g, '_');
    const inputId = `lcp_${studentKey}_${subject}_${month}_${date}`;
    const inputElement = document.getElementById(inputId);
    const lastCompletedPage = inputElement.value;
    
    if (!lastCompletedPage || lastCompletedPage === '') {
        showError('Please enter a valid last completed page number');
        return;
    }
    
    showLoading(true, 'Updating progress...');
    
    try {
        const response = await fetch('/student-progress/update-last-completed-page', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                student_name: fullStudentName,
                subject: subject,
                month: month,
                date: parseInt(date),
                last_completed_page: parseInt(lastCompletedPage)
            })
        });
        
        const result = await response.json();
        showLoading(false);
        
        if (result.success) {
            showSuccess(result.message);
            setTimeout(() => {
                loadLessonPlan();
            }, 1000);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showLoading(false);
        showError('Failed to update: ' + error.message);
    }
}

async function updateLevel(fullStudentName, subject, month, date) {
    if (!canEdit) {
        showError('You do not have permission to edit data.');
        return;
    }
    
    const studentKey = fullStudentName.replace(/\s+/g, '_');
    const inputId = `level_${studentKey}_${subject}_${month}_${date}`;
    const inputElement = document.getElementById(inputId);
    const newLevel = inputElement.value.trim();
    
    if (!newLevel || newLevel === '') {
        showError('Please enter a valid level');
        return;
    }
    
    if (!confirm(`Update level to "${newLevel}" for ${fullStudentName} - ${subject}?\n\nThis will affect future data. Continue?`)) {
        return;
    }
    
    showLoading(true, 'Updating level...');
    
    try {
        const response = await fetch('/student-progress/update-level', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                student_name: fullStudentName,
                subject: subject,
                month: month,
                date: parseInt(date),
                new_level: newLevel
            })
        });
        
        const result = await response.json();
        showLoading(false);
        
        if (result.success) {
            showSuccess(result.message);
            setTimeout(() => {
                loadLessonPlan();
            }, 1000);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showLoading(false);
        showError('Failed to update level: ' + error.message);
    }
}

// Helper Functions
function createFullName(firstName, lastName) {
    return lastName ? `${firstName} ${lastName}`.trim() : firstName.trim();
}

function applyClientFilters(data) {
    let filteredData = data;
    
    const studentNameSearch = document.getElementById('studentNameSearch').value.trim().toLowerCase();
    if (studentNameSearch) {
        filteredData = filteredData.filter(row => {
            const fullName = createFullName(row.studentName, row.studentLastName).toLowerCase();
            return fullName.includes(studentNameSearch);
        });
    }
    
    const subjectFilter = document.getElementById('subjectFilter').value;
    if (subjectFilter) {
        filteredData = filteredData.filter(row => row.subject === subjectFilter);
    }
    
    const studentFilter = document.getElementById('studentFilter').value;
    if (studentFilter) {
        const lastDashIndex = studentFilter.lastIndexOf(' - ');
        if (lastDashIndex !== -1) {
            const fullStudentName = studentFilter.substring(0, lastDashIndex);
            const subject = studentFilter.substring(lastDashIndex + 3);
            
            filteredData = filteredData.filter(row => {
                const rowFullName = createFullName(row.studentName, row.studentLastName);
                return rowFullName === fullStudentName && row.subject === subject;
            });
        }
    }
    
    return filteredData;
}

function sortLessonData(data) {
    return data.sort((a, b) => {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        
        const monthA = monthNames.indexOf(a.month);
        const monthB = monthNames.indexOf(b.month);
        
        if (monthA !== monthB) return monthA - monthB;
        if (a.date !== b.date) return a.date - b.date;
        
        const fullNameA = createFullName(a.studentName, a.studentLastName);
        const fullNameB = createFullName(b.studentName, b.studentLastName);
        if (fullNameA !== fullNameB) return fullNameA.localeCompare(fullNameB);
        
        return a.subject.localeCompare(b.subject);
    });
}

function isDateInPast(dateStr, month, year) {
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const monthIndex = monthNames.indexOf(month);
    if (monthIndex === -1) return false;
    
    const targetDate = new Date(year, monthIndex, parseInt(dateStr));
    const today = getPSTDate();
    targetDate.setHours(0, 0, 0, 0);
    
    return targetDate < today;
}

function getPSTDate() {
    const now = new Date();
    const pstOffset = -8 * 60;
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const pstTime = new Date(utc + (pstOffset * 60000));
    pstTime.setHours(0, 0, 0, 0);
    return pstTime;
}

function handleSubjectFilterChange() {
    if (allStudentSubjects.length > 0) {
        populateStudentFilter(allStudentSubjects);
    }
}

function populateStudentFilter(students) {
    const select = document.getElementById('studentFilter');
    const selectedSubject = document.getElementById('subjectFilter').value;
    
    select.innerHTML = '<option value="">All Students</option>';
    
    const filteredStudents = selectedSubject ? 
        students.filter(student => student.endsWith(' - ' + selectedSubject)) : 
        students;
    
    filteredStudents.forEach(function(student) {
        const option = document.createElement('option');
        option.value = student;
        option.textContent = student;
        select.appendChild(option);
    });
}

function showLoading(show, message = 'Loading...') {
    const loading = document.getElementById('loading');
    if (show) {
        loading.innerHTML = `<span class="loading-spinner">${message}</span>`;
        loading.classList.remove('hidden');
    } else {
        loading.classList.add('hidden');
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'fixed top-4 right-4 bg-red-500/90 backdrop-blur-sm text-white px-4 py-3 rounded-lg shadow-lg z-50 max-w-md';
    errorDiv.innerHTML = `
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-sm">${message}</span>
        </div>
    `;
    document.body.appendChild(errorDiv);
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.parentNode.removeChild(errorDiv);
        }
    }, 5000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'fixed top-4 right-4 bg-green-500/90 backdrop-blur-sm text-white px-4 py-3 rounded-lg shadow-lg z-50 max-w-md';
    successDiv.innerHTML = `
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-sm">${message}</span>
        </div>
    `;
    document.body.appendChild(successDiv);
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.parentNode.removeChild(successDiv);
        }
    }, 3000);
}

// Event listeners
document.getElementById('studentFilter').addEventListener('change', loadLessonPlan);
document.getElementById('subjectFilter').addEventListener('change', function() {
    handleSubjectFilterChange();
});
document.getElementById('yearInput').addEventListener('change', loadLessonPlan);
document.getElementById('loadLessonPlan').addEventListener('click', loadLessonPlan);

let searchTimeout;
document.getElementById('studentNameSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadLessonPlan();
    }, 500);
});

document.addEventListener('click', function(event) {
    if (!event.target.closest('#monthMultiSelect')) {
        closeAllDropdowns();
    }
    if (!event.target.closest('#studentNameSearch') && !event.target.closest('#studentNameDropdown')) {
        document.getElementById('studentNameDropdown').classList.add('hidden');
    }
});

window.addEventListener('resize', function() {
    closeAllDropdowns();
});

window.addEventListener('scroll', function() {
    const monthDropdown = document.getElementById('monthDropdown');
    const studentDropdown = document.getElementById('studentNameDropdown');
    
    if (monthDropdown.classList.contains('dropdown-fixed')) {
        const display = document.getElementById('monthDisplay');
        const rect = display.getBoundingClientRect();
        monthDropdown.style.top = (rect.bottom + 4) + 'px';
        monthDropdown.style.left = rect.left + 'px';
    }
    
    if (studentDropdown.classList.contains('dropdown-fixed')) {
        const input = document.getElementById('studentNameSearch');
        const rect = input.getBoundingClientRect();
        studentDropdown.style.top = (rect.bottom + 4) + 'px';
        studentDropdown.style.left = rect.left + 'px';
    }
});


async function toggleTestDay(fullStudentName, subject, month, date, isChecked) {
    if (!canEdit) {
        showError('You do not have permission to edit data.');
        return;
    }
    
    const isTestDay = isChecked ? 'Y' : 'N';
    
    showLoading(true, 'Updating test day status...');
    
    try {
        const response = await fetch('/student-progress/update-test-day', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                student_name: fullStudentName,
                subject: subject,
                month: month,
                date: parseInt(date),
                is_test_day: isTestDay
            })
        });
        
        const result = await response.json();
        showLoading(false);
        
        if (result.success) {
            showSuccess(result.message);
            setTimeout(() => {
                loadLessonPlan();
            }, 1000);
        } else {
            showError(result.error);
            // Revert checkbox state
            document.getElementById(`test_${fullStudentName.replace(/\s+/g, '_')}_${subject}_${month}_${date}`).checked = !isChecked;
        }
    } catch (error) {
        showLoading(false);
        showError('Failed to update test day: ' + error.message);
        // Revert checkbox state
        document.getElementById(`test_${fullStudentName.replace(/\s+/g, '_')}_${subject}_${month}_${date}`).checked = !isChecked;
    }
}
</script>
@endpush