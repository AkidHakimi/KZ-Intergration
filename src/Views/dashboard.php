<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — KAZ-SIGN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100">

<?php
// ─────────────────────────────────────────────────────────────────────────────
// NAV
// ─────────────────────────────────────────────────────────────────────────────
$role      = $_SESSION['role'] ?? 'holder';
$roleColor = match($role) {
    'issuer'   => 'border-blue-700 bg-blue-900/30 text-blue-300',
    'verifier' => 'border-purple-700 bg-purple-900/30 text-purple-300',
    default    => 'border-emerald-700 bg-emerald-900/30 text-emerald-300',
};
$roleIcon = match($role) { 'issuer' => '🏛', 'verifier' => '🔍', default => '👤' };
?>
<nav class="border-b border-slate-800 bg-slate-900">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between">
            <a href="<?= $base ?>/" class="flex items-center gap-2.5 hover:opacity-80 transition-opacity">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs">KZ</span>
                <span class="text-sm font-semibold tracking-widest text-slate-100">KAZ&#8209;SIGN</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="<?= $base ?>/" class="text-xs text-slate-400 hover:text-slate-100">Dashboard</a>
                <span class="inline-flex items-center gap-1.5 rounded-full border <?= $roleColor ?> px-2.5 py-0.5 text-[10px] font-semibold capitalize">
                    <?= $roleIcon ?> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if (!empty($_SESSION['private_key'])): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-800 bg-emerald-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Signing ON
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-slate-800 px-2.5 py-0.5 text-[10px] font-semibold text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>Verify only
                    </span>
                <?php endif; ?>
                <div class="flex items-center gap-3 border-l border-slate-800 pl-4">
                    <span class="text-xs text-slate-500"><?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <a href="<?= $base ?>/logout" class="text-xs text-red-400 hover:text-red-300">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10 space-y-8">

<?php
// ─────────────────────────────────────────────────────────────────────────────
// FLASH MESSAGE
// ─────────────────────────────────────────────────────────────────────────────
?>
<?php if (!empty($flash)): ?>
    <div class="rounded-lg border px-4 py-3 text-sm
        <?= $flash['type'] === 'success' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-300' : 'border-red-800 bg-red-950/50 text-red-300' ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php
// ─────────────────────────────────────────────────────────────────────────────
// ROLE BADGE
// ─────────────────────────────────────────────────────────────────────────────
?>
<div class="flex items-center gap-3">
    <span class="inline-flex items-center gap-2 rounded-full border <?= $roleColor ?> px-3 py-1 text-xs font-semibold capitalize">
        <?= $roleIcon ?> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> Dashboard
    </span>
    <?php if (empty($_SESSION['private_key'])): ?>
        <span class="text-xs text-yellow-400">⚠ No private key loaded —
            <a href="<?= $base ?>/logout" class="underline">re-login with key</a> to enable signing.
        </span>
    <?php endif; ?>
</div>

