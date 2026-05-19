<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>PROJETO TRAJETÓRIAS — Painel Científico INPE/Fiocruz</title>

        <!-- Google Fonts: Outfit (UI/Titles) and JetBrains Mono (Scientific/Technical elements) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Outfit:wght@100..900&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Outfit', sans-serif;
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
    <body class="bg-[#f4f6f9] text-slate-800 min-h-screen flex flex-col selection:bg-blue-600 selection:text-white antialiased overflow-hidden">
        
        <!-- TOP NAVIGATION BAR (INPE Institutional Deep Blue) -->
        <header class="border-b border-[#0a2347] bg-[#0c2b5e] text-white sticky top-0 z-50 px-6 h-16 flex items-center justify-between shadow-sm">
            <!-- Logo & Brand -->
            <div class="flex items-center space-x-3">
                <!-- INPE Inspired Logo Mark -->
                <div class="h-9 w-9 rounded-lg bg-blue-600 flex items-center justify-center shadow-sm">
                    <span class="text-xs font-mono font-black text-white">INPE</span>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[9px] font-mono font-bold tracking-wider text-blue-200 uppercase">Ministério da Ciência, Tecnologia e Inovação</span>
                        <span class="px-1.5 py-0.2 rounded bg-blue-800 text-[8px] font-mono text-blue-200 border border-blue-700 uppercase font-bold">Painel Oficial</span>
                    </div>
                    <h1 class="text-sm font-bold tracking-tight text-white uppercase font-sans">
                        Projeto Trajetórias — Cruzamento de Indicadores
                    </h1>
                </div>
            </div>

            <!-- Live node status indicators -->
            <div class="flex items-center space-x-4 text-[10px] font-mono text-slate-200">
                <div class="flex items-center space-x-2 bg-blue-950/60 px-3 py-1 rounded-md border border-blue-900">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Cubo Científico:</span>
                    <span class="font-bold text-emerald-300">CARREGADO</span>
                </div>
                <div class="flex items-center space-x-2 bg-blue-950/60 px-3 py-1 rounded-md border border-blue-900">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Sincronização:</span>
                    <span class="font-bold text-emerald-300">CONCLUÍDA</span>
                </div>
            </div>
        </header>

        <!-- MAIN LAYOUT WRAPPER (Edge-to-Edge, fits the remaining screen height) -->
        <main class="w-full p-0 flex-grow flex items-stretch h-[calc(100vh-64px-40px)] overflow-hidden">
            <livewire:data-cube-dashboard />
        </main>

        <!-- FOOTER -->
        <footer class="h-10 border-t border-slate-200 bg-white px-6 text-slate-500 font-mono text-[10px] flex items-center justify-between">
            <p>
                Projeto Trajetórias &copy; {{ date('Y') }} — Todos os direitos reservados.
            </p>
            <div class="flex items-center space-x-3">
                <a href="https://www.gov.br/inpe" target="_blank" class="hover:text-blue-600 transition">INPE (Ambiente)</a>
                <span>•</span>
                <a href="https://portal.fiocruz.br" target="_blank" class="hover:text-blue-600 transition">Fiocruz (Saúde)</a>
                <span>•</span>
                <span class="text-slate-400">ROADMAP AD European Union Inspirando Tecnologia Nacional</span>
            </div>
        </footer>

        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html>
