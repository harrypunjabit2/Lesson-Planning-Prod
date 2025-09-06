<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lesson Plan Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#8b5cf6'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="text-white flex items-center justify-center min-h-screen p-4">
    <div class="glass-effect rounded-2xl p-8 w-full max-w-md shadow-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold gradient-text mb-2">Lesson Plan Tracker</h1>
            <p class="text-gray-300 text-sm">Sign in to your account</p>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-red-400 font-medium">Error</span>
                </div>
                <ul class="text-red-300 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Success Messages -->
        @if (session('success'))
            <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-green-400">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf
            
            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                    Email Address
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required 
                    autofocus
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:bg-white/15 focus:border-primary focus:outline-none transition-all"
                    placeholder="Enter your email"
                >
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                    Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:bg-white/15 focus:border-primary focus:outline-none transition-all"
                    placeholder="Enter your password"
                >
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="remember" 
                    name="remember"
                    class="h-4 w-4 text-primary bg-white/10 border-white/20 rounded focus:ring-primary focus:ring-2"
                >
                <label for="remember" class="ml-2 block text-sm text-gray-300">
                    Remember me
                </label>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-primary to-secondary text-white font-semibold py-3 rounded-lg hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
            >
                Sign In
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-gray-400 text-xs">
                Lesson Plan Tracker &copy; {{ date('Y') }}
            </p>
        </div>
    </div>

    <!-- JavaScript for form validation feedback -->
    <script>
        // Add some interactive feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[type="email"], input[type="password"]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-400');
                        this.classList.remove('border-white/20');
                    } else {
                        this.classList.remove('border-red-400');
                        this.classList.add('border-white/20');
                    }
                });
            });
        });
    </script>
</body>
</html>