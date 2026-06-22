<div id="dashboard-capture-area" 
     class="flex-grow flex flex-col lg:flex-row items-stretch overflow-hidden w-full relative bg-slate-50 text-slate-900"
     x-data="{
        leftWidth: 42,
        isResizing: false,
        isDesktop: window.innerWidth >= 1024,
        activeTab: 'matriz',
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

            <!-- Tab Navigation Menu -->
            <div class="flex space-x-1 border-b border-slate-200">
                <button type="button" 
                        @click="activeTab = 'matriz'"
                        class="px-4 py-2 text-xs font-bold transition duration-150 border-b-2"
                        :class="activeTab === 'matriz' ? 'border-blue-900 text-blue-900 border-blue-900' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Matriz de Risco (Cubo)
                </button>
                <button type="button" 
                        @click="activeTab = 'grafo'"
                        class="px-4 py-2 text-xs font-bold transition duration-150 border-b-2"
                        :class="activeTab === 'grafo' ? 'border-blue-900 text-blue-900 border-blue-900' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Grafo de Relações (D3.js)
                </button>
                <button type="button" 
                        @click="activeTab = 'ml'"
                        class="px-4 py-2 text-xs font-bold transition duration-150 border-b-2"
                        :class="activeTab === 'ml' ? 'border-blue-900 text-blue-900 border-blue-900' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Pipeline de ML (Rede Neural)
                </button>
            </div>

            <!-- TAB: MATRIZ DE RISCO -->
            <div x-show="activeTab === 'matriz'" class="space-y-6">
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

                <!-- Advanced Date Period Filters & Zona Residencial -->
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

                <!-- Zona Residencial (Filtro para Saúde/Epidemiológico) -->
                <div class="pt-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center gap-1.5">
                            <span>Zona Residencial (Dimensão Epidemiológica)</span>
                            <x-context-tooltip title="Zona Residencial" content="Filtra a granularidade dos dados de saúde: Rural, Urbana ou Total Consolidado.">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </x-context-tooltip>
                        </label>
                        <span class="text-[10px] text-slate-400 font-sans">Selecione para filtrar os casos por área geográfica</span>
                    </div>
                    <div class="flex space-x-1.5 bg-slate-100 p-0.5 rounded-lg border border-slate-200 w-fit">
                        <button type="button" wire:click="$set('zona', 'total')" class="px-2.5 py-1 text-[10px] font-bold rounded-md transition duration-150 {{ $zona === 'total' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-550 hover:text-slate-700' }}">Consolidado (Total)</button>
                        <button type="button" wire:click="$set('zona', 'rural')" class="px-2.5 py-1 text-[10px] font-bold rounded-md transition duration-150 {{ $zona === 'rural' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-555 hover:text-slate-750' }}">Rural</button>
                        <button type="button" wire:click="$set('zona', 'urban')" class="px-2.5 py-1 text-[10px] font-bold rounded-md transition duration-150 {{ $zona === 'urban' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-555 hover:text-slate-750' }}">Urbana</button>
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
                                        <button type="button" wire:click="selectFirstCellOfRisk(4)" class="flex items-center space-x-1.5 p-1 rounded-lg border border-slate-100 bg-white hover:bg-slate-50 transition text-left focus:outline-none shadow-2xs">
                                            <span class="h-3 w-3 rounded bg-rose-600 border border-rose-700 block flex-shrink-0"></span>
                                            <span class="text-slate-650 font-semibold text-[10px]">Nível 4 (Crítico)</span>
                                        </button>
                                        <button type="button" wire:click="selectFirstCellOfRisk(3)" class="flex items-center space-x-1.5 p-1 rounded-lg border border-slate-100 bg-white hover:bg-slate-50 transition text-left focus:outline-none shadow-2xs">
                                            <span class="h-3 w-3 rounded bg-amber-500 border border-amber-600 block flex-shrink-0"></span>
                                            <span class="text-slate-650 font-semibold text-[10px]">Nível 3 (Alto)</span>
                                        </button>
                                        <button type="button" wire:click="selectFirstCellOfRisk(2)" class="flex items-center space-x-1.5 p-1 rounded-lg border border-slate-100 bg-white hover:bg-slate-50 transition text-left focus:outline-none shadow-2xs">
                                            <span class="h-3 w-3 rounded bg-yellow-400 border border-yellow-500 block flex-shrink-0"></span>
                                            <span class="text-slate-650 font-semibold text-[10px]">Nível 2 (Moderado)</span>
                                        </button>
                                        <button type="button" wire:click="selectFirstCellOfRisk(1)" class="flex items-center space-x-1.5 p-1 rounded-lg border border-slate-100 bg-white hover:bg-slate-50 transition text-left focus:outline-none shadow-2xs">
                                            <span class="h-3 w-3 rounded bg-slate-100 border border-slate-200 block flex-shrink-0"></span>
                                            <span class="text-slate-650 font-semibold text-[10px]">Nível 1 (Baseline)</span>
                                        </button>
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
                        
                        <!-- PAINEL DE ANÁLISE AVANÇADA - 6 GRÁFICOS CIENTÍFICOS -->
                        @php
                            $chartHistorical = $this->getHistoricalData($selectedCell['territory'], $selectedCell['row_indicator'])['series'];
                            $chartComparison = array_map(fn($t) => $this->getDiseaseCasesNumeric($t, $selectedCell['row_indicator']), $this->territories);
                            $chartRadar = $this->getRadarData($selectedCell['territory']);
                            $chartCorrelation = $this->getCorrelationData($selectedCell['territory'], $selectedCell['row_indicator'], $selectedInd1);
                            
                            $allDiseasesData = [];
                            foreach($this->dimensions['epidemiologica']['indicators'] as $d) {
                                $allDiseasesData[$d] = array_map(fn($t) => $this->getDiseaseCasesNumeric($t, $d), $this->territories);
                            }
                            
                            $riskDistribution = [0, 0, 0, 0];
                            foreach($this->dimensions['epidemiologica']['indicators'] as $d) {
                                foreach($this->territories as $t) {
                                    $rl = $this->getRiskLevel($t, $d);
                                    $riskDistribution[$rl - 1]++;
                                }
                            }
                            
                            $periodLabel = $data_inicio . ' a ' . $data_fim;
                        @endphp
                        <div x-data="dashboardCharts({
                                disease: '{{ $selectedCell['row_indicator'] }}',
                                territory: '{{ $selectedCell['territory'] }}',
                                historical: {{ json_encode($chartHistorical) }},
                                comparison: {{ json_encode($chartComparison) }},
                                radar: {{ json_encode($chartRadar) }},
                                correlation: {{ json_encode($chartCorrelation) }},
                                indicator: '{{ $selectedInd1 }}',
                                allDiseases: {{ json_encode($allDiseasesData) }},
                                riskDistribution: {{ json_encode($riskDistribution) }},
                                periodLabel: '{{ $periodLabel }}'
                             })"
                             x-on:selected-cell-updated.window="updateCharts($event.detail)"
                             wire:ignore
                             class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs mt-4 space-y-5"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5 mb-1">
                                <span class="text-[9px] font-mono text-slate-500 uppercase font-black tracking-widest block">Painel de Análise Avançada — Período Selecionado</span>
                                <span class="text-[9px] font-mono text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-100" x-text="currentPeriodLabel"></span>
                            </div>
                            
                            <!-- ROW 1: Progressão Temporal + Comparativo Territorial -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/60 relative">
                                    <h5 class="text-[10px] font-mono font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-rose-500 inline-block"></span>
                                        Progressão Temporal de Casos
                                    </h5>
                                    <p class="text-[9px] text-slate-500 mb-2 leading-snug">Evolução da incidência no período selecionado para o município e doença ativos.</p>
                                    <div class="h-48 relative w-full">
                                        <canvas id="dashboardLineChart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/60 relative">
                                    <h5 class="text-[10px] font-mono font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-blue-900 inline-block"></span>
                                        Incidência Territorial Comparada
                                    </h5>
                                    <p class="text-[9px] text-slate-500 mb-2 leading-snug">Comparação de casos entre os três municípios do Baixo Tocantins para a doença selecionada.</p>
                                    <div class="h-48 relative w-full">
                                        <canvas id="dashboardBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ROW 2: Radar Multidimensional + Dispersão Ecológica -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/60 relative">
                                    <h5 class="text-[10px] font-mono font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-emerald-600 inline-block"></span>
                                        Perfil de Risco Multidimensional
                                    </h5>
                                    <p class="text-[9px] text-slate-500 mb-2 leading-snug">Mapeamento de 5 eixos de vulnerabilidade: ambiental, social, sanitário, econômico e demográfico.</p>
                                    <div class="h-52 relative w-full">
                                        <canvas id="dashboardRadarChart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/60 relative">
                                    <h5 class="text-[10px] font-mono font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-violet-600 inline-block"></span>
                                        Dispersão Ecológica (Correlação)
                                    </h5>
                                    <p class="text-[9px] text-slate-500 mb-2 leading-snug">Relação entre o indicador ativo e os casos da doença vetorial por período.</p>
                                    <div class="h-52 relative w-full">
                                        <canvas id="dashboardScatterChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ROW 3: Multi-Doença + Distribuição de Risco -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/60 relative">
                                    <h5 class="text-[10px] font-mono font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-amber-500 inline-block"></span>
                                        Comparativo Multi-Doença por Território
                                    </h5>
                                    <p class="text-[9px] text-slate-500 mb-2 leading-snug">Panorama geral de todas as doenças vetoriais nos três municípios.</p>
                                    <div class="h-52 relative w-full">
                                        <canvas id="dashboardGroupedBarChart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50/80 p-3.5 rounded-lg border border-slate-200/60 relative">
                                    <h5 class="text-[10px] font-mono font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-slate-600 inline-block"></span>
                                        Distribuição de Níveis de Risco
                                    </h5>
                                    <p class="text-[9px] text-slate-500 mb-2 leading-snug">Proporção de classificações de risco (Crítico, Alto, Moderado, Baseline) na matriz ativa.</p>
                                    <div class="h-52 relative w-full flex items-center justify-center">
                                        <canvas id="dashboardDoughnutChart"></canvas>
                                    </div>
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
            </div> <!-- End of TAB: MATRIZ DE RISCO -->

            <!-- TAB: GRAFO DE RELAÇÕES -->
            <div x-show="activeTab === 'grafo'" style="display: none;" class="space-y-6" x-data="relationGraph">
                <div class="bg-white p-4 border border-slate-200 rounded-2xl space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2">
                        <span class="text-[9px] font-mono bg-blue-50 border border-blue-200 text-blue-900 px-2 py-0.5 rounded uppercase font-bold">FILTROS DO GRAFO</span>
                        <span class="text-[10px] text-slate-500 font-sans">Ajuste os parâmetros para recalcular o grafo de influência</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Filtro Lag -->
                        <div class="flex flex-col">
                            <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold">Lag Temporal (Atraso)</label>
                            <select x-model="lag" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 outline-none font-sans">
                                <option value="">Todos os Lags</option>
                                <option value="0">Lag 0 (Simultâneo)</option>
                                <option value="1">Lag 1 ano</option>
                                <option value="2">Lag 2 anos</option>
                                <option value="3">Lag 3 anos</option>
                            </select>
                        </div>
                        
                        <!-- Filtro P-Valor -->
                        <div class="flex flex-col">
                            <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold">P-Valor Máximo (Relevância)</label>
                            <select x-model="pMax" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 outline-none font-sans">
                                <option value="0.01">p < 0.01 (Extremo)</option>
                                <option value="0.05">p < 0.05 (Padrão Científico)</option>
                                <option value="0.10">p < 0.10 (Amplo)</option>
                            </select>
                        </div>
                        
                        <!-- Filtro Município -->
                        <div class="flex flex-col">
                            <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold">Território</label>
                            <select x-model="municipioId" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 outline-none font-sans">
                                <option value="">Região Baixo Tocantins (Todos)</option>
                                <option value="1">Baião</option>
                                <option value="2">Cametá</option>
                                <option value="3">Mocajuba</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- D3.js SVG Container and Info Sidenav -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch">
                    <!-- Graph Render Area -->
                    <div class="lg:col-span-2 bg-[#0f172a] rounded-2xl p-4 border border-slate-800 relative flex flex-col justify-between shadow-lg min-h-[500px]">
                        <div class="absolute top-4 left-4 z-10 bg-slate-900/80 border border-slate-800 px-3 py-1.5 rounded-xl text-white">
                            <h4 class="text-xs font-bold font-sans">Grafo de Influência (Graph Layer)</h4>
                            <span class="text-[9px] text-slate-400 font-mono font-bold block mt-1">Scroll para Zoom. Arraste os nós para organizar.</span>
                        </div>
                        <div class="flex items-center justify-center flex-grow w-full h-full" id="d3-graph-container">
                            <div x-show="isLoading" class="text-slate-400 font-mono text-xs animate-pulse">Carregando relações do banco de dados...</div>
                        </div>
                        
                        <!-- Legend of node colors -->
                        <div class="flex flex-wrap gap-3.5 bg-slate-950/80 border border-slate-900/50 p-3 rounded-xl">
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#10b981]"></span>
                                <span class="text-[9px] text-slate-350 font-mono font-bold">Ambiental</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#f59e0b]"></span>
                                <span class="text-[9px] text-slate-350 font-mono font-bold">Social</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#3b82f6]"></span>
                                <span class="text-[9px] text-slate-350 font-mono font-bold">Econômico</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#ef4444]"></span>
                                <span class="text-[9px] text-slate-350 font-mono font-bold">Epidemiológico</span>
                            </div>
                        </div>
                    </div>

                    <!-- Information Side Panel -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between shadow-xs">
                        <div class="space-y-4">
                            <span class="text-[9px] font-mono text-slate-500 uppercase font-black tracking-widest block border-b border-slate-100 pb-2">Detalhes da Relação Selecionada</span>
                            
                            <!-- If no selection -->
                            <div x-show="!selectedNode && !selectedEdge" class="text-center py-12 text-slate-400 font-sans text-xs">
                                <svg class="w-10 h-10 opacity-30 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                </svg>
                                Clique em um nó ou passe o mouse em uma seta para inspecionar os pesos matemáticos.
                            </div>

                            <!-- If node selected -->
                            <div x-show="selectedNode" style="display: none;" class="space-y-3">
                                <div>
                                    <span class="text-[8px] font-mono text-slate-400 block uppercase">Nó Selecionado (Variável)</span>
                                    <h4 class="text-sm font-bold text-slate-900" x-text="selectedNode ? selectedNode.label : ''"></h4>
                                </div>
                                <div>
                                    <span class="text-[8px] font-mono text-slate-400 block uppercase">Eixo Temático</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase inline-block" 
                                          :style="`background-color: ${selectedNode ? selectedNode.cor : ''}`"
                                          x-text="selectedNode ? selectedNode.eixo_nome : ''"></span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200/60 text-[11px] text-slate-650 leading-relaxed">
                                    Esta variável representa um indicador do Projeto Trajetórias que interage dinamicamente com as outras dimensões através de conexões causais.
                                </div>
                            </div>

                            <!-- If edge selected -->
                            <div x-show="selectedEdge" style="display: none;" class="space-y-3">
                                <div>
                                    <span class="text-[8px] font-mono text-slate-400 block uppercase">Aresta Selecionada (Conexão)</span>
                                    <div class="flex items-center space-x-1.5 flex-wrap">
                                        <span class="font-bold text-slate-800 text-xs" x-text="selectedEdge ? selectedEdge.source.label : ''"></span>
                                        <span class="text-slate-400 font-bold">&rarr;</span>
                                        <span class="font-bold text-slate-800 text-xs" x-text="selectedEdge ? selectedEdge.target.label : ''"></span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100 text-center">
                                        <span class="text-[8px] font-mono text-slate-400 uppercase block">Correlação Pearson</span>
                                        <strong class="text-sm font-mono" :class="selectedEdge && selectedEdge.pearson > 0 ? 'text-emerald-600' : 'text-rose-600'" 
                                                x-text="selectedEdge ? selectedEdge.pearson.toFixed(3) : ''"></strong>
                                    </div>
                                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100 text-center">
                                        <span class="text-[8px] font-mono text-slate-400 uppercase block">Defasagem (Lag)</span>
                                        <strong class="text-sm font-mono text-blue-900" x-text="selectedEdge ? selectedEdge.lag + ' ano(s)' : ''"></strong>
                                    </div>
                                </div>
                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100 text-center">
                                    <span class="text-[8px] font-mono text-slate-400 uppercase block">P-Valor (Significância)</span>
                                    <strong class="text-[10px] font-mono text-slate-800" x-text="selectedEdge ? selectedEdge.p_valor : ''"></strong>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200/60 text-[11px] text-slate-655 leading-relaxed">
                                    <span class="font-bold text-slate-800 block mb-1">Dedução Matemática:</span>
                                    Um aumento em <strong x-text="selectedEdge ? selectedEdge.source.label : ''"></strong> se traduz em uma alteração <span x-text="selectedEdge && selectedEdge.pearson > 0 ? 'positiva' : 'negativa'"></span> de <strong x-text="selectedEdge ? selectedEdge.target.label : ''"></strong> no território de forma defasada.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3.5 border-t border-slate-100 text-[10px] text-slate-500 leading-relaxed font-sans">
                            <strong>Nota Metodológica:</strong> O grafo de correlações permite identificar vias causais diretas e indiretas de influência socioecológica.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PIPELINE DE ML -->
            <div x-show="activeTab === 'ml'" style="display: none;" class="space-y-6">
                <div class="bg-white p-5 border border-slate-200 rounded-2xl space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2 border-b border-slate-100 pb-3">
                        <span class="text-[9px] font-mono bg-violet-50 border border-violet-200 text-violet-900 px-2 py-0.5 rounded uppercase font-bold">INTEGRAÇÃO PIPELINE DE IA</span>
                        <span class="text-[10px] text-slate-500 font-sans">Interface de preparação e exportação de dados para a Rede Neural</span>
                    </div>
                    
                    <p class="text-xs text-slate-650 leading-relaxed font-sans">
                        Nosso sistema consolida as medições do <strong>Cubo Star Schema</strong> e a estrutura relacional do <strong>Graph Layer</strong> em uma tabela unificada de vetores normalizados (<code class="bg-slate-100 text-violet-700 px-1 rounded">FeatureML</code>). Cada linha representa o estado socioecológico e epidemiológico completo de uma cidade em um ano específico.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60 space-y-3">
                            <h5 class="text-xs font-bold text-slate-800 font-sans">Baixar Dataset de Treinamento</h5>
                            <p class="text-[11px] text-slate-500 leading-normal">
                                Obtenha o conjunto completo de vetores formatado para treinamento (normalização Min-Max inclusa).
                            </p>
                            <div class="flex space-x-2 pt-1.5">
                                <a href="/api/v1/features-ml/export" target="_blank" class="px-3.5 py-1.5 bg-slate-900 text-white font-mono text-[10px] font-bold rounded-lg hover:bg-black transition shadow-sm">
                                    Exportar JSON
                                </a>
                                <a href="/api/v1/features-ml/export?formato=pytorch" target="_blank" class="px-3.5 py-1.5 bg-violet-650 text-white font-mono text-[10px] font-bold rounded-lg hover:bg-violet-700 transition shadow-sm">
                                    Formato PyTorch Tensor
                                </a>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60 space-y-3">
                            <h5 class="text-xs font-bold text-slate-800 font-sans">Comando de Sincronização Artisan</h5>
                            <p class="text-[11px] text-slate-500 leading-normal">
                                Rode este comando no terminal para reprocessar os quartis de risco e as features no banco de dados.
                            </p>
                            <div class="bg-slate-900 text-slate-200 p-2.5 rounded-lg font-mono text-[10px] border border-slate-800 select-all">
                                php artisan gerar:features-ml --export-csv
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Python/PyTorch Code Integration Widget -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="bg-slate-900 p-3.5 flex items-center justify-between border-b border-slate-800">
                        <span class="text-[9px] font-mono text-slate-350 uppercase font-black tracking-widest block">Script Python Integrado (PyTorch GNN/MLP)</span>
                        <span class="text-[9px] font-mono text-emerald-400 font-bold bg-emerald-950 px-2 py-0.5 rounded border border-emerald-900">PRONTO PARA USO</span>
                    </div>
                    <div class="p-5 bg-slate-950 text-slate-300 font-mono text-[11px] overflow-x-auto leading-relaxed border-t border-slate-900">
                        <pre class="text-slate-200"><code class="language-python">import torch
