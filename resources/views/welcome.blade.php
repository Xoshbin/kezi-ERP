<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ckb', 'ar']) ? 'rtl' : 'ltr' }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kezi — {{ __('Intelligence for Your Business') }}</title>

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
    </style>
</head>

<body class="antialiased bg-mesh selection:bg-amber-500/30">

    <!-- Navbar -->
    <nav class="fixed top-0 z-50 w-full px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between glass rounded-2xl px-6 py-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center font-display font-bold text-slate-950">K</div>
                <span class="text-xl font-display font-bold tracking-tight text-white">{{ __('Kezi') }}</span>
            </div>
            
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
                <a href="#features" class="hover:text-amber-500 transition-colors">{{ __('Platform') }}</a>
                <a href="#solutions" class="hover:text-amber-500 transition-colors">{{ __('Solutions') }}</a>
                <a href="#dashboard" class="hover:text-amber-500 transition-colors">{{ __('Interface') }}</a>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative group py-2">
                    <button class="flex items-center gap-1 text-sm font-medium text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h2.935M18 9v1a2 2 0 01-2 2h-1a2 2 0 00-2 2v1a2 2 0 01-2 2H8.945M18 10a2 2 0 002 2h1.055"></path></svg>
                        <span class="uppercase">{{ app()->getLocale() }}</span>
                    </button>
                    <div class="absolute right-0 top-full pt-2 w-24 hidden group-hover:block">
                        <div class="glass rounded-xl overflow-hidden">
                            <a href="/lang/en" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">English</a>
                            <a href="/lang/ckb" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">کوردی</a>
                        </div>
                    </div>
                </div>

                <a href="/kezi/login" class="text-sm font-medium text-slate-300 hover:text-amber-500 transition-colors">{{ __('Sign in') }}</a>
                <a href="/kezi/register" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold rounded-xl transition-all glow">
                    {{ __('Start Free Trial') }}
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="relative pt-40 pb-20 px-6 overflow-hidden">
        <div class="max-w-5xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-500 text-xs font-bold uppercase tracking-widest mb-6">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                {{ __('Enterprise Grade Intelligence') }}
            </div>
            
            <h1 class="text-5xl md:text-7xl font-display font-bold tracking-tight mb-8 leading-tight text-white">
                {{ __('One Platform.') }} <br>
                <span class="text-gradient">{{ __('Limitless Efficiency.') }}</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto mb-12 leading-relaxed">
                {{ __('Kezi unifies your entire business operations—from complex accounting to automated HR—into a single, immutable, and secure command center.') }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <!-- <a href="/kezi/register" class="w-full sm:w-auto px-8 py-4 bg-white text-slate-950 text-base font-bold rounded-2xl hover:bg-slate-100 transition-all transform hover:scale-105 shadow-xl">
                    {{ __('Get Started for Free') }}
                </a> -->
                <a href="/kezi/register" class="w-full sm:w-auto px-8 py-4 glass text-white text-base font-bold rounded-2xl hover:bg-white/5 transition-all">
                    {{ __('View Platform Tour') }}
                </a>
            </div>
        </div>

        <!-- Live Activity Section (Abstract Dashboard) -->
        <div id="dashboard" class="max-w-6xl mx-auto mt-24 px-4">
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

                    <!-- Card 2: Active Operations -->
                    <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 relative overflow-hidden group hover:border-sky-500/30 transition-all">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('Active Operations') }}</div>
                                <div class="text-3xl font-display font-bold text-white group-hover:text-sky-500 transition-colors">1,842</div>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-sky-500/10 flex items-center justify-center">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-sky-500"></span>
                                </span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">{{ __('Processing Invoices') }}</span>
                                <span class="text-white font-mono">85%</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-sky-500 w-[85%]"></div>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">{{ __('Syncing Bank Feeds') }}</span>
                                <span class="text-white font-mono">98%</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 w-[98%]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: System Health -->
                    <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 relative overflow-hidden group hover:border-purple-500/30 transition-all">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('System Status') }}</div>
                                <div class="text-xl font-display font-bold text-emerald-400">{{ __('Operational') }}</div>
                            </div>
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div class="bg-slate-900/50 p-3 rounded-lg border border-white/5">
                                <div class="text-[10px] text-slate-500 uppercase">{{ __('Uptime') }}</div>
                                <div class="text-lg font-mono text-white">99.99%</div>
                            </div>
                            <div class="bg-slate-900/50 p-3 rounded-lg border border-white/5">
                                <div class="text-[10px] text-slate-500 uppercase">{{ __('Security') }}</div>
                                <div class="text-lg font-mono text-white">{{ __('Shield') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Abstract Data Table / List -->
                <div class="mt-4 md:mt-6 bg-slate-950/80 rounded-2xl border border-white/5 p-4 overflow-hidden">
                    <div class="flex items-center justify-between mb-4 px-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">{{ __('Recent Transactions') }}</span>
                        <div class="flex gap-2">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @foreach(range(1, 3) as $i)
                        <div class="flex items-center justify-between py-2 px-3 hover:bg-white/5 rounded-lg transition-colors cursor-default border-b border-white/5 last:border-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-200">{{ __('Invoice') }} #INV-202{{ $i }}</div>
                                    <div class="text-xs text-slate-500">{{ now()->subMinutes($i * 12)->diffForHumans() }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-mono text-white">${{ number_format(rand(1000, 5000), 2) }}</div>
                                <div class="text-[10px] text-emerald-400">{{ __('Completed') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="py-24 px-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl font-display font-bold mb-6 text-white">{{ __('Built for the') }} <br><span class="text-amber-500">{{ __('Modern Enterprise') }}</span></h2>
                    <p class="text-slate-400 text-lg mb-8 leading-relaxed">
                        {{ __('Kezi isn\'t just another ERP. It\'s a precisely engineered system designed to handle the most demanding business workflows with zero compromise on speed or security.') }}
                    </p>

                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white mb-1">{{ __('Immutable Ledger Technology') }}</h3>
                                <p class="text-slate-400 text-sm">{{ __('Every transaction is cryptographic, permanent, and fully auditable from the core.') }}</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white mb-1">{{ __('Lightning Fast Performance') }}</h3>
                                <p class="text-slate-400 text-sm">{{ __('Optimized for high-concurrency environments across global operations.') }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div id="solutions" class="grid grid-cols-2 gap-4">
                    <div class="space-y-4 pt-12">
                        <div class="glass p-6 rounded-2xl hover:border-amber-500/50 transition-colors">
                            <div class="text-amber-500 mb-4 font-display font-bold">{{ __('Accounting') }}</div>
                            <div class="text-xs text-slate-400">{{ __('Automated reconciliations and GAAP compliant reports.') }}</div>
                        </div>
                        <div class="glass p-6 rounded-2xl hover:border-sky-500/50 transition-colors">
                            <div class="text-sky-500 mb-4 font-display font-bold">{{ __('Sales') }}</div>
                            <div class="text-xs text-slate-400">{{ __('Omnichannel commerce and predictive pipeline analysis.') }}</div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="glass p-6 rounded-2xl hover:border-amber-500/50 transition-colors">
                            <div class="text-amber-500 mb-4 font-display font-bold">{{ __('HR') }}</div>
                            <div class="text-xs text-slate-400">{{ __('Unified employee management and automated payroll.') }}</div>
                        </div>
                        <div class="glass p-6 rounded-2xl hover:border-sky-500/50 transition-colors">
                            <div class="text-sky-500 mb-4 font-display font-bold">{{ __('Purchase') }}</div>
                            <div class="text-xs text-slate-400">{{ __('Smart procurement and automated vendor workflows.') }}</div>
                        </div>
                        <div class="glass p-6 rounded-2xl hover:border-amber-500/50 transition-colors">
                            <div class="text-amber-500 mb-4 font-display font-bold">{{ __('Manufacturing') }}</div>
                            <div class="text-xs text-slate-400">{{ __('MRP II pipelines and quality control automation.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Operational Excellence (Bento Grid - Iraq Localized) -->
    <section id="solutions" class="py-24 px-6 relative overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-display font-bold mb-4 text-white">{{ __('Built for') }} <span class="text-gradient">{{ __('Iraq\'s Future') }}</span></h2>
                <p class="text-slate-400 max-w-2xl mx-auto">{{ __('The only ERP engineered specifically for the 2026 Unified Accounting System and Iraqi market dynamics.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Large Item: Dual Currency -->
                <div class="md:col-span-2 lg:col-span-2 glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-colors"></div>
                    <div class="w-12 h-12 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-display font-bold text-white mb-2">{{ __('Native Dual-Currency') }}</h3>
                    <p class="text-slate-400 text-sm mb-6 max-w-xs">{{ __('Seamlessly operate in IQD and USD simultaneously. Automatic daily rate updates from CBI and parallel markets ensure precise financial reporting.') }}</p>
                    <div class="inline-flex items-center gap-2 text-xs font-bold text-amber-500 uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        {{ __('CBI & Market Rates') }}
                    </div>
                </div>

                <!-- Small Item: Compliance -->
                <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-between group">
                    <div class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="text-4xl font-display font-bold text-white mb-1">{{ __('2026') }}</div>
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">{{ __('UAS & IFRS Ready') }}</div>
                        <p class="text-slate-400 text-xs">{{ __('Fully compliant with the new Unified Accounting System and IFRS standards.') }}</p>
                    </div>
                </div>

                <!-- Small Item: Tax -->
                <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-between group border-amber-500/10">
                    <div class="w-10 h-10 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-white mb-2">{{ __('Auto-Tax Engine') }}</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">{{ __('Automated calculation of 15% Corporate Tax and Social Security deductions.') }}</p>
                    </div>
                </div>

                <!-- Medium Item: Localization -->
                <div class="md:col-span-2 glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                    <div class="grid md:grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-xl font-display font-bold text-white mb-4">{{ __('Localized') }} <br><span class="text-sky-500 text-2xl">{{ __('Intelligence') }}</span></h3>
                            <p class="text-slate-400 text-sm leading-relaxed">{{ __('Native Arabic & Kurdish interfaces. Generate financial reports in your preferred language for instant local compliance.') }}</p>
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
                            <h4 class="text-xl font-display font-bold text-white mb-1">{{ __('Comprehensive Documentation') }}</h4>
                            <p class="text-slate-400 text-sm max-w-md">{{ __('Detailed guides and user manuals to help you master every feature of the platform.') }}</p>
                        </div>
                    </div>
                    <a href="/docs" class="px-6 py-3 bg-sky-500/10 hover:bg-sky-500 text-sky-400 hover:text-white rounded-xl text-sm font-bold transition-all border border-sky-500/20 whitespace-nowrap">
                        {{ __('Explore Documentation') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-32 px-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-amber-500/5 pointer-events-none"></div>
        <div class="max-w-4xl mx-auto text-center relative z-10 border border-white/10 rounded-[3rem] p-12 md:p-20 glass glow">
            <h2 class="text-4xl md:text-6xl font-display font-bold mb-8 text-white">{{ __('Ready to Scale?') }}</h2>
            <p class="text-xl text-slate-400 mb-12">
                {{ __('Join the businesses that chose intelligence over complexity.') }} <br class="hidden md:block"> {{ __('Start your transformation today.') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <a href="/kezi/register" class="w-full sm:w-auto px-10 py-5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-lg font-bold rounded-2xl transition-all glow transform hover:scale-105">
                    {{ __('Start Your Free Trial') }}
                </a>
                <a href="mailto:sales@kezi.com" class="w-full sm:w-auto px-10 py-5 glass text-white text-base font-bold rounded-2xl hover:bg-white/5 transition-all">
                    {{ __('Talk to an Expert') }}
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-slate-800 rounded flex items-center justify-center font-display font-bold text-xs text-white">K</div>
                <span class="text-sm font-display font-medium text-slate-400 uppercase tracking-widest leading-none">{{ __('Kezi Enterprise') }}</span>
            </div>
            
            <div class="flex gap-8 text-xs font-medium text-slate-500 uppercase tracking-widest">
                <a href="#" class="hover:text-amber-500 transition-colors">{{ __('Security') }}</a>
                <a href="#" class="hover:text-amber-500 transition-colors">{{ __('Privacy') }}</a>
                <a href="#" class="hover:text-amber-500 transition-colors">{{ __('Terms') }}</a>
            </div>

            <div class="text-xs text-slate-600 font-medium">
                © {{ date('Y') }} Kezi. {{ __('All rights reserved. Precise business intelligence.') }}
            </div>
        </div>
    </footer>

</body>

</html>
