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

        <!-- Setup Button -->
        <button id="setupButton" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all">
            Initialize Student Data
        </button>
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

    <!-- Results -->
    <div id="results" class="mt-6"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadConfigurations();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('setupButton').addEventListener('click', startSetup);
}

async function loadConfigurations() {
    try {
        // For now, we'll make a simple request to get students
        // In a real implementation, you'd create a specific endpoint for configurations
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
                'X-CSRF-TOKEN': csrfToken
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
    // Reset progress
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

    // Auto-remove after 10 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            results.innerHTML = '';
        }, 10000);
    }
}
</script>
@endpush