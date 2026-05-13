<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — KAZ-SIGN System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex items-center justify-center px-4">

<div class="w-full max-w-sm space-y-8">

    <!-- Logo -->
    <div class="text-center space-y-2">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-slate-950 font-bold text-lg">KZ</span>
        <h1 class="text-xl font-semibold tracking-widest text-slate-100">KAZ&#8209;SIGN</h1>
        <p class="text-xs text-slate-500">Create your account &amp; generate your key pair</p>
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

    <!-- Form -->
    <form action="<?= $base ?>/register" method="POST"
          class="space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-8">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

        <div class="space-y-1.5">
            <label for="username" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Username</label>
            <input id="username" name="username" type="text" required autocomplete="username"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                          placeholder-slate-600 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
        </div>

        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</label>
            <input id="email" name="email" type="email" required autocomplete="email"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                          placeholder-slate-600 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
        </div>

        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" minlength="8"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                          placeholder-slate-600 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
            <p class="text-[10px] text-slate-600">Minimum 8 characters</p>
        </div>

        <!-- Key-pair notice -->
        <div class="rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-xs text-slate-500 space-y-1">
            <p class="text-slate-400 font-semibold">Key pair generated on registration</p>
            <p>Your <span class="text-emerald-400">private key</span> lives only in your browser session and is never stored. Your <span class="text-emerald-400">public key</span> is saved to the database for verification.</p>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold uppercase tracking-widest
                       text-slate-950 hover:bg-emerald-400 active:scale-95 transition-all duration-150">
            Create Account
        </button>

        <p class="text-center text-xs text-slate-500">
            Already have an account?
            <a href="<?= $base ?>/login" class="text-emerald-400 hover:underline">Sign in</a>
        </p>
    </form>
</div>

</body>
</html>
