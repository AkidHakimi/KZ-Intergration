<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Save Your Private Key — KAZ-SIGN System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-12">

<div class="w-full max-w-xl space-y-6">

    <!-- Logo -->
    <div class="text-center space-y-2">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-slate-950 font-bold text-lg">KZ</span>
        <h1 class="text-xl font-semibold tracking-widest">KAZ&#8209;SIGN</h1>
    </div>

    <!-- Warning banner -->
    <div class="flex gap-3 rounded-xl border border-yellow-700 bg-yellow-900/20 px-5 py-4">
        <span class="text-yellow-400 text-lg mt-0.5">⚠</span>
        <div class="space-y-1 text-xs">
            <p class="font-semibold text-yellow-300">Save your private key now — this is the only time it will be shown.</p>
            <p class="text-yellow-600">It is never stored on the server. If you lose it, you will not be able to sign new documents and must register a new account.</p>
        </div>
    </div>

    <!-- Private key box -->
    <div class="rounded-xl border border-slate-700 bg-slate-900 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Your Private Key</h2>
            <button id="copy-btn"
                    onclick="copyKey()"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800
                           px-3 py-1.5 text-[11px] font-semibold text-slate-300
                           hover:border-emerald-600 hover:text-emerald-300 transition-all">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                </svg>
                Copy
            </button>
        </div>

        <textarea id="private-key-display"
                  readonly
                  rows="6"
                  class="w-full rounded-lg border border-slate-700 bg-slate-800/60 px-4 py-3
                         text-[11px] text-emerald-300 leading-relaxed resize-none
                         focus:outline-none select-all"><?= htmlspecialchars($private_key ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

        <p class="text-[10px] text-slate-600">
            Tip: Save this in a password manager, an encrypted file, or a secure note.
        </p>
    </div>

    <!-- Confirm & continue -->
    <form action="<?= $base ?>/key/confirm" method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

        <label class="flex items-start gap-3 cursor-pointer mb-4">
            <input id="saved-check" type="checkbox" required
                   class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-800 accent-emerald-500" />
            <span class="text-xs text-slate-400">
                I have saved my private key in a safe place and understand I cannot recover it if lost.
            </span>
        </label>

        <button type="submit"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold uppercase
                       tracking-widest text-slate-950 hover:bg-emerald-400 active:scale-95
                       transition-all duration-150">
            I've Saved It — Continue to Dashboard
        </button>
    </form>

</div>

<script>
function copyKey() {
    const ta  = document.getElementById('private-key-display');
    const btn = document.getElementById('copy-btn');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        btn.textContent = '✓ Copied!';
        btn.classList.add('text-emerald-400', 'border-emerald-600');
        setTimeout(() => { btn.innerHTML = `<svg class="h-3 w-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>Copy`; btn.classList.remove('text-emerald-400','border-emerald-600'); }, 2000);
    });
}
</script>

</body>
</html>
