<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kezi — Intelligence for Your Business</title>

    <!-- SEO -->
    <meta name="description" content="Kezi is the ultimate enterprise command center. Immutable, secure, and built for scale.">

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
                <span class="text-xl font-display font-bold tracking-tight text-white">Kezi</span>
            </div>
            
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
                <a href="#features" class="hover:text-amber-500 transition-colors">Platform</a>
                <a href="#solutions" class="hover:text-amber-500 transition-colors">Solutions</a>
                <a href="#dashboard" class="hover:text-amber-500 transition-colors">Interface</a>
            </div>

            <div class="flex items-center gap-4">
                <a href="/kezi/login" class="text-sm font-medium text-slate-300 hover:text-amber-500 transition-colors">Sign in</a>
                <a href="/kezi/register" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold rounded-xl transition-all glow">
                    Start Free Trial
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
                Enterprise Grade Intelligence
            </div>
            
            <h1 class="text-5xl md:text-7xl font-display font-bold tracking-tight mb-8 leading-tight text-white">
                One Platform. <br>
                <span class="text-gradient">Limitless Efficiency.</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto mb-12 leading-relaxed">
                Kezi unifies your entire business operations—from complex accounting to automated HR—into a single, immutable, and secure command center.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/kezi/register" class="w-full sm:w-auto px-8 py-4 bg-white text-slate-950 text-base font-bold rounded-2xl hover:bg-slate-100 transition-all transform hover:scale-105 shadow-xl">
                    Get Started for Free
                </a>
                <a href="#dashboard" class="w-full sm:w-auto px-8 py-4 glass text-white text-base font-bold rounded-2xl hover:bg-white/5 transition-all">
                    View Platform Tour
                </a>
            </div>
        </div>

        <!-- Hero Screenshot -->
        <div id="dashboard" class="max-w-6xl mx-auto mt-24 px-4">
            <div class="screenshot-frame group relative">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent z-20 pointer-events-none transition-opacity group-hover:opacity-0"></div>
                <img src="/images/screenshots/dashboard_1770399216637.png" alt="Kezi Financial Dashboard" class="w-full opacity-90 group-hover:opacity-100 transition-opacity duration-700">
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="py-24 px-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl font-display font-bold mb-6 text-white">Built for the <br><span class="text-amber-500">Modern Enterprise</span></h2>
                    <p class="text-slate-400 text-lg mb-8 leading-relaxed">
                        Kezi isn't just another ERP. It's a precisely engineered system designed to handle the most demanding business workflows with zero compromise on speed or security.
                    </p>

                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white mb-1">Immutable Ledger Technology</h3>
                                <p class="text-slate-400 text-sm">Every transaction is cryptographic, permanent, and fully auditable from the core.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white mb-1">Lightning Fast Performance</h3>
                                <p class="text-slate-400 text-sm">Optimized for high-concurrency environments across global operations.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div id="solutions" class="grid grid-cols-2 gap-4">
                    <div class="space-y-4 pt-12">
                        <div class="glass p-6 rounded-2xl hover:border-amber-500/50 transition-colors">
                            <div class="text-amber-500 mb-4 font-display font-bold">Accounting</div>
                            <div class="text-xs text-slate-400">Automated reconciliations and GAAP compliant reports.</div>
                        </div>
                        <div class="glass p-6 rounded-2xl hover:border-sky-500/50 transition-colors">
                            <div class="text-sky-500 mb-4 font-display font-bold">Sales</div>
                            <div class="text-xs text-slate-400">Omnichannel commerce and predictive pipeline analysis.</div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="glass p-6 rounded-2xl hover:border-amber-500/50 transition-colors">
                            <div class="text-amber-500 mb-4 font-display font-bold">HR</div>
                            <div class="text-xs text-slate-400">Unified employee management and automated payroll.</div>
                        </div>
                        <div class="glass p-6 rounded-2xl hover:border-sky-500/50 transition-colors">
                            <div class="text-sky-500 mb-4 font-display font-bold">Purchase</div>
                            <div class="text-xs text-slate-400">Smart procurement and automated vendor workflows.</div>
                        </div>
                        <div class="glass p-6 rounded-2xl hover:border-amber-500/50 transition-colors">
                            <div class="text-amber-500 mb-4 font-display font-bold">Manufacturing</div>
                            <div class="text-xs text-slate-400">MRP II pipelines and quality control automation.</div>
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
                <h2 class="text-3xl md:text-5xl font-display font-bold mb-4 text-white">Built for <span class="text-gradient">Iraq's Future</span></h2>
                <p class="text-slate-400 max-w-2xl mx-auto">The only ERP engineered specifically for the 2026 Unified Accounting System and Iraqi market dynamics.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Large Item: Dual Currency -->
                <div class="md:col-span-2 lg:col-span-2 glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-colors"></div>
                    <div class="w-12 h-12 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-display font-bold text-white mb-2">Native Dual-Currency</h3>
                    <p class="text-slate-400 text-sm mb-6 max-w-xs">Seamlessly operate in IQD and USD simultaneously. Automatic daily rate updates from CBI and parallel markets ensure precise financial reporting.</p>
                    <div class="inline-flex items-center gap-2 text-xs font-bold text-amber-500 uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        CBI & Market Rates
                    </div>
                </div>

                <!-- Small Item: Compliance -->
                <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-between group">
                    <div class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="text-4xl font-display font-bold text-white mb-1">2026</div>
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">UAS & IFRS Ready</div>
                        <p class="text-slate-400 text-xs">Fully compliant with the new Unified Accounting System and IFRS standards.</p>
                    </div>
                </div>

                <!-- Small Item: Tax -->
                <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-between group border-amber-500/10">
                    <div class="w-10 h-10 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-white mb-2">Auto-Tax Engine</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">Automated calculation of 15% Corporate Tax and Social Security deductions.</p>
                    </div>
                </div>

                <!-- Medium Item: Localization -->
                <div class="md:col-span-2 glass p-8 rounded-[2.5rem] relative overflow-hidden group">
                    <div class="grid md:grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-xl font-display font-bold text-white mb-4">Localized <br><span class="text-sky-500 text-2xl">Intelligence</span></h3>
                            <p class="text-slate-400 text-sm leading-relaxed">Native Arabic & Kurdish interfaces. Generate financial reports in your preferred language for instant local compliance.</p>
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

                <!-- Medium Item: Digital Payments -->
                <div class="md:col-span-2 glass p-8 rounded-[2.5rem] border-sky-500/10 group">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-sky-500/10 border border-sky-500/20 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xl font-display font-bold text-white">Digital Payments Ready</h4>
                            <p class="text-slate-400 text-sm">Prepared for 2026 electronic payment mandates. Integrated with audit trails for digital invoicing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-32 px-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-amber-500/5 pointer-events-none"></div>
        <div class="max-w-4xl mx-auto text-center relative z-10 border border-white/10 rounded-[3rem] p-12 md:p-20 glass glow">
            <h2 class="text-4xl md:text-6xl font-display font-bold mb-8 text-white">Ready to Scale?</h2>
            <p class="text-xl text-slate-400 mb-12">
                Join the businesses that chose intelligence over complexity. <br class="hidden md:block"> Start your transformation today.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <a href="/kezi/register" class="w-full sm:w-auto px-10 py-5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-lg font-bold rounded-2xl transition-all glow transform hover:scale-105">
                    Start Your Free Trial
                </a>
                <a href="mailto:sales@kezi.com" class="w-full sm:w-auto px-10 py-5 glass text-white text-base font-bold rounded-2xl hover:bg-white/5 transition-all">
                    Talk to an Expert
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-slate-800 rounded flex items-center justify-center font-display font-bold text-xs text-white">K</div>
                <span class="text-sm font-display font-medium text-slate-400 uppercase tracking-widest leading-none">Kezi Enterprise</span>
            </div>
            
            <div class="flex gap-8 text-xs font-medium text-slate-500 uppercase tracking-widest">
                <a href="#" class="hover:text-amber-500 transition-colors">Security</a>
                <a href="#" class="hover:text-amber-500 transition-colors">Privacy</a>
                <a href="#" class="hover:text-amber-500 transition-colors">Terms</a>
            </div>

            <div class="text-xs text-slate-600 font-medium">
                © {{ date('Y') }} Kezi. All rights reserved. Precise business intelligence.
            </div>
        </div>
    </footer>

</body>

</html>
