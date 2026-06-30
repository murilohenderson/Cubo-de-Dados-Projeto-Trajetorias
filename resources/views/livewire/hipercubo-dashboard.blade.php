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
                Rotacione o cubo tridimensional e analise as diferentes faces. Clique na face de interesse para exibir a tabela de variáveis cruzadas. No Modo Comparativo, você pode analisar e rotacionar dois cubos independentes.
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
                    </d                    <!-- 3D Scene Viewport -->
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

                        <!-- D-Pad no canto inferior direito -->
                        <div class="absolute bottom-2 right-2 z-10 flex flex-col items-center gap-0.5">
                            <button type="button"
                                    @click="rotateX = Math.max(-75, rotateX - 45)"
                                    @keydown.enter.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                    @keydown.space.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                    aria-label="Girar cubo principal para cima"
                                    tabindex="0"
                                    class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                            >▲</button>
                            <div class="flex items-center gap-0.5">
                                <button type="button"
                                        @click="rotateY = rotateY - 45"
                                        @keydown.enter.prevent="rotateY = rotateY - 45"
                                        @keydown.space.prevent="rotateY = rotateY - 45"
                                        aria-label="Girar cubo principal para a esquerda"
                                        tabindex="0"
                                        class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                                >◀</button>
                                <span class="text-[7px] font-bold text-slate-400 font-mono w-4 text-center select-none">3D</span>
                                <button type="button"
                                        @click="rotateY = rotateY + 45"
                                        @keydown.enter.prevent="rotateY = rotateY + 45"
                                        @keydown.space.prevent="rotateY = rotateY + 45"
                                        aria-label="Girar cubo principal para a direita"
                                        tabindex="0"
                                        class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                                >▶</button>
                            </div>
                            <button type="button"
                                    @click="rotateX = Math.min(75, rotateX + 45)"
                                    @keydown.enter.prevent="rotateX = Math.min(75, rotateX + 45)"
                                    @keydown.space.prevent="rotateX = Math.min(75, rotateX + 45)"
                                    aria-label="Girar cubo principal para baixo"
                                    tabindex="0"
                                    class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                            >▼</button>
                        </div>
                    </div>

                    <!-- Preset buttons (Cubo A) mais para baixo -->
                    <div class="flex flex-wrap justify-center gap-1 mt-6">
                        <button type="button" @click="rotateTo(-20, 35)"  class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-900">Frontal</button>
                        <button type="button" @click="rotateTo(-20, 215)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-900">Post</button>
                        <button type="button" @click="rotateTo(-90, 0)"   class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-900">Sup</button>
                        <button type="button" @click="rotateTo(90, 0)"    class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-900">Inf</button>
                    </div>       </div>
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
                                   <!-- 3D Scene Viewport -->
                    <div class="cube-container h-56 w-full relative overflow-hidden select-none cursor-grab active:cursor-grabbing">
                        <!-- D-Pad no canto superior direito -->
                        <div class="absolute top-2 right-2 z-10 flex flex-col items-center gap-0.5">
                            <button type="button"
                                    @click="rotateX = Math.max(-75, rotateX - 45)"
                                    @keydown.enter.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                    @keydown.space.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                    aria-label="Girar cubo comparador para cima"
                                    tabindex="0"
                                    class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                            >▲</button>
                            <div class="flex items-center gap-0.5">
                                <button type="button"
                                        @click="rotateY = rotateY - 45"
                                        @keydown.enter.prevent="rotateY = rotateY - 45"
                                        @keydown.space.prevent="rotateY = rotateY - 45"
                                        aria-label="Girar cubo comparador para a esquerda"
                                        tabindex="0"
                                        class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                                >◀</button>
                                <span class="text-[7px] font-bold text-slate-400 font-mono w-4 text-center select-none">3D</span>
                                <button type="button"
                                        @click="rotateY = rotateY + 45"
                                        @keydown.enter.prevent="rotateY = rotateY + 45"
                                        @keydown.space.prevent="rotateY = rotateY + 45"
                                        aria-label="Girar cubo comparador para a direita"
                                        tabindex="0"
                                        class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                                >▶</button>
                            </div>
                            <button type="button"
                                    @click="rotateX = Math.min(75, rotateX + 45)"
                                    @keydown.enter.prevent="rotateX = Math.min(75, rotateX + 45)"
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

                        <!-- D-Pad no canto inferior direito -->
                        <div class="absolute bottom-2 right-2 z-10 flex flex-col items-center gap-0.5">
                            <button type="button"
                                    @click="rotateX = Math.max(-75, rotateX - 45)"
                                    @keydown.enter.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                    @keydown.space.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                    aria-label="Girar cubo comparador para cima"
                                    tabindex="0"
                                    class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                            >▲</button>
                            <div class="flex items-center gap-0.5">
                                <button type="button"
                                        @click="rotateY = rotateY - 45"
                                        @keydown.enter.prevent="rotateY = rotateY - 45"
                                        @keydown.space.prevent="rotateY = rotateY - 45"
                                        aria-label="Girar cubo comparador para a esquerda"
                                        tabindex="0"
                                        class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                                >◀</button>
                                <span class="text-[7px] font-bold text-slate-400 font-mono w-4 text-center select-none">3D</span>
                                <button type="button"
                                        @click="rotateY = rotateY + 45"
                                        @keydown.enter.prevent="rotateY = rotateY + 45"
                                        @keydown.space.prevent="rotateY = rotateY + 45"
                                        aria-label="Girar cubo comparador para a direita"
                                        tabindex="0"
                                        class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                                >▶</button>
                            </div>
                            <button type="button"
                                    @click="rotateX = Math.min(75, rotateX + 45)"
                                    @keydown.enter.prevent="rotateX = Math.min(75, rotateX + 45)"
                                    @keydown.space.prevent="rotateX = Math.min(75, rotateX + 45)"
                                    aria-label="Girar cubo comparador para baixo"
                                    tabindex="0"
                                    class="h-5 w-5 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-650 active:bg-slate-800 text-white text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-indigo-700 shadow-sm"
                            >▼</button>
                        </div>
                    </div>

                    <!-- Preset buttons (Cubo B) mais para baixo -->
                    <div class="flex flex-wrap justify-center gap-1 mt-6">
                        <button type="button" @click="rotateTo(-20, 35)"  class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-indigo-700">Frontal</button>
                        <button type="button" @click="rotateTo(-20, 215)" class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-indigo-700">Post</button>
                        <button type="button" @click="rotateTo(-90, 0)"   class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-indigo-700">Sup</button>
                        <button type="button" @click="rotateTo(90, 0)"    class="px-2 py-0.5 bg-white border border-slate-300 text-[8px] font-mono text-slate-700 rounded shadow-xs font-bold focus:outline-none focus:ring-1 focus:ring-indigo-700">Inf</button>
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

                    <!-- D-Pad no canto inferior direito -->
                    <div class="absolute bottom-4 right-4 z-20 flex flex-col items-center gap-1">
                        <button type="button"
                                @click="rotateX = Math.max(-75, rotateX - 45)"
                                @keydown.enter.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                @keydown.space.prevent="rotateX = Math.max(-75, rotateX - 45)"
                                aria-label="Girar cubo para cima"
                                tabindex="0"
                                class="h-6 w-6 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-600 active:bg-slate-800 text-white text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                        >▲</button>
                        <div class="flex items-center gap-1">
                            <button type="button"
                                    @click="rotateY = rotateY - 45"
                                    @keydown.enter.prevent="rotateY = rotateY - 45"
                                    @keydown.space.prevent="rotateY = rotateY - 45"
                                    aria-label="Girar cubo para a esquerda"
                                    tabindex="0"
                                    class="h-6 w-6 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-600 active:bg-slate-800 text-white text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                            >◀</button>
                            <span class="text-[8px] font-bold text-slate-400 font-mono w-6 text-center select-none font-sans">3D</span>
                            <button type="button"
                                    @click="rotateY = rotateY + 45"
                                    @keydown.enter.prevent="rotateY = rotateY + 45"
                                    @keydown.space.prevent="rotateY = rotateY + 45"
                                    aria-label="Girar cubo para a direita"
                                    tabindex="0"
                                    class="h-6 w-6 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-600 active:bg-slate-800 text-white text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                            >▶</button>
                        </div>
                        <button type="button"
                                @click="rotateX = Math.min(75, rotateX + 45)"
                                @keydown.enter.prevent="rotateX = Math.min(75, rotateX + 45)"
                                @keydown.space.prevent="rotateX = Math.min(75, rotateX + 45)"
                                aria-label="Girar cubo para baixo"
                                tabindex="0"
                                class="h-6 w-6 flex items-center justify-center rounded-full bg-slate-700 hover:bg-slate-600 active:bg-slate-800 text-white text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-900 shadow-sm"
                        >▼</button>
                    </div>
                </div>

                <!-- Preset buttons (Single Cube) mais para baixo -->
                <div class="mt-8 flex justify-center space-x-1.5 w-full">
                    <button type="button" @click="rotateTo(-20, 35)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold focus:outline-none focus:ring-2 focus:ring-blue-900">
                        Frontal
                    </button>
                    <button type="button" @click="rotateTo(-20, 215)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold focus:outline-none focus:ring-2 focus:ring-blue-900">
                        Posterior
                    </button>
                    <button type="button" @click="rotateTo(-90, 0)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold focus:outline-none focus:ring-2 focus:ring-blue-900">
                        Superior
                    </button>
                    <button type="button" @click="rotateTo(90, 0)" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-[10px] font-mono text-slate-700 rounded-md transition duration-200 shadow-sm font-bold focus:outline-none focus:ring-2 focus:ring-blue-900">
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

    <!-- RIGHT COLUMN: Variables Panel -->
    <div class="w-full flex flex-col justify-between p-6 overflow-y-auto h-full bg-slate-50 border-t border-slate-200 lg:border-t-0"
         :style="isDesktop ? 'width: ' + (100 - leftWidth) + '%' : ''">
        
        <div class="space-y-6">
            <!-- Header & Action Triggers (CSV Export & PNG capturing) -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 mt-0.5">Painel de Variáveis Cruzadas</h3>
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
                        class="px-4 py-2 text-xs font-bold transition duration-150 border-b-2 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1 rounded-t"
                        :class="activeTab === 'matriz' ? 'border-blue-900 text-blue-900' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        aria-controls="tab-panel-matriz"
                        :aria-selected="activeTab === 'matriz'"
                        role="tab"
                >
                    Variáveis Cruzadas
                </button>
                <!-- Grafo tab — disabled until correlation branch is merged -->
                <button type="button" 
                        disabled
                        title="Disponível na branch de correlações — em desenvolvimento"
                        aria-disabled="true"
                        aria-label="Grafo de Relações — indisponível nesta branch"
                        class="px-4 py-2 text-xs font-bold border-b-2 border-transparent text-slate-300 opacity-40 cursor-not-allowed pointer-events-none"
                        role="tab"
                >
                    Grafo de Relações (D3.js)
                </button>
                <!-- ML tab — disabled until correlation branch is merged -->
                <button type="button" 
                        disabled
                        title="Disponível na branch de correlações — em desenvolvimento"
                        aria-disabled="true"
                        aria-label="Pipeline de ML — indisponível nesta branch"
                        class="px-4 py-2 text-xs font-bold border-b-2 border-transparent text-slate-300 opacity-40 cursor-not-allowed pointer-events-none"
                        role="tab"
                >
                    Pipeline de ML (Rede Neural)
                </button>
            </div>

            <!-- TAB: VARIÁVEIS CRUZADAS -->
            <div x-show="activeTab === 'matriz'" id="tab-panel-matriz" role="tabpanel" class="space-y-6">

                <!-- Advanced Date Period Filters -->
                <div class="bg-white p-4 border border-slate-200 rounded-2xl space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2">
                        <span class="text-[9px] font-mono bg-blue-50 border border-blue-200 text-blue-900 px-2 py-0.5 rounded uppercase font-bold">FILTRO TEMPORAL DO CUBO</span>
                        <span class="text-[10px] text-slate-500 font-sans">Defina o período de análise espacial</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center justify-between">
                                <span>Data Início</span>
                                <x-context-tooltip title="Período de Início" content="Define o limite de tempo inferior para os dados exibidos na tabela de variáveis cruzadas.">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </x-context-tooltip>
                            </label>
                            <input type="date" wire:model.live="data_inicio" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 outline-none font-sans" />
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[9px] font-mono tracking-wider text-slate-500 uppercase mb-1.5 font-bold flex items-center justify-between">
                                <span>Data Fim</span>
                                <x-context-tooltip title="Período Fim" content="Define o limite de tempo superior para os dados exibidos na tabela e nos gráficos de relação.">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </x-context-tooltip>
                            </label>
                            <input type="date" wire:model.live="data_fim" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-lg p-2.5 focus:ring-2 focus:ring-blue-900 focus:border-blue-900 outline-none font-sans" />
                        </div>
                    </div>
                </div>

                <!-- Cross-Variable Table -->
                <div class="border border-slate-200 rounded-2xl bg-white overflow-hidden shadow-sm">
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center gap-2">
                        <span class="text-[9px] font-mono bg-blue-50 border border-blue-200 text-blue-900 px-2 py-0.5 rounded uppercase font-bold">TABELA DE VARIÁVEIS CRUZADAS</span>
                        <span class="text-[10px] text-slate-500 font-sans">
                            Face ativa: <strong class="text-slate-700">{{ $activeMapping['label'] }}</strong>
                        </span>
                    </div>
                    <div class="overflow-x-auto w-full">
                        <table class="w-full border-collapse"
                               id="cross-variables-table"
                               role="table"
                               aria-label="Variáveis cruzadas por município — {{ $activeMapping['label'] }}">
                            <thead>
                                <tr class="bg-slate-100">
                                    <th scope="col" class="px-4 py-3.5 text-left text-[10px] font-mono font-bold tracking-wider text-slate-600 border-b border-slate-200 uppercase min-w-[200px]">Variável</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-[10px] font-mono font-bold tracking-wider text-slate-600 border-b border-slate-200 uppercase min-w-[130px]">Dimensão</th>
                                    <th scope="col" class="px-3 py-3.5 text-center text-[10px] font-mono font-bold tracking-wider text-slate-600 border-b border-slate-200 uppercase min-w-[80px]">Unidade</th>
                                    <th scope="col" class="px-3 py-3.5 text-center text-[10px] font-mono font-bold tracking-wider text-slate-600 border-b border-slate-200 uppercase min-w-[110px]">Período</th>
                                    @foreach($territories as $territory)
                                        <th scope="col" class="px-3 py-3.5 text-center text-[10px] font-mono font-bold tracking-wider text-slate-600 border-b border-slate-200 uppercase min-w-[110px]">{{ $territory }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tableData as $index => $row)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} border-b border-slate-100 hover:bg-blue-50/30 transition duration-100">
                                        <td class="px-4 py-3 text-xs font-semibold text-slate-800 font-sans">{{ $row['variavel'] }}</td>
                                        <td class="px-3 py-3">
                                            <span class="text-[9px] font-mono text-blue-900 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded font-bold whitespace-nowrap">{{ $row['dimensao'] }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center text-[10px] font-mono text-slate-600">{{ $row['unidade'] ?: '—' }}</td>
                                        <td class="px-3 py-3 text-center text-[10px] font-mono text-slate-500 whitespace-nowrap">{{ $row['periodo'] }}</td>
                                        @foreach($territories as $territory)
                                            <td class="px-3 py-3 text-center text-xs font-mono font-bold text-slate-800">
                                                {{ $row['territories'][$territory]['display'] ?? '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 4 + count($territories) }}" class="px-4 py-12 text-center">
                                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                                <svg class="w-8 h-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="text-xs font-sans">Nenhum dado disponível para a face selecionada no período configurado. Ajuste os filtros acima.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Comparison and Temporal Chart Section -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4" x-data="{ subTab: 'relacao' }">
                    <div class="flex flex-wrap items-center justify-between border-b border-slate-100 pb-3 gap-2">
                        <div class="flex space-x-1.5 bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                            <button type="button" @click="subTab = 'relacao'" :class="subTab === 'relacao' ? 'bg-white text-slate-800 shadow-xs border border-slate-200 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="px-3 py-1 text-xs rounded-md transition duration-150 focus:outline-none">
                                Correlação entre Variáveis (Face)
                            </button>
                            <button type="button" @click="subTab = 'territorial'" :class="subTab === 'territorial' ? 'bg-white text-slate-800 shadow-xs border border-slate-200 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="px-3 py-1 text-xs rounded-md transition duration-150 focus:outline-none">
                                Evolução por Município (Série)
                            </button>
                        </div>
                    </div>
                    
                    <!-- SUB-TAB: CORRELAÇÃO ENTRE VARIÁVEIS (FACE) -->
                    <div x-show="subTab === 'relacao'" class="space-y-4">
                        <!-- Selection controls for correlation graph -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-slate-55 rounded-xl border border-slate-200/60">
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-mono text-slate-500 uppercase font-bold tracking-wider">Município</label>
                                <select wire:model.live="selectedTerritory" class="bg-white border border-slate-200 text-slate-800 text-xs rounded-lg p-2 focus:ring-1 focus:ring-blue-900 outline-none font-sans">
                                    @foreach($territories as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-mono text-slate-500 uppercase font-bold tracking-wider">Eixo X: {{ $dim1_label }}</label>
                                <select wire:model.live="selectedInd1" class="bg-white border border-slate-200 text-slate-800 text-xs rounded-lg p-2 focus:ring-1 focus:ring-blue-900 outline-none font-sans">
                                    @foreach($dim1_indicators as $ind)
                                        <option value="{{ $ind }}">{{ $ind }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-mono text-slate-500 uppercase font-bold tracking-wider">Eixo Y: {{ $dim2_label }}</label>
                                <select wire:model.live="selectedInd2" class="bg-white border border-slate-200 text-slate-800 text-xs rounded-lg p-2 focus:ring-1 focus:ring-blue-900 outline-none font-sans">
                                    @foreach($dim2_indicators as $ind)
                                        <option value="{{ $ind }}">{{ $ind }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- Graphs Side-by-Side -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6"
                             x-data="comparisonChartsComponent({{ json_encode($comparisonChartData) }})"
                             x-init="$nextTick(() => initCharts())"
                             wire:key="comparison-charts-{{ md5($selectedTerritory . $selectedInd1 . $selectedInd2 . $data_inicio . $data_fim) }}"
                        >
                            <!-- Graph 1: Dual Y-Axis Line Chart -->
                            <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-200/60 relative">
                                <div class="mb-2">
                                    <span class="text-[9px] font-mono text-slate-400 uppercase font-bold">Comportamento Temporal Comparado</span>
                                    <h4 class="text-xs font-bold text-slate-700 leading-tight">Linha do Tempo (Escalas Independentes)</h4>
                                </div>
                                <div class="h-64 relative w-full">
                                    <canvas id="dualAxisLineChart"></canvas>
                                </div>
                            </div>
                            
                            <!-- Graph 2: Scatter Plot -->
                            <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-200/60 relative">
                                <div class="mb-2">
                                    <span class="text-[9px] font-mono text-slate-400 uppercase font-bold">Distribuição de Dispersão</span>
                                    <h4 class="text-xs font-bold text-slate-700 leading-tight">Diagrama de Correlação (Ano a Ano)</h4>
                                </div>
                                <div class="h-64 relative w-full">
                                    <canvas id="correlationScatterChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SUB-TAB: EVOLUÇÃO POR MUNICÍPIO (SÉRIE) -->
                    <div x-show="subTab === 'territorial'" class="space-y-4" style="display: none;">
                        <!-- Seletor de Variável -->
                        <div class="flex flex-wrap items-center justify-between gap-4 bg-slate-55 p-3 rounded-xl border border-slate-200/60">
                            <div>
                                <span class="text-[9px] font-mono text-slate-400 uppercase font-bold">Variável sob Análise</span>
                                <p class="text-[10px] text-slate-500 mt-0.5 font-sans">Compare o comportamento desta variável nos três municípios simultaneamente.</p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <select wire:model.live="selectedChartVariable"
                                        class="bg-white border border-slate-200 text-slate-800 text-xs rounded-lg p-2 focus:ring-1 focus:ring-blue-900 outline-none font-sans min-w-[220px]"
                                >
                                    @foreach($allFaceVariables as $var)
                                        <option value="{{ $var }}">{{ $var }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- Standard temporal graph -->
                        <div
                            x-data="temporalChartComponent({{ json_encode($temporalData) }})"
                            x-init="$nextTick(() => initChart())"
                            wire:key="temporal-chart-{{ md5($selectedChartVariable . $activeFace . $data_inicio . $data_fim . $zona) }}"
                            class="relative"
                        >
                            <div class="h-72 relative w-full">
                                <canvas id="temporalLineChart"
                                        aria-label="Gráfico de evolução temporal de {{ $selectedChartVariable }} por município"
                                        role="img"></canvas>
                            </div>
                            <div x-show="!hasData"
                                 class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 text-xs font-sans gap-2 bg-slate-50/90 rounded-xl">
                                Sem dados disponíveis para a variável e período selecionados.
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End of TAB: VARIÁVEIS CRUZADAS -->

            {{--
            ─────────────────────────────────────────────────────────────────────────
            TAB: GRAFO DE RELAÇÕES (D3.js)
            ─────────────────────────────────────────────────────────────────────────
            NOTA: Aba desabilitada nesta branch. O código abaixo é mantido para
            reuso na branch de correlações. Não navegar até esta aba via UI.
            ─────────────────────────────────────────────────────────────────────────
            --}}
            <div x-show="activeTab === 'grafo'" style="display: none;" class="space-y-6" x-data="relationGraph">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800 text-xs font-sans">
                    <strong>Aba temporariamente desabilitada.</strong> O Grafo de Relações estará disponível na branch de correlações.
                </div>
            </div>

            {{--
            ─────────────────────────────────────────────────────────────────────────
            TAB: PIPELINE DE ML (Rede Neural)
            ─────────────────────────────────────────────────────────────────────────
            NOTA: Aba desabilitada nesta branch. O código abaixo é mantido para
            reuso na branch de correlações. Não navegar até esta aba via UI.
            ─────────────────────────────────────────────────────────────────────────
            --}}
            <div x-show="activeTab === 'ml'" style="display: none;" class="space-y-6">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800 text-xs font-sans">
                    <strong>Aba temporariamente desabilitada.</strong> O Pipeline de ML estará disponível na branch de correlações.
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
                <button @click="open = false" class="text-slate-400 hover:text-white transition focus:outline-none focus:ring-2 focus:ring-white rounded" aria-label="Fechar painel">
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
                    <p class="font-bold text-slate-700 mb-1">Série Histórica:</p>
                    <p>O comportamento histórico temporal do município de <strong>{{ $drillDownData['territory'] ?? '' }}</strong> exibe as tendências epidemiológicas reais agregadas sob as frentes de alteração ambiental de longo prazo.</p>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-3 flex justify-end border-t border-slate-100">
                <button @click="open = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-900">
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

<!-- TEMPORAL CHART ALPINE COMPONENT -->
<script>
    if (window.Alpine) {
        registerTemporalChart();
    } else {
        document.addEventListener('alpine:init', registerTemporalChart);
    }

    function registerTemporalChart() {
        // Guard: avoid double registration
        if (window._temporalChartRegistered) return;
        window._temporalChartRegistered = true;

        // ── Temporal Line Chart Component ──────────────────────────────────────
        Alpine.data('temporalChartComponent', (initialData) => ({
            chart: null,
            hasData: false,

            initChart() {
                this.$nextTick(() => {
                    const ctx = document.getElementById('temporalLineChart');
                    if (!ctx) return;

                    // Destroy any previous Chart.js instance on this canvas
                    const existing = Chart.getChart(ctx);
                    if (existing) existing.destroy();

                    const data = initialData || { labels: [], datasets: [], variavel: '', unidade: '' };
                    this.hasData = !!(data.labels && data.labels.length > 0);

                    if (!this.hasData) return;

                    // Municipality colours — WCAG AA compliant
                    const PALETTE = [
                        { border: '#1e3a8a', bg: 'rgba(30,58,138,0.10)' },   // Baião  — blue-900
                        { border: '#065f46', bg: 'rgba(6,95,70,0.10)' },      // Cametá — emerald-900
                        { border: '#78350f', bg: 'rgba(120,53,15,0.10)' }     // Mocajuba — amber-900
                    ];

                    const datasets = (data.datasets || []).map((ds, idx) => ({
                        label: ds.territory,
                        data: ds.data,
                        borderColor: PALETTE[idx % PALETTE.length].border,
                        backgroundColor: PALETTE[idx % PALETTE.length].bg,
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: PALETTE[idx % PALETTE.length].border,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        spanGaps: true
                    }));

                    const unitLabel = data.unidade ? ` (${data.unidade})` : '';

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        font: { family: 'Inter, sans-serif', size: 10, weight: 'bold' },
                                        padding: 12
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        title: (items) => 'Período: ' + items[0].label,
                                        label: (item) => {
                                            const val = item.parsed.y;
                                            if (val === null || val === undefined) return ` ${item.dataset.label}: Sem dados`;
                                            return ` ${item.dataset.label}: ${val.toLocaleString('pt-BR')}${unitLabel}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    grid: { color: 'rgba(0,0,0,0.05)' },
                                    title: {
                                        display: true,
                                        text: data.variavel + unitLabel,
                                        font: { family: 'Inter, sans-serif', size: 9, weight: 'bold' },
                                        color: '#64748b'
                                    },
                                    ticks: {
                                        font: { family: 'JetBrains Mono, monospace', size: 9 },
                                        callback: (v) => {
                                            if (Math.abs(v) >= 1000000) return (v / 1000000).toFixed(1) + 'M';
                                            if (Math.abs(v) >= 1000) return (v / 1000).toFixed(1) + 'k';
                                            return v;
                                        }
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        font: { family: 'JetBrains Mono, monospace', size: 9 },
                                        maxRotation: 45,
                                        autoSkip: true,
                                        maxTicksLimit: 12
                                    }
                                }
                            }
                        }
                    });
                });
            }
        }));

        // ── Comparison Charts Component ───────────────────────────────────────
        Alpine.data('comparisonChartsComponent', (initialData) => ({
            lineChart: null,
            scatterChart: null,
            hasData: false,

            initCharts() {
                this.$nextTick(() => {
                    const lineCtx = document.getElementById('dualAxisLineChart');
                    const scatterCtx = document.getElementById('correlationScatterChart');

                    if (!lineCtx || !scatterCtx) return;

                    // Clean up existing charts
                    const existingLine = Chart.getChart(lineCtx);
                    if (existingLine) existingLine.destroy();
                    const existingScatter = Chart.getChart(scatterCtx);
                    if (existingScatter) existingScatter.destroy();

                    const data = initialData || { labels: [], values1: [], values2: [], scatter: [], var1_name: '', var2_name: '', var1_unit: '', var2_unit: '', territory: '' };
                    this.hasData = !!(data.labels && data.labels.length > 0);

                    if (!this.hasData) return;

                    // 1. Dual Y-Axis Line Chart
                    this.lineChart = new Chart(lineCtx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: data.var1_name,
                                    data: data.values1,
                                    borderColor: '#1e3a8a', // Azul
                                    backgroundColor: 'rgba(30,58,138,0.05)',
                                    borderWidth: 2.5,
                                    yAxisID: 'y1',
                                    tension: 0.3,
                                    spanGaps: true
                                },
                                {
                                    label: data.var2_name,
                                    data: data.values2,
                                    borderColor: '#059669', // Verde
                                    backgroundColor: 'rgba(5,150,105,0.05)',
                                    borderWidth: 2.5,
                                    yAxisID: 'y2',
                                    tension: 0.3,
                                    spanGaps: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: true, position: 'top', labels: { boxWidth: 10, font: { size: 9 } } }
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 8 } } },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: { display: true, text: data.var1_name + (data.var1_unit ? ` (${data.var1_unit})` : ''), color: '#1e3a8a', font: { size: 8, weight: 'bold' } },
                                    ticks: { font: { size: 8 }, color: '#1e3a8a' },
                                    grid: { color: 'rgba(0,0,0,0.05)' }
                                },
                                y2: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: { display: true, text: data.var2_name + (data.var2_unit ? ` (${data.var2_unit})` : ''), color: '#059669', font: { size: 8, weight: 'bold' } },
                                    ticks: { font: { size: 8 }, color: '#059669' },
                                    grid: { drawOnChartArea: false }
                                }
                            }
                        }
                    });

                    // 2. Scatter Plot
                    this.scatterChart = new Chart(scatterCtx, {
                        type: 'scatter',
                        data: {
                            datasets: [{
                                label: `${data.var1_name} vs ${data.var2_name}`,
                                data: data.scatter.map(pt => ({ x: pt.x, y: pt.y })),
                                backgroundColor: '#7c3aed', // Roxo
                                borderColor: '#7c3aed',
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const idx = context.dataIndex;
                                            const pt = data.scatter[idx];
                                            const label = pt ? pt.label : '';
                                            return `Período: ${label} | X: ${context.parsed.x} | Y: ${context.parsed.y}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    title: { display: true, text: data.var1_name + (data.var1_unit ? ` (${data.var1_unit})` : ''), font: { size: 8, weight: 'bold' } },
                                    ticks: { font: { size: 8 } }
                                },
                                y: {
                                    title: { display: true, text: data.var2_name + (data.var2_unit ? ` (${data.var2_unit})` : ''), font: { size: 8, weight: 'bold' } },
                                    ticks: { font: { size: 8 } }
                                }
                            }
                        }
                    });
                });
            }
        }));

        // ── Grafo de Relações — stub (disabled in this branch) ─────────────────
        // Code preserved for reuse in the correlations branch.
        Alpine.data('relationGraph', () => ({
            lag: '',
            pMax: '0.05',
            municipioId: '',
            isLoading: false,
            selectedNode: null,
            selectedEdge: null,
            simulation: null,

            init() {
                // Disabled — correlation graph not available in this branch.
                // To re-enable: uncomment this.fetchGraph() and restore routes/api.php
                // this.fetchGraph();
            },

            fetchGraph() {
                // Placeholder — API routes disabled in this branch
                console.info('[relationGraph] Correlation API disabled. Re-enable in branch correlações.');
            },

            renderD3Graph() {
                // Placeholder
            }
        }));
    }
</script>
