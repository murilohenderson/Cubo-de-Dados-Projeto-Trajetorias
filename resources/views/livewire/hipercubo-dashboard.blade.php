<div id="dashboard-capture-area" 
     class="flex-grow flex flex-col lg:flex-row items-stretch overflow-hidden w-full relative bg-slate-50 text-slate-900"
     x-data="{
        leftWidth: 42,
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
            const newWidth = (e.clientX / containerWidth) * 100;
            if (newWidth > 25 && newWidth < 70) {
                this.leftWidth = newWidth;
            }
        },
        stopResize() {
            if (this.isResizing) {
                this.isResizing = false;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            }
        }
     }"
     @mousemove="resize($event)"
     @mouseup="stopResize()"
     @mouseleave="stopResize()"
>
    <!-- LEFT COLUMN: 3D Data Cube(s) (Resizable split-screen) -->
    <div class="w-full flex flex-col justify-between bg-[radial-gradient(circle_at_50%_45%,#cbd5e1_0%,#f1f5f9_65%,#f8fafc_100%)] p-6 relative overflow-y-auto h-full flex-shrink-0 border-b border-slate-200 lg:border-b-0"
         :style="isDesktop ? 'width: ' + leftWidth + '%' : ''">
        
        <!-- Header Info & Comparative Toggle -->
        <div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="h-2.5 w-2.5 rounded bg-blue-900"></span>
                    <span class="text-[10px] font-mono tracking-widest text-blue-900 uppercase font-bold">PAINEL DE PROJEÇÃO CIENTÍFICA</span>
                </div>
                <!-- Comparative Mode Toggle -->
                <button type="button" 
                        wire:click="toggleModoComparativo"
                        class="flex items-center space-x-1.5 px-3 py-1 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-bold text-slate-700 rounded-lg shadow-sm transition duration-200"
                >
                    <span class="h-2 w-2 rounded-full {{ $modoComparativo ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' }}"></span>
                    <span>Modo Comparativo</span>
                </button>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 mt-2">
                Cubo de dados Tridimensional
            </h2>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                Rotacione o cubo tridimensional e analise as diferentes faces. Clique na face de interesse para filtrar a matriz. No Modo Comparativo, você pode analisar e rotacionar dois cubos independentes.
            </p>
        </div>

        @if($modoComparativo)
            <!-- DUAL CUBES SIDE-BY-SIDE (Independent Alpine Rotation Contexts) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 my-6 w-full items-stretch">
                <!-- Cube A (Main active selection) -->
                <div class="flex flex-col items-center border border-slate-200/80 bg-white/70 backdrop-blur-xs p-3.5 rounded-2xl relative shadow-xs"
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
                        minZoomZ: -100,
                        maxZoomZ: 200,
                        
                        startDrag(e) {
                            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('select')) return;
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
                                this.rotateY = this.tempRotateY + dx * 0.45;
                                this.rotateX = Math.max(-75, Math.min(75, this.tempRotateX - dy * 0.45));
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
                                return;
                            }
                            $wire.setActiveFace(face);
                        }
                     }"
                     @mousedown="startDrag($event)"
                     @mousemove="drag($event)"
                     @mouseup.window="stopDrag()"
                     @touchstart="startDrag($event)"
                     @touchmove="drag($event)"
                     @touchend.window="stopDrag()"
                >
                    <div class="w-full flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                        <span class="text-[9px] font-mono font-bold text-slate-400 uppercase">Cubo Principal (A)</span>
                        <span class="text-[9px] font-mono text-blue-900 font-extrabold">{{ $activeMapping['label'] }}</span>
                    </div>

                    <!-- 3D Scene Viewport -->
                    <div class="cube-container h-56 w-full relative overflow-hidden select-none cursor-grab active:cursor-grabbing">
                        <div class="animate-float w-full h-full flex items-center justify-center" style="transform-style: preserve-3d; animation-delay: 0s;">
                            <div class="cube-wrapper" :style="`transform: translateZ(${zoomZ}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(0.75)`">
                                @foreach(['front', 'back', 'left', 'right', 'top', 'bottom'] as $face)
                                    <div @click="handleFaceClick('{{ $face }}', $event)"
                                         class="cube-face flex flex-col justify-between p-3 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden {{ 'face-' . $face }}"
                                         :class="$wire.activeFace === '{{ $face }}' 
                                             ? 'border-blue-900 bg-blue-900 text-white scale-102 technical-grid-active shadow-md' 
                                             : 'border-slate-300 bg-slate-55 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-xs'"
                                    >
                                        <div class="w-full flex justify-between items-start text-[7px] font-mono font-bold">
                                            <span class="uppercase">{{ $face }}</span>
                                            <div class="h-1.5 w-1.5 rounded-full" :class="$wire.activeFace === '{{ $face }}' ? 'bg-white' : 'bg-blue-900'"></div>
                                        </div>
                                        <div class="text-center flex-grow flex items-center justify-center font-bold text-[9px] uppercase leading-tight font-sans">
                                            {{ $faceMappings[$face]['label'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Presets controls -->
                    <div class="flex items-center space-x-1 mt-2">
                        <button type="button" @click="rotateTo(-20, 35)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Frontal</button>
                        <button type="button" @click="rotateTo(-20, 215)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Post</button>
                        <button type="button" @click="rotateTo(-90, 0)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Sup</button>
                        <button type="button" @click="rotateTo(90, 0)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Inf</button>
                    </div>
                </div>

                <!-- Cube B (Comparative secondary selection) -->
                <div class="flex flex-col items-center border border-slate-200/80 bg-white/70 backdrop-blur-xs p-3.5 rounded-2xl relative shadow-xs"
                     x-data="{
                        isDragging: false,
                        hasMoved: false,
                        startX: 0,
                        startY: 0,
                        rotateX: -20,
                        rotateY: 215,
                        tempRotateX: -20,
                        tempRotateY: 215,
                        threshold: 5,
                        zoomZ: 0,
                        minZoomZ: -100,
                        maxZoomZ: 200,
                        
                        startDrag(e) {
                            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('select')) return;
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
                                this.rotateY = this.tempRotateY + dx * 0.45;
                                this.rotateX = Math.max(-75, Math.min(75, this.tempRotateX - dy * 0.45));
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
                                return;
                            }
                            $wire.setActiveFaceB(face);
                        }
                     }"
                     @mousedown="startDrag($event)"
                     @mousemove="drag($event)"
                     @mouseup.window="stopDrag()"
                     @touchstart="startDrag($event)"
                     @touchmove="drag($event)"
                     @touchend.window="stopDrag()"
                >
                    <div class="w-full flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                        <span class="text-[9px] font-mono font-bold text-slate-400 uppercase">Cubo Comparador (B)</span>
                        <span class="text-[9px] font-mono text-indigo-900 font-extrabold">{{ $activeMappingB['label'] }}</span>
                    </div>

                    <!-- 3D Scene Viewport -->
                    <div class="cube-container h-56 w-full relative overflow-hidden select-none cursor-grab active:cursor-grabbing">
                        <div class="animate-float w-full h-full flex items-center justify-center" style="transform-style: preserve-3d; animation-delay: -2s;">
                            <div class="cube-wrapper" :style="`transform: translateZ(${zoomZ}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(0.75)`">
                                @foreach(['front', 'back', 'left', 'right', 'top', 'bottom'] as $face)
                                    <div @click="handleFaceClick('{{ $face }}', $event)"
                                         class="cube-face flex flex-col justify-between p-3 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden {{ 'face-' . $face }}"
                                         :class="$wire.activeFaceB === '{{ $face }}' 
                                             ? 'border-indigo-900 bg-indigo-900 text-white scale-102 technical-grid-active shadow-md' 
                                             : 'border-slate-300 bg-slate-55 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-xs'"
                                    >
                                        <div class="w-full flex justify-between items-start text-[7px] font-mono font-bold">
                                            <span class="uppercase">{{ $face }}</span>
                                            <div class="h-1.5 w-1.5 rounded-full" :class="$wire.activeFaceB === '{{ $face }}' ? 'bg-white' : 'bg-indigo-900'"></div>
                                        </div>
                                        <div class="text-center flex-grow flex items-center justify-center font-bold text-[9px] uppercase leading-tight font-sans">
                                            {{ $faceMappings[$face]['label'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Presets controls -->
                    <div class="flex items-center space-x-1 mt-2">
                        <button type="button" @click="rotateTo(-20, 35)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Frontal</button>
                        <button type="button" @click="rotateTo(-20, 215)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Post</button>
                        <button type="button" @click="rotateTo(-90, 0)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Sup</button>
                        <button type="button" @click="rotateTo(90, 0)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold">Inf</button>
                    </div>
                </div>
            </div>
        @else
            <!-- STANDARD SINGLE CUBE -->
            <div class="my-6 py-4 flex flex-col items-center justify-center min-h-[440px] select-none relative"
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
                    minZoomZ: -150,
                    maxZoomZ: 350,
                    
                    startDrag(e) {
                        if (e.target.closest('button') || e.target.closest('a') || e.target.closest('select')) {
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
                <!-- 3D Scene Viewport -->
                <div class="perspective-1000 w-96 h-96 flex items-center justify-center cursor-grab active:cursor-grabbing relative"
                     @wheel.prevent="handleWheel($event)">
                    
                    <!-- Floor Shadow -->
                    <div class="absolute bottom-6 w-40 h-2 bg-slate-300/60 rounded-full blur-md pointer-events-none transition-all duration-300 ease-out"
                         :style="`transform: scale(${(isDragging ? 0.8 : 1) * (1 + zoomZ / 350)})`"
                    ></div>

                    <!-- Cube Wrapper -->
                    <div class="cube-container">
                        <div class="animate-float preserve-3d">
                            <div class="cube-wrapper"
                                 :style="`transform: translateZ(${zoomZ}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`">
                                 
                                @foreach(['front', 'back', 'left', 'right', 'top', 'bottom'] as $face)
                                    <div @click="handleFaceClick('{{ $face }}', $event)"
                                         class="cube-face flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-350 backface-hidden {{ 'face-' . $face }}"
                                         :class="$wire.activeFace === '{{ $face }}' 
                                             ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                             : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                                    >
                                        <div class="w-full flex justify-between items-start">
                                            <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                                  :class="$wire.activeFace === '{{ $face }}' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                                {{ strtoupper($face) }}
                                            </span>
                                            <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === '{{ $face }}' ? 'bg-white' : 'bg-blue-900'"></div>
                                        </div>
                                        
                                        <div class="flex flex-col items-center text-center flex-grow justify-center">
                                            <span class="text-sm font-extrabold tracking-tight font-sans leading-tight uppercase">
                                                {{ $faceMappings[$face]['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preset rotation controls -->
                <div class="mt-6 flex items-center justify-center space-x-2 relative z-10 w-full">
                    <button type="button" @click="rotateTo(-20, 35)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold">
                        Frontal
                    </button>
                    <button type="button" @click="rotateTo(-20, 215)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold">
                        Posterior
                    </button>
                    <button type="button" @click="rotateTo(-90, 0)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold">
                        Superior
                    </button>
                    <button type="button" @click="rotateTo(90, 0)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold">
                        Inferior
                    </button>
                </div>
            </div>
        @endif

        <!-- Active Face Information Badge (Bottom Left Footer) -->
        <div class="mt-4 p-3 border border-slate-200 rounded-xl bg-white text-[11px] text-slate-600 shadow-sm">
            <span class="font-bold text-slate-800 block uppercase font-mono text-[9px] tracking-wider mb-0.5">Cruzamento Ativo:</span>
            <span class="font-extrabold text-blue-900">{{ $activeMapping['label'] }}</span>
            @if($modoComparativo)
                <span class="text-slate-400 mx-1">|</span>
                <span class="font-bold text-slate-500 uppercase font-mono text-[9px]">Comparador (B):</span>
                <span class="font-extrabold text-indigo-900">{{ $activeMappingB['label'] }}</span>
            @endif
            <p class="text-[10px] mt-1 leading-relaxed text-slate-500 font-sans">
                {{ $activeMapping['desc'] }}
            </p>
        </div>
    </div>

    <!-- RESIZABLE SPLIT-SCREEN HANDLE BAR -->
    <div class="hidden lg:block w-1.5 bg-slate-200 hover:bg-blue-900 transition-colors cursor-col-resize relative flex-shrink-0"
         @mousedown="startResize($event)">
        <div class="absolute inset-y-0 -left-1.5 -right-1.5 cursor-col-resize z-30"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col space-y-1">
            <span class="h-1 w-1 rounded-full bg-slate-400"></span>
            <span class="h-1 w-1 rounded-full bg-slate-400"></span>
            <span class="h-1 w-1 rounded-full bg-slate-400"></span>
        </div>
    </div>

    <!-- RIGHT COLUMN: Synthesis, Heatmap Matrix, Evidence Panel -->
    <div class="w-full flex flex-col justify-between p-6 overflow-y-auto h-full bg-slate-50 border-t border-slate-200 lg:border-t-0"
         :style="isDesktop ? 'width: ' + (100 - leftWidth) + '%' : ''">
        
        <div class="space-y-6">
            <!-- Header & Action Triggers (CSV Export & PNG capturing) -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 mt-0.5">Painel de Correlação Cruzada</h3>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Export CSV StreamedResponse -->
                    <button type="button" 
                            wire:click="exportarCSV" 
                            class="flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-[10px] font-mono font-bold text-white rounded-lg shadow-sm transition duration-200"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Exportar CSV</span>
                    </button>

                    <!-- Export PNG Capture -->
                    <button id="download-png-btn" 
                            type="button" 
                            onclick="downloadDashboardPNG()" 
                            class="flex items-center space-x-1.5 px-3 py-1.5 bg-blue-900 hover:bg-blue-950 text-[10px] font-mono font-bold text-white rounded-lg shadow-sm transition duration-200"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Salvar Imagem PNG</span>
                    </button>
                </div>
            </div>

            <!-- Dynamic Selectors & Advanced Date Period Filters -->
            <div class="bg-white p-4 border border-slate-200 rounded-2xl space-y-4 shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-[9px] font-mono bg-blue-50 border border-blue-200 text-blue-900 px-2 py-0.5 rounded uppercase font-bold">FILTROS AVANÇADOS DO CUBO</span>
                    <span class="text-[10px] text-slate-500 font-sans">Defina as variáveis e o período de análise espacial</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Dropdown 1 -->
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center justify-between">
                            <span>{{ $dim1_label }}</span>
                            <x-context-tooltip title="{{ $dim1_label }}" content="Primeiro eixo de influência selecionado com base nas correlações da face do cubo tridimensional ativa.">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </x-context-tooltip>
                        </label>
                        <select wire:model.live="selectedInd1" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg focus:ring-blue-900 focus:border-blue-900 block w-full p-2.5 outline-none font-sans">
                            @foreach($dim1_indicators as $ind)
                                <option value="{{ $ind }}">{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dropdown 2 -->
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center justify-between">
                            <span>{{ $dim2_label }}</span>
                            <x-context-tooltip title="{{ $dim2_label }}" content="Segundo eixo de influência selecionado com base nas relações da face do cubo tridimensional ativa.">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </x-context-tooltip>
                        </label>
                        <select wire:model.live="selectedInd2" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg focus:ring-blue-900 focus:border-blue-900 block w-full p-2.5 outline-none font-sans">
                            @foreach($dim2_indicators as $ind)
                                <option value="{{ $ind }}">{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Advanced Date Period Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3.5 border-t border-slate-100">
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center justify-between">
                            <span>Data Início</span>
                            <x-context-tooltip title="Período de Início" content="Define o limite de tempo inferior para o cálculo dinâmico das estimativas e taxas de riscos da doença vetorial.">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </x-context-tooltip>
                        </label>
                        <input type="date" wire:model.live="data_inicio" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 focus:ring-blue-900 focus:border-blue-900 outline-none font-sans" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center justify-between">
                            <span>Data Fim</span>
                            <x-context-tooltip title="Período Fim" content="Define o limite de tempo superior para as taxas e modelagens estatísticas das doenças vetoriais coletadas.">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </x-context-tooltip>
                        </label>
                        <input type="date" wire:model.live="data_fim" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 focus:ring-blue-900 focus:border-blue-900 outline-none font-sans" />
                    </div>
                </div>
            </div>

            <!-- Heatmap Matrix -->
            <div class="border border-slate-200 rounded-2xl bg-white overflow-hidden shadow-sm">
                <div class="overflow-x-auto w-full">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="px-4 py-3.5 text-left text-[10px] font-mono font-bold tracking-wider text-slate-500 border-b border-slate-200 uppercase min-w-[220px]">
                                    Doenças Vetoriais (Incidência / Linhas)
                                </th>
                                @foreach($territories as $territory)
                                    <th class="px-2 py-3.5 text-center text-[10px] font-mono font-bold tracking-wider text-slate-500 border-b border-slate-200 uppercase min-w-[120px]">
                                        {{ $territory }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heatmapRows as $row)
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition duration-150">
                                    <!-- Row Name -->
                                    <td class="px-4 py-3 text-left">
                                        <div class="text-xs font-semibold text-slate-800 font-sans flex items-center space-x-1">
                                            <span>{{ $row['indicator'] }}</span>
                                            <x-context-tooltip title="{{ $row['indicator'] }}" content="Variável epidemiológica dependente sob estudo de co-relação e influência cruzada no Baixo Tocantins.">
                                                <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </x-context-tooltip>
                                        </div>
                                    </td>

                                    <!-- Heatmap Cells -->
                                    @foreach($territories as $territory)
                                        @php
                                            $riskLevel = $this->getRiskLevel($territory, $row['indicator']);
                                            
                                            $bgClass = '';
                                            $borderClass = '';
                                            $textClass = '';
                                            $riskText = '';
                                            
                                            if ($riskLevel === 4) {
                                                $bgClass = 'bg-rose-600 hover:bg-rose-700';
                                                $borderClass = 'border-rose-700';
                                                $textClass = 'text-white';
                                                $riskText = 'CRÍTICO';
                                            } elseif ($riskLevel === 3) {
                                                $bgClass = 'bg-amber-500 hover:bg-amber-600';
                                                $borderClass = 'border-amber-650';
                                                $textClass = 'text-white';
                                                $riskText = 'ALTO';
                                            } elseif ($riskLevel === 2) {
                                                $bgClass = 'bg-yellow-400 hover:bg-yellow-500';
                                                $borderClass = 'border-yellow-500';
                                                $textClass = 'text-slate-900';
                                                $riskText = 'MODERADO';
                                            } else {
                                                $bgClass = 'bg-slate-100 hover:bg-slate-200';
                                                $borderClass = 'border-slate-200';
                                                $textClass = 'text-slate-500';
                                                $riskText = 'BASELINE';
                                            }
                                            
                                            $isCellActive = $selectedCell && 
                                                           $selectedCell['territory'] === $territory && 
                                                           $selectedCell['row_indicator'] === $row['indicator'];
                                        @endphp
                                        <td class="p-1.5">
                                            <button type="button"
                                                    wire:click="selectCell('{{ $territory }}', '{{ $row['indicator'] }}')"
                                                    class="w-full h-12 flex flex-col justify-center items-center rounded-lg border transition duration-200 font-sans {{ $bgClass }} {{ $borderClass }} {{ $textClass }} {{ $isCellActive ? 'ring-2 ring-blue-900 ring-offset-2 scale-102 border-blue-900' : '' }}">
                                                <span class="text-[8px] font-mono tracking-wider font-extrabold uppercase">
                                                    {{ $riskText }}
                                                </span>
                                                <span class="text-[9px] font-mono mt-0.5 font-bold opacity-90">
                                                    Nível {{ $riskLevel }}
                                                </span>
                                            </button>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- EVIDENCE PANEL (Scientific Synthesis Footer) -->
        <div class="mt-6">
            @if($selectedCell)
                @php
                    $alertColor = 'border-slate-200 bg-white text-slate-800';
                    if ($selectedCell['risk_level'] === 4) {
                        $alertColor = 'border-rose-200 bg-rose-50/20 text-slate-900';
                    } elseif ($selectedCell['risk_level'] === 3) {
                        $alertColor = 'border-amber-200 bg-amber-50/20 text-slate-900';
                    } elseif ($selectedCell['risk_level'] === 2) {
                        $alertColor = 'border-yellow-200 bg-yellow-50/20 text-slate-900';
                    } else {
                        $alertColor = 'border-slate-200 bg-slate-50/30 text-slate-800';
                    }
                @endphp
                <div class="border rounded-2xl p-5 {{ $alertColor }} transition-all duration-350 space-y-5 shadow-xs">
                    <!-- Title & Time -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[8px] font-mono tracking-wider font-black uppercase border
                                {{ $selectedCell['risk_level'] === 4 ? 'bg-rose-600 border-rose-700 text-white' : 
                                  ($selectedCell['risk_level'] === 3 ? 'bg-amber-500 border-amber-600 text-white' : 
                                  ($selectedCell['risk_level'] === 2 ? 'bg-yellow-400 border-yellow-500 text-slate-900' : 
                                  'bg-slate-200 border-slate-300 text-slate-700')) }}">
                                {{ $selectedCell['risk_level'] === 4 ? 'CRÍTICO' : 
                                  ($selectedCell['risk_level'] === 3 ? 'ALTO' : 
                                  ($selectedCell['risk_level'] === 2 ? 'MODERADO' : 'BASELINE')) }}
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-800 uppercase tracking-tight">
                                DOSSIÊ CAUSAL INTEGRADO — {{ $selectedCell['territory'] }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-3.5">
                            <!-- Drill-down Button -->
                            <button type="button" 
                                    wire:click="abrirDrillDown" 
                                    class="flex items-center space-x-1 px-2.5 py-1 bg-rose-650 hover:bg-rose-700 text-[9px] font-mono font-bold text-white rounded-md shadow-xs transition duration-200"
                            >
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span>Drill-down Temporal</span>
                            </button>
                            <span class="text-[9px] font-mono text-slate-500">
                                Sincronizado: {{ $selectedCell['timestamp'] }}
                            </span>
                        </div>
                    </div>

                    <!-- PREMIUM RESPONSIVE CARDS GRID (With hover micro-animations & overflow treatment) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Stat 1: Indicator 1 -->
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 flex flex-col justify-between min-w-0 hover:scale-105 hover:shadow-xl hover:border-blue-300 transition-all duration-300 cursor-pointer">
                            <div>
                                <span class="text-slate-400 text-[8px] font-mono uppercase font-bold tracking-wider block">INDICADOR AMBIENTAL / ECONÔMICO</span>
                                <span class="text-xs font-bold text-slate-800 block mt-0.5 truncate">{{ $selectedCell['indicator_1'] }}</span>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-100 flex items-baseline justify-between gap-1">
                                <span class="text-[10px] font-mono text-slate-500 flex-shrink-0">Métrica local:</span>
                                <span class="text-xs font-bold text-blue-900 font-mono truncate text-right">{{ $selectedCell['ind1_val'] }}</span>
                            </div>
                        </div>

                        <!-- Stat 2: Indicator 2 -->
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 flex flex-col justify-between min-w-0 hover:scale-105 hover:shadow-xl hover:border-blue-300 transition-all duration-300 cursor-pointer">
                            <div>
                                <span class="text-slate-400 text-[8px] font-mono uppercase font-bold tracking-wider block">INDICADOR SOCIAL / INFRAESTRUTURA</span>
                                <span class="text-xs font-bold text-slate-800 block mt-0.5 truncate">{{ $selectedCell['indicator_2'] }}</span>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-100 flex items-baseline justify-between gap-1">
                                <span class="text-[10px] font-mono text-slate-500 flex-shrink-0">Métrica local:</span>
                                <span class="text-xs font-bold text-blue-900 font-mono truncate text-right">{{ $selectedCell['ind2_val'] }}</span>
                            </div>
                        </div>

                        <!-- Stat 3: Disease Cases -->
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 flex flex-col justify-between min-w-0 hover:scale-105 hover:shadow-xl hover:border-rose-350 transition-all duration-300 cursor-pointer">
                            <div>
                                <span class="text-slate-400 text-[8px] font-mono uppercase font-bold tracking-wider block">VARIÁVEL DE INCIDÊNCIA (LINHA)</span>
                                <span class="text-xs font-bold text-slate-800 block mt-0.5 truncate">Casos de {{ $selectedCell['row_indicator'] }}</span>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-100 flex items-baseline justify-between gap-1">
                                <span class="text-[10px] font-mono text-slate-500 flex-shrink-0">Registros anuais:</span>
                                <span class="text-xs font-bold text-rose-600 font-mono truncate text-right">{{ $selectedCell['disease_val'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Synthesis scientific narrative -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-250/60 shadow-inner">
                        <span class="text-[9px] font-mono text-slate-500 uppercase font-black tracking-widest block mb-1">Mecanismo de Relação e Transmissão</span>
                        <p class="text-xs text-slate-700 leading-relaxed font-sans">
                            {{ $selectedCell['evidence_text'] }}
                        </p>
                    </div>

                    <!-- INTEGRADO COM MAPA REAL (Leaflet.js / SVG Toggle) -->
                    <div x-data="{ mapTab: 'svg' }" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                        <div class="flex items-center justify-between mb-3.5 border-b border-slate-100 pb-2">
                            <span class="text-[9px] font-mono text-slate-500 uppercase font-black tracking-widest block">Análise Cartográfica (Corográfica)</span>
                            <!-- Tab selector -->
                            <div class="flex space-x-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                                <button type="button" @click="mapTab = 'svg'" class="px-2.5 py-1 text-[9px] font-bold rounded-md transition" :class="mapTab === 'svg' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700'">
                                    Esquemático SVG
                                </button>
                                <button type="button" @click="mapTab = 'leaflet'" class="px-2.5 py-1 text-[9px] font-bold rounded-md transition" :class="mapTab === 'leaflet' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700'">
                                    Mapa Leaflet Real
                                </button>
                            </div>
                        </div>
                        
                        <!-- SVG Map Layout -->
                        <div x-show="mapTab === 'svg'" class="flex flex-col lg:flex-row items-center justify-center gap-6">
                            <!-- SVG Map -->
                            <div class="w-full max-w-[280px]">
                                <svg viewBox="0 0 400 320" class="w-full h-auto drop-shadow-md">
                                    <!-- Tocantins River -->
                                    <path d="M 210,320 C 190,260 220,200 190,140 C 170,90 200,40 185,0" 
                                          stroke="#3b82f6" stroke-width="8" fill="none" stroke-linecap="round" opacity="0.3" />
                                    <path d="M 210,320 C 190,260 220,200 190,140 C 170,90 200,40 185,0" 
                                          stroke="#60a5fa" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.6" />
                                    
                                    <text x="135" y="210" font-family="monospace" font-size="8" fill="#3b82f6" font-weight="bold" opacity="0.6" transform="rotate(-70 135 210)">Rio Tocantins</text>
                                    
                                    <!-- Baião (Southern Municipality) -->
                                    @php
                                        $isBaiao = ($selectedCell['territory'] === 'Baião');
                                        $baiaoRisk = $isBaiao ? $selectedCell['risk_level'] : $this->getRiskLevel('Baião', $selectedCell['row_indicator']);
                                        $baiaoColor = $baiaoRisk === 4 ? '#e11d48' : ($baiaoRisk === 3 ? '#f59e0b' : ($baiaoRisk === 2 ? '#facc15' : '#e2e8f0'));
                                        $baiaoStroke = $isBaiao ? '#1e3a8a' : '#94a3b8';
                                        $baiaoWidth = $isBaiao ? '3.5' : '1';
                                    @endphp
                                    <g class="cursor-pointer group" wire:click="selectCell('Baião', '{{ $selectedCell['row_indicator'] }}')">
                                        <path d="M 200,240 L 260,220 L 250,280 L 190,285 Z" 
                                              fill="{{ $baiaoColor }}" stroke="{{ $baiaoStroke }}" stroke-width="{{ $baiaoWidth }}" 
                                              class="transition duration-200 hover:brightness-95" />
                                        <text x="225" y="255" font-family="sans-serif" font-size="10" font-weight="bold" fill="{{ $baiaoRisk === 1 ? '#475569' : ($baiaoRisk === 2 ? '#1e293b' : '#ffffff') }}" text-anchor="middle">Baião</text>
                                    </g>

                                    <!-- Mocajuba (Central Municipality) -->
                                    @php
                                        $isMocajuba = ($selectedCell['territory'] === 'Mocajuba');
                                        $mocajubaRisk = $isMocajuba ? $selectedCell['risk_level'] : $this->getRiskLevel('Mocajuba', $selectedCell['row_indicator']);
                                        $mocajubaColor = $mocajubaRisk === 4 ? '#e11d48' : ($mocajubaRisk === 3 ? '#f59e0b' : ($mocajubaRisk === 2 ? '#facc15' : '#e2e8f0'));
                                        $mocajubaStroke = $isMocajuba ? '#1e3a8a' : '#94a3b8';
                                        $mocajubaWidth = $isMocajuba ? '3.5' : '1';
                                    @endphp
                                    <g class="cursor-pointer group" wire:click="selectCell('Mocajuba', '{{ $selectedCell['row_indicator'] }}')">
                                        <path d="M 170,120 L 215,110 L 230,170 L 180,180 Z" 
                                              fill="{{ $mocajubaColor }}" stroke="{{ $mocajubaStroke }}" stroke-width="{{ $mocajubaWidth }}" 
                                              class="transition duration-200 hover:brightness-95" />
                                        <text x="198" y="150" font-family="sans-serif" font-size="10" font-weight="bold" fill="{{ $mocajubaRisk === 1 ? '#475569' : ($mocajubaRisk === 2 ? '#1e293b' : '#ffffff') }}" text-anchor="middle">Mocajuba</text>
                                    </g>

                                    <!-- Cametá (Northern Municipality) -->
                                    @php
                                        $isCameta = ($selectedCell['territory'] === 'Cametá');
                                        $cametaRisk = $isCameta ? $selectedCell['risk_level'] : $this->getRiskLevel('Cametá', $selectedCell['row_indicator']);
                                        $cametaColor = $cametaRisk === 4 ? '#e11d48' : ($cametaRisk === 3 ? '#f59e0b' : ($cametaRisk === 2 ? '#facc15' : '#e2e8f0'));
                                        $cametaStroke = $isCameta ? '#1e3a8a' : '#94a3b8';
                                        $cametaWidth = $isCameta ? '3.5' : '1';
                                    @endphp
                                    <g class="cursor-pointer group" wire:click="selectCell('Cametá', '{{ $selectedCell['row_indicator'] }}')">
                                        <path d="M 155,40 L 220,30 L 240,90 L 175,85 Z" 
                                              fill="{{ $cametaColor }}" stroke="{{ $cametaStroke }}" stroke-width="{{ $cametaWidth }}" 
                                              class="transition duration-200 hover:brightness-95" />
                                        <text x="198" y="65" font-family="sans-serif" font-size="10" font-weight="bold" fill="{{ $cametaRisk === 1 ? '#475569' : ($cametaRisk === 2 ? '#1e293b' : '#ffffff') }}" text-anchor="middle">Cametá</text>
                                    </g>
                                </svg>
                            </div>

                            <!-- Map Legend & Details (Collapsible Alpine) -->
                            <div class="flex-1 space-y-3 font-sans text-xs">
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100" x-data="{ legendOpen: true }">
                                    <button type="button" 
                                            @click="legendOpen = !legendOpen" 
                                            class="w-full flex items-center justify-between font-bold text-slate-800 text-[9px] uppercase font-mono tracking-wider focus:outline-none"
                                    >
                                        <span>Níveis de Risco Associativo</span>
                                        <svg class="h-3 w-3 transform transition-transform duration-200" :class="legendOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    
                                    <div x-show="legendOpen" x-transition class="grid grid-cols-2 gap-2 mt-3 pt-2.5 border-t border-slate-200/60">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="h-3 w-3 rounded bg-rose-600 border border-rose-700 block"></span>
                                            <span class="text-slate-650 font-medium">Nível 4 (Crítico)</span>
                                        </div>
                                        <div class="flex items-center space-x-1.5">
                                            <span class="h-3 w-3 rounded bg-amber-500 border border-amber-600 block"></span>
                                            <span class="text-slate-650 font-medium">Nível 3 (Alto)</span>
                                        </div>
                                        <div class="flex items-center space-x-1.5">
                                            <span class="h-3 w-3 rounded bg-yellow-400 border border-yellow-500 block"></span>
                                            <span class="text-slate-650 font-medium">Nível 2 (Moderado)</span>
                                        </div>
                                        <div class="flex items-center space-x-1.5">
                                            <span class="h-3 w-3 rounded bg-slate-100 border border-slate-200 block"></span>
                                            <span class="text-slate-650 font-medium">Nível 1 (Baseline)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-500 leading-relaxed space-y-1">
                                    <p class="font-semibold text-slate-700">Explicação do Mapa:</p>
                                    <p>Os polígonos representam os três municípios limítrofes do Baixo Tocantins. As cores refletem a força de influência de <span class="font-bold text-slate-700">{{ $selectedCell['indicator_1'] }}</span> e <span class="font-bold text-slate-700">{{ $selectedCell['indicator_2'] }}</span> na ocorrência de <span class="font-bold text-slate-700">{{ $selectedCell['row_indicator'] }}</span>.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Real Leaflet Map Layout -->
                        <div x-show="mapTab === 'leaflet'" style="display: none;" class="space-y-4">
                            <div x-data="{
                                map: null,
                                markersGroup: null,
                                coordinates: {
                                    'Baião': [-2.791, -49.673],
                                    'Cametá': [-2.244, -49.497],
                                    'Mocajuba': [-2.584, -49.507]
                                },
                                riskColors: {
                                    4: '#e11d48',
                                    3: '#f59e0b',
                                    2: '#facc15',
                                    1: '#94a3b8'
                                },
                                setupMap() {
                                    this.$nextTick(() => {
                                        const container = document.getElementById('leaflet-map');
                                        if (!container || this.map) return;

                                        this.map = L.map('leaflet-map', {
                                            zoomControl: true,
                                            scrollWheelZoom: true
                                        }).setView([-2.52, -49.56], 9);

                                         // High-resolution Esri World Imagery (Satellite)
                                         L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                             attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                                             maxZoom: 18
                                         }).addTo(this.map);

                                         // Esri Reference Labels and Borders overlay for clear identification
                                         L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                                             attribution: 'Labels &copy; Esri',
                                             maxZoom: 18
                                         }).addTo(this.map);

                                        this.markersGroup = L.layerGroup().addTo(this.map);
                                        this.updateMarkers();
                                    });
                                },
                                updateMarkers() {
                                    if (!this.map || !this.markersGroup) return;
                                    this.markersGroup.clearLayers();

                                    const territories = ['Baião', 'Cametá', 'Mocajuba'];
                                    territories.forEach(name => {
                                        const coords = this.coordinates[name];
                                        if (!coords) return;

                                        const risk = this.getRisk(name);
                                        const color = this.riskColors[risk] || '#94a3b8';
                                        const cases = this.getCases(name);

                                         // Circle markers customized with high contrast white outline for perfect visibility over satellite texture
                                         const circle = L.circleMarker(coords, {
                                             radius: risk * 7 + 10,
                                             fillColor: color,
                                             color: '#ffffff',
                                             weight: 2.5,
                                             opacity: 1.0,
                                             fillOpacity: 0.85
                                         }).addTo(this.markersGroup);

                                        circle.bindPopup(`
                                            <div class='font-sans p-1.5 text-xs text-slate-800' style='min-width: 140px;'>
                                                <h5 class='font-bold border-b border-slate-100 pb-1 mb-1 text-slate-900'>${name}</h5>
                                                <div class='flex items-center space-x-1.5 mt-1'>
                                                    <span class='h-2 w-2 rounded-full' style='background-color: ${color}'></span>
                                                    <span>Risco: <strong>Nível ${risk}</strong></span>
                                                </div>
                                                <div class='mt-0.5 text-slate-650'>
                                                    Casos: <strong class='text-rose-600'>${cases}</strong>
                                                </div>
                                            </div>
                                        `);
                                    });
                                },
                                getRisk(name) {
                                    if (name === 'Baião') return {{ $this->getRiskLevel('Baião', $selectedCell['row_indicator'] ?? 'Dengue') }};
                                    if (name === 'Cametá') return {{ $this->getRiskLevel('Cametá', $selectedCell['row_indicator'] ?? 'Dengue') }};
                                    if (name === 'Mocajuba') return {{ $this->getRiskLevel('Mocajuba', $selectedCell['row_indicator'] ?? 'Dengue') }};
                                    return 1;
                                },
                                getCases(name) {
                                    if (name === 'Baião') return '{{ $this->getDiseaseCases('Baião', $selectedCell['row_indicator'] ?? 'Dengue') }}';
                                    if (name === 'Cametá') return '{{ $this->getDiseaseCases('Cametá', $selectedCell['row_indicator'] ?? 'Dengue') }}';
                                    if (name === 'Mocajuba') return '{{ $this->getDiseaseCases('Mocajuba', $selectedCell['row_indicator'] ?? 'Dengue') }}';
                                    return '0';
                                }
                            }"
                            x-init="
                                $watch('mapTab', value => {
                                    if (value === 'leaflet') {
                                        $nextTick(() => {
                                            if (!map) {
                                                setupMap();
                                            } else {
                                                setTimeout(() => {
                                                    map.invalidateSize();
                                                    updateMarkers();
                                                }, 100);
                                            }
                                        });
                                    }
                                });
                                if (mapTab === 'leaflet') {
                                    setupMap();
                                }
                            "
                            @update-map.window="updateMarkers()"
                            @click.window="if(mapTab === 'leaflet') { $nextTick(() => { if(map) map.invalidateSize(); }) }"
                            class="relative"
                            >
                                <div id="leaflet-map" class="w-full h-80 rounded-xl overflow-hidden shadow-inner border border-slate-200 bg-slate-900 z-10"></div>
                                
                                <div class="text-[10px] text-slate-500 bg-slate-50 p-3.5 border border-slate-200/60 rounded-xl leading-relaxed mt-3">
                                    <p class="font-bold text-slate-700">Explicação do Mapa Cartográfico Real:</p>
                                    <p>A camada coroplética com círculos proporcionais pulsantes ilustra a intensidade espacial dos riscos vetoriais. Ao se basear em dados latitudinais e longitudinais reais, é possível inferir a influência geoecológica dos cursos d'água adjacentes e das rodovias de conexão regional.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-center text-slate-400 bg-slate-50/50 border border-dashed border-slate-200 rounded-xl">
                    <svg class="w-8 h-8 opacity-40 mb-2.5 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span class="text-[10px] font-mono tracking-wider uppercase font-bold text-slate-500">Selecione uma célula na matriz de síntese para abrir o dossiê geo-espacial e drill-down</span>
                </div>
            @endif
        </div>
    </div>

    <!-- DRILL-DOWN MODAL WITH TIME-SERIES CHART (Livewire + Alpine + Chart.js) -->
    <div x-data="{ 
            open: @entangle('isModalOpen'),
            chart: null,
            initChart(data) {
                if (!data || !data.series) return;
                
                this.$nextTick(() => {
                    const ctx = document.getElementById('drillDownChart');
                    if (!ctx) return;
                    
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    
                    const labels = data.series.map(item => item.year);
                    const values = data.series.map(item => item.cases);
                    
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: `Casos Históricos de ${data.disease} em ${data.territory}`,
                                data: values,
                                borderColor: 'rgb(225, 29, 72)',
                                backgroundColor: 'rgba(225, 29, 72, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: 'rgb(225, 29, 72)',
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        font: {
                                            family: 'Inter',
                                            size: 11,
                                            weight: 'bold'
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    ticks: {
                                        font: {
                                            family: 'JetBrains Mono',
                                            size: 10
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            family: 'JetBrains Mono',
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            }
         }"
         x-show="open"
         @open-drilldown-modal.window="initChart($event.detail)"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
    >
        <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-2xl overflow-hidden shadow-2xl transition-all"
             @click.away="open = false">
            <div class="bg-[#0f172a] text-white p-4 flex items-center justify-between">
                <div>
                    <span class="text-[9px] font-mono text-blue-400 font-bold uppercase tracking-wider block">ANÁLISE TEMPORAL DRILL-DOWN</span>
                    <h4 class="text-sm font-bold font-sans">
                        Série Histórica: {{ $drillDownData['disease'] ?? '' }} em {{ $drillDownData['territory'] ?? '' }}
                    </h4>
                </div>
                <button @click="open = false" class="text-slate-400 hover:text-white transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="h-64 relative w-full bg-slate-50 rounded-xl p-3 border border-slate-100 shadow-inner">
                    <canvas id="drillDownChart"></canvas>
                </div>
                
                <div class="text-xs text-slate-500 leading-relaxed font-sans bg-slate-50 p-3.5 border border-slate-200/60 rounded-xl">
                    <p class="font-bold text-slate-700 mb-1">Deduções Epidemiológicas Multicritério:</p>
                    <p>O comportamento histórico temporal do município de <strong>{{ $drillDownData['territory'] ?? '' }}</strong> exibe as tendências epidemiológicas reais agregadas sob as frentes de alteração ambiental de longo prazo. Flutuações anuais correlacionam diretamente com oscilações térmicas e de desmatamento cumulativo.</p>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-3 flex justify-end border-t border-slate-100">
                <button @click="open = false" class="px-4 py-2 bg-slate-250 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-lg transition">
                    Fechar Painel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CAPTURE PORTAL VANILLA JAVASCRIPT -->
<script>
    function downloadDashboardPNG() {
        // Load html2canvas dynamically if not present
        if (typeof html2canvas === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            script.crossOrigin = 'anonymous';
            script.onload = () => executeCapture();
            document.head.appendChild(script);
        } else {
            executeCapture();
        }

        function executeCapture() {
            const container = document.getElementById('dashboard-capture-area');
            if (!container) {
                alert('Elemento da captura não encontrado!');
                return;
            }

            const btn = document.getElementById('download-png-btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span>Capturando...</span>';
            btn.disabled = true;

            // Generate premium quality canvas capture
            html2canvas(container, {
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#f8fafc',
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'visualizacao-cubo.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }).catch(err => {
                console.error('Falha ao exportar PNG:', err);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }
    }
</script>
