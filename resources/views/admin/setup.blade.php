<!-- resources/views/admin/setup.blade.php -->
@extends('layouts.app')

@section('title', 'Student Data Setup')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold gradient-text mb-2">Student Data Setup</h1>
        <p class="text-gray-400">Initialize lesson plans for all students based on their configurations.</p>
    </div>

    <!-- Setup Process Card -->
    <div class="glass-effect rounded-lg p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">🚀</span>
            <h2 class="text-xl font-semibold">Setup Process</h2>
        </div>
        
        <div class="space-y-4 mb-6">
            <div class="flex items-start gap-3">
                <span class="text-primary text-lg">•</span>
                <p class="text-sm text-gray-300">Reads all student configurations from the database</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-primary text-lg">•</span>
                <p class="text-sm text-gray-300">Generates lesson plan entries for all days in each student's configured month</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-primary text-lg">•</span>
                <p class="text-sm text-gray-300">Highlights class days based on configured class schedule</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-primary text-lg">•</span>
                <p class="text-sm text-gray-300">Sets initial worksheet values to 0 (to be updated when progress is tracked)</p>
            </div>
        </div>

        <div class="bg-blue-500/10 border border-blue-500/20 rounded p-4 mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-blue-400">ℹ️</span>
                <strong class="text-blue-300">Information</strong>
            </div>
            <p class="text-sm text-blue-200">
                This process will only create new entries. Existing lesson plan data will not be modified or deleted.
                If you need to reset data, use the Delete Data function first.
            </p>
        </div>

        <!-- Action Button -->
        <button id="setupButton" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="flex items-center justify-center gap-2">
                <span class="text-lg">🚀</span>
                Initialize Student Data
            </span>
        </button>
    </div>

    <!-- Generate Month Lesson Plans Card -->
    <div class="glass-effect rounded-lg p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📅</span>
            <h2 class="text-xl font-semibold">Generate Month Lesson Plans</h2>
        </div>
        
        <div class="space-y-4 mb-6">
            <p class="text-sm text-gray-300">
                Generate lesson plans for a specific month and year based on student configurations.
            </p>
            
            <!-- Month and Year Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Month</label>
                    <select id="monthSelect" class="w-full bg-gray-700/50 border border-gray-600 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                    <select id="yearSelect" class="w-full bg-gray-700/50 border border-gray-600 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <!-- Years will be populated by JavaScript -->
                    </select>
                </div>
            </div>

            <div class="bg-blue-500/10 border border-blue-500/20 rounded p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-blue-400">ℹ️</span>
                    <strong class="text-blue-300">Information</strong>
                </div>
                <p class="text-sm text-blue-200">
                    This will generate lesson plans for the selected month/year based on existing student configurations.
                    Make sure you have student configurations set up for the target month and year.
                </p>
            </div>

            <button id="generateCurrentBtn" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="generateCurrentContent" class="flex items-center justify-center gap-2">
                    <span class="text-lg">📅</span>
                    <span id="generateCurrentText">Generate Lesson Plans</span>
                </span>
                <span id="generateCurrentSpinner" class="hidden flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
        </div>
    </div>

    <!-- Current Configurations -->
    <div class="glass-effect rounded-lg overflow-hidden">
        <div class="p-4 border-b border-white/10">
            <h3 class="text-lg font-semibold gradient-text">Current Student Configurations</h3>
            <p class="text-sm text-gray-400 mt-1">These students will have lesson plans generated</p>
        </div>
        
        <div id="configList" class="p-4">
            <div class="text-center text-gray-400 py-8">
                <span class="loading-spinner">Loading configurations...</span>
            </div>
        </div>
    </div>

    <!-- Progress Modal -->
    <div id="progressModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
        <div class="bg-gray-800/98 backdrop-blur-lg border border-white/30 rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mb-4">
                    <div class="w-16 h-16 mx-auto mb-4 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                    <h3 class="text-lg font-semibold mb-2">Setting Up Student Data</h3>
                    <p class="text-sm text-gray-400" id="progressText">Initializing process...</p>
                </div>
                <div class="bg-gray-700 rounded-full h-2 mb-4">
                    <div id="progressBar" class="bg-gradient-to-r from-primary to-secondary h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
                <div class="text-xs text-gray-500" id="progressDetails"></div>
            </div>
        </div>
    </div>

    <!-- Generate Current Month Modal -->
    <div id="generateModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
        <div class="bg-gray-800/98 backdrop-blur-lg border border-white/30 rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mb-4">
                    <div class="w-16 h-16 mx-auto mb-4 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <h3 class="text-lg font-semibold mb-2">Generating Lesson Plans</h3>
                    <p class="text-sm text-gray-400" id="generateProgressText">Processing lesson plans...</p>
                </div>
                <div class="bg-gray-700 rounded-full h-2 mb-4">
                    <div id="generateProgressBar" class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
                <div class="text-xs text-gray-500" id="generateProgressDetails">Please wait while we generate the lesson plans...</div>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div id="results" class="mt-6"></div>
