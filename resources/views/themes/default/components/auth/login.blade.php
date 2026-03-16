<div class="w-full max-w-md">
    <!-- Logo/Brand -->
    <div class="text-center mb-10">
        <div
            class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-tr from-yellow-500 to-amber-600 rounded-2xl shadow-2xl shadow-yellow-500/20 mb-6 group transition-transform hover:scale-105 duration-300">
            <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Добро пожаловать</h1>
        <p class="text-gray-400 mt-2">Войдите в свой аккаунт для продолжения</p>
    </div>

    <!-- Login Form Card -->
    <div class="bg-[#181b21] p-8 rounded-3xl border border-[#2a2e39] shadow-2xl">
        <form wire:submit="login" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">Email адрес</label>
                <div class="relative">
                    <input wire:model="email" type="email" id="email"
                        class="w-full bg-[#13161b] border border-[#2a2e39] rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-yellow-500/50 focus:ring-4 focus:ring-yellow-500/10 transition-all duration-300"
                        placeholder="name@example.com" required>
                    @error('email')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-sm font-semibold text-gray-300">Пароль</label>
                </div>
                <div class="relative">
                    <input wire:model="password" type="password" id="password"
                        class="w-full bg-[#13161b] border border-[#2a2e39] rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-yellow-500/50 focus:ring-4 focus:ring-yellow-500/10 transition-all duration-300"
                        placeholder="••••••••" required>
                    @error('password')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input wire:model="remember" type="checkbox"
                        class="w-4 h-4 rounded border-[#2a2e39] bg-[#13161b] text-yellow-500 focus:ring-yellow-500/20">
                    <span class="text-sm text-gray-400 group-hover:text-gray-300 transition">Запомнить меня</span>
                </label>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-yellow-500 to-amber-600 hover:from-yellow-400 hover:to-amber-500 text-black font-bold py-4 rounded-xl transition duration-300 transform active:scale-[0.98] shadow-lg shadow-yellow-500/10 flex items-center justify-center gap-2">
                <span wire:loading.remove>Войти в панель</span>
                <span wire:loading
                    class="w-5 h-5 border-2 border-black/30 border-t-black rounded-full animate-spin"></span>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-[#2a2e39] text-center">
            <p class="text-sm text-gray-500">
                Забыли пароль? Обратитесь к администратору.
            </p>
        </div>
    </div>

    <!-- Footer links -->
    <p class="text-center text-gray-500 text-sm mt-8">
        © 2026 analytics.stashevski.by
        . Все права защищены.
    </p>
</div>
