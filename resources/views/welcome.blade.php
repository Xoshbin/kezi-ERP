<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kezi</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }

        .hero-section {
            background: linear-gradient(to right, #6366F1, #8B5CF6);
        }
    </style>
</head>

<body class="antialiased bg-gray-100 dark:bg-gray-900">
    <div class="flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-md">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="/" class="text-2xl font-bold text-gray-800 dark:text-white">Kezi</a>
                    </div>
                    <nav class="hidden md:flex items-center space-x-6">
                        @auth
                            <a href="{{ url('/kezi') }}"
                                class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium transition duration-200">Dashboard</a>
                        @else
                            <a href="{{ route('filament.kezi.auth.login') }}"
                                class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium transition duration-200">Login</a>
                            <a href="{{ route('filament.kezi.auth.register') }}"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-medium transition duration-200 shadow-sm">Register</a>
                        @endauth
                    </nav>
                    <div class="md:hidden">
                        <button class="text-gray-800 dark:text-white focus:outline-hidden">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16m-7 6h7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="grow">
            <!-- Hero Section -->
            <section class="hero-section text-white py-20">
                <div class="container mx-auto px-6 text-center">
                    <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-4">Streamline Your Business Operations
                    </h1>
                    <p class="text-lg md:text-xl mb-8">A powerful, intuitive, and scalable solution for managing your
                        enterprise resources.</p>
                    <a href="{{ url('/kezi') }}"
                        class="bg-white text-indigo-600 font-bold py-3 px-8 rounded-full hover:bg-gray-200 transition duration-300 shadow-lg">Get
                        Started</a>
                </div>
            </section>

            <!-- Features Section -->
            <section class="py-20">
                <div class="container mx-auto px-6">
                    <h2 class="text-3xl font-bold text-center text-gray-800 dark:text-white mb-12">Key Features</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Feature 1 -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg text-center">
                            <div class="text-indigo-500 dark:text-indigo-400 mb-4">
                                <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Bill Management</h3>
                            <p class="text-gray-600 dark:text-gray-300">A comprehensive workflow for creating, managing,
                                and paying bills with automated inventory and accounting updates.</p>
                        </div>
                        <!-- Feature 2 -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg text-center">
                            <div class="text-indigo-500 dark:text-indigo-400 mb-4">
                                <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Invoice Management</h3>
                            <p class="text-gray-600 dark:text-gray-300">Complete flow for creating, sending, and
                                receiving payments for invoices, with automated inventory deduction.</p>
                        </div>
                        <!-- Feature 3 -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg text-center">
                            <div class="text-indigo-500 dark:text-indigo-400 mb-4">
                                <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7v8a2 2 0 002 2h4M8 7a2 2 0 012-2h4a2 2 0 012 2v8a2 2 0 01-2 2h-4a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Financial Reporting</h3>
                            <p class="text-gray-600 dark:text-gray-300">Generate insightful financial reports, including
                                balance sheets and profit & loss statements, to make informed decisions.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 py-8">
            <div class="container mx-auto px-6">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <p class="text-gray-600 dark:text-gray-300">&copy; {{ date('Y') }} Kezi. All rights reserved.
                    </p>
                    <div class="flex mt-4 md:mt-0 space-x-6">
                        <a href="#"
                            class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Privacy
                            Policy</a>
                        <a href="#"
                            class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Terms
                            of Service</a>
                        <a href="#"
                            class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