<?php
// =============================================================================
// ██████████████████████   ISSUER DASHBOARD   █████████████████████████████████
// =============================================================================
if ($role === 'issuer'):
?>

    <!-- Issue credential form -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-5">
        <div>
            <h2 class="text-sm font-semibold text-slate-100">Issue a Verifiable Credential</h2>
            <p class="text-xs text-slate-500 mt-1">Select a holder, fill in the subject data, sign and issue.</p>
        </div>

        <?php if (empty($holders)): ?>
            <p class="text-xs text-slate-500">No holders registered yet. Ask holders to create an account first.</p>
        <?php else: ?>
        <form action="<?= $base ?>/credentials/issue" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Holder</label>
                    <select name="holder_id" required
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        <option value="">— select holder —</option>
                        <?php foreach ($holders as $h): ?>
                            <option value="<?= (int)$h['id'] ?>">
                                <?= htmlspecialchars($h['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars($h['username'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Credential Type</label>
                    <select name="credential_type"
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        <option value="AcademicCredential">Academic Credential</option>
                        <option value="EmploymentCredential">Employment Credential</option>
                        <option value="IdentityCredential">Identity Credential</option>
                        <option value="MedicalCredential">Medical Credential</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    Subject Data
                    <span class="text-[10px] font-normal text-slate-600 normal-case ml-1">— JSON or plain text</span>
                </label>
                <textarea name="subject_data" rows="5" required
                          placeholder='{"degree": "Bachelor of Science", "institution": "UTM", "year": "2024"}'
                          class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-xs text-slate-300 font-mono resize-none focus:border-emerald-500 focus:outline-none"></textarea>
            </div>

            <button type="submit" <?= empty($_SESSION['private_key']) ? 'disabled' : '' ?>
                    class="rounded-lg bg-emerald-500 px-5 py-2 text-xs font-bold uppercase tracking-widest
                           text-slate-950 hover:bg-emerald-400 active:scale-95 transition-all
                           disabled:opacity-40 disabled:cursor-not-allowed">
                Issue &amp; Sign Credential
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Issued credentials table -->
    <div class="space-y-3">
        <h2 class="text-sm font-semibold text-slate-100">Issued Credentials
            <span class="ml-2 rounded-full border border-slate-700 bg-slate-800 px-2 py-0.5 text-[10px] text-slate-500"><?= count($credentials) ?></span>
        </h2>

        <?php if (empty($credentials)): ?>
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-12 text-center">
                <p class="text-sm text-slate-500">No credentials issued yet.</p>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-800 overflow-hidden">
                <table class="w-full text-xs">
                    <thead class="border-b border-slate-700 bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">Holder</th>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">Credential ID</th>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">Issued</th>
                            <th class="px-4 py-3 text-left text-slate-400 uppercase tracking-wider">JSON-LD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900">
                    <?php foreach ($credentials as $c):
                        $jld  = json_decode($c['jsonld'], true);
                        $type = implode(', ', array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential'));
                    ?>
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-slate-500"><?= (int)$c['id'] ?></td>
                            <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($c['holder_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 font-mono text-slate-500 max-w-[160px] truncate"
                                title="<?= htmlspecialchars($c['credential_id'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(substr($c['credential_id'], 0, 26), ENT_QUOTES, 'UTF-8') ?>…
                            </td>
                            <td class="px-4 py-3">
                                <?php $s = $c['status']; ?>
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                                    <?= $s === 'verified' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400'
                                      : ($s === 'rejected' ? 'border-red-800 bg-red-950/40 text-red-400'
                                      : 'border-blue-700 bg-blue-900/30 text-blue-400') ?>">
                                    <?= ucfirst($s) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars(substr($c['issued_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button onclick="toggleJsonLd(<?= (int)$c['id'] ?>)"
                                        class="text-emerald-400 hover:underline text-[10px]">View JSON-LD</button>
                            </td>
                        </tr>
                        <!-- Inline JSON-LD row -->
                        <tr id="jsonld-<?= (int)$c['id'] ?>" class="hidden">
                            <td colspan="7" class="px-4 py-3 bg-slate-800/60">
                                <pre class="text-[10px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed"><?= htmlspecialchars(
                                    json_encode(json_decode($c['jsonld'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                                    ENT_QUOTES, 'UTF-8'
                                ) ?></pre>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php
// =============================================================================
// ██████████████████████   HOLDER DASHBOARD   █████████████████████████████████
// =============================================================================
elseif ($role === 'holder'):
?>

    <div class="space-y-4">
        <h2 class="text-sm font-semibold text-slate-100">My Credentials
            <span class="ml-2 rounded-full border border-slate-700 bg-slate-800 px-2 py-0.5 text-[10px] text-slate-500"><?= count($credentials) ?></span>
        </h2>

        <?php if (empty($credentials)): ?>
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-16 text-center space-y-2">
                <p class="text-sm text-slate-500">No credentials issued to you yet.</p>
                <p class="text-xs text-slate-600">An issuer will send credentials once they register and issue one to you.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4">
            <?php foreach ($credentials as $c):
                $jld     = json_decode($c['jsonld'], true);
                $subject = $jld['credentialSubject'] ?? [];
                $types   = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
                $type    = implode(', ', $types);
                $s       = $c['status'];
            ?>
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
                    <!-- Header -->
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-400"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Issued by <span class="text-slate-300"><?= htmlspecialchars($c['issuer_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                · <?= htmlspecialchars(substr($c['issued_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                            <?= $s === 'verified' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400'
                              : ($s === 'rejected' ? 'border-red-800 bg-red-950/40 text-red-400'
                              : 'border-blue-700 bg-blue-900/30 text-blue-400') ?>">
                            <?= ucfirst($s) ?>
                        </span>
                    </div>

                    <!-- Subject fields -->
                    <div class="rounded-lg bg-slate-800 p-4 space-y-1.5">
                        <?php foreach ($subject as $key => $val): ?>
                            <?php if ($key === 'id') continue; ?>
                            <div class="flex gap-4 text-xs">
                                <span class="text-slate-500 w-28 shrink-0"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-slate-300"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Credential ID + toggle JSON-LD -->
                    <div class="flex items-center justify-between gap-4">
                        <code class="text-[10px] font-mono text-slate-600 truncate"
                              title="<?= htmlspecialchars($c['credential_id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($c['credential_id'], ENT_QUOTES, 'UTF-8') ?>
                        </code>
                        <div class="flex items-center gap-3 shrink-0">
                            <button onclick="copyToClipboard('<?= htmlspecialchars($c['credential_id'], ENT_JS, 'UTF-8') ?>')"
                                    class="text-[10px] text-slate-400 hover:text-slate-200 hover:underline">Copy ID</button>
                            <button onclick="toggleJsonLd(<?= (int)$c['id'] ?>)"
                                    class="text-[10px] text-emerald-400 hover:underline">View JSON-LD</button>
                        </div>
                    </div>

                    <!-- Inline JSON-LD -->
                    <div id="jsonld-<?= (int)$c['id'] ?>" class="hidden">
                        <pre class="rounded-lg bg-slate-800 p-4 text-[10px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed overflow-x-auto"><?= htmlspecialchars(
                            json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ENT_QUOTES, 'UTF-8'
                        ) ?></pre>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php
// =============================================================================
// █████████████████████   VERIFIER DASHBOARD   ████████████████████████████████
// =============================================================================
elseif ($role === 'verifier'):
?>

    <!-- Verify form -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-5">
        <div>
            <h2 class="text-sm font-semibold text-slate-100">Verify a Credential</h2>
            <p class="text-xs text-slate-500 mt-1">Enter the Credential ID shared by the holder.</p>
        </div>
        <form action="<?= $base ?>/credentials/verify" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Credential ID</label>
                <input name="credential_id" type="text" required
                       placeholder="urn:uuid:xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                              text-sm font-mono text-slate-100 focus:border-emerald-500 focus:outline-none" />
            </div>
            <button type="submit"
                    class="rounded-lg bg-purple-600 px-5 py-2 text-xs font-bold uppercase tracking-widest
                           text-white hover:bg-purple-500 active:scale-95 transition-all">
                Verify Credential
            </button>
        </form>
    </div>

    <!-- Verification result -->
    <?php if (!empty($result)):
        $cred   = $result['credential'];
        $jld    = $result['jsonld'];
        $subj   = $jld['credentialSubject'] ?? [];
        $types  = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
    ?>
    <div class="rounded-xl border <?= $result['verified'] ? 'border-emerald-700' : 'border-red-800' ?> bg-slate-900 p-6 space-y-5">
        <h2 class="text-sm font-semibold <?= $result['verified'] ? 'text-emerald-300' : 'text-red-300' ?>">
            <?= $result['verified'] ? '✓ Credential Verified' : '✗ Verification Failed' ?>
        </h2>

        <!-- Check rows -->
        <div class="space-y-2">
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">File integrity (SHA-256)</span>
                <span class="text-[10px] font-bold rounded-full px-3 py-0.5 border
                    <?= $result['hash_intact'] ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400' : 'border-red-800 bg-red-950/40 text-red-400' ?>">
                    <?= $result['hash_intact'] ? 'Intact' : 'MODIFIED' ?>
                </span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">KAZ-SIGN signature</span>
                <span class="text-[10px] font-bold rounded-full px-3 py-0.5 border
                    <?= $result['signature_valid'] ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400' : 'border-red-800 bg-red-950/40 text-red-400' ?>">
                    <?= $result['signature_valid'] ? 'Valid' : 'Invalid' ?>
                </span>
            </div>
        </div>

        <!-- Credential detail fields -->
        <div class="rounded-lg bg-slate-800 p-4 space-y-2">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mb-2">Credential Details</p>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-28 shrink-0">Type</span>
                <span class="text-slate-300"><?= htmlspecialchars(implode(', ', $types), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-28 shrink-0">Issued by</span>
                <span class="text-slate-300"><?= htmlspecialchars($jld['issuer']['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-28 shrink-0">Issued on</span>
                <span class="text-slate-300"><?= htmlspecialchars(substr($cred['issued_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php foreach ($subj as $key => $val): ?>
                <?php if ($key === 'id') continue; ?>
                <div class="flex gap-4 text-xs">
                    <span class="text-slate-500 w-28 shrink-0"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-slate-300"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Full JSON-LD toggle -->
        <div>
            <button onclick="toggleJsonLd('verify-result')"
                    class="text-xs text-slate-500 hover:text-slate-300">▶ Show full JSON-LD</button>
            <div id="jsonld-verify-result" class="hidden mt-3">
                <pre class="rounded-lg bg-slate-800 p-4 text-[10px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed overflow-x-auto"><?= htmlspecialchars(
                    json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ENT_QUOTES, 'UTF-8'
                ) ?></pre>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>
</main>

<script>
function toggleJsonLd(id) {
    const el  = document.getElementById('jsonld-' + id);
    const btn = event.target;
    if (el.classList.toggle('hidden')) {
        btn.textContent = btn.textContent.replace('▼', '▶').replace('Hide', 'View');
    } else {
        btn.textContent = btn.textContent.replace('▶', '▼').replace('View', 'Hide');
    }
}
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const orig = btn.textContent;
        btn.textContent = '✓ Copied';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}
</script>
</body>
</html>