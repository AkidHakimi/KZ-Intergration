<nav class="border-b border-slate-800 bg-slate-900">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between">

            <!-- Brand -->
            <a href="<?= $base ?>/" class="flex items-center gap-2.5 hover:opacity-80 transition-opacity">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs">KZ</span>
                <span class="text-sm font-semibold tracking-widest text-slate-100">KAZ&#8209;SIGN</span>
            </a>

            <!-- Right side -->
            <div class="flex items-center gap-5">

                <a href="<?= $base ?>/"
                   class="text-xs text-slate-400 hover:text-slate-100 transition-colors">
                    Dashboard
                </a>

                <a href="<?= $base ?>/documents/upload"
                   class="text-xs text-slate-400 hover:text-slate-100 transition-colors">
                    Upload
                </a>

                <!-- Key status -->
                <?php if (!empty($_SESSION['private_key'])): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-800
                                 bg-emerald-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Signing ON
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700
                                 bg-slate-800 px-2.5 py-0.5 text-[10px] font-semibold text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>
                        Verify only
                    </span>
                <?php endif; ?>

                <!-- Divider + user -->
                <div class="flex items-center gap-3 border-l border-slate-800 pl-4">
                    <span class="text-xs text-slate-500">
                        <?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <a href="<?= $base ?>/logout"
                       class="text-xs text-red-400 hover:text-red-300 transition-colors">
                        Logout
                    </a>
                </div>

            </div>
        </div>
    </div>
</nav>