import torch.nn as nn
import requests
import pandas as pd

# 1. Carrega os dados direto da API do nosso sistema
url = "http://localhost:8000/api/v1/features-ml/export?formato=pytorch"
response = requests.get(url).json()

feature_names = response['feature_names']
X = torch.tensor(response['X'], dtype=torch.float32)  # [n_samples, 17 features + graph embeddings]
y = torch.tensor(response['y'], dtype=torch.long)      # Labels de risco (1: Baixo, 4: Crítico)

# 2. Definição da Rede Neural de Classificação de Relações
class RedeClassificadoraTrajetorias(nn.Module):
    def __init__(self, input_dim, num_classes=4):
        super().__init__()
        self.network = nn.Sequential(
            nn.Linear(input_dim, 64),
            nn.ReLU(),
            nn.Dropout(0.2),
            nn.Linear(64, 32),
            nn.ReLU(),
            nn.Linear(32, num_classes)
        )
    def forward(self, x):
        return self.network(x)

# Inicializa o modelo
modelo = RedeClassificadoraTrajetorias(input_dim=X.shape[1])
saida_logits = modelo(X)
print("Formato da saída predita da Rede Neural:", saida_logits.shape)
# Saída esperada: [n_samples, 4 classes de risco]</code></pre>
                    </div>
                </div>
            </div>
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