</div>
@endsection

@push('scripts')
<script>
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
    document.addEventListener('DOMContentLoaded', function() {
        initializeMonthYear();
        loadConfigurations();
        setupEventListeners();
    });

    function initializeMonthYear() {
        // Set current month
        const monthSelect = document.getElementById('monthSelect');
        const currentMonth = new Date().toLocaleString('default', { month: 'long' });
        monthSelect.value = currentMonth;
        
        // Populate years (current year - 1 to current year + 3)
        const yearSelect = document.getElementById('yearSelect');
        const currentYear = new Date().getFullYear();
        
        for (let year = currentYear - 1; year <= currentYear + 3; year++) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) {
                option.selected = true;
            }
            yearSelect.appendChild(option);
        }
    }

    function setupEventListeners() {
        document.getElementById('setupButton').addEventListener('click', startSetup);
        document.getElementById('generateCurrentBtn').addEventListener('click', generateCurrentMonth);
    }

    async function generateCurrentMonth() {
        const button = document.getElementById('generateCurrentBtn');
        const contentSpan = document.getElementById('generateCurrentContent');
        const spinnerSpan = document.getElementById('generateCurrentSpinner');
        
        // Get selected month and year
        const selectedMonth = document.getElementById('monthSelect').value;
        const selectedYear = document.getElementById('yearSelect').value;
        
        if (!selectedMonth || !selectedYear) {
            showGenerateResult('Please select both month and year', 'error');
            return;
        }
        
        // Disable button and show loading state
        button.disabled = true;
        contentSpan.classList.add('hidden');
        spinnerSpan.classList.remove('hidden');
        
        // Show progress modal
        showGenerateModal();
        updateGenerateProgress(20, 'Initializing generation...', `Preparing to generate ${selectedMonth} ${selectedYear}...`);
        
        try {
            updateGenerateProgress(40, 'Reading configurations...', 'Fetching student data...');
            
            const response = await fetch('/admin/generate-current-month', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    month: selectedMonth,
                    year: parseInt(selectedYear)
                })
            });
            
            updateGenerateProgress(70, 'Processing data...', 'Creating lesson plan entries...');
            
            const data = await response.json();
            
            updateGenerateProgress(90, 'Finalizing...', 'Saving to database...');
            
            // Simulate completion delay for better UX
            setTimeout(() => {
                updateGenerateProgress(100, 'Complete!', `${selectedMonth} ${selectedYear} generation finished successfully`);
                
                setTimeout(() => {
                    hideGenerateModal();
                    
                    if (data.success) {
                        showGenerateResult(data.message, 'success');
                    } else {
                        showGenerateResult(data.error || 'An error occurred', 'error');
                    }
                }, 1000);
            }, 500);
            
        } catch (error) {
            console.error('Error:', error);
            hideGenerateModal();
            showGenerateResult('Network error occurred: ' + error.message, 'error');
        } finally {
            // Re-enable button and hide loading state
            button.disabled = false;
            contentSpan.classList.remove('hidden');
            spinnerSpan.classList.add('hidden');
        }
    }

    function showGenerateModal() {
        const selectedMonth = document.getElementById('monthSelect').value;
        const selectedYear = document.getElementById('yearSelect').value;
        
        document.getElementById('generateModal').classList.remove('hidden');
        document.getElementById('generateProgressText').textContent = `Generating ${selectedMonth} ${selectedYear}...`;
    }

    function hideGenerateModal() {
        document.getElementById('generateModal').classList.add('hidden');
        // Reset progress
        updateGenerateProgress(0, '', '');
    }

    function updateGenerateProgress(percentage, text, details) {
        document.getElementById('generateProgressBar').style.width = percentage + '%';
        document.getElementById('generateProgressText').textContent = text;
        document.getElementById('generateProgressDetails').textContent = details;
    }

    function showGenerateResult(message, type) {
        const results = document.getElementById('results');
        const bgColor = type === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-200' : 'bg-red-500/10 border-red-500/20 text-red-200';
        const icon = type === 'success' ? '✅' : '❌';
        const title = type === 'success' ? 'Generation Successful' : 'Generation Failed';
        
        const selectedMonth = document.getElementById('monthSelect').value;
        const selectedYear = document.getElementById('yearSelect').value;
        
        results.innerHTML = `
            <div class="${bgColor} border rounded-lg p-6 animate-fade-in">
                <div class="flex items-start gap-4">
                    <span class="text-2xl">${icon}</span>
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2">${title}</h3>
                        <p class="text-sm opacity-90 mb-2">
                            <strong>${selectedMonth} ${selectedYear}</strong> - ${message}
                        </p>
                        
                        ${type === 'success' ? `
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="/student-progress" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-all inline-flex items-center gap-2">
                                    <span>📊</span>
                                    View Lesson Plans
                                </a>
                                <button onclick="this.closest('[class*=\"bg-green\"]').remove()" class="bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-lg text-sm transition-all">
                                    Dismiss
                                </button>
                            </div>
                        ` : `
                            <div class="mt-4 flex gap-3">
                                <button onclick="generateCurrentMonth()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-all">
                                    Try Again
                                </button>
                                <button onclick="this.closest('[class*=\"bg-red\"]').remove()" class="bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-lg text-sm transition-all">
                                    Dismiss
                                </button>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;

        // Auto-remove success messages after 8 seconds
        if (type === 'success') {
            setTimeout(() => {
                const resultElement = results.querySelector('[class*="bg-green"]');
                if (resultElement) {
                    resultElement.style.opacity = '0';
                    resultElement.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        results.innerHTML = '';
                    }, 300);
                }
            }, 8000);
        }
    }

    async function loadConfigurations() {
        try {
            const response = await fetch('/student-progress/students');
            const students = await response.json();
            
            displayConfigurations(students);
        } catch (error) {
            document.getElementById('configList').innerHTML = `
                <div class="text-center text-red-400 py-4">
                    <p>Failed to load configurations: ${error.message}</p>
                </div>
            `;
        }
    }

    function displayConfigurations(students) {
        const configList = document.getElementById('configList');
        
        if (!students || students.length === 0) {
            configList.innerHTML = `
                <div class="text-center text-gray-400 py-8">
                    <p>No student configurations found.</p>
                    <p class="text-sm mt-2">Please add student configurations first in the 
                       <a href="/admin/config" class="text-primary hover:underline">Student Config</a> section.</p>
                </div>
            `;
            return;
        }

        let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
        
        students.forEach(student => {
            html += `
                <div class="bg-white/5 border border-white/10 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">👨‍🎓</span>
                        <strong class="text-sm">${student}</strong>
                    </div>
                    <div class="text-xs text-gray-400 space-y-1">
                        <div>Configuration will be read from database</div>
                        <div>Lesson plans will be generated for configured month/year</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        configList.innerHTML = html;
    }

    async function startSetup() {
        if (!confirm('This will initialize lesson plan data for all configured students. Continue?')) {
            return;
        }

        showProgressModal();
        updateProgress(10, 'Reading student configurations...', 'Fetching data from database');

        try {
            const response = await fetch('/student-progress/setup-student-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });

            updateProgress(50, 'Processing configurations...', 'Creating lesson plan entries');

            const result = await response.json();
            
            updateProgress(90, 'Finalizing setup...', 'Saving data to database');

            setTimeout(() => {
                updateProgress(100, 'Setup complete!', 'All data has been initialized');
                
                setTimeout(() => {
                    hideProgressModal();
                    
                    if (result.success) {
                        showResult(result.message, 'success');
                    } else {
                        showResult(result.error, 'error');
                    }
                }, 1000);
            }, 500);

        } catch (error) {
            hideProgressModal();
            showResult('Failed to setup student data: ' + error.message, 'error');
        }
    }

    function showProgressModal() {
        document.getElementById('progressModal').classList.remove('hidden');
    }

    function hideProgressModal() {
        document.getElementById('progressModal').classList.add('hidden');
        updateProgress(0, '', '');
    }

    function updateProgress(percentage, text, details) {
        document.getElementById('progressBar').style.width = percentage + '%';
        document.getElementById('progressText').textContent = text;
        document.getElementById('progressDetails').textContent = details;
    }

    function showResult(message, type) {
        const results = document.getElementById('results');
        const bgColor = type === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-200' : 'bg-red-500/10 border-red-500/20 text-red-200';
        const icon = type === 'success' ? '✅' : '❌';
        
        results.innerHTML = `
            <div class="${bgColor} border rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="text-xl">${icon}</span>
                    <div>
                        <h3 class="font-semibold">${type === 'success' ? 'Setup Successful' : 'Setup Failed'}</h3>
                        <p class="text-sm mt-1">${message}</p>
                    </div>
                </div>
                ${type === 'success' ? `
                    <div class="mt-4 flex gap-3">
                        <a href="/student-progress" class="bg-primary hover:bg-primary/80 text-white px-4 py-2 rounded text-sm transition-all">
                            View Lesson Plans
                        </a>
                        <button onclick="this.parentElement.parentElement.remove()" class="bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded text-sm transition-all">
                            Dismiss
                        </button>
                    </div>
                ` : `
                    <div class="mt-4">
                        <button onclick="this.parentElement.parentElement.remove()" class="bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded text-sm transition-all">
                            Dismiss
                        </button>
                    </div>
                `}
            </div>
        `;

        if (type === 'success') {
            setTimeout(() => {
                results.innerHTML = '';
            }, 10000);
        }
    }
</script>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>
@endpush