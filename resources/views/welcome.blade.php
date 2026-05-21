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
        
        <!-- Leaflet JS & CSS (Loaded statically for absolute reliability) -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col selection:bg-blue-900 selection:text-white antialiased overflow-hidden">
        
        <!-- TOP NAVIGATION BAR (INPE Institutional Deep Blue + Light Slate Hybrid) -->
        <header class="border-b border-slate-800 bg-[#0f172a] text-white sticky top-0 z-50 px-6 h-16 flex items-center justify-between shadow-md">
            <!-- Logo & Brand -->
            <div class="flex items-center space-x-4">
                <!-- Logos container -->
                <div class="flex items-center space-x-3">
                    <!-- INPE Logo (renamed from image.png and cropped) -->
                    <div class="flex items-center h-10">
                        <img src="/images/logo-inpe.png" alt="INPE" class="h-9 w-auto object-contain transition-all hover:opacity-90 duration-200 brightness-0 invert">
                    </div>
                    
                    <div class="h-6 w-[1px] bg-slate-700/80"></div>
                    
                    <!-- Fiocruz Logo -->
                    <div class="flex items-center h-10">
                        <img src="/sites/fiocruz.br/files/marca_fiocruz_horizontal_tagline_branca_0.png" alt="Início" class="h-6 w-auto object-contain transition-all hover:opacity-90 duration-200" fetchpriority="high">
                    </div>
                </div>
                
                <!-- Vertical Line Separator (Line style |) -->
                <div class="h-6 w-[1px] bg-slate-700 mx-2"></div>
                
                <div class="flex flex-col justify-center">
                    <h1 class="text-xs font-bold tracking-wider text-slate-100 uppercase font-sans">
                        Plataforma Trajetórias
                    </h1>
                    <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest font-sans mt-0.5">
                        Cubo de dados de Indicadores Co-relacionados
                    </span>
                </div>
            </div>
        </header>

        <!-- MAIN LAYOUT WRAPPER (Edge-to-Edge, fits the remaining screen height) -->
        <main class="w-full p-0 flex-grow flex items-stretch h-[calc(100vh-64px)] overflow-hidden">
            <livewire:hipercubo-dashboard />
        </main>

        <!-- Livewire Scripts -->
        @livewireScripts
        <!-- Chart.js for drill‑down visualizations -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
        <!-- Global styles for smooth tooltip fade‑in/out -->
        <style>
            .tooltip {
                position: absolute;
                background: rgba(0,0,0,0.75);
                color: #fff;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 0.75rem;
                white-space: nowrap;
                opacity: 0;
                transition: opacity 0.2s ease-in-out;
                pointer-events: none;
                z-index: 50;
            }
            .tooltip.show { opacity: 1; }
        </style>
    </body>
</html>
