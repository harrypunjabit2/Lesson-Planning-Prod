<!-- resources/views/layouts/app.blade.php - FIXED VERSION -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Lesson Plan Tracker')</title>
    
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
        
        .class-day-row {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            border-left: 4px solid #3b82f6;
        }
        
        .loading-spinner::before {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            padding: 16px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        @media (max-width: 768px) {
            .mobile-menu {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            
            .mobile-menu.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="text-white">
    @auth
    <!-- Navigation -->
    <nav class="glass-effect border-b border-white/10 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('student-progress.index') }}" class="text-xl font-bold gradient-text">Lesson Plan Tracker</a>
                    <span class="hidden sm:inline-block text-sm text-gray-400">
                        Welcome, {{ auth()->user()->name }}
                    </span>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Main Navigation Links -->
                    <a href="{{ route('student-progress.index') }}" 
                       class="px-3 py-2 rounded text-sm font-medium transition-all {{ request()->routeIs('student-progress.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        Lesson Plans
                    </a>

                    @if(auth()->user()->canEdit())
                        <a href="{{ route('admin.config') }}" 
                           class="px-3 py-2 rounded text-sm font-medium transition-all {{ request()->routeIs('admin.config*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            Student Config
                        </a>

                        <a href="{{ route('admin.concepts') }}" 
                           class="px-3 py-2 rounded text-sm font-medium transition-all {{ request()->routeIs('admin.concepts*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            New Concepts
                        </a>

                        

                        <a href="{{ route('admin.setup') }}" 
                           class="px-3 py-2 rounded text-sm font-medium transition-all {{ request()->routeIs('admin.setup') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            Setup Data
                        </a>
                    @endif

                    @if(auth()->user()->canManageUsers())
                        <a href="{{ route('admin.users.index') }}" 
                           class="px-3 py-2 rounded text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            Users
                        </a>
                    @endif

                    <!-- User Menu Dropdown -->
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-2 px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                @if(auth()->user()->role === 'admin') bg-red-500 text-white
                                @elseif(auth()->user()->role === 'planner') bg-blue-500 text-white
                                @else bg-gray-500 text-white
                                @endif">
                                {{ auth()->user()->role_display }}
                            </span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-800/95 backdrop-blur-lg border border-white/20 rounded-lg shadow-lg z-50">
                            <div class="py-1">
                                <div class="px-4 py-2 text-xs text-gray-400 border-b border-white/10">
                                    Signed in as<br>
                                    <span class="font-medium text-gray-200">{{ auth()->user()->email }}</span>
                                </div>
                                <a href="{{ route('change-password') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 transition-all">
                                    Change Password
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-white/10 transition-all">
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobileMenuButton" class="p-2 rounded text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobileMenu" class="md:hidden mobile-menu fixed inset-y-0 left-0 w-64 bg-gray-900/95 backdrop-blur-lg border-r border-white/20 z-40">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-white/10">
                    <h2 class="text-lg font-bold gradient-text">Menu</h2>
                    <button id="closeMobileMenu" class="p-2 rounded text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 py-4 space-y-2">
                    <a href="{{ route('student-progress.index') }}" 
                       class="block px-4 py-2 text-sm font-medium transition-all {{ request()->routeIs('student-progress.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        Lesson Plans
                    </a>

                    @if(auth()->user()->canEdit())
                        <a href="{{ route('admin.config') }}" 
                           class="block px-4 py-2 text-sm font-medium transition-all {{ request()->routeIs('admin.config*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            Student Config
                        </a>

                        <a href="{{ route('admin.concepts') }}" 
                           class="block px-4 py-2 text-sm font-medium transition-all {{ request()->routeIs('admin.concepts*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            Concepts
                        </a>

                        <a href="{{ route('admin.setup') }}" 
                           class="block px-4 py-2 text-sm font-medium transition-all {{ request()->routeIs('admin.setup') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            Setup Data
                        </a>
                    @endif

                    @if(auth()->user()->canManageUsers())
                        <a href="{{ route('admin.users.index') }}" 
                           class="block px-4 py-2 text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                            User Management
                        </a>
                    @endif
                </div>

                <div class="border-t border-white/10 p-4 space-y-2">
                    <div class="text-xs text-gray-400 mb-2">
                        {{ auth()->user()->name }}<br>
                        <span class="text-gray-500">{{ auth()->user()->email }}</span>
                    </div>
                    <a href="{{ route('change-password') }}" class="block px-2 py-1 text-sm text-gray-300 hover:bg-white/10 rounded transition-all">
                        Change Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-2 py-1 text-sm text-red-400 hover:bg-white/10 rounded transition-all">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile menu overlay -->
        <div id="mobileMenuOverlay" class="hidden md:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-30"></div>
    </nav>
    @endauth

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="notification success show" id="successNotification">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="notification error show" id="errorNotification">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- CSRF Token for JavaScript -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Navigation JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // User menu dropdown
            const userMenuButton = document.getElementById('userMenuButton');
            const userMenuDropdown = document.getElementById('userMenuDropdown');
            
            if (userMenuButton && userMenuDropdown) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenuDropdown.classList.toggle('hidden');
                });
            }

            // Mobile menu
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const closeMobileMenu = document.getElementById('closeMobileMenu');

            function openMobileMenu() {
                if (mobileMenu) {
                    mobileMenu.classList.add('open');
                    mobileMenuOverlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeMobileMenuFn() {
                if (mobileMenu) {
                    mobileMenu.classList.remove('open');
                    mobileMenuOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            }

            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', openMobileMenu);
            }
            if (closeMobileMenu) {
                closeMobileMenu.addEventListener('click', closeMobileMenuFn);
            }
            if (mobileMenuOverlay) {
                mobileMenuOverlay.addEventListener('click', closeMobileMenuFn);
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function() {
                if (userMenuDropdown) {
                    userMenuDropdown.classList.add('hidden');
                }
            });

            // Auto-hide notifications
            setTimeout(() => {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(notification => {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                });
            }, 5000);
        });

        // Global notification functions
        function showSuccess(message) {
            showNotification(message, 'success');
        }

        function showError(message) {
            showNotification(message, 'error');
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        ${type === 'success' 
                            ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>'
                            : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
                        }
                    </svg>
                    ${message}
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Global loading function
        function showLoading(show, message = 'Loading...') {
            const loading = document.getElementById('loading');
            if (loading) {
                loading.classList.toggle('hidden', !show);
                if (show && message) {
                    const spinner = loading.querySelector('.loading-spinner');
                    if (spinner) {
                        spinner.textContent = message;
                    }
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>