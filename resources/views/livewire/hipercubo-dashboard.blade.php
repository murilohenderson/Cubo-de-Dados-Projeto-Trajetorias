<div class="flex-grow flex flex-col lg:flex-row items-stretch overflow-hidden w-full relative bg-slate-50 text-slate-900"
     x-data="{
        leftWidth: 40,
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
    <!-- Add CSS styles for preserve-3d, backface-hidden and technical grid patterns -->
    <style>
        .preserve-3d {
            transform-style: preserve-3d;
        }
        .backface-hidden {
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        .perspective-1000 {
            perspective: 1000px;
        }
        .technical-grid {
            background-color: #f8fafc;
            background-image: radial-gradient(circle, #cbd5e1 1.2px, transparent 1.2px);
            background-size: 16px 16px;
        }
        .technical-grid-active {
            background-color: #1e3a8a;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.15) 1.2px, transparent 1.2px);
            background-size: 16px 16px;
        }
    </style>

    <!-- LEFT COLUMN: 3D Data Cube (Resizable split-screen) -->
    <div class="w-full flex flex-col justify-between bg-slate-100 p-6 relative overflow-y-auto h-full flex-shrink-0 border-b border-slate-200 lg:border-b-0"
         :style="isDesktop ? 'width: ' + leftWidth + '%' : ''">
        
        <!-- Header Info -->
        <div>
            <div class="flex items-center space-x-2">
                <span class="h-2.5 w-2.5 rounded bg-blue-900"></span>
                <span class="text-[10px] font-mono tracking-widest text-blue-900 uppercase font-bold">PAINEL DE PROJEÇÃO CIENTÍFICA</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 mt-1">
                Hipercubo Tridimensional
            </h2>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                Clique e arraste com o mouse para rotacionar o cubo tridimensional e analisar as diferentes faces do par de forças. Use o scroll para ampliar. Clique na face de interesse para filtrar a matriz de correlação.
            </p>
        </div>

        <!-- Interactive 3D Cube Container (Sober Light Colors & Floor Shadow) -->
        <div class="my-6 py-4 flex flex-col items-center justify-center min-h-[460px] select-none relative"
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
            <!-- 3D Scene viewport -->
            <div class="perspective-1000 w-96 h-96 flex items-center justify-center cursor-grab active:cursor-grabbing relative"
                 @wheel.prevent="handleWheel($event)">
                
                <!-- Floor Shadow under the cube (Reacts to dragging and zoom) -->
                <div class="absolute bottom-6 w-40 h-2 bg-slate-300/60 rounded-full blur-md pointer-events-none transition-all duration-300 ease-out"
                     :style="`transform: scale(${(isDragging ? 0.8 : 1) * (1 + zoomZ / 350)})`"
                ></div>

                <!-- Cube Container (Static & technical - removed float animation) -->
                <div class="preserve-3d">
                    <!-- Cube Wrapper -->
                    <div class="relative w-64 h-64 preserve-3d transition-transform duration-100 ease-out"
                         style="will-change: transform;"
                         :style="`transform: translateZ(${zoomZ}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`">
                         
                        <!-- FACE 1: FRONT (Ambiental × Epidemiológica) -->
                        <div 
                            @click="handleFaceClick('front', $event)"
                            class="absolute inset-0 flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden"
                            :class="$wire.activeFace === 'front' 
                                ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                            style="transform: rotateY(0deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                      :class="$wire.activeFace === 'front' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                    FACE FRONTAL
                                </span>
                                <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === 'front' ? 'bg-white' : 'bg-blue-900'"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] tracking-wider font-mono uppercase font-bold" :class="$wire.activeFace === 'front' ? 'text-blue-200' : 'text-slate-500'">PAR DE FORÇAS 1</span>
                                <span class="text-base font-extrabold tracking-tight mt-1 font-sans leading-tight" :class="$wire.activeFace === 'front' ? 'text-white' : 'text-slate-800'">AMBIENTAL<br>× SAÚDE</span>
                            </div>
                            
                            <div class="w-full text-center text-[9px] font-mono pt-2 border-t" :class="$wire.activeFace === 'front' ? 'border-blue-800 text-blue-200' : 'border-slate-200 text-slate-500'">
                                INPE / FIOCRUZ
                            </div>
                        </div>

                        <!-- FACE 2: BACK (Econômica × Social) -->
                        <div 
                            @click="handleFaceClick('back', $event)"
                            class="absolute inset-0 flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden"
                            :class="$wire.activeFace === 'back' 
                                ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                            style="transform: rotateY(180deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                      :class="$wire.activeFace === 'back' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                    FACE POSTERIOR
                                </span>
                                <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === 'back' ? 'bg-white' : 'bg-blue-900'"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] tracking-wider font-mono uppercase font-bold" :class="$wire.activeFace === 'back' ? 'text-blue-200' : 'text-slate-500'">PAR DE FORÇAS 2</span>
                                <span class="text-base font-extrabold tracking-tight mt-1 font-sans leading-tight" :class="$wire.activeFace === 'back' ? 'text-white' : 'text-slate-800'">ECONOMIA<br>× SOCIAL</span>
                            </div>
                            
                            <div class="w-full text-center text-[9px] font-mono pt-2 border-t" :class="$wire.activeFace === 'back' ? 'border-blue-800 text-blue-200' : 'border-slate-200 text-slate-500'">
                                PRODUÇÃO / MUNICIPIOS
                            </div>
                        </div>

                        <!-- FACE 3: LEFT (Ambiental × Social) -->
                        <div 
                            @click="handleFaceClick('left', $event)"
                            class="absolute inset-0 flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden"
                            :class="$wire.activeFace === 'left' 
                                ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                            style="transform: rotateY(-90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                      :class="$wire.activeFace === 'left' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                    FACE ESQUERDA
                                </span>
                                <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === 'left' ? 'bg-white' : 'bg-blue-900'"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] tracking-wider font-mono uppercase font-bold" :class="$wire.activeFace === 'left' ? 'text-blue-200' : 'text-slate-500'">PAR DE FORÇAS 3</span>
                                <span class="text-base font-extrabold tracking-tight mt-1 font-sans leading-tight" :class="$wire.activeFace === 'left' ? 'text-white' : 'text-slate-800'">AMBIENTAL<br>× SOCIAL</span>
                            </div>
                            
                            <div class="w-full text-center text-[9px] font-mono pt-2 border-t" :class="$wire.activeFace === 'left' ? 'border-blue-800 text-blue-200' : 'border-slate-200 text-slate-500'">
                                INPE / SOCIODEMOGRAFIA
                            </div>
                        </div>

                        <!-- FACE 4: RIGHT (Econômica × Epidemiológica) -->
                        <div 
                            @click="handleFaceClick('right', $event)"
                            class="absolute inset-0 flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden"
                            :class="$wire.activeFace === 'right' 
                                ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                            style="transform: rotateY(90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                      :class="$wire.activeFace === 'right' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                    FACE DIREITA
                                </span>
                                <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === 'right' ? 'bg-white' : 'bg-blue-900'"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] tracking-wider font-mono uppercase font-bold" :class="$wire.activeFace === 'right' ? 'text-blue-200' : 'text-slate-500'">PAR DE FORÇAS 4</span>
                                <span class="text-base font-extrabold tracking-tight mt-1 font-sans leading-tight" :class="$wire.activeFace === 'right' ? 'text-white' : 'text-slate-800'">ECONOMIA<br>× SAÚDE</span>
                            </div>
                            
                            <div class="w-full text-center text-[9px] font-mono pt-2 border-t" :class="$wire.activeFace === 'right' ? 'border-blue-800 text-blue-200' : 'border-slate-200 text-slate-500'">
                                MERCADO / FIOCRUZ
                            </div>
                        </div>

                        <!-- FACE 5: TOP (Ambiental × Econômica) -->
                        <div 
                            @click="handleFaceClick('top', $event)"
                            class="absolute inset-0 flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden"
                            :class="$wire.activeFace === 'top' 
                                ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                            style="transform: rotateX(90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                      :class="$wire.activeFace === 'top' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                    FACE SUPERIOR
                                </span>
                                <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === 'top' ? 'bg-white' : 'bg-blue-900'"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] tracking-wider font-mono uppercase font-bold" :class="$wire.activeFace === 'top' ? 'text-blue-200' : 'text-slate-500'">PAR DE FORÇAS 5</span>
                                <span class="text-base font-extrabold tracking-tight mt-1 font-sans leading-tight" :class="$wire.activeFace === 'top' ? 'text-white' : 'text-slate-800'">AMBIENTAL<br>× ECONOMIA</span>
                            </div>
                            
                            <div class="w-full text-center text-[9px] font-mono pt-2 border-t" :class="$wire.activeFace === 'top' ? 'border-blue-800 text-blue-200' : 'border-slate-200 text-slate-500'">
                                INPE / PRODUÇÃO
                            </div>
                        </div>

                        <!-- FACE 6: BOTTOM (Social × Epidemiológica) -->
                        <div 
                            @click="handleFaceClick('bottom', $event)"
                            class="absolute inset-0 flex flex-col justify-between p-4 border rounded-xl cursor-pointer transition-all duration-300 backface-hidden"
                            :class="$wire.activeFace === 'bottom' 
                                ? 'border-blue-900 bg-blue-900 text-white scale-102 z-10 technical-grid-active shadow-md' 
                                : 'border-slate-300 bg-slate-100 text-slate-600 hover:border-slate-400 hover:bg-slate-200 technical-grid shadow-sm'"
                            style="transform: rotateX(-90deg) translateZ(128px); will-change: transform;"
                        >
                            <div class="w-full flex justify-between items-start">
                                <span class="text-[8px] font-mono tracking-wider px-1.5 py-0.5 rounded font-bold border"
                                      :class="$wire.activeFace === 'bottom' ? 'bg-blue-950 text-blue-200 border-blue-800' : 'bg-slate-200 text-slate-700 border-slate-300'">
                                    FACE INFERIOR
                                </span>
                                <div class="h-2 w-2 rounded-full" :class="$wire.activeFace === 'bottom' ? 'bg-white' : 'bg-blue-900'"></div>
                            </div>
                            
                            <div class="flex flex-col items-center text-center">
                                <span class="text-[10px] tracking-wider font-mono uppercase font-bold" :class="$wire.activeFace === 'bottom' ? 'text-blue-200' : 'text-slate-500'">PAR DE FORÇAS 6</span>
                                <span class="text-base font-extrabold tracking-tight mt-1 font-sans leading-tight" :class="$wire.activeFace === 'bottom' ? 'text-white' : 'text-slate-800'">SOCIAL<br>× SAÚDE</span>
                            </div>
                            
                            <div class="w-full text-center text-[9px] font-mono pt-2 border-t" :class="$wire.activeFace === 'bottom' ? 'border-blue-800 text-blue-200' : 'border-slate-200 text-slate-500'">
                                VULNERABILIDADE / FIOCRUZ
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Preset rotation controls -->
            <div class="mt-6 flex items-center justify-center space-x-2 relative z-10 w-full" data-clickable>
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

        <!-- Active Face Information Badge (Bottom Footer Left) -->
        <div class="mt-4 p-3 border border-slate-200 rounded-xl bg-white text-[11px] text-slate-600 shadow-sm">
            <span class="font-bold text-slate-800 block uppercase font-mono text-[9px] tracking-wider mb-0.5">Cruzamento Ativo:</span>
            <span class="font-extrabold text-blue-900">{{ $activeMapping['label'] }}</span>
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
            <!-- Header & Face Context -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-blue-900 uppercase font-bold">MATRIZ DE SÍNTESE GEOGRÁFICA</span>
                    <h3 class="text-lg font-bold text-slate-900 mt-0.5">Painel de Correlação Cruzada</h3>
                </div>
                <div class="bg-white px-3 py-1.5 rounded-lg border border-slate-200 text-[10px] font-mono text-slate-700 shadow-sm">
                    Projeção: <span class="text-blue-900 font-bold uppercase">{{ $activeMapping['label'] }}</span>
                </div>
            </div>

            <!-- Dynamic Selectors (Dropdowns) -->
            <div class="bg-white p-4 border border-slate-200 rounded-xl space-y-4 shadow-sm">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="text-[9px] font-mono bg-blue-50 border border-blue-200 text-blue-900 px-2 py-0.5 rounded uppercase font-bold">FILTROS DO HIPERCUBO</span>
                    <span class="text-[10px] text-slate-500 font-sans">Defina as variáveis ativas do par de forças</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Dropdown 1 -->
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold">
                            {{ $dim1_label }}
                        </label>
                        <select wire:model.live="selectedInd1" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg focus:ring-blue-900 focus:border-blue-900 block w-full p-2.5 outline-none transition duration-200 font-sans">
                            @foreach($dim1_indicators as $ind)
                                <option value="{{ $ind }}">{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dropdown 2 -->
                    <div class="flex flex-col">
                        <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold">
                            {{ $dim2_label }}
                        </label>
                        <select wire:model.live="selectedInd2" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg focus:ring-blue-900 focus:border-blue-900 block w-full p-2.5 outline-none transition duration-200 font-sans">
                            @foreach($dim2_indicators as $ind)
                                <option value="{{ $ind }}">{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Heatmap Matrix -->
            <div class="border border-slate-200 rounded-xl bg-white overflow-hidden shadow-sm">
                <div class="overflow-x-auto w-full">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="px-4 py-3 text-left text-[10px] font-mono font-bold tracking-wider text-slate-650 border-b border-slate-200 uppercase min-w-[220px]">
                                    Indicadores Linha (Demais Dimensões)
                                </th>
                                @foreach($territories as $territory)
                                    <th class="px-2 py-3 text-center text-[10px] font-mono font-bold tracking-wider text-slate-650 border-b border-slate-200 uppercase min-w-[120px]">
                                        {{ $territory }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heatmapRows as $row)
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition duration-150">
                                    <!-- Row Name & Dimension Badge -->
                                    <td class="px-4 py-3 text-left">
                                        <span class="text-[8px] font-mono px-1.5 py-0.5 rounded bg-slate-100 border border-slate-250 text-slate-600 font-bold uppercase inline-block">
                                            {{ $row['dimension'] }}
                                        </span>
                                        <div class="text-xs font-semibold text-slate-800 mt-1 font-sans">
                                            {{ $row['indicator'] }}
                                        </div>
                                    </td>

                                    <!-- Heatmap Cells -->
                                    @foreach($territories as $territory)
                                        @php
                                            // Deterministic calculation of risk level
                                            $hash = crc32($territory . $row['indicator'] . $selectedInd1 . $selectedInd2);
                                            $riskLevel = abs($hash % 4) + 1;
                                            
                                            // Design semantics mappings based on rigid institutional guidelines
                                            $bgClass = '';
                                            $borderClass = '';
                                            $textClass = '';
                                            $riskText = '';
                                            
                                            if ($riskLevel === 4) {
                                                // Risco Crítico = Vermelho-tijolo fosco (bg-red-800)
                                                $bgClass = 'bg-red-800 hover:bg-red-900';
                                                $borderClass = 'border-red-900';
                                                $textClass = 'text-white';
                                                $riskText = 'CRÍTICO';
                                            } elseif ($riskLevel === 3) {
                                                // Risco Alto = Laranja-queimado um pouco mais forte ou tom intermediário
                                                $bgClass = 'bg-amber-700 hover:bg-amber-800';
                                                $borderClass = 'border-amber-800';
                                                $textClass = 'text-white';
                                                $riskText = 'ALTO';
                                            } elseif ($riskLevel === 2) {
                                                // Risco Moderado = Laranja-queimado suave (bg-amber-600)
                                                $bgClass = 'bg-amber-600 hover:bg-amber-700';
                                                $borderClass = 'border-amber-650';
                                                $textClass = 'text-white';
                                                $riskText = 'MODERADO';
                                            } else {
                                                // Dados Insuficientes / Baseline = Cinza-claro discreto (bg-slate-200)
                                                $bgClass = 'bg-slate-200 hover:bg-slate-300';
                                                $borderClass = 'border-slate-300';
                                                $textClass = 'text-slate-800';
                                                $riskText = 'DADOS INS.';
                                            }
                                            
                                            // Check active state
                                            $isCellActive = $selectedCell && 
                                                           $selectedCell['territory'] === $territory && 
                                                           $selectedCell['row_indicator'] === $row['indicator'];
                                        @endphp
                                        <td class="p-1">
                                            <button type="button"
                                                    wire:click="selectCell('{{ $territory }}', '{{ $row['indicator'] }}')"
                                                    class="w-full h-12 flex flex-col justify-center items-center rounded-lg border transition duration-200 font-sans {{ $bgClass }} {{ $borderClass }} {{ $textClass }} {{ $isCellActive ? 'ring-2 ring-blue-900 ring-offset-2 scale-102 border-blue-900' : '' }}">
                                                <span class="text-[8px] font-mono tracking-wider font-extrabold uppercase">
                                                    {{ $riskText }}
                                                </span>
                                                <span class="text-[9px] font-mono mt-0.5 font-bold opacity-90">
                                                    @if($riskLevel === 1) N/A @else Nível {{ $riskLevel }} @endif
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
        <div class="mt-6 border border-slate-200 rounded-xl bg-white p-4 transition-all duration-300 shadow-sm">
            @if($selectedCell)
                @php
                    $alertColor = 'border-slate-250 bg-slate-50 text-slate-800';
                    if ($selectedCell['risk_level'] === 4) {
                        $alertColor = 'border-red-800 bg-red-50 text-red-950';
                    } elseif ($selectedCell['risk_level'] === 3) {
                        $alertColor = 'border-amber-700 bg-amber-50 text-amber-950';
                    } elseif ($selectedCell['risk_level'] === 2) {
                        $alertColor = 'border-amber-600 bg-amber-50/50 text-amber-950';
                    } else {
                        $alertColor = 'border-slate-300 bg-slate-100 text-slate-800';
                    }
                @endphp
                <div class="border rounded-xl p-4 {{ $alertColor }} transition-all duration-300">
                    <!-- Title & Time -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3 mb-3">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[8px] font-mono tracking-wider font-black uppercase border
                                @if($selectedCell['risk_level'] === 4) bg-red-800 border-red-900 text-white
                                @elseif($selectedCell['risk_level'] === 3) bg-amber-700 border-amber-800 text-white
                                @elseif($selectedCell['risk_level'] === 2) bg-amber-600 border-amber-650 text-white
                                @else bg-slate-250 border-slate-350 text-slate-700 @endif">
                                @if($selectedCell['risk_level'] === 4) CRÍTICO
                                @elseif($selectedCell['risk_level'] === 3) ALTO
                                @elseif($selectedCell['risk_level'] === 2) MODERADO
                                @else DADOS INS. @endif
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-800 uppercase tracking-tight">
                                SÍNTESE CIENTÍFICA — {{ $selectedCell['territory'] }}
                            </span>
                        </div>
                        <div class="text-[9px] font-mono text-slate-500">
                            Sincronizado: {{ $selectedCell['timestamp'] }}
                        </div>
                    </div>

                    <!-- Context info grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-3 text-[10px] font-mono text-slate-700">
                        <div class="bg-white p-2 rounded border border-slate-200">
                            <span class="text-slate-400 block text-[8px] uppercase font-bold">{{ $selectedCell['dim_1_label'] }}</span>
                            <span class="font-semibold text-slate-800">{{ $selectedCell['indicator_1'] }}</span>
                        </div>
                        <div class="bg-white p-2 rounded border border-slate-200">
                            <span class="text-slate-400 block text-[8px] uppercase font-bold">{{ $selectedCell['dim_2_label'] }}</span>
                            <span class="font-semibold text-slate-800">{{ $selectedCell['indicator_2'] }}</span>
                        </div>
                        <div class="bg-white p-2 rounded border border-slate-200">
                            <span class="text-slate-400 block text-[8px] uppercase font-bold">Variável Linha</span>
                            <span class="font-semibold text-slate-800">{{ $selectedCell['row_indicator'] }}</span>
                        </div>
                    </div>

                    <!-- Synthesis scientific narrative -->
                    <p class="text-xs text-slate-800 leading-relaxed font-sans mt-2">
                        {{ $selectedCell['evidence_text'] }}
                    </p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-6 text-center text-slate-400">
                    <svg class="w-7 h-7 opacity-60 mb-2 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    <span class="text-[10px] font-mono tracking-wider uppercase font-bold">Selecione uma célula na matriz de síntese para abrir o dossiê científico</span>
                </div>
            @endif
        </div>
    </div>
</div>
