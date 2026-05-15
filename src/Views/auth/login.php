<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — KAZ-SIGN System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-12">

<div class="w-full max-w-sm space-y-8">

    <!-- Logo -->
    <div class="text-center space-y-2">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-slate-950 font-bold text-lg">KZ</span>
        <h1 class="text-xl font-semibold tracking-widest text-slate-100">KAZ&#8209;SIGN</h1>
        <p class="text-xs text-slate-500">Sign in to your account</p>
    </div>

    <!-- Flash -->
    <?php if (!empty($flash)): ?>
        <div class="rounded-lg border px-4 py-3 text-sm
            <?= $flash['type'] === 'success'
                ? 'border-emerald-700 bg-emerald-900/30 text-emerald-300'
                : 'border-red-800 bg-red-950/50 text-red-300' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="<?= $base ?>/login" method="POST"
          class="space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-8">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

        <!-- Username -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Username</label>
            <input name="username" type="text" required autocomplete="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                          text-sm text-slate-100 focus:border-emerald-500 focus:outline-none" />
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
            <input name="password" type="password" required autocomplete="current-password"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                          text-sm text-slate-100 focus:border-emerald-500 focus:outline-none" />
        </div>

        <!-- Private Key -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    Private Key
                </label>
                <span class="text-[10px] text-slate-600">Issuer only — optional for others</span>
            </div>
            <textarea name="private_key" rows="4" autocomplete="off"
                      placeholder="KAZSIGN-PRV-v1::paste your key here..."
                      class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                             text-[11px] text-emerald-300 placeholder-slate-700 leading-relaxed
                             resize-none focus:border-emerald-500 focus:outline-none"></textarea>

            <!-- Key help box -->
            <div class="rounded-lg border border-slate-700 bg-slate-800/40 px-4 py-3 space-y-1.5">
                <p class="text-[10px] font-semibold text-slate-400">Where is my private key?</p>
                <ul class="text-[10px] text-slate-500 space-y-1 list-none">
                    <li>→ Shown <strong class="text-slate-400">once</strong> after you registered</li>
                    <li>→ Starts with <code class="text-emerald-500">KAZSIGN-PRV-v1::</code></li>
                    <li>→ You should have saved it in a text file</li>
                </ul>
                <div class="border-t border-slate-700 pt-2 space-y-1">
                    <p class="text-[10px] font-semibold text-slate-400">Don't have it?</p>
                    <p class="text-[10px] text-slate-500">
                        <strong class="text-yellow-400">Issuer</strong> — you need it to sign credentials.
                        Leave blank to login in verify-only mode (cannot sign).
                    </p>
                    <p class="text-[10px] text-slate-500">
                        <strong class="text-emerald-400">Holder</strong> /
                        <strong class="text-purple-400">Verifier</strong> — leave blank, no key needed.
                    </p>
                </div>
            </div>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold uppercase
                       tracking-widest text-slate-950 hover:bg-emerald-400 active:scale-95 transition-all">
            Sign In
        </button>

        <p class="text-center text-xs text-slate-500">
            No account?
            <a href="<?= $base ?>/register" class="text-emerald-400 hover:underline">Register here</a>
        </p>
    </form>

    <!-- Role reminder -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-4 space-y-3">
        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Who needs the private key?</p>
        <div class="space-y-2 text-[10px]">
            <div class="flex items-start gap-3">
                <span class="text-blue-300 shrink-0">Issuer</span>
                <span class="text-slate-500">Must paste private key to sign credentials</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-emerald-300 shrink-0">Holder</span>
                <span class="text-slate-500">Leave blank — just use username + password</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-purple-300 shrink-0">Verifier</span>
                <span class="text-slate-500">Leave blank — just use username + password</span>
            </div>
        </div>
    </div>

</div>

</body>
</html>