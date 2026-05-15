<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — KAZ-SIGN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-12">

<div class="w-full max-w-md space-y-8">

    <!-- Logo -->
    <div class="text-center space-y-2">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-slate-950 font-bold text-lg">KZ</span>
        <h1 class="text-xl font-semibold tracking-widest">KAZ&#8209;SIGN</h1>
        <p class="text-xs text-slate-500">Create your account</p>
    </div>

    <!-- Flash -->
    <?php if (!empty($flash)): ?>
        <div class="rounded-lg border px-4 py-3 text-sm
            <?= $flash['type'] === 'success' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-300' : 'border-red-800 bg-red-950/50 text-red-300' ?>">
            <!-- NEW — renders safe HTML links in flash messages -->
            <?= strip_tags($flash['message'], '<a><strong><code>') ?>       
        </div>
    <?php endif; ?>

    <form action="<?= $base ?>/register" method="POST"
          class="space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-8">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

        <!-- Role selector -->
        <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">I am a</label>
            <div class="grid grid-cols-3 gap-2">
                <label class="cursor-pointer rounded-lg border border-slate-700 bg-slate-800 p-3 text-center
                              transition-all hover:border-emerald-600"
                       id="label-issuer">
                    <input type="radio" name="role" value="issuer" class="sr-only"
                           onchange="switchRole('issuer')" />
                    <span class="block text-lg mb-1">🏛</span>
                    <span class="block text-[10px] font-semibold text-slate-300">Issuer</span>
                </label>
                <label class="cursor-pointer rounded-lg border border-emerald-500 bg-emerald-900/20 p-3 text-center
                              transition-all hover:border-emerald-400"
                       id="label-holder">
                    <input type="radio" name="role" value="holder" class="sr-only"
                           onchange="switchRole('holder')" checked />
                    <span class="block text-lg mb-1">👤</span>
                    <span class="block text-[10px] font-semibold text-slate-300">Holder</span>
                </label>
                <label class="cursor-pointer rounded-lg border border-slate-700 bg-slate-800 p-3 text-center
                              transition-all hover:border-emerald-600"
                       id="label-verifier">
                    <input type="radio" name="role" value="verifier" class="sr-only"
                           onchange="switchRole('verifier')" />
                    <span class="block text-lg mb-1">🔍</span>
                    <span class="block text-[10px] font-semibold text-slate-300">Verifier</span>
                </label>
            </div>
        </div>

        <!-- Username -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Username</label>
            <input name="username" type="text" required autocomplete="username"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                          focus:border-emerald-500 focus:outline-none" />
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</label>
            <input name="email" type="email" required autocomplete="email"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                          focus:border-emerald-500 focus:outline-none" />
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
            <input name="password" type="password" required minlength="8" autocomplete="new-password"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                          focus:border-emerald-500 focus:outline-none" />
            <p class="text-[10px] text-slate-600">Minimum 8 characters</p>
        </div>

        <!-- Holder fields (default visible) -->
        <div id="holder-fields" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Full Name</label>
                <input name="full_name" type="text"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                              focus:border-emerald-500 focus:outline-none"
                       placeholder="e.g. Ahmad Ali" />
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">ID Number</label>
                <input name="id_number" type="text"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                              focus:border-emerald-500 focus:outline-none"
                       placeholder="e.g. 991234-01-5678" />
            </div>
        </div>

        <!-- Issuer / Verifier fields (hidden by default) -->
        <div id="org-fields" class="space-y-4 hidden">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Organisation</label>
                <input name="organisation" type="text"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100
                              focus:border-emerald-500 focus:outline-none"
                       placeholder="e.g. Universiti Teknologi Malaysia" />
            </div>
        </div>

        <!-- Key notice -->
        <div class="rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-xs text-slate-500 space-y-1">
            <p class="text-slate-400 font-semibold">Key pair generated on registration</p>
            <p>Your <span class="text-emerald-400">private key</span> is shown once — save it to sign credentials.</p>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold uppercase tracking-widest
                       text-slate-950 hover:bg-emerald-400 active:scale-95 transition-all">
            Create Account
        </button>

        <p class="text-center text-xs text-slate-500">
            Already have an account?
            <a href="<?= $base ?>/login" class="text-emerald-400 hover:underline">Sign in</a>
        </p>
    </form>
</div>

<script>
function switchRole(role) {
    // Reset all labels
    ['issuer','holder','verifier'].forEach(r => {
        const lbl = document.getElementById('label-' + r);
        lbl.classList.remove('border-emerald-500','bg-emerald-900/20');
        lbl.classList.add('border-slate-700','bg-slate-800');
    });
    // Highlight selected
    const selected = document.getElementById('label-' + role);
    selected.classList.remove('border-slate-700','bg-slate-800');
    selected.classList.add('border-emerald-500','bg-emerald-900/20');

    // Toggle fields
    const isHolder = role === 'holder';
    document.getElementById('holder-fields').classList.toggle('hidden', !isHolder);
    document.getElementById('org-fields').classList.toggle('hidden', isHolder);
}
</script>
</body>
</html>
