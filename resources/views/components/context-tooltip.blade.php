<div x-data="{
    open: false,
    timer: null,
    showTooltip() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.open = true;
        }, 1500);
    },
    hideTooltip() {
        clearTimeout(this.timer);
        this.open = false;
    }
}"
class="relative inline-block"
@mouseenter="showTooltip()"
@mouseleave="hideTooltip()"
>
    <!-- Trigger Element (the icon or underlined text) -->
    <div class="cursor-help flex items-center">
        {{ $slot }}
    </div>

    <!-- Tooltip Frosted Glass Box -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
         class="absolute z-[9999] bottom-full left-1/2 -translate-x-1/2 mb-2 w-72 p-3.5 text-xs text-white rounded-xl bg-slate-950/85 backdrop-blur-md border border-white/10 shadow-2xl pointer-events-none"
         style="display: none;"
    >
        <!-- Indicator Arrow -->
        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-950/85"></div>

        <div class="font-mono text-[9px] text-blue-400 font-extrabold uppercase tracking-widest mb-1.5 flex items-center justify-between">
            <span>{{ $title ?? 'Especificação Técnica' }}</span>
            <span class="text-[8px] bg-blue-950 text-blue-300 px-1 py-0.2 rounded border border-blue-900 font-mono">1.5s delay</span>
        </div>
        
        <p class="font-sans leading-relaxed text-slate-200 text-[11px]">
            {{ $content }}
        </p>
    </div>
</div>
