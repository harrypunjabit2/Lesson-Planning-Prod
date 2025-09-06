<!-- resources/views/admin/delete.blade.php -->
@extends('layouts.app')

@section('title', 'Delete Student Data')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold gradient-text mb-2">Delete Student Data</h1>
        <p class="text-gray-400">Permanently remove lesson plan data for specific students and subjects.</p>
    </div>

    <!-- Warning Card -->
    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-6 mb-6">
        <div class="flex items-start gap-3">
            <span class="text-red-400 text-2xl">⚠️</span>
            <div>
                <h3 class="text-red-300 font-semibold text-lg mb-2">Danger Zone</h3>
                <div class="text-red-200 text-sm space-y-2">
                    <p>This action will permanently delete ALL lesson plan data for the selected student, subject, month, and year combination.</p>
                    <p><strong>This cannot be undone!</strong> Make sure you have backups if needed.</p>
                    <p>Deleted data includes: worksheets, completed pages, repeats, and all progress tracking information.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <div class="glass-effect rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
            <span>🗑️</span>
            Select Data to Delete
        </h2>
        
        <form id="deleteForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Student & Subject Selection -->
                <div>
                    <label for="deleteStudentSelect" class="block text-sm font-semibold text-gray-300 mb-2">Student & Subject *</label>
                    <select id="deleteStudentSelect" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
                        <option value="">Select Student & Subject</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Choose the specific student and subject combination</p>
                </div>

                <!-- Month Selection -->
                <div>
                    <label for="deleteMonthSelect" class="block text-sm font-semibold text-gray-300 mb-2">Month *</label>
                    <select id="deleteMonthSelect" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
                        <option value="">Select Month</option>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                            <option value="{{ $month }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Year Selection -->
                <div>
                    <label for="deleteYearInput" class="block text-sm font-semibold text-gray-300 mb-2">Year *</label>
                    <input type="number" id="deleteYearInput" min="2020" max="2030" value="{{ date('Y') }}" required 
                           class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
                </div>

                <!-- Preview Section -->
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Data Preview</label>
                    <div id="dataPreview" class="bg-white/5 border border-white/10 rounded p-3 min-h-[60px] flex items-center justify-center text-gray-400 text-sm">
                        Select criteria above to preview data
                    </div>
                </div>
            </div>

            <!-- Confirmation Checkbox -->
            <div class="border-t border-white/10 pt-6">
                <label class="flex items-start gap-3">
                    <input type="checkbox" id="confirmDelete" class="mt-1 accent-red-500">
                    <div class="text-sm">
                        <div class="text-red-300 font-medium">I understand this action cannot be undone</div>
                        <div class="text-gray-400 mt-1">I confirm that I want to permanently delete the selected lesson plan data.</div>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 justify-between">
                <a href="{{ route('student-progress.index') }}" class="bg-white/10 hover:bg-white/15 text-white px-6 py-2 rounded transition-all">
                    Cancel
                </a>
                
                <button type="submit" id="deleteButton" disabled 
                        class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-2 rounded font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    Delete Data
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div id="results" class="mt-6"></div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800/98 backdrop-blur-lg border border-red-500/30 rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-red-500/20 rounded-full flex items-center justify-center">
                <span class="text-red-400 text-2xl">🗑️</span>
            </div>
            <h3 class="text-lg font-semibold text-red-300 mb-2">Confirm Deletion</h3>
            <p class="text-sm text-gray-300 mb-4" id="confirmationText">Are you sure you want to delete this data?</p>
            
            <div class="flex gap-3 justify-center">
                <button id="cancelConfirmation" class="bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded text-sm transition-all">
                    Cancel
                </button>
                <button id="confirmDeletion" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm transition-all">
                    Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let pendingDeletionData = null;

document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    setupEventListeners();
});

function setupEventListeners() {
    // Form submission
    document.getElementById('deleteForm').addEventListener('submit', handleDeleteSubmit);
    
    // Preview updates
    document.getElementById('deleteStudentSelect').addEventListener('change', updatePreview);
    document.getElementById('deleteMonthSelect').addEventListener('change', updatePreview);
    document.getElementById('deleteYearInput').addEventListener('change', updatePreview);
    
    // Confirmation checkbox
    document.getElementById('confirmDelete').addEventListener('change', function() {
        document.getElementById('deleteButton').disabled = !this.checked;
    });
    
    // Modal buttons
    document.getElementById('cancelConfirmation').addEventListener('click', hideConfirmationModal);
    document.getElementById('confirmDeletion').addEventListener('click', executeDelete);
}

async function loadStudents() {
    try {
        const response = await fetch('/student-progress/students');
        const students = await response.json();
        
        const select = document.getElementById('deleteStudentSelect');
        select.innerHTML = '<option value="">Select Student & Subject</option>';
        
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student;
            option.textContent = student;
            select.appendChild(option);
        });
    } catch (error) {
        showResult('Failed to load students: ' + error.message, 'error');
    }
}

function updatePreview() {
    const student = document.getElementById('deleteStudentSelect').value;
    const month = document.getElementById('deleteMonthSelect').value;
    const year = document.getElementById('deleteYearInput').value;
    const preview = document.getElementById('dataPreview');
    
    if (!student || !month || !year) {
        preview.innerHTML = '<span class="text-gray-400">Select all criteria above to preview data</span>';
        return;
    }
    
    // Parse student data
    const lastDashIndex = student.lastIndexOf(' - ');
    if (lastDashIndex === -1) {
        preview.innerHTML = '<span class="text-red-400">Invalid student format</span>';
        return;
    }
    
    const studentName = student.substring(0, lastDashIndex);
    const subject = student.substring(lastDashIndex + 3);
    
    preview.innerHTML = `
        <div class="text-left space-y-2">
            <div><strong>Student:</strong> ${studentName}</div>
            <div><strong>Subject:</strong> ${subject}</div>
            <div><strong>Period:</strong> ${month} ${year}</div>
            <div class="text-red-300 text-xs mt-2">⚠️ All lesson plan data for this combination will be deleted</div>
        </div>
    `;
}

