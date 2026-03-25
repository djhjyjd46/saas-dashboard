@component($layout, ['header' => 'Технические настройки'])
    <div class="w-full h-full mx-auto bg-[#434141] rounded-2xl p-10 overflow-auto">
        <div class="header mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Технические настройки</h1>
            <p class="text-white/60 text-sm">Управление группировкой рекламных кампаний для дашборда.</p>
        </div>

        @if (session()->has('message'))
            <div class="bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-lg mb-8 text-sm">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-[whi] rounded-xl p-6 border border-white/5">
            <form action="{{ route('technical.settings.save') }}" method="POST">
                @csrf

                <div class="mb-6">
                    <div class="flex gap-4 mb-2 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        <div class="w-1/4 pl-2">Название категории</div>
                        <div class="w-2/4 pl-2">ID кампаний или метки (_glavnaya_vuz)</div>
                        <div class="w-1/4"></div>
                    </div>

                    <div id="categories-container" class="space-y-3">
                        @forelse ($ui_mapping as $categoryName => $campaignsStr)
                            <div class="category-row flex gap-4 items-start">
                                <input type="text" name="category_names[]" value="{{ $categoryName }}"
                                    class="w-1/4 bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-sm text-white focus:border-gold/50 focus:outline-none placeholder:text-white/20"
                                    placeholder="Название..." required>

                                <input type="text" name="category_campaigns[]" value="{{ $campaignsStr }}"
                                    class="w-2/4 bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-sm text-white focus:border-gold/50 focus:outline-none placeholder:text-white/20"
                                    placeholder="707572867, _glavnaya_vuz..." required>

                                <button type="button" onclick="this.closest('.category-row').remove()"
                                    class="text-red-400 hover:text-red-300 hover:bg-red-400/10 px-3 py-2 rounded-lg text-sm transition-colors">
                                    Удалить
                                </button>

                                <div class="flex flex-col gap-1">
                                    <button type="button" onclick="moveCategoryRow(this, -1)"
                                        class="text-white/60 hover:text-white hover:bg-white/10 px-2 py-1 rounded text-xs transition-colors"
                                        title="Поднять выше">▲</button>
                                    <button type="button" onclick="moveCategoryRow(this, 1)"
                                        class="text-white/60 hover:text-white hover:bg-white/10 px-2 py-1 rounded text-xs transition-colors"
                                        title="Опустить ниже">▼</button>
                                </div>
                            </div>
                        @empty
                            <div class="category-row flex gap-4 items-start">
                                <input type="text" name="category_names[]"
                                    class="w-1/4 bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-sm text-white focus:border-gold/50 focus:outline-none placeholder:text-white/20"
                                    placeholder="Название..." required>

                                <input type="text" name="category_campaigns[]"
                                    class="w-2/4 bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-sm text-white focus:border-gold/50 focus:outline-none placeholder:text-white/20"
                                    placeholder="707572867, _glavnaya_vuz..." required>

                                <button type="button" onclick="this.closest('.category-row').remove()"
                                    class="text-red-400 hover:text-red-300 hover:bg-red-400/10 px-3 py-2 rounded-lg text-sm transition-colors">
                                    Удалить
                                </button>

                                <div class="flex flex-col gap-1">
                                    <button type="button" onclick="moveCategoryRow(this, -1)"
                                        class="text-white/60 hover:text-white hover:bg-white/10 px-2 py-1 rounded text-xs transition-colors"
                                        title="Поднять выше">▲</button>
                                    <button type="button" onclick="moveCategoryRow(this, 1)"
                                        class="text-white/60 hover:text-white hover:bg-white/10 px-2 py-1 rounded text-xs transition-colors"
                                        title="Опустить ниже">▼</button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center gap-6 mt-8">
                    <button type="button" onclick="addCategoryRow()"
                        class="text-blue-400 hover:text-blue-300 text-sm font-medium flex items-center gap-1 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Добавить строку
                    </button>

                    <button type="submit"
                        class="ml-auto bg-green-600 hover:bg-green-500 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                        Сохранить настройки
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addCategoryRow() {
            const container = document.getElementById('categories-container');
            const rowHTML = `
            <div class="category-row flex gap-4 items-start">
                <input type="text" name="category_names[]"
                    class="w-1/4 bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-sm text-white focus:border-gold/50 focus:outline-none placeholder:text-white/20"
                    placeholder="Название..." required>

                <input type="text" name="category_campaigns[]"
                    class="w-2/4 bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-sm text-white focus:border-gold/50 focus:outline-none placeholder:text-white/20"
                    placeholder="ID1, _glavnaya_vuz..." required>

                <button type="button" onclick="this.closest('.category-row').remove()"
                    class="text-red-400 hover:text-red-300 hover:bg-red-400/10 px-3 py-2 rounded-lg text-sm transition-colors">
                    Удалить
                </button>

                <div class="flex flex-col gap-1">
                    <button type="button" onclick="moveCategoryRow(this, -1)"
                        class="text-white/60 hover:text-white hover:bg-white/10 px-2 py-1 rounded text-xs transition-colors"
                        title="Поднять выше">▲</button>
                    <button type="button" onclick="moveCategoryRow(this, 1)"
                        class="text-white/60 hover:text-white hover:bg-white/10 px-2 py-1 rounded text-xs transition-colors"
                        title="Опустить ниже">▼</button>
                </div>
            </div>
        `;
            container.insertAdjacentHTML('beforeend', rowHTML);
        }

        function moveCategoryRow(button, direction) {
            const row = button.closest('.category-row');
            if (!row) return;

            const sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling) return;

            if (direction < 0) {
                row.parentNode.insertBefore(row, sibling);
            } else {
                row.parentNode.insertBefore(sibling, row);
            }
        }
    </script>
@endcomponent