<!-- CHART.JS INTEGRATION RUNTIME FOR MAIN DASHBOARD -->
<script>
    if (window.Alpine) {
        registerAlpineCharts();
    } else {
        document.addEventListener('alpine:init', registerAlpineCharts);
    }

    function registerAlpineCharts() {
        if (window.Alpine.components && window.Alpine.components['dashboardCharts']) return;
        
        Alpine.data('dashboardCharts', (initialData) => ({
            lineChart: null,
            barChart: null,
            radarChart: null,
            scatterChart: null,
            groupedBarChart: null,
            doughnutChart: null,
            currentPeriodLabel: initialData.periodLabel,
            
            init() {
                this.$nextTick(() => {
                    this.initCharts(initialData);
                });
            },
            
            initCharts(data) {
                const lineCtx = document.getElementById('dashboardLineChart');
                const barCtx = document.getElementById('dashboardBarChart');
                const radarCtx = document.getElementById('dashboardRadarChart');
                const scatterCtx = document.getElementById('dashboardScatterChart');
                const groupedCtx = document.getElementById('dashboardGroupedBarChart');
                const doughnutCtx = document.getElementById('dashboardDoughnutChart');
                
                // 1. Line Chart
                if (lineCtx) {
                    const lineLabels = data.historical.map(item => item.year);
                    const lineValues = data.historical.map(item => item.cases);
                    
                    this.lineChart = new Chart(lineCtx, {
                        type: 'line',
                        data: {
                            labels: lineLabels,
                            datasets: [{
                                label: `Casos de ${data.disease} em ${data.territory}`,
                                data: lineValues,
                                borderColor: 'rgb(225, 29, 72)',
                                backgroundColor: 'rgba(225, 29, 72, 0.08)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: 'rgb(225, 29, 72)',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: { boxWidth: 12, font: { family: 'Inter', size: 9, weight: 'bold' } }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                                    ticks: { font: { family: 'JetBrains Mono', size: 9 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { family: 'JetBrains Mono', size: 9 } }
                                }
                            }
                        }
                    });
                }
                
                // 2. Bar Chart
                if (barCtx) {
                    const territories = ['Baião', 'Cametá', 'Mocajuba'];
                    this.barChart = new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: territories,
                            datasets: [{
                                label: `Casos Locais (${data.disease})`,
                                data: data.comparison,
                                backgroundColor: territories.map(t => t === data.territory ? 'rgba(30, 58, 138, 0.85)' : 'rgba(148, 163, 184, 0.5)'),
                                borderColor: territories.map(t => t === data.territory ? 'rgb(30, 58, 138)' : 'rgb(148, 163, 184)'),
                                borderWidth: 1.5,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: { boxWidth: 12, font: { family: 'Inter', size: 9, weight: 'bold' } }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                                    ticks: { font: { family: 'JetBrains Mono', size: 9 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { family: 'JetBrains Mono', size: 9 } }
                                }
                            }
                        }
                    });
                }

                // 3. Radar Chart
                if (radarCtx) {
                    this.radarChart = new Chart(radarCtx, {
                        type: 'radar',
                        data: {
                            labels: ['Ambiental', 'Vulnerabilidade Social', 'Risco Sanitário', 'Pressão Econômica', 'População Total'],
                            datasets: [{
                                label: `Perfil de Risco (${data.territory})`,
                                data: data.radar,
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                borderColor: 'rgb(16, 185, 129)',
                                pointBackgroundColor: 'rgb(16, 185, 129)',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                r: {
                                    angleLines: { display: true },
                                    suggestedMin: 0,
                                    suggestedMax: 100,
                                    ticks: { backdropColor: 'transparent', font: { size: 8 } }
                                }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                }

                // 4. Scatter Chart
                if (scatterCtx) {
                    this.scatterChart = new Chart(scatterCtx, {
                        type: 'scatter',
                        data: {
                            datasets: [{
                                label: `${data.indicator} vs Casos de ${data.disease}`,
                                data: data.correlation,
                                backgroundColor: 'rgb(139, 92, 246)',
                                borderColor: 'rgb(139, 92, 246)',
                                pointRadius: 6,
                                pointHoverRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    title: { display: true, text: data.indicator, font: { size: 9, weight: 'bold' } },
                                    ticks: { font: { size: 8 } }
                                },
                                y: {
                                    title: { display: true, text: 'Casos', font: { size: 9, weight: 'bold' } },
                                    ticks: { font: { size: 8 } }
                                }
                            }
                        }
                    });
                }

                // 5. Grouped Bar Chart
                if (groupedCtx) {
                    const diseases = Object.keys(data.allDiseases);
                    const colors = [
                        'rgba(225, 29, 72, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(100, 116, 139, 0.8)'
                    ];
                    const datasets = diseases.map((disease, idx) => ({
                        label: disease,
                        data: data.allDiseases[disease],
                        backgroundColor: colors[idx % colors.length],
                        borderRadius: 2
                    }));

                    this.groupedBarChart = new Chart(groupedCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Baião', 'Cametá', 'Mocajuba'],
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { font: { size: 8 } } },
                                x: { ticks: { font: { size: 8 } } }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 8, font: { size: 8 } }
                                }
                            }
                        }
                    });
                }

                // 6. Doughnut Chart
                if (doughnutCtx) {
                    this.doughnutChart = new Chart(doughnutCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Baseline (N1)', 'Moderado (N2)', 'Alto (N3)', 'Crítico (N4)'],
                            datasets: [{
                                data: data.riskDistribution,
                                backgroundColor: ['#e2e8f0', '#facc15', '#f59e0b', '#e11d48'],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: { boxWidth: 10, font: { size: 9, weight: 'bold' } }
                                }
                            },
                            cutout: '60%'
                        }
                    });
                }
            },
            
            updateCharts(detail) {
                this.currentPeriodLabel = detail.periodLabel || this.currentPeriodLabel;

                if (!this.lineChart || !this.barChart) {
                    this.initCharts({
                        disease: detail.disease,
                        territory: detail.territory,
                        historical: detail.historical,
                        comparison: detail.comparison,
                        radar: detail.radar,
                        correlation: detail.correlation,
                        indicator: detail.indicator,
                        allDiseases: detail.allDiseases || initialData.allDiseases,
                        riskDistribution: detail.riskDistribution || initialData.riskDistribution
                    });
                    return;
                }
                
                // Update Line Chart
                const lineLabels = detail.historical.map(item => item.year);
                const lineValues = detail.historical.map(item => item.cases);
                this.lineChart.data.labels = lineLabels;
                this.lineChart.data.datasets[0].label = `Casos de ${detail.disease} em ${detail.territory}`;
                this.lineChart.data.datasets[0].data = lineValues;
                this.lineChart.update();
                
                // Update Bar Chart
                const territories = ['Baião', 'Cametá', 'Mocajuba'];
                this.barChart.data.datasets[0].label = `Casos Locais (${detail.disease})`;
                this.barChart.data.datasets[0].data = detail.comparison;
                this.barChart.data.datasets[0].backgroundColor = territories.map(t => t === detail.territory ? 'rgba(30, 58, 138, 0.85)' : 'rgba(148, 163, 184, 0.5)');
                this.barChart.data.datasets[0].borderColor = territories.map(t => t === detail.territory ? 'rgb(30, 58, 138)' : 'rgb(148, 163, 184)');
                this.barChart.update();

                // Update Radar Chart
                if (this.radarChart) {
                    this.radarChart.data.datasets[0].label = `Perfil de Risco (${detail.territory})`;
                    this.radarChart.data.datasets[0].data = detail.radar;
                    this.radarChart.update();
                }

                // Update Scatter Chart
                if (this.scatterChart) {
                    this.scatterChart.data.datasets[0].label = `${detail.indicator} vs Casos de ${detail.disease}`;
                    this.scatterChart.data.datasets[0].data = detail.correlation;
                    this.scatterChart.options.scales.x.title.text = detail.indicator;
                    this.scatterChart.update();
                }

                // Update Doughnut Risk distribution if supplied
                if (this.doughnutChart && detail.riskDistribution) {
                    this.doughnutChart.data.datasets[0].data = detail.riskDistribution;
                    this.doughnutChart.update();
                }
            }
        }));

        // ── D3.js RELATION GRAPH COMPONENT ──
        if (window.Alpine.components && window.Alpine.components['relationGraph']) return;
        
        Alpine.data('relationGraph', () => ({
            lag: '',
            pMax: '0.05',
            municipioId: '',
            isLoading: false,
            selectedNode: null,
            selectedEdge: null,
            simulation: null,
            
            init() {
                this.fetchGraph();
                this.$watch('lag', () => this.fetchGraph());
                this.$watch('pMax', () => this.fetchGraph());
                this.$watch('municipioId', () => this.fetchGraph());
            },
            
            fetchGraph() {
                this.isLoading = true;
                let url = `/api/v1/grafo?p_max=${this.pMax}`;
                if (this.lag !== '') url += `&lag=${this.lag}`;
                if (this.municipioId !== '') url += `&municipio_id=${this.municipioId}`;
                
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.renderD3Graph(data);
                        this.isLoading = false;
                    })
                    .catch(err => {
                        console.error('Falha ao carregar grafo:', err);
                        this.isLoading = false;
                    });
            },
            
            renderD3Graph(data) {
                const container = document.getElementById('d3-graph-container');
                if (!container) return;
                container.innerHTML = '';
                
                const width = container.clientWidth || 500;
                const height = 440;
                
                const svg = d3.select('#d3-graph-container')
                    .append('svg')
                    .attr('width', '100%')
                    .attr('height', height)
                    .attr('viewBox', [0, 0, width, height])
                    .attr('style', 'max-width: 100%; height: auto; background-color: #0f172a; border-radius: 1rem;');
                    
                const g = svg.append('g');
                
                // Zoom & Pan
                svg.call(d3.zoom().scaleExtent([0.5, 5]).on('zoom', (event) => {
                    g.attr('transform', event.transform);
                }));
                
                const nodes = data.nos.map(d => ({...d}));
                const links = data.arestas.map(d => ({
                    source: d.source,
                    target: d.target,
                    pearson: d.pearson,
                    lag: d.lag,
                    p_valor: d.p_valor,
                    peso: d.weight
                }));
                
                this.simulation = d3.forceSimulation(nodes)
                    .force('link', d3.forceLink(links).id(d => d.id).distance(130))
                    .force('charge', d3.forceManyBody().strength(-200))
                    .force('center', d3.forceCenter(width / 2, height / 2))
                    .force('collision', d3.forceCollide().radius(35));
                    
                // Markers for arrows
                svg.append('defs').selectAll('marker')
                    .data(['arrow'])
                    .enter().append('marker')
                    .attr('id', d => d)
                    .attr('viewBox', '0 -5 10 10')
                    .attr('refX', 28) // Offset to sit outside the node circle
                    .attr('refY', 0)
                    .attr('markerWidth', 6)
                    .attr('markerHeight', 6)
                    .attr('orient', 'auto')
                    .append('path')
                    .attr('fill', '#475569')
                    .attr('d', 'M0,-5L10,0L0,5');
                    
                // Link lines
                const link = g.append('g')
                    .selectAll('line')
                    .data(links)
                    .join('line')
                    .attr('stroke', d => d.pearson > 0 ? '#10b981' : '#ef4444')
                    .attr('stroke-opacity', 0.6)
                    .attr('stroke-width', d => Math.max(1.5, Math.abs(d.pearson) * 5))
                    .attr('marker-end', 'url(#arrow)')
                    .style('cursor', 'pointer')
                    .on('mouseover', (event, d) => {
                        this.selectedEdge = d;
                        this.selectedNode = null;
                    });
                    
                // Node groups
                const node = g.append('g')
                    .selectAll('g')
                    .data(nodes)
                    .join('g')
                    .call(d3.drag()
                        .on('start', (e, d) => {
                            if (!e.active) this.simulation.alphaTarget(0.3).restart();
                            d.fx = d.x;
                            d.fy = d.y;
                        })
                        .on('drag', (e, d) => {
                            d.fx = e.x;
                            d.fy = e.y;
                        })
                        .on('end', (e, d) => {
                            if (!e.active) this.simulation.alphaTarget(0);
                            d.fx = null;
                            d.fy = null;
                        }))
                    .style('cursor', 'grab')
                    .on('click', (event, d) => {
                        this.selectedNode = d;
                        this.selectedEdge = null;
                    });
                    
                node.append('circle')
                    .attr('r', 12)
                    .attr('fill', d => d.cor)
                    .attr('stroke', '#1e293b')
                    .attr('stroke-width', 2);
                    
                node.append('text')
                    .attr('x', 16)
                    .attr('y', 4)
                    .text(d => d.label)
                    .attr('font-size', '8px')
                    .attr('font-family', 'Inter, sans-serif')
                    .attr('fill', '#cbd5e1')
                    .style('text-shadow', '1px 1px 2px #000')
                    .attr('pointer-events', 'none');
                    
                this.simulation.on('tick', () => {
                    link
                        .attr('x1', d => d.source.x)
                        .attr('y1', d => d.source.y)
                        .attr('x2', d => d.target.x)
                        .attr('y2', d => d.target.y);
                        
                    node
                        .attr('transform', d => `translate(${d.x},${d.y})`);
                });
            }
        }));
    }
</script>
