<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ckb', 'ar']) ? 'rtl' : 'ltr' }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kezi ERP — {{ __('Intelligence for Your Business') }}</title>

    <!-- SEO -->
    <meta name="description" content="{{ __('Kezi is the ultimate enterprise command center. Immutable, secure, and built for scale.') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700|inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --brand-primary: #f59e0b;
            --brand-secondary: #0ea5e9;
            --bg-deep: #020617;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-deep);
            color: #f8fafc;
        }

        /* Adjust font for Kurdish if needed, though Inter usually supports it well or falls back */
        html[dir="rtl"] body {
            font-family: 'Inter', sans-serif; /* Ensure RTL fontstack if different later */
        }

        .font-display {
            font-family: 'Outfit', sans-serif;
        }

        .glass {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-gradient {
            background: linear-gradient(to right, #f59e0b, #fbbf24, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-mesh {
            background-image: 
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(14, 165, 233, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(245, 158, 11, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(14, 165, 233, 0.05) 0px, transparent 50%);
        }

        .glow {
            box-shadow: 0 0 40px -10px rgba(245, 158, 11, 0.3);
        }

        .screenshot-frame {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .screenshot-frame::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 24px;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10;
        }

        /* Animations */
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse-slow {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.8s ease-out forwards;
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .animate-pulse-slow {
            animation: pulse-slow 4s ease-in-out infinite;
        }

        /* Scroll Trigger Classes */
        .scroll-trigger {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .scroll-trigger.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Stagger delays for children */
        .delay-100 { transition-delay: 100ms; animation-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; animation-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; animation-delay: 300ms; }
    </style>
</head>

<body class="antialiased bg-mesh selection:bg-amber-500/30">

    <!-- Navbar -->
    <nav class="fixed top-0 z-50 w-full px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between glass rounded-2xl px-6 py-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center font-display font-bold text-slate-950">K</div>
                <span class="text-xl font-display font-bold tracking-tight text-white">{{ __('Kezi ERP') }}</span>
            </div>
            
            <div class="hidden lg:flex items-center gap-8 text-sm font-medium text-slate-400">
                <a href="#clusters" class="hover:text-amber-500 transition-colors">{{ __('ERP Clusters') }}</a>
                <a href="#compliance" class="hover:text-amber-500 transition-colors">{{ __('Compliance') }}</a>
                <a href="#intelligence" class="hover:text-amber-500 transition-colors">{{ __('Intelligence') }}</a>
                <a href="/docs" class="hover:text-amber-500 transition-colors">{{ __('Documentation') }}</a>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative group py-2">
                    <button class="flex items-center gap-1 text-sm font-medium text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h2.935M18 9v1a2 2 0 01-2 2h-1a2 2 0 00-2 2v1a2 2 0 01-2 2H8.945M18 10a2 2 0 002 2h1.055"></path></svg>
                        <span class="uppercase">{{ app()->getLocale() }}</span>
                    </button>
                    <div class="absolute right-0 top-full pt-2 w-24 hidden group-hover:block">
                        <div class="glass rounded-xl overflow-hidden shadow-2xl">
                            <a href="/lang/en" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">English</a>
                            <a href="/lang/ckb" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">کوردی</a>
                            <a href="/lang/ar" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">العربية</a>
                        </div>
                    </div>
                </div>

                <a href="/kezi/login" class="text-sm font-medium text-slate-300 hover:text-amber-500 transition-colors">{{ __('Sign in') }}</a>
                <a href="https://github.com/Xoshbin/kezi-ERP" target="_blank" class="flex items-center gap-2 text-sm font-medium text-slate-300 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path></svg>
                    {{ __('GitHub') }}
                </a>
                <a href="/kezi/register" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold rounded-xl transition-all glow">
                    {{ __('Get Started') }}
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="relative pt-40 pb-32 px-6 overflow-hidden">
        <div class="max-w-6xl mx-auto flex flex-col items-center text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-500 text-xs font-bold uppercase tracking-widest mb-8">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                {{ __('Next-Gen Open-Source ERP') }}
            </div>
            
            <h1 class="text-6xl md:text-8xl font-display font-bold tracking-tight mb-8 leading-tight text-white max-w-4xl animate-fade-in-up">
                {{ __('One Core.') }} <br>
                <span class="text-gradient">{{ __('Fully Open Source.') }}</span>
            </h1>
            
            <p class="text-lg md:text-2xl text-slate-400 max-w-3xl mx-auto mb-12 leading-relaxed animate-fade-in-up delay-100">
                {{ __('Kezi unifies your entire business operations—from deep accounting to global logistics—into a single, high-performance open-source command center.') }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 mb-20 animate-fade-in-up delay-200">
                <a href="https://github.com/Xoshbin/kezi-ERP" target="_blank" class="w-full sm:w-auto px-10 py-5 bg-white text-slate-950 text-lg font-bold rounded-2xl hover:bg-slate-100 transition-all transform hover:scale-105 shadow-2xl glow flex items-center justify-center gap-2">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path></svg>
                    {{ __('View on GitHub') }}
                </a>
                <a href="/kezi/register" class="w-full sm:w-auto px-10 py-5 glass text-white text-lg font-bold rounded-2xl hover:bg-white/5 transition-all flex items-center justify-center gap-2">
                    {{ __('Get Started') }}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>

            <!-- Hero Image Visualization -->
            <!-- CSS-Only Abstract Dashboard -->
            <div id="dashboard" class="max-w-6xl mx-auto mt-24 px-4 text-left delay-300">
                <div class="relative glass rounded-3xl p-1 md:p-4 border-amber-500/20 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-b from-amber-500/5 to-transparent pointer-events-none"></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                        <!-- Card 1: Revenue Momentum -->
                        <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 relative overflow-hidden group hover:border-amber-500/30 transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('Monthly Revenue') }}</div>
                                    <div class="text-3xl font-display font-bold text-white group-hover:text-amber-500 transition-colors">$124,500</div>
                                </div>
                                <div class="flex items-center gap-1 text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg text-xs font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    <span>+12.5%</span>
                                </div>
                            </div>
                            <div class="h-16 flex items-end gap-1">
                                <div class="w-1/6 bg-amber-500/20 h-[40%] rounded-t-sm"></div>
                                <div class="w-1/6 bg-amber-500/30 h-[60%] rounded-t-sm"></div>
                                <div class="w-1/6 bg-amber-500/40 h-[50%] rounded-t-sm"></div>
                                <div class="w-1/6 bg-amber-500/60 h-[70%] rounded-t-sm"></div>
                                <div class="w-1/6 bg-amber-500/80 h-[85%] rounded-t-sm"></div>
                                <div class="w-1/6 bg-amber-500 h-[100%] rounded-t-sm animate-pulse"></div>
                            </div>
                        </div>

                        <!-- Card 2: Inventory Status -->
                        <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 relative overflow-hidden group hover:border-sky-500/30 transition-all">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('Inventory Status') }}</div>
                                    <div class="text-3xl font-display font-bold text-white group-hover:text-sky-500 transition-colors">$842,200</div>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-sky-500/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">{{ __('Stock On Hand') }}</span>
                                    <span class="text-white font-mono">12,450 Units</span>
                                </div>
                                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-sky-500 w-[75%]"></div>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-amber-500">{{ __('Low Stock Alerts') }}</span>
                                    <span class="text-amber-500 font-bold">5 Items</span>
                                </div>
                                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500 w-[15%]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Net Profit -->
                        <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 relative overflow-hidden group hover:border-emerald-500/30 transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('Net Profit') }}</div>
                                    <div class="text-3xl font-display font-bold text-emerald-400">$38,900</div>
                                </div>
                                <div class="flex items-center gap-1 text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg text-xs font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    <span>24%</span>
                                </div>
                            </div>
                            <div class="relative h-24 mt-4 w-full">
                                <!-- Abstract Chart Line -->
                                <svg class="absolute inset-0 w-full h-full text-emerald-500/20" preserveAspectRatio="none" viewBox="0 0 100 100">
                                    <path d="M0 100 L0 60 L20 70 L40 40 L60 50 L80 20 L100 10 L100 100 Z" fill="currentColor"></path>
                                    <path d="M0 60 L20 70 L40 40 L60 50 L80 20 L100 10" fill="none" stroke="#10b981" stroke-width="2" vector-effect="non-scaling-stroke"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Abstract Data Table / List -->
                    <div class="mt-4 md:mt-6 bg-slate-950/80 rounded-2xl border border-white/5 p-4 overflow-hidden">
                        <div class="flex items-center justify-between mb-4 px-2">
                            <span class="text-xs font-bold text-slate-500 uppercase">{{ __('Recent Activity') }}</span>
                            <div class="flex gap-2">
                                <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <!-- Item 1 -->
                            <div class="flex items-center justify-between py-2 px-3 hover:bg-white/5 rounded-lg transition-colors cursor-default border-b border-white/5 last:border-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-200">{{ __('Inv #2024 Paid') }}</div>
                                        <div class="text-xs text-slate-500">{{ __('2 mins ago') }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-mono text-emerald-400">+$2,450.00</div>
                                </div>
                            </div>
                            <!-- Item 2 -->
                            <div class="flex items-center justify-between py-2 px-3 hover:bg-white/5 rounded-lg transition-colors cursor-default border-b border-white/5 last:border-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-200">{{ __('Low Stock: MacBook Pro') }}</div>
                                        <div class="text-xs text-slate-500">{{ __('15 mins ago') }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-mono text-slate-200">5 {{ __('Units') }}</div>
                                </div>
                            </div>
                            <!-- Item 3 -->
                            <div class="flex items-center justify-between py-2 px-3 hover:bg-white/5 rounded-lg transition-colors cursor-default border-b border-white/5 last:border-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center text-sky-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-200">{{ __('Stock Move #WH/IN/004') }}</div>
                                        <div class="text-xs text-slate-500">{{ __('1 hour ago') }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-mono text-slate-200">+150 {{ __('Units') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ERP Clusters Section -->
    <section id="clusters" class="py-32 px-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-20 scroll-trigger">
                <h2 class="text-4xl md:text-6xl font-display font-bold mb-6 text-white">{{ __('Integrated') }} <span class="text-gradient">{{ __('ERP Clusters') }}</span></h2>
                <p class="text-slate-400 text-lg md:text-xl max-w-3xl mx-auto italic">
                    {{ __('Stop jumping between software. Kezi groups your business operations into powerful, native clusters that share a single source of truth.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 scroll-trigger delay-200">
                <!-- Cluster 1: Finance -->
                <div class="group relative bg-slate-900/40 rounded-[2.5rem] border border-white/5 p-8 overflow-hidden hover:border-amber-500/30 transition-all duration-500">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-all"></div>
                    <div class="h-48 w-full bg-gradient-to-br from-slate-800/50 to-slate-900/50 rounded-2xl mb-8 border border-white/5 flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                        <div class="text-amber-500/20">
                            <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center text-amber-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-display font-bold text-white">{{ __('Finance & Strategy') }}</h3>
                    </div>
                    <p class="text-slate-400 mb-6 text-sm leading-relaxed">
                        {{ __('Deeply integrated accounting, asset management, and payment processing with multi-currency support.') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Accounting') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Asset Mgmt') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Payments') }}</span>
                    </div>
                </div>

                <!-- Cluster 2: Supply Chain -->
                <div class="group relative bg-slate-900/40 rounded-[2.5rem] border border-white/5 p-8 overflow-hidden hover:border-sky-500/30 transition-all duration-500">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-sky-500/5 rounded-full blur-3xl group-hover:bg-sky-500/10 transition-all"></div>
                    <div class="h-48 w-full bg-gradient-to-br from-slate-800/50 to-slate-900/50 rounded-2xl mb-8 border border-white/5 flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                        <div class="text-sky-500/20">
                            <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center text-sky-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-display font-bold text-white">{{ __('Supply Chain') }}</h3>
                    </div>
                    <p class="text-slate-400 mb-6 text-sm leading-relaxed">
                        {{ __('End-to-end logistics from smart inventory to omnichannel sales and automated purchasing.') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Inventory') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Sales') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Purchase') }}</span>
                    </div>
                </div>

                <!-- Cluster 3: Manufacturing -->
                <div class="group relative bg-slate-900/40 rounded-[2.5rem] border border-white/5 p-8 overflow-hidden hover:border-amber-500/30 transition-all duration-500">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-all"></div>
                    <div class="h-48 w-full bg-gradient-to-br from-slate-800/50 to-slate-900/50 rounded-2xl mb-8 border border-white/5 flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                        <div class="text-amber-500/20">
                            <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center text-amber-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-2xl font-display font-bold text-white">{{ __('Manufacturing') }}</h3>
                    </div>
                    <p class="text-slate-400 mb-6 text-sm leading-relaxed">
                        {{ __('Precise production planning (MRP II), work center management, and strict quality control.') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Production') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Quality') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Maintenance') }}</span>
                    </div>
                </div>

                <!-- Cluster 4: HR -->
                <div class="group relative bg-slate-900/40 rounded-[2.5rem] border border-white/5 p-8 overflow-hidden hover:border-purple-500/30 transition-all duration-500">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-purple-500/5 rounded-full blur-3xl group-hover:bg-purple-500/10 transition-all"></div>
                    <div class="h-48 w-full bg-gradient-to-br from-slate-800/50 to-slate-900/50 rounded-2xl mb-8 border border-white/5 flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                        <div class="text-purple-500/20">
                            <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-500/10 border border-purple-500/20 rounded-xl flex items-center justify-center text-purple-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-display font-bold text-white">{{ __('Human Resources') }}</h3>
                    </div>
                    <p class="text-slate-400 mb-6 text-sm leading-relaxed">
                        {{ __('Modern talent management with automated payroll and comprehensive employee lifecycles.') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Payroll') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Employee') }}</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Attendance') }}</span>
                    </div>
                </div>

                <!-- Cluster 5: Operations -->
                <div class="group relative bg-slate-900/40 rounded-[2.5rem] border border-white/5 p-8 overflow-hidden hover:border-emerald-500/30 transition-all duration-500 md:col-span-2">
                    <div class="grid md:grid-cols-2 gap-8 items-center h-full">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center justify-center text-emerald-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <h3 class="text-2xl font-display font-bold text-white">{{ __('Operational Core') }}</h3>
                            </div>
                            <p class="text-slate-400 mb-6 text-sm leading-relaxed">
                                {{ __('The technical foundation of Kezi—Project Management, custom field flexibility, and deep security audits.') }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Project') }}</span>
                                <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Custom Fields') }}</span>
                                <span class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ __('Security') }}</span>
                            </div>
                        </div>
                        <div class="relative bg-slate-950/50 rounded-2xl p-4 border border-white/5">
                             <!-- Activity Mockup -->
                             <div class="space-y-3">
                                 <div class="flex items-center gap-2">
                                     <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                     <div class="h-1.5 w-24 bg-slate-800 rounded-full"></div>
                                 </div>
                                 <div class="h-1.5 w-full bg-slate-900 rounded-full"></div>
                                 <div class="h-1.5 w-4/5 bg-slate-900 rounded-full"></div>
                                 <div class="flex items-center gap-2 pt-2">
                                     <div class="w-4 h-4 rounded-full bg-amber-500"></div>
                                     <div class="h-1.5 w-16 bg-slate-800 rounded-full"></div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Operational Excellence (Bento Grid - Iraq Localized) -->
    <section id="compliance" class="py-24 px-6 relative overflow-hidden bg-slate-950/50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 scroll-trigger">
                <h2 class="text-3xl md:text-5xl font-display font-bold mb-4 text-white">{{ __('Global Standard,') }} <span class="text-gradient">{{ __('Local Depth') }}</span></h2>
                <p class="text-slate-400 max-w-2xl mx-auto">{{ __('The only ERP engineered specifically for the 2026 Unified Accounting System and Iraqi market dynamics, while maintaining global IFRS standard.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 scroll-trigger delay-200">
                <!-- Large Item: Dual Currency -->
                <div class="md:col-span-2 lg:col-span-2 glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-colors"></div>
                    <div class="w-12 h-12 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-display font-bold text-white mb-2">{{ __('Iraqi Dual-Currency') }}</h3>
                    <p class="text-slate-400 text-sm mb-6 max-w-xs">{{ __('Seamlessly operate in IQD and USD simultaneously. Automatic daily market rate updates ensure precise financial reporting for Iraqi companies.') }}</p>
                    <div class="inline-flex items-center gap-2 text-xs font-bold text-amber-500 uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        {{ __('CBI & Parallel Market Sync') }}
                    </div>
                </div>

                <!-- Small Item: Compliance -->
                <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-between group">
                    <div class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="text-4xl font-display font-bold text-white mb-1">{{ __('2026') }}</div>
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{{ __('UAS & IFRS Certified') }}</div>
                        <p class="text-slate-400 text-xs">{{ __('Fully compliant with the 2026 Unified Accounting System.') }}</p>
                    </div>
                </div>

                <!-- Small Item: Tax -->
                <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-between group border-amber-500/10">
                    <div class="w-10 h-10 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-white mb-2">{{ __('Tax Automation') }}</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">{{ __('Automated calculation of Corporate Tax and Social Security (Istiqta\') per Iraqi regulations.') }}</p>
                    </div>
                </div>

                <!-- Medium Item: Localization -->
                <div class="md:col-span-2 glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                    <div class="grid md:grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-xl font-display font-bold text-white mb-4">{{ __('Native') }} <br><span class="text-sky-500 text-2xl">{{ __('Localization') }}</span></h3>
                            <p class="text-slate-400 text-sm leading-relaxed">{{ __('Built-in support for Kurdish (CKB), Arabic, and English. Switch languages instantly across the entire platform.') }}</p>
                        </div>
                        <div class="flex flex-col gap-3">
                            <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-sky-500 w-[75%]"></div>
                            </div>
                            <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-sky-500 to-amber-500 w-[45%]"></div>
                            </div>
                            <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-sky-500 w-[90%]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medium Item: Documentation -->
                <div class="md:col-span-2 glass p-8 rounded-[2.5rem] border-sky-500/10 group flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-sky-500/10 border border-sky-500/20 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xl font-display font-bold text-white mb-1">{{ __('Deep Documentation') }}</h4>
                            <p class="text-slate-400 text-sm max-w-md">{{ __('Expert-written guides covering everything from Ledger setup to MRP II workflows.') }}</p>
                        </div>
                    </div>
                    <a href="/docs" class="px-6 py-3 bg-sky-500/10 hover:bg-sky-500 text-sky-400 hover:text-white rounded-xl text-sm font-bold transition-all border border-sky-500/20 whitespace-nowrap">
                        {{ __('Learn More') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Tailored Section -->
    <section id="customization" class="py-24 px-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-center scroll-trigger">
                <!-- Left Content -->
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 text-xs font-bold uppercase tracking-widest mb-6">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        {{ __('Tailored to Your Vision') }}
                    </div>
                    <h2 class="text-4xl md:text-5xl font-display font-bold mb-6 text-white leading-tight">
                        {{ __('Your Business,') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">{{ __('Your Rules') }}</span>
                    </h2>
                    <p class="text-slate-400 text-lg leading-relaxed mb-8">
                        {{ __('Kezi adapts to your specific needs. Whether it\'s custom workflows, specialized reporting, or unique integration requirements, our platform is built to be molded around your business model.') }}
                    </p>
                    
                    <div class="space-y-6 mb-10">
                        <!-- Feature 1 -->
                        <div class="flex gap-4 group">
                            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center flex-shrink-0 group-hover:border-purple-500/50 transition-colors">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-lg mb-1 group-hover:text-purple-400 transition-colors">{{ __('Custom Workflows') }}</h4>
                                <p class="text-slate-500 text-sm">{{ __('Define your own approval processes and logic.') }}</p>
                            </div>
                        </div>
                        
                        <!-- Feature 2 -->
                        <div class="flex gap-4 group">
                            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center flex-shrink-0 group-hover:border-pink-500/50 transition-colors">
                                <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-lg mb-1 group-hover:text-pink-400 transition-colors">{{ __('Specialized Reporting') }}</h4>
                                <p class="text-slate-500 text-sm">{{ __('Get the insights that matter most to you.') }}</p>
                            </div>
                        </div>

                        <!-- Feature 3 -->
                        <div class="flex gap-4 group">
                            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center flex-shrink-0 group-hover:border-amber-500/50 transition-colors">
                                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-lg mb-1 group-hover:text-amber-400 transition-colors">{{ __('Seamless Integration') }}</h4>
                                <p class="text-slate-500 text-sm">{{ __('Connect with your existing tools and services.') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 bg-slate-900/50 rounded-2xl border border-purple-500/20 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/5 to-pink-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="relative z-10">
                            <h4 class="text-white font-bold mb-2">{{ __('Need a Specific Feature?') }}</h4>
                            <p class="text-slate-400 text-sm mb-6">{{ __('Our engineering team is ready to build custom modules tailored exactly to your requirements.') }}</p>
                            <a href="mailto:features@kezi.com?subject=Feature%20Request" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-purple-500/20 hover:shadow-purple-500/40 transform hover:-translate-y-1 w-full sm:w-auto">
                                {{ __('Request a Feature') }}
                                <svg class="w-5 h-5 ml-2 rtl:rotate-180 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Visual -->
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-purple-500/20 to-pink-500/20 rounded-[3rem] blur-3xl -z-10"></div>
                     <div class="glass rounded-[2rem] p-8 border border-white/10 relative overflow-hidden h-full min-h-[500px] flex items-center justify-center">
                        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20"></div>
                        
                        <!-- Visual Stack -->
                        <div class="relative w-full max-w-sm">
                            <!-- Background Card -->
                            <div class="absolute top-0 left-0 right-0 h-64 bg-slate-800 rounded-2xl border border-white/5 transform -rotate-6 scale-90 opacity-50"></div>
                            <div class="absolute top-4 left-2 right-2 h-64 bg-slate-800 rounded-2xl border border-white/5 transform -rotate-3 scale-95 opacity-70"></div>
                            
                            <!-- Main "Feature Request" Card -->
                            <div class="relative bg-slate-900 rounded-2xl border border-purple-500/30 p-6 shadow-2xl shadow-purple-900/20">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                                        </div>
                                        <div>
                                            <div class="text-white font-bold">{{ __('New Feature Request') }}</div>
                                            <div class="text-xs text-purple-400 font-mono">#REQ-2024-892</div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 bg-purple-500/20 text-purple-400 text-[10px] font-bold uppercase rounded-lg border border-purple-500/20">{{ __('In Progress') }}</span>
                                </div>
                                
                                <div class="space-y-3 mb-6">
                                    <div class="h-2 bg-slate-800 rounded-full w-3/4"></div>
                                    <div class="h-2 bg-slate-800 rounded-full w-full"></div>
                                    <div class="h-2 bg-slate-800 rounded-full w-5/6"></div>
                                </div>
                                
                                <div class="flex items-center justify-between pt-4 border-t border-white/5">
                                    <div class="flex -space-x-2">
                                        <div class="w-8 h-8 rounded-full bg-slate-700 border-2 border-slate-900"></div>
                                        <div class="w-8 h-8 rounded-full bg-slate-600 border-2 border-slate-900"></div>
                                        <div class="w-8 h-8 rounded-full bg-purple-600 border-2 border-slate-900 flex items-center justify-center text-[10px] text-white font-bold">+3</div>
                                    </div>
                                    <div class="text-xs text-slate-500">{{ __('Est. Delivery: 2 Days') }}</div>
                                </div>
                            </div>

                            <!-- "Approved" Badge -->
                            <div class="absolute -bottom-4 -right-4 bg-emerald-500 text-white px-4 py-2 rounded-lg shadow-lg font-bold text-sm flex items-center gap-2 animate-bounce">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                {{ __('Approved') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-32 px-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-amber-500/5 pointer-events-none"></div>
        <div class="max-w-4xl mx-auto text-center relative z-10 border border-white/10 rounded-[3rem] p-12 md:p-20 glass glow scroll-trigger">
            <h2 class="text-4xl md:text-6xl font-display font-bold mb-8 text-white text-gradient">{{ __('Ready to Automate?') }}</h2>
            <p class="text-xl text-slate-400 mb-12">
                {{ __('Join the forward-thinking businesses in Iraq and beyond.') }} <br class="hidden md:block"> {{ __('Scale with the power of Kezi.') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <a href="/kezi/register" class="w-full sm:w-auto px-10 py-5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xl font-bold rounded-2xl transition-all glow transform hover:scale-105">
                    {{ __('Get Started Now') }}
                </a>
                <a href="mailto:sales@kezi.com" class="w-full sm:w-auto px-10 py-5 glass text-white text-lg font-bold rounded-2xl hover:bg-white/5 transition-all">
                    {{ __('Talk to Sales') }}
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-16 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-start gap-12 mb-12">
                <div class="max-w-xs">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-8 h-8 bg-amber-500 rounded flex items-center justify-center font-display font-bold text-slate-950">K</div>
                        <span class="text-xl font-display font-bold text-white tracking-tight">{{ __('Kezi ERP') }}</span>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed">
                        {{ __('The ultimate enterprise command center. Designed for Iraqi and global businesses.') }}
                    </p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-12">
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">{{ __('Platform') }}</h4>
                        <ul class="space-y-4 text-sm text-slate-500">
                           <li><a href="#clusters" class="hover:text-amber-500 transition-colors">{{ __('ERP Clusters') }}</a></li>
                           <li><a href="#compliance" class="hover:text-amber-500 transition-colors">{{ __('Compliance') }}</a></li>
                           <li><a href="/docs" class="hover:text-amber-500 transition-colors">{{ __('Documentation') }}</a></li>
                        </ul>
                    </div>
                    <div>
                         <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">{{ __('Solutions') }}</h4>
                         <ul class="space-y-4 text-sm text-slate-500">
                            <li><a href="#" class="hover:text-amber-500 transition-colors">{{ __('Accounting') }}</a></li>
                            <li><a href="#" class="hover:text-amber-500 transition-colors">{{ __('Supply Chain') }}</a></li>
                            <li><a href="#" class="hover:text-amber-500 transition-colors">{{ __('Manufacturing') }}</a></li>
                         </ul>
                    </div>
                </div>
            </div>
            
            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-xs text-slate-600 font-medium">
                    © {{ date('Y') }} Kezi ERP. {{ __('All rights reserved. Intelligence for Your Business.') }}
                </div>
                <div class="flex gap-8 text-xs font-medium text-slate-600 uppercase tracking-widest">
                    <a href="#" class="hover:text-amber-500 transition-colors">{{ __('Security') }}</a>
                    <a href="#" class="hover:text-amber-500 transition-colors">{{ __('Privacy Policy') }}</a>
                    <a href="#" class="hover:text-amber-500 transition-colors">{{ __('Terms of Service') }}</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target); // Only animate once
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.scroll-trigger').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>

</html>
