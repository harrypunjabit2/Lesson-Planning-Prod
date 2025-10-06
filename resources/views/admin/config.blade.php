<!-- resources/views/admin/config.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Configurations</title>
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
            <h1 class="text-2xl font-semibold gradient-text">Manage Student Configurations</h1>
            <div class="flex gap-3">
                <a href="{{ route('student-progress.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/15 rounded text-sm transition-all">
                    Back to Progress Tracker
                </a>
                <a href="{{ route('admin.concepts') }}" class="px-4 py-2 bg-accent hover:bg-accent/80 text-white rounded text-sm transition-all">
                    Manage Concepts
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
            <h2 class="text-lg font-semibold mb-4">Upload Student Configurations CSV</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Upload Form -->
                <div>
                    <form action="{{ route('admin.config.upload-csv') }}" method="POST" enctype="multipart/form-data">
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
        <!-- Add New Configuration Form -->
        <div class="glass-effect rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Add New Student Configuration</h2>
            <form action="{{ route('admin.config.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="student_first_name" class="block text-sm font-medium text-gray-300 mb-1">First Name *</label>
                        <input type="text" name="student_first_name" id="student_first_name" required 
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all"
                               value="{{ old('student_first_name') }}">
                        @error('student_first_name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="student_last_name" class="block text-sm font-medium text-gray-300 mb-1">Last Name</label>
                        <input type="text" name="student_last_name" id="student_last_name"
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all"
                               value="{{ old('student_last_name') }}">
                        @error('student_last_name')
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
                        <label for="class_day_1" class="block text-sm font-medium text-gray-300 mb-1">Class Day 1</label>
                        <select name="class_day_1" id="class_day_1"
                                class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                            <option value="">Select Day</option>
                            <option value="Monday" {{ old('class_day_1') == 'Monday' ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ old('class_day_1') == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ old('class_day_1') == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ old('class_day_1') == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ old('class_day_1') == 'Friday' ? 'selected' : '' }}>Friday</option>
                            <option value="Saturday" {{ old('class_day_1') == 'Saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="Sunday" {{ old('class_day_1') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                        @error('class_day_1')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="class_day_2" class="block text-sm font-medium text-gray-300 mb-1">Class Day 2</label>
                        <select name="class_day_2" id="class_day_2"
                                class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                            <option value="">Select Day</option>
                            <option value="Monday" {{ old('class_day_2') == 'Monday' ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ old('class_day_2') == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ old('class_day_2') == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ old('class_day_2') == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ old('class_day_2') == 'Friday' ? 'selected' : '' }}>Friday</option>
                            <option value="Saturday" {{ old('class_day_2') == 'Saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="Sunday" {{ old('class_day_2') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                        @error('class_day_2')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pattern" class="block text-sm font-medium text-gray-300 mb-1">Pattern *</label>
                        <input type="text" name="pattern" id="pattern" required placeholder="e.g., 2:3:2"
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all"
                               value="{{ old('pattern') }}">
                        @error('pattern')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    

                    <div>
                        <label for="month" class="block text-sm font-medium text-gray-300 mb-1">Month *</label>
                        <select name="month" id="month" required
                                class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                            <option value="">Select Month</option>
                            <option value="January" {{ old('month') == 'January' ? 'selected' : '' }}>January</option>
                            <option value="February" {{ old('month') == 'February' ? 'selected' : '' }}>February</option>
                            <option value="March" {{ old('month') == 'March' ? 'selected' : '' }}>March</option>
                            <option value="April" {{ old('month') == 'April' ? 'selected' : '' }}>April</option>
                            <option value="May" {{ old('month') == 'May' ? 'selected' : '' }}>May</option>
                            <option value="June" {{ old('month') == 'June' ? 'selected' : '' }}>June</option>
                            <option value="July" {{ old('month') == 'July' ? 'selected' : '' }}>July</option>
                            <option value="August" {{ old('month') == 'August' ? 'selected' : '' }}>August</option>
                            <option value="September" {{ old('month') == 'September' ? 'selected' : '' }}>September</option>
                            <option value="October" {{ old('month') == 'October' ? 'selected' : '' }}>October</option>
                            <option value="November" {{ old('month') == 'November' ? 'selected' : '' }}>November</option>
                            <option value="December" {{ old('month') == 'December' ? 'selected' : '' }}>December</option>
                        </select>
                        @error('month')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-300 mb-1">Year *</label>
                        <input type="number" name="year" id="year" min="2020" max="2030" required value="{{ old('year', date('Y')) }}"
                               class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
                        @error('year')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-gradient-to-r from-primary to-secondary text-white px-6 py-2 rounded font-medium hover:shadow-lg hover:-translate-y-0.5 transition-all">
                        Add Configuration
                    </button>
                </div>
            </form>
        </div>
 
        <!-- Existing Configurations -->
        <div class="glass-effect rounded-lg overflow-hidden">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-lg font-semibold gradient-text">Existing Student Configurations</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800/90">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Student</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Subject</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Class Days</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Pattern</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Month</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Year</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($configs as $config)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors" id="config-row-{{ $config->id }}">
                            <td class="px-4 py-3">
                                <strong>{{ $config->full_name }}</strong>
                            </td>
                            <td class="px-4 py-3">{{ $config->subject }}</td>
                            <td class="px-4 py-3">
                                {{ $config->class_day_1 }}{{ $config->class_day_2 ? ', ' . $config->class_day_2 : '' }}
                            </td>
                            <td class="px-4 py-3">{{ $config->pattern }}</td>
                            <td class="px-4 py-3">{{ $config->month }}</td>
                            <td class="px-4 py-3">{{ $config->year }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button onclick="editConfig({{ $config->id }})" 
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs transition-all">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.config.destroy', $config) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this configuration?')"
                                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-all">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Hidden edit form for this config -->
                        <tr id="edit-form-{{ $config->id }}" class="hidden bg-gray-800/50">
                            <td colspan="8" class="px-4 py-6">
                                <form action="{{ route('admin.config.update', $config) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">First Name *</label>
                                            <input type="text" name="student_first_name" required value="{{ $config->student_first_name }}"
                                                   class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Last Name</label>
                                            <input type="text" name="student_last_name" value="{{ $config->student_last_name }}"
                                                   class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Subject *</label>
                                            <input type="text" name="subject" required value="{{ $config->subject }}"
                                                   class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Class Day 1</label>
                                            <select name="class_day_1" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                                                <option value="">Select Day</option>
                                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <option value="{{ $day }}" {{ $config->class_day_1 == $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Class Day 2</label>
                                            <select name="class_day_2" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                                                <option value="">Select Day</option>
                                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <option value="{{ $day }}" {{ $config->class_day_2 == $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Pattern *</label>
                                            <input type="text" name="pattern" required value="{{ $config->pattern }}"
                                                   class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Month *</label>
                                            <select name="month" required class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary">
                                                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                                    <option value="{{ $month }}" {{ $config->month == $month ? 'selected' : '' }}>{{ $month }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Year *</label>
                                            <input type="number" name="year" min="2020" max="2030" required value="{{ $config->year }}"
                                                   class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-xs">
                                        </div>
                                    </div>
                                    <div class="flex gap-2 justify-end">
                                        <button type="button" onclick="cancelEdit({{ $config->id }})"
                                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-1 rounded text-xs transition-all">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-1 rounded text-xs transition-all">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                No student configurations found. Add one above to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
  
        function editConfig(id) {
            // Hide the regular row and show the edit form
            document.getElementById('config-row-' + id).classList.add('hidden');
            document.getElementById('edit-form-' + id).classList.remove('hidden');
        }

        function cancelEdit(id) {
            // Show the regular row and hide the edit form
            document.getElementById('config-row-' + id).classList.remove('hidden');
            document.getElementById('edit-form-' + id).classList.add('hidden');
        }
    </script>
</body>
</html>

