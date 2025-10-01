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
    <!-- Updated Navigation Section for app.blade.php -->
<!-- Updated Navigation Section for app.blade.php -->
<nav class="glass-effect border-b border-white/10 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo/Brand -->
            <div class="flex items-center space-x-4">
                <a href="{{ route('student-progress.index') }}" class="text-xl font-bold gradient-text">
                    Lesson Plan Tracker
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1">
                <!-- Main Navigation Links -->
                @if(auth()->user()->canEdit() || auth()->user()->hasRole('viewer'))
                    <a href="{{ route('student-progress.index') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('student-progress.*') ? 'bg-primary text-white shadow-lg' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        Dashboard
                    </a>
                @endif

                @if(auth()->user()->canGrade())
                    <a href="{{ route('grading.index') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('grading*') ? 'bg-primary text-white shadow-lg' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        Grading
                    </a>
                @endif

                @if(auth()->user()->canManageConfig())
                <!-- Admin Dropdown -->
                <div class="relative group">
                    <button class="flex items-center space-x-1 px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.*') && !request()->routeIs('admin.users.*') ? 'bg-primary text-white shadow-lg' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <span>Admin</span>
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-gray-800/95 backdrop-blur-lg border border-white/20 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="py-2">
                            <a href="{{ route('admin.config') }}" 
                               class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white transition-all {{ request()->routeIs('admin.config*') ? 'bg-primary/20 text-primary' : '' }}">
                                Student Config
                            </a>
                            <a href="{{ route('admin.concepts') }}" 
                               class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white transition-all {{ request()->routeIs('admin.concepts*') ? 'bg-primary/20 text-primary' : '' }}">
                                New Concepts
                            </a>
                            <a href="{{ route('admin.setup') }}" 
                               class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white transition-all {{ request()->routeIs('admin.setup') ? 'bg-primary/20 text-primary' : '' }}">
                                Setup Data
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                @if(auth()->user()->canManageUsers())
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-primary text-white shadow-lg' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    Users
                </a>
                @endif

                <!-- User Menu -->
                <div class="relative ml-4">
                    <button id="userMenuButton" class="flex items-center space-x-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-r from-primary to-secondary rounded-full flex items-center justify-center text-white text-sm font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-gray-400">{{ auth()->user()->role_display }}</div>
                            </div>
                        </div>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-64 bg-gray-800/95 backdrop-blur-lg border border-white/20 rounded-lg shadow-xl z-50">
                        <div class="py-2">
                            <div class="px-4 py-3 text-xs text-gray-400 border-b border-white/10">
                                <div class="font-medium text-gray-200">{{ auth()->user()->name }}</div>
                                <div class="text-gray-400 mb-2">{{ auth()->user()->email }}</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach(auth()->user()->getRoles() as $role)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                            @if($role === 'admin') bg-red-500 text-white
                                            @elseif($role === 'planner') bg-blue-500 text-white
                                            @elseif($role === 'grader') bg-green-500 text-white
                                            @else bg-gray-500 text-white
                                            @endif">
                                            {{ App\Models\User::getRoleLabels()[$role] ?? ucfirst($role) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <a href="{{ route('change-password') }}" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2m0 0V7a2 2 0 012-2m0 0V5a2 2 0 012-2h4a2 2 0 012 2v2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Change Password
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" class="flex items-center w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-white/10 hover:text-red-300 transition-all">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobileMenuButton" class="p-2 rounded-lg text-gray-300 hover:bg-white/10 hover:text-white transition-all">
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
                <button id="closeMobileMenu" class="p-2 rounded-lg text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="flex-1 py-4 space-y-1 px-3">
                @if(auth()->user()->canEdit() || auth()->user()->hasRole('viewer'))
                    <a href="{{ route('student-progress.index') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('student-progress.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                @endif

                @if(auth()->user()->canGrade())
                    <a href="{{ route('grading.index') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('grading*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Grading
                    </a>
                @endif

                @if(auth()->user()->canManageConfig())
                    <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</div>
                    <a href="{{ route('admin.config') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.config*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Student Config
                    </a>

                    <a href="{{ route('admin.concepts') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.concepts*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        New Concepts
                    </a>

                    <a href="{{ route('admin.setup') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.setup') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Setup Data
                    </a>
                @endif

                @if(auth()->user()->canManageUsers())
                    <a href="{{ route('admin.users.index') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Users
                    </a>

                    <a href="{{ route('admin.activity-logs.index') }}" 
                       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.activity-logs.*') ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Logs
                    </a>
                @endif
            </div>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-primary to-secondary rounded-full flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-400">{{ auth()->user()->email }}</div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach(auth()->user()->getRoles() as $role)
                                <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold
                                    @if($role === 'admin') bg-red-500 text-white
                                    @elseif($role === 'planner') bg-blue-500 text-white
                                    @elseif($role === 'grader') bg-green-500 text-white
                                    @else bg-gray-500 text-white
                                    @endif">
                                    {{ App\Models\User::getRoleLabels()[$role] ?? ucfirst($role) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="space-y-1">
                    <a href="{{ route('change-password') }}" class="flex items-center px-3 py-2 text-sm text-gray-300 hover:bg-white/10 hover:text-white rounded-lg transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2m0 0V7a2 2 0 012-2m0 0V5a2 2 0 012-2h4a2 2 0 012 2v2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Change Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full text-left px-3 py-2 text-sm text-red-400 hover:bg-white/10 hover:text-red-300 rounded-lg transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                </div>
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