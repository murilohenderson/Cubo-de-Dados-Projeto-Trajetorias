<div class="flex flex-col lg:flex-row items-stretch w-full h-full min-h-0 overflow-hidden"
     x-data="{
        leftWidth: 45,
        isResizing: false,
        isDesktop: window.innerWidth >= 1024,

        init() {
            window.addEventListener('resize', () => {
                this.isDesktop = window.innerWidth >= 1024;
            });
        },
        startResize(e) {
            this.isResizing = true;
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        },
        resize(e) {
            if (!this.isResizing) return;
            const containerWidth = $el.clientWidth;
            const clientX = e.clientX || (e.touches && e.touches[0].clientX);
            if (!clientX) return;
            
            let newWidth = (clientX / containerWidth) * 100;
            if (newWidth < 25) newWidth = 25;
            if (newWidth > 75) newWidth = 75;
            
            this.leftWidth = newWidth;
        },
        stopResize() {
            if (this.isResizing) {
                this.isResizing = false;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            }
        }
     }"
     @mousemove.window="resize($event)"
     @mouseup.window="stopResize()"
     @touchmove.window="resize($event)"
     @touchend.window="stopResize()"
>
    <!-- Style override for native CSS 3D transforms & light theme scrollbars -->
    <style>
        .perspective-1000 {
            perspective: 1000px;
        }
        .preserve-3d {
            transform-style: preserve-3d;
        }
        .backface-hidden {
            backface-visibility: hidden;
        }
        /* Custom scrollbar for clean institutional feel */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-12px);
            }
        }
        .animate-float {
            animation: float 4.5s ease-in-out infinite;
        }
    </style>

    <!-- LEFT COLUMN: 3D Data Cube (Resizable split-screen) -->
    <div class="w-full flex flex-col justify-between bg-[radial-gradient(circle_at_50%_45%,rgba(186,218,255,0.9)_0%,rgba(255,255,255,1)_85%)] p-6 relative overflow-y-auto h-full flex-shrink-0 border-b border-slate-200 lg:border-b-0"
         :style="isDesktop ? 'width: ' + leftWidth + '%' : ''">
        
        <!-- Header Info -->
        <div class="relative z-10">
            <div class="flex items-center space-x-2.5 mb-1.5">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                <span class="text-xs font-mono font-bold tracking-wider text-blue-600 uppercase">INPE / FIOCRUZ • Metodologia ROADMAP AD</span>
            </div>
            <h2 class="text-base font-bold tracking-tight text-slate-900 font-sans uppercase">
                Visualizador de Dados 3D
            </h2>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Navegue pelas dimensões espaciais e filtre os dados ambientais e de saúde de forma interativa.
            </p>
        </div>

        <!-- Interactive 3D Cube Container (Sober Colors & Floor Shadow) -->
        <div class="my-6 py-4 flex flex-col items-center justify-center min-h-[500px] select-none relative"
             x-data="{
                isDragging: false,
                hasMoved: false,
                startX: 0,
                startY: 0,
                rotateX: -20,
                rotateY: 35,
                tempRotateX: -20,
                tempRotateY: 35,
                threshold: 5,
                zoomZ: 0,
                minZoomZ: -300,
                maxZoomZ: 400,
                
                startDrag(e) {
                    if (e.target.closest('button') || e.target.closest('a')) {
                        return;
                    }
                    this.isDragging = true;
                    this.hasMoved = false;
                    this.startX = e.clientX || (e.touches && e.touches[0].clientX);
                    this.startY = e.clientY || (e.touches && e.touches[0].clientY);
                    this.tempRotateX = this.rotateX;
                    this.tempRotateY = this.rotateY;
                },
                drag(e) {
                    if (!this.isDragging) return;
                    const x = e.clientX || (e.touches && e.touches[0].clientX);
                    const y = e.clientY || (e.touches && e.touches[0].clientY);
                    const dx = x - this.startX;
                    const dy = y - this.startY;
                    
                    if (Math.abs(dx) > this.threshold || Math.abs(dy) > this.threshold) {
                        this.hasMoved = true;
                    }
                    
                    if (this.hasMoved) {
                        this.rotateY = this.tempRotateY + dx * 0.4;
                        this.rotateX = Math.max(-75, Math.min(75, this.tempRotateX - dy * 0.4));
                    }
                },
                stopDrag() {
                    this.isDragging = false;
                },
                rotateTo(x, y) {
                    this.rotateX = x;
                    this.rotateY = y;
                },
                handleFaceClick(face, e) {
                    if (this.hasMoved) {
                        e.preventDefault();
                        e.stopPropagation();
                        return;
                    }
                    $wire.setActiveFace(face);
                },
                handleWheel(e) {
                    const delta = -e.deltaY * 0.45;
                    this.zoomZ = Math.max(this.minZoomZ, Math.min(this.maxZoomZ, this.zoomZ + delta));
                }
             }"
             @mousedown="startDrag($event)"
             @mousemove="drag($event)"
             @mouseup.window="stopDrag()"
             @touchstart="startDrag($event)"
             @touchmove="drag($event)"
             @touchend.window="stopDrag()"
        >
            <!-- 3D Scene viewport (Resized to w-96 h-96 with perspective) -->
            <div class="perspective-1000 w-96 h-96 flex items-center justify-center cursor-grab active:cursor-grabbing relative"
                 @wheel.prevent="handleWheel($event)">
                
                <!-- Floating Shadow under the cube (Reacts to dragging and zoom) -->
                <div class="absolute bottom-6 w-40 h-2.5 bg-slate-400/20 rounded-full blur-md pointer-events-none transition-all duration-300 ease-out"
                     :style="`transform: scale(${(isDragging ? 0.8 : 1) * (1 + zoomZ / 350)})`"
                ></div>

                <!-- Floating Container wrapping the cube wrapper -->
                <div class="animate-float preserve-3d">
                    <!-- Cube Wrapper (Resized to w-64 h-64) -->
                    <div class="relative w-64 h-64 preserve-3d transition-transform duration-100 ease-out"
                         style="will-change: transform;"
                         :style="`transform: translateZ(${zoomZ}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`">
                         
                        <!-- FACE 1: FRONT (INPE - Ambiental) - Premium Blue Gradient -->
                        <div 
                            @click="handleFaceClick('inpe', $event)"
                            class="absolute inset-0 flex flex-col items-center justify-between p-4 border rounded-2xl cursor-pointer transition-all duration-300 backface-hidden shadow-[inset_0_1px_2px_rgba(255,255,255,0.4)]"
                            :class="$wire.activeFace === 'inpe' 
                                ? 'bg-gradient-to-br from-[#0c2b5e] to-[#2563eb] border-blue-300 shadow-[0_12px_24px_rgba(12,43,94,0.3),inset_0_1px_2px_rgba(255,255,255,0.4)] scale-105 z-10' 
                                : 'bg-gradient-to-br from-[#12387a] to-[#1e40af] border-blue-500/80 hover:border-blue-300 hover:scale-102'"
                            style="transform: rotateY(0deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider bg-blue-900/60 text-blue-200 px-1.5 py-0.5 rounded font-bold">DIMENSÃO A</span>
                                <div class="h-1.5 w-1.5 rounded-full bg-blue-300"></div>
                            </div>
                            
                            <div class="flex flex-col items-center">
                                <span class="text-base font-bold tracking-wider text-white uppercase text-center">INPE</span>
                                <span class="text-[9px] text-blue-100 mt-0.5 uppercase tracking-wide">Meio Ambiente</span>
                            </div>
                            
                            <div class="text-[9px] font-medium text-blue-200 flex items-center space-x-1">
                                <span>Ver Indicadores</span>
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>

                        <!-- FACE 2: BACK (FIOCRUZ - Saúde) - Premium Teal Gradient -->
                        <div 
                            @click="handleFaceClick('fiocruz', $event)"
                            class="absolute inset-0 flex flex-col items-center justify-between p-4 border rounded-2xl cursor-pointer transition-all duration-300 backface-hidden shadow-[inset_0_1px_2px_rgba(255,255,255,0.4)]"
                            :class="$wire.activeFace === 'fiocruz' 
                                ? 'bg-gradient-to-br from-[#004d4d] to-[#0f968c] border-teal-300 shadow-[0_12px_24px_rgba(0,77,77,0.3),inset_0_1px_2px_rgba(255,255,255,0.4)] scale-105 z-10' 
                                : 'bg-gradient-to-br from-[#006666] to-[#0d9488] border-teal-500/80 hover:border-teal-300 hover:scale-102'"
                            style="transform: rotateY(180deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider bg-teal-900/60 text-teal-200 px-1.5 py-0.5 rounded font-bold">DIMENSÃO B</span>
                                <div class="h-1.5 w-1.5 rounded-full bg-teal-300"></div>
                            </div>
                            
                            <div class="flex flex-col items-center">
                                <span class="text-base font-bold tracking-wider text-white uppercase text-center font-sans">FIOCRUZ</span>
                                <span class="text-[9px] text-teal-100 mt-0.5 uppercase tracking-wide">Saúde Pública</span>
                            </div>
                            
                            <div class="text-[9px] font-medium text-teal-200 flex items-center space-x-1">
                                <span>Ver Indicadores</span>
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>

                        <div 
                            @click="handleFaceClick('territorio', $event)"
                            class="absolute inset-0 flex flex-col items-center justify-between p-4 border rounded-2xl cursor-pointer transition-all duration-300 backface-hidden shadow-[inset_0_1px_2px_rgba(255,255,255,0.4)]"
                            :class="$wire.activeFace === 'all' 
                                ? 'bg-gradient-to-br from-[#1e293b] to-[#475569] border-slate-300 shadow-[0_12px_24px_rgba(30,41,59,0.3),inset_0_1px_2px_rgba(255,255,255,0.4)] scale-105 z-10' 
                                : 'bg-gradient-to-br from-[#334155] to-[#5a6a80] border-slate-500/80 hover:border-slate-300 hover:scale-102'"
                            style="transform: rotateY(-90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider bg-slate-800/80 text-slate-200 px-1.5 py-0.5 rounded font-bold">VISÃO TOTAL</span>
                                <div class="h-1.5 w-1.5 rounded-full bg-slate-300"></div>
                            </div>
                            
                            <div class="flex flex-col items-center">
                                <span class="text-base font-bold tracking-wider text-white uppercase text-center font-sans">TERRITÓRIO</span>
                                <span class="text-[9px] text-slate-200 mt-0.5 uppercase tracking-wide">Integração Geral</span>
                            </div>
                            
                            <div class="text-[9px] font-medium text-slate-200 flex items-center space-x-1">
                                <span>Exibir Todos</span>
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>

                        <div 
                            class="absolute inset-0 flex flex-col items-center justify-between p-4 border border-slate-300 rounded-2xl bg-slate-50 backface-hidden shadow-[inset_0_1px_2px_rgba(255,255,255,0.4)]"
                            style="transform: rotateY(90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-semibold">METADADOS</span>
                                <div class="h-1.5 w-1.5 rounded-full bg-slate-400"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] font-mono font-bold text-slate-700">INPE-FIOCRUZ</span>
                                <span class="text-[9px] text-slate-500 mt-0.5">25 Nexos Causais</span>
                            </div>
                            
                            <div class="text-[8px] font-mono text-slate-400">
                                ROADMAP AD v2.0
                            </div>
                        </div>

                        <div 
                            class="absolute inset-0 flex flex-col justify-between p-2 border border-slate-300 rounded-2xl bg-slate-50 backface-hidden shadow-[inset_0_1px_2px_rgba(255,255,255,0.4)]"
                            style="transform: rotateX(90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="grid grid-cols-5 gap-1 w-full h-full p-0.5 opacity-60">
                                @for ($i = 0; $i < 25; $i++)
                                    <div class="border rounded-sm transition-all duration-300
                                        @if ($i % 8 === 0) border-blue-500 bg-blue-100 
                                        @elseif ($i % 6 === 0) border-teal-500 bg-teal-100 
                                        @else border-slate-200 bg-white @endif">
                                    </div>
                                @endfor
                            </div>
                            <div class="text-center pb-0.5">
                                <span class="text-[8px] font-mono text-slate-500 tracking-wider uppercase">Matriz Ortogonal</span>
                            </div>
                        </div>

                        <div 
                            class="absolute inset-0 flex flex-col items-center justify-between p-4 border border-slate-300 rounded-2xl bg-slate-50 backface-hidden shadow-[inset_0_1px_2px_rgba(255,255,255,0.4)]"
                            style="transform: rotateX(-90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-semibold font-mono">SUPORTE</span>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <div class="text-slate-800 text-[10px] font-sans font-bold">PROJETO TRAJETÓRIAS</div>
                                <div class="text-[9px] text-slate-500 mt-0.5">INPE / MCTI &copy; 2026</div>
                            </div>

                            <div class="text-[8px] font-mono text-slate-400">
                                COORDENAÇÃO GERAL
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 flex items-center justify-center space-x-2 relative z-10 w-full" data-clickable>
                <button type="button" @click="rotateTo(-20, 35)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm">
                    Frontal
                </button>
                <button type="button" @click="rotateTo(-20, 215)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm">
                    Posterior
                </button>
                <button type="button" @click="rotateTo(-90, 0)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm">
                    Topo
                </button>
                <button type="button" @click="rotateTo(-20, -55)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm">
                    Lateral
                </button>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-4 relative z-10">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-600">Filtro Aplicado no Cubo:</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono uppercase font-bold tracking-wide
                    @if($activeFace === 'inpe') bg-blue-50 text-blue-800 border border-blue-200
                    @elseif($activeFace === 'fiocruz') bg-teal-50 text-teal-800 border border-teal-200
                    @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                    @if($activeFace === 'inpe') INPE (Ambiental)
                    @elseif($activeFace === 'fiocruz') FIOCRUZ (Saúde)
                    @else EXIBINDO TODOS
                    @endif
                </span>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200 text-[11px] text-slate-600 leading-relaxed flex items-start space-x-2">
                <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>
                    <strong>Instruções:</strong> Use o <strong>clique e arraste</strong> em qualquer lugar do container do cubo para rotacioná-lo. Clique diretamente sobre as faces coloridas para filtrar o painel e os dados.
                </span>
            </div>
            
            @if($activeFace !== 'all')
                <div class="mt-2 text-right">
                    <button type="button" @click="$wire.setActiveFace('all')" class="text-[10px] text-red-600 hover:text-red-700 font-bold tracking-wider uppercase transition">
                        Limpar Filtro de Dimensão
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="hidden lg:block w-1.5 hover:w-2 bg-slate-200 hover:bg-blue-600 cursor-col-resize transition-all duration-150 flex-shrink-0 relative z-20 group"
         @mousedown="startResize($event)"
         @touchstart="startResize($event)">
         <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-[1px] bg-slate-300 group-hover:bg-blue-300"></div>
    </div>

    <div class="w-full flex flex-col justify-between bg-[#f8fafc] h-full overflow-y-auto"
         :style="isDesktop ? 'width: ' + (100 - leftWidth) + '%' : ''">
        
        <div class="p-6 bg-white relative overflow-hidden flex-grow flex flex-col border-b border-slate-200">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-200 pb-4 mb-4 space-y-2 md:space-y-0">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 flex items-center space-x-2 uppercase tracking-wider font-sans">
                        <span>Matriz de Associação Ambiental e Epidemiológica</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Mapeamento dos níveis de incidência e cruzamentos territoriais.</p>
                </div>
                
                <div class="flex items-center space-x-3 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                    <span class="text-[9px] font-mono text-slate-500 uppercase font-semibold">Legenda:</span>
                    <div class="flex items-center space-x-2.5">
                        <div class="flex items-center space-x-1">
                            <span class="h-2.5 w-2.5 rounded-sm bg-rose-600"></span>
                            <span class="text-[10px] text-slate-600 font-medium">4 Crítico</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span>
                            <span class="text-[10px] text-slate-600 font-medium">3 Moderado</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="h-2.5 w-2.5 rounded-sm bg-yellow-400"></span>
                            <span class="text-[10px] text-slate-600 font-medium">2 Baixo</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="h-2.5 w-2.5 rounded-sm bg-slate-100 border border-slate-300"></span>
                            <span class="text-[10px] text-slate-600 font-medium">1 Sem Dados</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto w-full flex-grow">
                <table class="w-full border-collapse select-none">
                    <thead>
                        <tr>
                            <th class="p-3 text-left text-xs font-mono text-slate-400 border-b border-slate-200 font-semibold tracking-wider min-w-[200px]">
                                Indicador Científico
                            </th>
                            @foreach ($regions as $region)
                                <th class="p-3 text-center text-[10px] font-mono text-slate-600 border-b border-slate-200 font-bold tracking-wider max-w-[100px] leading-tight">
                                    <div class="px-2 py-1 rounded bg-slate-50 border border-slate-200 text-slate-700">
                                        {{ $region->name }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="bg-blue-50/40">
                                <td colspan="{{ count($regions) + 1 }}" class="p-2 border-y border-blue-100/60 text-xs font-bold tracking-wider text-blue-800 uppercase pl-3 bg-gradient-to-r from-blue-50 via-transparent to-transparent">
                                    <div class="flex items-center space-x-2">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                        <span>{{ $category->name }}</span>
                                    </div>
                                </td>
                            </tr>
                            
                            @foreach ($category->indicators as $indicator)
                                <tr class="hover:bg-slate-50/70 transition border-b border-slate-100">
                                    <td class="p-3 text-xs font-semibold text-slate-700 leading-normal pl-4">
                                        {{ $indicator->name }}
                                    </td>
                                    
                                    @foreach ($regions as $region)
                                        @php
                                            $cellKey = $indicator->id . '-' . $region->id;
                                            $cell = isset($cells[$cellKey]) ? $cells[$cellKey]->first() : null;
                                            $density = $cell ? $cell->density_level : 1;
                                            $cellId = $cell ? $cell->id : null;
                                            $isSelected = ($selectedCellId === $cellId && $cellId !== null);
                                        @endphp
                                        <td class="p-2 text-center">
                                            @if ($cell)
                                                <button 
                                                    wire:click="selectCell({{ $indicator->id }}, {{ $region->id }})"
                                                    type="button"
                                                    title="Clique para ver a análise de correlação científica"
                                                    class="w-9 h-9 rounded-lg font-mono text-xs font-bold transition-all duration-300 relative focus:outline-none flex items-center justify-center border
                                                        @if ($density === 4) bg-rose-600 border-rose-700 text-white hover:bg-rose-700
                                                        @elseif ($density === 3) bg-amber-500 border-amber-600 text-slate-900 hover:bg-amber-600
                                                        @elseif ($density === 2) bg-yellow-400 border-yellow-500 text-slate-900 hover:bg-yellow-500
                                                        @else bg-slate-100 border-slate-200 text-slate-400 hover:bg-slate-200/80 hover:text-slate-600 @endif
                                                        @if ($isSelected) ring-4 ring-blue-600 scale-105 z-10 shadow-md @endif"
                                                >
                                                    @if ($isSelected)
                                                        <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-600 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-600"></span>
                                                        </span>
                                                    @endif
                                                    
                                                    {{ $density }}
                                                </button>
                                            @else
                                                <div class="w-9 h-9 mx-auto rounded-lg bg-slate-50 border border-dashed border-slate-200 flex items-center justify-center text-slate-400 text-xs">
                                                    -
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ count($regions) + 1 }}" class="p-8 text-center text-slate-400 text-xs font-mono">
                                    Nenhum indicador correspondente ao filtro ativo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scientific Analysis Lower Panel (Sober Colors) -->
        <div class="bg-[#f1f5f9] p-6 relative overflow-hidden flex-shrink-0 border-t border-slate-200">
            <!-- Alert Borders depending on selection density level -->
            @if ($cellDetails)
                <div class="absolute inset-y-0 left-0 w-2.5 
                    @if($cellDetails['density_level'] === 4) bg-rose-600
                    @elseif($cellDetails['density_level'] === 3) bg-amber-500
                    @elseif($cellDetails['density_level'] === 2) bg-yellow-400
                    @else bg-slate-400 @endif">
                </div>
            @endif

            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <h4 class="text-xs font-bold tracking-wider text-slate-800 uppercase font-sans">
                        Relatório de Correlação Científica
                    </h4>
                </div>
                @if ($cellDetails)
                    <button 
                        wire:click="$set('selectedCellId', null); $set('cellDetails', null)"
                        class="text-[9px] text-slate-600 hover:text-slate-800 font-mono tracking-wider uppercase border border-slate-300 hover:border-slate-400 bg-white px-2 py-0.5 rounded shadow-sm"
                    >
                        Limpar Relatório
                    </button>
                @endif
            </div>

            @if ($cellDetails)
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                            <span class="text-[9px] font-mono text-slate-400 uppercase block">Indicador</span>
                            <span class="text-xs font-bold text-slate-700 block truncate" title="{{ $cellDetails['indicator_name'] }}">
                                {{ $cellDetails['indicator_name'] }}
                            </span>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                            <span class="text-[9px] font-mono text-slate-400 uppercase block">Região Geográfica</span>
                            <span class="text-xs font-bold text-slate-700 block truncate">
                                {{ $cellDetails['region_name'] }}
                            </span>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200 flex justify-between items-center">
                            <div>
                                <span class="text-[9px] font-mono text-slate-400 uppercase block">Nível de Associação</span>
                                <span class="text-xs font-bold text-slate-700 block">
                                    Grau {{ $cellDetails['density_level'] }} / 4
                                </span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono tracking-wide uppercase border
                                @if($cellDetails['density_level'] === 4) bg-rose-50 text-rose-700 border-rose-200
                                @elseif($cellDetails['density_level'] === 3) bg-amber-50 text-amber-700 border-amber-200
                                @elseif($cellDetails['density_level'] === 2) bg-yellow-50 text-yellow-700 border-yellow-200
                                @else bg-slate-50 text-slate-600 border-slate-200 @endif">
                                @if($cellDetails['density_level'] === 4) Crítico
                                @elseif($cellDetails['density_level'] === 3) Moderado
                                @elseif($cellDetails['density_level'] === 2) Baixo
                                @else Sem Dados
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- The synthesis text -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                        <span class="text-[9px] font-mono text-blue-700 uppercase font-bold tracking-widest block mb-1">Nexo Causal Integrado</span>
                        <p class="text-xs text-slate-700 leading-relaxed font-sans font-normal">
                            {{ $cellDetails['correlation_text'] }}
                        </p>
                    </div>

                    <!-- Faked scientific confidence factors & source systems -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 pt-1">
                        <div class="text-[9px] font-mono text-slate-400">
                            <span class="block uppercase">P-Value Epidemiológico</span>
                            <span class="text-slate-700 font-bold">p &lt; 0.005</span>
                        </div>
                        <div class="text-[9px] font-mono text-slate-400">
                            <span class="block uppercase">Metodologia Aplicada</span>
                            <span class="text-slate-700 font-bold">ROADMAP-AD v2</span>
                        </div>
                        <div class="text-[9px] font-mono text-slate-400">
                            <span class="block uppercase">Confiança Estatística</span>
                            <span class="text-slate-700 font-bold">95.4% (Intervalo)</span>
                        </div>
                        <div class="text-[9px] font-mono text-slate-400">
                            <span class="block uppercase">Última Modificação</span>
                            <span class="text-slate-700 font-bold">{{ $cellDetails['updated_at'] ?? 'Sincronizado' }}</span>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty Placeholder State -->
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <div class="p-3 bg-slate-200/50 rounded-2xl border border-slate-300/40 mb-2.5 text-slate-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                    </div>
                    <span class="text-xs font-semibold text-slate-500 font-mono uppercase tracking-wider">Aguardando Seleção de Dados</span>
                    <p class="text-[11px] text-slate-400 max-w-[420px] mt-1 leading-normal">
                        Selecione qualquer célula preenchida da tabela para abrir o nexo causal científico cruzando dados socioambientais e de saúde da Amazônia.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
