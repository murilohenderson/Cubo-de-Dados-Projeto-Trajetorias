<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>PROJETO TRAJETÓRIAS — Portal Científico INPE/Fiocruz</title>

        <!-- Google Fonts: Inter (Academic UI/Titles) and JetBrains Mono (Technical elements) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .font-mono {
                font-family: 'JetBrains Mono', monospace;
            }
        </style>

        <!-- Tailwind and JS via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col selection:bg-blue-900 selection:text-white antialiased overflow-hidden">
        
        <!-- TOP NAVIGATION BAR (INPE Institutional Deep Blue + Light Slate Hybrid) -->
        <header class="border-b border-slate-200 bg-white text-slate-900 sticky top-0 z-50 px-6 h-16 flex items-center justify-between shadow-sm">
            <!-- Logo & Brand -->
            <div class="flex items-center space-x-3">
                <!-- INPE Inspired Logo Mark -->
                <div class="h-9 w-12 rounded bg-blue-900 flex items-center justify-center shadow-sm">
                    <span class="text-[10px] font-mono font-black text-white tracking-widest">INPE</span>
                </div>
                <!-- Fiocruz Inspired Logo Mark -->
                <div class="h-9 w-16 rounded bg-indigo-950 flex items-center justify-center shadow-sm">
                    <span class="text-[10px] font-sans font-extrabold text-white tracking-wider">FIOCRUZ</span>
                </div>
                <div class="h-6 w-[1px] bg-slate-200"></div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[9px] font-mono font-bold tracking-wider text-slate-500 uppercase">Governo Federal — MCTI &amp; MS</span>
                        <span class="px-1.5 py-0.2 rounded bg-blue-50 text-[8px] font-mono text-blue-900 border border-blue-200 uppercase font-bold">Portal de Pesquisa</span>
                    </div>
                    <h1 class="text-sm font-bold tracking-tight text-slate-900 uppercase font-sans">
                        Plataforma Trajetórias — Hipercubo de Indicadores Co-relacionados
                    </h1>
                </div>
            </div>

            <!-- Live node status indicators -->
            <div class="flex items-center space-x-4 text-[10px] font-mono text-slate-500">
                <div class="flex items-center space-x-2 bg-slate-100 px-3 py-1 rounded border border-slate-200">
                    <span class="h-2 w-2 rounded-full bg-blue-900"></span>
                    <span>Hipercubo 4D:</span>
                    <span class="font-bold text-blue-900">ATIVO</span>
                </div>
                <div class="flex items-center space-x-2 bg-slate-100 px-3 py-1 rounded border border-slate-200">
                    <span class="h-2 w-2 rounded-full bg-blue-900"></span>
                    <span>Sincronização:</span>
                    <span class="font-bold text-blue-900">CONCLUÍDA</span>
                </div>
            </div>
        </header>

        <!-- MAIN LAYOUT WRAPPER (Edge-to-Edge, fits the remaining screen height) -->
        <main class="w-full p-0 flex-grow flex items-stretch h-[calc(100vh-64px-40px)] overflow-hidden">
            <livewire:hipercubo-dashboard />
        </main>

        <!-- FOOTER -->
        <footer class="h-10 border-t border-slate-200 bg-white px-6 text-slate-550 font-mono text-[10px] flex items-center justify-between">
            <p>
                Projeto Trajetórias &copy; {{ date('Y') }} — Todos os direitos reservados. MCTI / MS.
            </p>
            <div class="flex items-center space-x-3 text-slate-500">
                <a href="https://www.gov.br/inpe" target="_blank" class="hover:text-blue-900 transition">INPE (Ambiente)</a>
                <span>•</span>
                <a href="https://portal.fiocruz.br" target="_blank" class="hover:text-blue-900 transition">Fiocruz (Saúde)</a>
                <span>•</span>
                <span class="text-slate-400">Ambiente de Análise e Planejamento Territorial</span>
            </div>
        </footer>

        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html>
