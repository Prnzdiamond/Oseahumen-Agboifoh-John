@php
    $statePath = $field->getStatePath();
    $id        = $field->getId();
    $label     = $field->getLabel();
@endphp

<x-dynamic-component :component="$field->getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            search: '',
            results: [],
            loading: false,
            open: false,
            selected: @entangle($statePath),

            init() {
                // Populate search box with current value on edit
                if (this.selected) {
                    this.search = this.selected
                }
            },

            async doSearch() {
                const term = this.search.trim()
                if (term.length < 2) {
                    this.results = []
                    this.open = false
                    return
                }
                this.loading = true
                try {
                    const res = await fetch(`/api/icons?search=${encodeURIComponent(term)}`)
                    const data = await res.json()
                    this.results = data.data || []
                    this.open = this.results.length > 0
                } catch (e) {
                    this.results = []
                } finally {
                    this.loading = false
                }
            },

            pick(name) {
                this.selected = name
                this.search = name
                this.open = false
                this.results = []
            },

            clear() {
                this.selected = null
                this.search = ''
                this.results = []
                this.open = false
            }
        }"
        class="relative"
        @click.outside="open = false"
        @keydown.escape="open = false"
    >

        {{-- ── Input row ──────────────────────────────────────────────── --}}
        <div class="flex items-center gap-2">

            <div class="relative flex-1">
                <input
                    type="text"
                    x-model="search"
                    @input.debounce.350ms="doSearch()"
                    @focus="if (search.length >= 2 && results.length === 0) doSearch()"
                    placeholder="Search icons… e.g. game, music, mail, code"
                    autocomplete="off"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600
                           bg-white dark:bg-gray-700
                           px-3 py-2 text-sm text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:border-primary-500 focus:ring-1 focus:ring-primary-500
                           transition"
                />
                {{-- Loading spinner --}}
                <div x-show="loading" class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2">
                    <svg class="h-4 w-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            </div>

            {{-- Selected badge ──────────────────────────────────────── --}}
            <div
                x-show="selected"
                x-cloak
                class="flex shrink-0 items-center gap-1.5 rounded-lg border border-primary-300 dark:border-primary-700
                       bg-primary-50 dark:bg-primary-900/20
                       px-3 py-2 text-xs font-mono text-primary-700 dark:text-primary-300"
            >
                <span x-text="selected" class="max-w-[120px] truncate"></span>
                <button
                    type="button"
                    @click="clear()"
                    class="ml-0.5 text-primary-400 hover:text-primary-600 dark:hover:text-primary-200 transition"
                    title="Clear"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Results grid ────────────────────────────────────────────── --}}
        <div
            x-show="open && results.length > 0"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute left-0 right-0 top-full z-50 mt-1.5
                   max-h-72 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-600
                   bg-white dark:bg-gray-800 p-3 shadow-xl"
        >
            <div class="grid grid-cols-6 gap-1.5 sm:grid-cols-8">
                <template x-for="icon in results" :key="icon.name">
                    <button
                        type="button"
                        @click="pick(icon.name)"
                        :title="icon.name"
                        :class="selected === icon.name
                            ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-primary-600 dark:hover:text-primary-400'"
                        class="flex flex-col items-center gap-1 rounded-lg p-2 transition cursor-pointer"
                    >
                        {{-- SVG preview if available, otherwise styled name pill --}}
                        <div class="flex h-7 w-7 items-center justify-center [&_svg]:h-5 [&_svg]:w-5 [&_svg]:stroke-current"
                             x-html="icon.svg
                                ? icon.svg
                                : `<span class='text-[9px] font-mono leading-tight text-center break-all opacity-70'>${icon.name.split('-').slice(0,2).join('-')}</span>`">
                        </div>
                        <span
                            class="w-full truncate text-center text-[10px] leading-tight opacity-60"
                            x-text="icon.name.length > 9 ? icon.name.slice(0,9) + '…' : icon.name"
                        ></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- ── Empty state ─────────────────────────────────────────────── --}}
        <p
            x-show="!loading && open && results.length === 0 && search.length >= 2"
            x-cloak
            class="mt-1 text-xs text-gray-400 dark:text-gray-500"
        >
            No icons found for "<span x-text="search"></span>" — try a different keyword.
        </p>

        {{-- Hidden real input for Livewire sync (belt-and-suspenders) --}}
        <input type="hidden" :value="selected" />
    </div>
</x-dynamic-component>