// Add these debugging functions to your script section

function handleDeleteSubmit(e) {
    e.preventDefault();
    
    const student = document.getElementById('deleteStudentSelect').value;
    const month = document.getElementById('deleteMonthSelect').value;
    const year = document.getElementById('deleteYearInput').value;
    const confirmed = document.getElementById('confirmDelete').checked;
    
    console.log('Form submission data:', { student, month, year, confirmed });
    
    if (!student || !month || !year) {
        console.log('Validation failed: missing fields');
        showResult('Please fill in all required fields', 'error');
        return;
    }
    
    if (!confirmed) {
        console.log('Validation failed: not confirmed');
        showResult('Please confirm that you understand this action cannot be undone', 'error');
        return;
    }
    
    // Parse student data with better error handling
    const lastDashIndex = student.lastIndexOf(' - ');
    console.log('Student parsing:', { student, lastDashIndex });
    
    if (lastDashIndex === -1) {
        console.log('Parsing failed: invalid format');
        showResult('Invalid student format selected', 'error');
        return;
    }
    
    const studentName = student.substring(0, lastDashIndex);
    const subject = student.substring(lastDashIndex + 3);
    
    console.log('Parsed student data:', { studentName, subject });
    
    // Store data for confirmation
    pendingDeletionData = {
        studentName,
        subject,
        month,
        year: parseInt(year)
    };
    
    console.log('Set pendingDeletionData:', pendingDeletionData);
    
    // Show confirmation modal
    showConfirmationModal(studentName, subject, month, year);
}

function showConfirmationModal(studentName, subject, month, year) {
    console.log('Showing confirmation modal for:', { studentName, subject, month, year });
    
    const modal = document.getElementById('confirmationModal');
    const text = document.getElementById('confirmationText');
    
    if (!modal || !text) {
        console.error('Modal elements not found');
        showResult('Modal elements not found', 'error');
        return;
    }
    
    text.innerHTML = `
        You are about to delete ALL lesson plan data for:<br><br>
        <strong>${studentName} - ${subject}</strong><br>
        <strong>${month} ${year}</strong><br><br>
        This action cannot be undone. Are you absolutely sure?
    `;
    
    modal.classList.remove('hidden');
    console.log('Modal should now be visible');
}

function hideConfirmationModal() {
    console.log('Hiding confirmation modal');
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    console.log('Clearing pendingDeletionData');
  //  pendingDeletionData = null;
}

async function executeDelete() {
    console.log('executeDelete called with pendingDeletionData:', pendingDeletionData);
    
    if (!pendingDeletionData) {
        console.log('No pending deletion data, aborting');
        hideConfirmationModal();
        showResult('No deletion data found. Please try again.', 'error');
        return;
    }
    
    hideConfirmationModal();
    showResult('Deleting data...', 'loading');
    
    try {
        console.log('Sending delete request with:', pendingDeletionData);
        
        const response = await fetch('/student-progress/delete-student-data', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                student_name: pendingDeletionData.studentName,
                subject: pendingDeletionData.subject,
                month: pendingDeletionData.month,
                year: pendingDeletionData.year
            })
        });
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            showResult(result.message, 'success');
            // Reset form
            document.getElementById('deleteForm').reset();
            document.getElementById('deleteButton').disabled = true;
            updatePreview();
        } else {
            showResult(result.error, 'error');
        }
    } catch (error) {
        console.error('Delete request failed:', error);
        showResult('Failed to delete student data: ' + error.message, 'error');
    }
    
    pendingDeletionData = null;
}

// Additional debug function to check current state
function debugCurrentState() {
    console.log('=== Current State Debug ===');
    console.log('pendingDeletionData:', pendingDeletionData);
    console.log('Form values:', {
        student: document.getElementById('deleteStudentSelect')?.value,
        month: document.getElementById('deleteMonthSelect')?.value,
        year: document.getElementById('deleteYearInput')?.value,
        confirmed: document.getElementById('confirmDelete')?.checked
    });
    console.log('Modal visibility:', {
        modal: document.getElementById('confirmationModal')?.classList.contains('hidden')
    });
    console.log('==========================');
}

function showResult(message, type) {
    const results = document.getElementById('results');
    let html = '';
    
    if (type === 'loading') {
        html = `
            <div class="bg-blue-500/10 border border-blue-500/20 text-blue-200 border rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                    <span>${message}</span>
                </div>
            </div>
        `;
    } else {
        const bgColor = type === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-200' : 'bg-red-500/10 border-red-500/20 text-red-200';
        const icon = type === 'success' ? '✅' : '❌';
        
        html = `
            <div class="${bgColor} border rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="text-xl">${icon}</span>
                    <div class="flex-1">
                        <h3 class="font-semibold">${type === 'success' ? 'Deletion Successful' : 'Deletion Failed'}</h3>
                        <p class="text-sm mt-1">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-200 p-1">
                        ×
                    </button>
                </div>
                ${type === 'success' ? `
                    <div class="mt-4 flex gap-3">
                        <a href="/student-progress" class="bg-primary hover:bg-primary/80 text-white px-4 py-2 rounded text-sm transition-all">
                            View Lesson Plans
                        </a>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    results.innerHTML = html;
    
    // Auto-remove success messages
    if (type === 'success') {
        setTimeout(() => {
            results.innerHTML = '';
        }, 8000);
    }
}

// You can call debugCurrentState() in the browser console at any time
</script>
@endpush