<!-- resources/views/admin/concepts.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage New Concepts</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#06b6d4'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto max-w-7xl px-4 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold gradient-text">Manage New Concepts</h1>
            <div class="flex gap-3">
                <a href="{{ route('student-progress.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/15 rounded text-sm transition-all">
                    Back to Progress Tracker
                </a>
                <a href="{{ route('admin.config') }}" class="px-4 py-2 bg-accent hover:bg-accent/80 text-white rounded text-sm transition-all">
                    Manage Configurations
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-500/10 border border-green-500/20 text-green-200 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/20 text-red-200 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="glass-effect rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Upload New Concepts CSV</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Upload Form -->
                <div>
                    <form action="{{ route('admin.concepts.upload-csv') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="csv_file" class="block text-sm font-medium text-gray-300 mb-2">Select CSV File</label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all file:mr-4 file:py-1 file:px-4 file:rounded file:border-0 file:text-sm file:bg-primary file:text-white hover:file:bg-primary/80">
                            @error('csv_file')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="bg-gradient-to-r from-secondary to-accent text-white px-6 py-2 rounded font-medium hover:shadow-lg hover:-translate-y-0.5 transition-all">
                            Upload CSV
                        </button>
                    </form>
                </div>
    </div>
    <br>
        <!-- Add New Concept Form -->
        <div class="glass-effect rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Add New Concept</h2>
            <form action="{{ route('admin.concepts.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-300 mb-1">Level *</label>
                        <input type="text" name="level" id="level" required
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all"
                               value="{{ old('level') }}">
                        @error('level')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-300 mb-1">Subject *</label>
                        <input type="text" name="subject" id="subject" required
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all"
                               value="{{ old('subject') }}">
                        @error('subject')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="worksheet" class="block text-sm font-medium text-gray-300 mb-1">Worksheet *</label>
                        <input type="number" name="worksheet" id="worksheet" min="1" required
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all"
                               value="{{ old('worksheet') }}">
                        @error('worksheet')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="is_new_concept" class="block text-sm font-medium text-gray-300 mb-1">Is New Concept? *</label>
                        <select name="is_new_concept" id="is_new_concept" required
                                class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
                            <option value="">Select</option>
                            <option value="Y" {{ old('is_new_concept') == 'Y' ? 'selected' : '' }}>Yes</option>
                            <option value="N" {{ old('is_new_concept') == 'N' ? 'selected' : '' }}>No</option>
                        </select>
                        @error('is_new_concept')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-gradient-to-r from-primary to-secondary text-white px-6 py-2 rounded font-medium hover:shadow-lg hover:-translate-y-0.5 transition-all">
                        Add Concept
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Concepts -->
        <div class="glass-effect rounded-lg overflow-hidden">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-lg font-semibold gradient-text">Existing New Concepts</h2>
            </div>
            
        </div>
    </div>

    <script>
        function editConcept(id) {
            // Hide the regular row and show the edit form
            document.getElementById('concept-row-' + id).classList.add('hidden');
            document.getElementById('edit-form-' + id).classList.remove('hidden');
        }

        function cancelConceptEdit(id) {
            // Show the regular row and hide the edit form
            document.getElementById('concept-row-' + id).classList.remove('hidden');
            document.getElementById('edit-form-' + id).classList.add('hidden');
        }
    </script>
</body>
</html>