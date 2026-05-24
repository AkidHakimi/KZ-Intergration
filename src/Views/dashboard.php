<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — KAZ-SIGN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <!-- QR Code generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100">

<?php
$role      = $_SESSION['role'] ?? 'holder';
$did       = $_SESSION['did']  ?? ('did:kazsign:' . hash('sha256', $_SESSION['username'] ?? ''));
$roleColor = match($role) {
    'issuer'   => 'border-blue-700 bg-blue-900/30 text-blue-300',
    'verifier' => 'border-purple-700 bg-purple-900/30 text-purple-300',
    default    => 'border-emerald-700 bg-emerald-900/30 text-emerald-300',
};
$roleIcon = match($role) { 'issuer' => '', 'verifier' => '', default => '' };

$credentialFields = [
    'AcademicCredential' => [
        ['key' => 'name',              'label' => 'Name',                 'type' => 'text',     'placeholder' => 'e.g. RAJA HAZEERA NAJWA'],
        ['key' => 'awardNameEnglish',  'label' => 'Award Name (English)', 'type' => 'text',     'placeholder' => 'e.g. BACHELOR OF EDUCATION WITH HONOURS'],
        ['key' => 'awardNameMalay',    'label' => 'Award Name (Malay)',   'type' => 'textarea', 'placeholder' => 'e.g. SARJANA MUDA PENDIDIKAN DENGAN KEPUJIAN'],
        ['key' => 'certificateSerial', 'label' => 'Certificate Serial No','type' => 'text',     'placeholder' => 'e.g. 88720802'],
        ['key' => 'senateDate',        'label' => 'Senate Date',          'type' => 'text',     'placeholder' => 'e.g. 28 AUGUST 2024'],
        ['key' => 'convocationYear',   'label' => 'Convocation Year',     'type' => 'text',     'placeholder' => 'e.g. 2024'],
    ],
  
];
?>

<!-- NAV -->
<nav class="border-b border-slate-800 bg-slate-900">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between">
            <a href="<?= $base ?>/" class="flex items-center gap-2.5 hover:opacity-80">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs">KZ</span>
                <span class="text-sm font-semibold tracking-widest">KAZ&#8209;SIGN</span>
            </a>
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 rounded-full border <?= $roleColor ?> px-2.5 py-0.5 text-[10px] font-semibold capitalize">
                    <?= $roleIcon ?> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if (!empty($_SESSION['private_key'])): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-800 bg-emerald-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>PQC Signing ON
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

    <?php if (!empty($flash)): ?>
        <div class="rounded-lg border px-4 py-3 text-sm
            <?= $flash['type'] === 'success' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-300' : 'border-red-800 bg-red-950/50 text-red-300' ?>">
            <?= $flash['message'] ?>
        </div>
    <?php endif; ?>

    <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-2 rounded-full border <?= $roleColor ?> px-3 py-1 text-xs font-semibold capitalize">
            <?= $roleIcon ?> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> Dashboard
        </span>
    </div>

<?php if ($role === 'issuer'): ?>
<!-- ═══════════════════════ ISSUER ═══════════════════════════════════════════ -->

    <!-- DID + PQC Identity Card -->
    <div class="rounded-xl border border-blue-800 bg-blue-900/10 p-5 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold text-blue-300 uppercase tracking-widest">Issuer Identity</h2>
            <div class="flex gap-2">
                <span class="rounded-full border border-purple-700 bg-purple-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-purple-300">PQC</span>
                <span class="rounded-full border border-blue-700 bg-blue-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-blue-300">KAZ-SIGN v1</span>
            </div>
        </div>
        <div class="space-y-1">
            <p class="text-[9px] text-slate-500 uppercase tracking-widest font-semibold">DID</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 rounded-lg bg-slate-800 px-3 py-2 text-[10px] font-mono text-emerald-300 break-all"><?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?></code>
                <button onclick="copyText('<?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>', this)"
                        class="shrink-0 rounded border border-slate-700 bg-slate-800 px-2 py-1 text-[9px] text-slate-300 hover:text-emerald-300">Copy</button>
            </div>
        </div>
    </div>

    <!-- Issue form -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-5">
        <h2 class="text-sm font-semibold text-slate-100">Issue a Verifiable Credential</h2>

        <?php if (empty($holders)): ?>
            <div class="rounded-lg border border-yellow-800 bg-yellow-900/20 px-4 py-3 text-xs text-yellow-300">
                No holders registered yet.
            </div>
        <?php else: ?>
        <form action="<?= $base ?>/credentials/issue" method="POST" class="space-y-5" id="issue-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="subject_data" id="subject_data_hidden" />

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Holder</label>
                    <select name="holder_id" required
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        <option value="">— select holder —</option>
                        <?php foreach ($holders as $h): ?>
                            <option value="<?= (int)$h['id'] ?>">
                                <?= htmlspecialchars($h['full_name'] ?? $h['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars($h['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Credential Type</label>
                    <select name="credential_type" id="credential_type" onchange="switchFields(this.value)"
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        <option value="AcademicCredential">Academic Credential</option>
                    </select>
                </div>
            </div>

            <?php foreach ($credentialFields as $credType => $fields): ?>
            <div id="fields-<?= $credType ?>" class="space-y-4 <?= $credType !== 'AcademicCredential' ? 'hidden' : '' ?>">
                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold border-b border-slate-800 pb-2">Subject Fields</p>
                <?php foreach (array_chunk($fields, 2) as $pair): ?>
                <div class="grid grid-cols-<?= count($pair) === 2 ? '2' : '1' ?> gap-4">
                    <?php foreach ($pair as $f): ?>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider"><?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></label>
                        <?php if ($f['type'] === 'textarea'): ?>
                        <textarea data-field-key="<?= $f['key'] ?>" data-cred-type="<?= $credType ?>"
                                  placeholder="<?= htmlspecialchars($f['placeholder'], ENT_QUOTES, 'UTF-8') ?>" rows="3"
                                  class="subject-field w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 resize-none focus:border-emerald-500 focus:outline-none"></textarea>
                        <?php else: ?>
                        <input type="text" data-field-key="<?= $f['key'] ?>" data-cred-type="<?= $credType ?>"
                               placeholder="<?= htmlspecialchars($f['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                               class="subject-field w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 focus:border-emerald-500 focus:outline-none" />
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <button type="submit" <?= empty($_SESSION['private_key']) ? 'disabled' : '' ?>
                    class="rounded-lg bg-emerald-500 px-5 py-2 text-xs font-bold uppercase tracking-widest text-slate-950 hover:bg-emerald-400 active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                Issue &amp; Sign Credential
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Issued credentials table -->
    <div class="space-y-3">
        <h2 class="text-sm font-semibold text-slate-100">
            Issued Credentials
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
                            <th class="px-3 py-3 text-left text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-3 py-3 text-left text-slate-400 uppercase tracking-wider">Holder</th>
                            <th class="px-3 py-3 text-left text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-3 py-3 text-left text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-3 py-3 text-left text-slate-400 uppercase tracking-wider">Issued</th>
                            <th class="px-3 py-3 text-left text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900">
                    <?php foreach ($credentials as $c):
                        $jld        = json_decode($c['jsonld'] ?? '{}', true) ?: [];
                        $type       = implode(', ', array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential'));
                        $holderName = $c['holder_name'] ?? 'Unknown';
                        $s          = $c['status'] ?? 'issued';
                        $credId     = $c['credential_id'] ?? '';
                        $subject    = $jld['credentialSubject'] ?? [];
                    ?>
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-3 py-3 text-slate-500"><?= (int)$c['id'] ?></td>
                            <td class="px-3 py-3 text-slate-300"><?= htmlspecialchars($holderName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-3 py-3 text-slate-400"><?= htmlspecialchars($type ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-3 py-3">
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                                    <?= $s === 'verified' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400'
                                      : ($s === 'rejected'  ? 'border-red-800 bg-red-950/40 text-red-400'
                                      : ($s === 'revoked'   ? 'border-orange-700 bg-orange-900/30 text-orange-400'
                                      : 'border-blue-700 bg-blue-900/30 text-blue-400')) ?>">
                                    <?= ucfirst($s) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 text-slate-500"><?= htmlspecialchars(substr($c['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-3 py-3 space-y-1.5">
                                <!-- View JSON-LD -->
                                <button onclick="toggleJsonLd(<?= (int)$c['id'] ?>, this)"
                                        class="block text-[10px] text-emerald-400 hover:underline">View JSON-LD</button>

                                <!-- Generate PDF + QR -->
                                <button onclick='generateCredentialPDF(<?= htmlspecialchars(json_encode([
                                    'id'          => $c['id'],
                                    'credId'      => $credId,
                                    'holderName'  => $holderName,
                                    'type'        => $type,
                                    'issuedAt'    => substr($c['issued_at'] ?? '', 0, 10),
                                    'issuerName'  => $jld['issuer']['name'] ?? '',
                                    'issuerDid'   => $jld['issuer']['id'] ?? '',
                                    'holderDid'   => $subject['id'] ?? '',
                                    'subject'     => $subject,
                                    'status'      => $s,
                                ]), ENT_QUOTES, 'UTF-8') ?>)'
                                        class="block text-[10px] text-blue-400 hover:underline">Download PDF</button>

                                <!-- Revoke -->
                                <?php if ($s !== 'revoked'): ?>
                                <form method="POST" action="<?= $base ?>/credentials/revoke" class="inline"
                                      onsubmit="return confirm('Revoke this credential? This cannot be undone.')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="credential_db_id" value="<?= (int)$c['id'] ?>" />
                                    <button type="submit" class="block text-[10px] text-red-400 hover:underline">Revoke</button>
                                </form>
                                <?php else: ?>
                                    <span class="block text-[10px] text-orange-600">Revoked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- JSON-LD inline row -->
                        <tr id="jsonld-<?= (int)$c['id'] ?>" class="hidden">
                            <td colspan="6" class="px-4 py-4 bg-slate-800/60">
                                <pre class="text-[9px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed max-h-64 overflow-y-auto"><?= htmlspecialchars(
                                    json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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

<?php elseif ($role === 'holder'): ?>
<!-- ═══════════════════════ HOLDER ═══════════════════════════════════════════ -->

    <!-- DID -->
    <div class="rounded-xl border border-emerald-800/50 bg-emerald-900/10 p-4 space-y-2">
        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Your DID</p>
        <div class="flex items-center gap-2">
            <code class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-xs font-mono text-emerald-300 break-all"><?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?></code>
            <button onclick="copyText('<?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>', this)"
                    class="shrink-0 rounded border border-slate-700 bg-slate-800 px-2 py-1 text-[9px] text-slate-300 hover:text-emerald-300">Copy</button>
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="text-sm font-semibold text-slate-100">
            My Credentials
            <span class="ml-2 rounded-full border border-slate-700 bg-slate-800 px-2 py-0.5 text-[10px] text-slate-500"><?= count($credentials) ?></span>
        </h2>

        <?php if (empty($credentials)): ?>
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-16 text-center space-y-2">
                <p class="text-sm text-slate-500">No credentials issued to you yet.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4">
            <?php foreach ($credentials as $c):
                $jld        = json_decode($c['jsonld'] ?? '{}', true) ?: [];
                $subject    = $jld['credentialSubject'] ?? [];
                $types      = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
                $type       = implode(', ', $types);
                $s          = $c['status'] ?? 'issued';
                $issuerName = $c['issuer_name'] ?? 'Unknown Issuer';
                $issuerDid  = $jld['issuer']['id'] ?? '—';
                $credId     = $c['credential_id'] ?? '';
            ?>
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
                    <!-- Header -->
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-400"><?= htmlspecialchars($type ?: 'VerifiableCredential', ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Issued by <span class="text-slate-300"><?= htmlspecialchars($issuerName, ENT_QUOTES, 'UTF-8') ?></span>
                                · <?= htmlspecialchars(substr($c['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-[10px] text-slate-600 font-mono mt-0.5 truncate max-w-xs">Issuer DID: <?= htmlspecialchars($issuerDid, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                            <?= $s === 'verified' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400'
                              : ($s === 'rejected'  ? 'border-red-800 bg-red-950/40 text-red-400'
                              : ($s === 'revoked'   ? 'border-orange-700 bg-orange-900/30 text-orange-400'
                              : 'border-blue-700 bg-blue-900/30 text-blue-400')) ?>">
                            <?= $s === 'revoked' ? 'Revoked' : ucfirst($s) ?>
                        </span>
                    </div>

                    <!-- Subject fields -->
                    <div class="rounded-lg bg-slate-800 p-4 space-y-1.5">
                        <?php foreach ($subject as $key => $val): if ($key === 'id') continue; ?>
                            <div class="flex gap-4 text-xs">
                                <span class="text-slate-500 w-36 shrink-0"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-slate-300"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Credential ID -->
                    <div class="flex items-center justify-between gap-4">
                        <code class="text-[10px] font-mono text-slate-600 truncate flex-1" title="<?= htmlspecialchars($credId, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($credId, ENT_QUOTES, 'UTF-8') ?>
                        </code>
                        <div class="flex items-center gap-3 shrink-0">
                            <button onclick="copyText('<?= htmlspecialchars($credId, ENT_QUOTES, 'UTF-8') ?>', this)"
                                    class="text-[10px] text-slate-400 hover:underline">Copy ID</button>
                            <button onclick="toggleJsonLd(<?= (int)$c['id'] ?>, this)"
                                    class="text-[10px] text-emerald-400 hover:underline">View JSON-LD</button>
                            <!-- Download PDF -->
                            <button onclick='generateCredentialPDF(<?= htmlspecialchars(json_encode([
                                'id'         => $c['id'],
                                'credId'     => $credId,
                                'holderName' => $subject['name'] ?? '',
                                'type'       => $type,
                                'issuedAt'   => substr($c['issued_at'] ?? '', 0, 10),
                                'issuerName' => $issuerName,
                                'issuerDid'  => $issuerDid,
                                'holderDid'  => $subject['id'] ?? '',
                                'subject'    => $subject,
                                'status'     => $s,
                            ]), ENT_QUOTES, 'UTF-8') ?>)'
                                    class="text-[10px] text-blue-400 hover:underline">Download PDF</button>
                        </div>
                    </div>

                    <!-- Inline JSON-LD -->
                    <div id="jsonld-<?= (int)$c['id'] ?>" class="hidden">
                        <pre class="rounded-lg bg-slate-800 p-4 text-[9px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed overflow-x-auto max-h-64 overflow-y-auto"><?= htmlspecialchars(
                            json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ENT_QUOTES, 'UTF-8'
                        ) ?></pre>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($role === 'verifier'): ?>
<!-- ═══════════════════════ VERIFIER ═════════════════════════════════════════ -->

    <!-- Verifier DID -->
    <div class="rounded-xl border border-purple-800/50 bg-purple-900/10 p-4 space-y-2">
        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Your DID</p>
        <div class="flex items-center gap-2">
            <code class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-xs font-mono text-purple-300 break-all"><?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?></code>
            <button onclick="copyText('<?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>', this)"
                    class="shrink-0 rounded border border-slate-700 bg-slate-800 px-2 py-1 text-[9px] text-slate-300 hover:text-purple-300">Copy</button>
        </div>
    </div>

    <!-- Two verify methods -->
    <div class="grid grid-cols-2 gap-4">

        <!-- Method 1: Type Credential ID -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-100">Method 1 — Enter Credential ID</h2>
                <p class="text-xs text-slate-500 mt-1">Paste the credential ID from the holder.</p>
            </div>
            <form action="<?= $base ?>/credentials/verify" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <input name="credential_id" type="text" required
                       placeholder="urn:uuid:xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                              text-sm font-mono text-slate-100 focus:border-purple-500 focus:outline-none" />
                <button type="submit"
                        class="w-full rounded-lg bg-purple-600 px-5 py-2 text-xs font-bold uppercase tracking-widest
                               text-white hover:bg-purple-500 active:scale-95 transition-all">
                    Verify by ID
                </button>
            </form>
        </div>

        <!-- Method 2: Upload PDF -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-100">Method 2 — Upload PDF</h2>
                <p class="text-xs text-slate-500 mt-1">Upload the credential PDF — the system extracts the ID and verifies automatically.</p>
            </div>
            <div class="space-y-3">
                <label class="block">
                    <div class="rounded-lg border-2 border-dashed border-slate-700 bg-slate-800/50 px-4 py-8 text-center cursor-pointer hover:border-purple-600 transition-colors"
                         id="drop-zone"
                         ondragover="event.preventDefault(); this.classList.add('border-purple-500')"
                         ondragleave="this.classList.remove('border-purple-500')"
                         ondrop="handleFileDrop(event)">
                        <p class="text-xs text-slate-400">📎 Drop PDF here or click to browse</p>
                        <p class="text-[10px] text-slate-600 mt-1">Credential PDF generated by KAZ-SIGN</p>
                        <input type="file" id="pdf-upload" accept=".pdf" class="hidden" onchange="handleFileSelect(this)" />
                    </div>
                </label>
                <div id="pdf-status" class="hidden rounded-lg bg-slate-800 px-4 py-3 text-xs text-slate-400"></div>
                <button id="verify-pdf-btn" onclick="verifyFromPDF()" disabled
                        class="w-full rounded-lg bg-purple-600 px-5 py-2 text-xs font-bold uppercase tracking-widest
                               text-white hover:bg-purple-500 active:scale-95 transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed">
                    Verify PDF
                </button>
            </div>
            <!-- Hidden form for PDF verify -->
            <form id="pdf-verify-form" action="<?= $base ?>/credentials/verify" method="POST" class="hidden">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="credential_id" id="pdf-credential-id" />
            </form>
        </div>
    </div>

    <!-- Verification result -->
    <?php if (!empty($result)):
        $cred    = $result['credential'] ?? [];
        $jld     = $result['jsonld']     ?? [];
        $subj    = $jld['credentialSubject'] ?? [];
        $types   = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
        $revoked = $result['revoked'] ?? false;
    ?>
    <div class="rounded-xl border <?= $revoked ? 'border-orange-700' : (($result['verified'] ?? false) ? 'border-emerald-700' : 'border-red-800') ?>
                bg-slate-900 p-6 space-y-5">

        <?php if ($revoked): ?>
        <div class="rounded-lg border border-orange-700 bg-orange-900/20 px-4 py-3 text-sm font-semibold text-orange-300">
            REVOKED — This credential was cancelled by the issuer and is no longer valid.
        </div>
        <?php endif; ?>

        <h2 class="text-sm font-semibold <?= $revoked ? 'text-orange-300' : (($result['verified'] ?? false) ? 'text-emerald-300' : 'text-red-300') ?>">
            <?= $revoked ? 'Credential Revoked' : (($result['verified'] ?? false) ? '✓ Credential Verified' : '✗ Verification Failed') ?>
        </h2>

        <?php if (!$revoked): ?>
        <div class="space-y-2">
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">SHA-256 Hash Integrity</span>
                <span class="text-[10px] font-bold rounded-full px-3 py-0.5 border
                    <?= ($result['hash_intact'] ?? false) ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400' : 'border-red-800 bg-red-950/40 text-red-400' ?>">
                    <?= ($result['hash_intact'] ?? false) ? 'Intact ✓' : 'MODIFIED ✗' ?>
                </span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">KAZ-SIGN PQC Signature</span>
                <span class="text-[10px] font-bold rounded-full px-3 py-0.5 border
                    <?= ($result['signature_valid'] ?? false) ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400' : 'border-red-800 bg-red-950/40 text-red-400' ?>">
                    <?= ($result['signature_valid'] ?? false) ? 'Valid ✓' : 'Invalid ✗' ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div class="rounded-lg bg-slate-800 p-4 space-y-2">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mb-2">Credential Details</p>
            <div class="flex gap-4 text-xs"><span class="text-slate-500 w-36 shrink-0">Type</span><span class="text-slate-300"><?= htmlspecialchars(implode(', ', $types) ?: '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="flex gap-4 text-xs"><span class="text-slate-500 w-36 shrink-0">Issuer DID</span><span class="text-blue-300 font-mono break-all text-[9px]"><?= htmlspecialchars($jld['issuer']['id'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="flex gap-4 text-xs"><span class="text-slate-500 w-36 shrink-0">Issued by</span><span class="text-slate-300"><?= htmlspecialchars($jld['issuer']['name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="flex gap-4 text-xs"><span class="text-slate-500 w-36 shrink-0">Issued on</span><span class="text-slate-300"><?= htmlspecialchars(substr($cred['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php foreach ($subj as $key => $val): if ($key === 'id') continue; ?>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-36 shrink-0"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="text-slate-300"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Full JSON-LD -->
        <div>
            <button onclick="toggleJsonLd('verify-result', this)" class="text-xs text-slate-500 hover:text-slate-300">▶ Show full JSON-LD</button>
            <div id="jsonld-verify-result" class="hidden mt-3">
                <pre class="rounded-lg bg-slate-800 p-4 text-[9px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed overflow-x-auto max-h-64 overflow-y-auto"><?= htmlspecialchars(
                    json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ENT_QUOTES, 'UTF-8'
                ) ?></pre>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>
</main>

<!-- Hidden QR container (off-screen) -->
<div id="qr-container" style="position:absolute;left:-9999px;top:0;width:200px;height:200px;background:#fff;padding:10px;"></div>

<script>
// ─── Field switching ──────────────────────────────────────────────────────────
function switchFields(type) {
    document.querySelectorAll('[id^="fields-"]').forEach(el => el.classList.add('hidden'));
    const t = document.getElementById('fields-' + type);
    if (t) t.classList.remove('hidden');
}

document.getElementById('issue-form')?.addEventListener('submit', function() {
    const credType  = document.getElementById('credential_type').value;
    const container = document.getElementById('fields-' + credType);
    const obj       = {};
    if (container) {
        container.querySelectorAll('.subject-field').forEach(el => {
            const key = el.dataset.fieldKey;
            const val = el.value.trim();
            if (key && val !== '') obj[key] = val;
        });
    }
    document.getElementById('subject_data_hidden').value = JSON.stringify(obj);
});

// ─── Toggle JSON-LD ───────────────────────────────────────────────────────────
function toggleJsonLd(id, btn) {
    const el = document.getElementById('jsonld-' + id);
    const hidden = el.classList.toggle('hidden');
    if (btn) {
        btn.textContent = hidden
            ? btn.textContent.replace('Hide','View').replace('▼','▶')
            : btn.textContent.replace('View','Hide').replace('▶','▼');
    }
}

// ─── Copy text ────────────────────────────────────────────────────────────────
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}

// ─── PDF Generation with QR Code ─────────────────────────────────────────────
async function generateCredentialPDF(data) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });

    const pageW  = 210;
    const margin = 20;
    const colW   = pageW - margin * 2;
    let y = 20;

    // Header background
    doc.setFillColor(15, 23, 42); // slate-950
    doc.rect(0, 0, pageW, 40, 'F');

    // Logo text
    doc.setTextColor(16, 185, 129); // emerald
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text('KAZ-SIGN', margin, 18);

    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(148, 163, 184); // slate-400
    doc.text('Post-Quantum Verifiable Credential', margin, 26);
    doc.text('did:kazsign  |  KAZ-SIGN-128 PQC Algorithm', margin, 32);

    // Status badge
    const statusColor = data.status === 'verified' ? [16,185,129]
                      : data.status === 'revoked'  ? [249,115,22]
                      : [59,130,246];
    doc.setFillColor(...statusColor);
    doc.roundedRect(pageW - margin - 30, 12, 30, 10, 2, 2, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(8);
    doc.setFont('helvetica', 'bold');
    doc.text(data.status.toUpperCase(), pageW - margin - 15, 18.5, { align: 'center' });

    y = 50;

    // Credential type title
    doc.setTextColor(30, 41, 59);
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text(data.type || 'Verifiable Credential', margin, y);
    y += 8;

    // Divider
    doc.setDrawColor(226, 232, 240);
    doc.line(margin, y, pageW - margin, y);
    y += 8;

    // Two-column layout: details left, QR right
    const detailW  = colW - 55;
    const qrStartX = margin + detailW + 5;
    const qrSize   = 48;

    // Generate QR code
    const qrContainer = document.getElementById('qr-container');
    qrContainer.innerHTML = '';
    await new Promise(resolve => {
        new QRCode(qrContainer, {
            text: data.credId,
            width: 200,
            height: 200,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
        });
        setTimeout(resolve, 300);
    });

    const qrCanvas = qrContainer.querySelector('canvas');
    if (qrCanvas) {
        const qrDataUrl = qrCanvas.toDataURL('image/png');
        doc.addImage(qrDataUrl, 'PNG', qrStartX, y - 5, qrSize, qrSize);
        doc.setFontSize(6);
        doc.setTextColor(100, 116, 139);
        doc.text('Scan to verify', qrStartX + qrSize / 2, y + qrSize - 1, { align: 'center' });
    }

    // Subject details (left column)
    const fields = [];
    if (data.holderName) fields.push(['Holder Name', data.holderName]);
    if (data.issuerName) fields.push(['Issued By', data.issuerName]);
    fields.push(['Issue Date', data.issuedAt]);

    // Add subject fields
    if (data.subject) {
        const skip = ['id'];
        Object.entries(data.subject).forEach(([k, v]) => {
            if (!skip.includes(k) && v) {
                const label = k.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());
                fields.push([label, String(v)]);
            }
        });
    }

    doc.setFont('helvetica', 'normal');
    fields.forEach(([label, value]) => {
        doc.setFontSize(7);
        doc.setTextColor(100, 116, 139);
        doc.text(label.toUpperCase(), margin, y);
        y += 4.5;
        doc.setFontSize(9);
        doc.setTextColor(15, 23, 42);
        doc.setFont('helvetica', 'bold');
        const lines = doc.splitTextToSize(value, detailW);
        doc.text(lines, margin, y);
        y += lines.length * 5 + 3;
        doc.setFont('helvetica', 'normal');
    });

    y = Math.max(y, 50 + qrSize + 10);
    y += 5;

    // Divider
    doc.setDrawColor(226, 232, 240);
    doc.line(margin, y, pageW - margin, y);
    y += 8;

    // DID Section
    doc.setFontSize(9);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text('Decentralized Identifiers (DID)', margin, y);
    y += 6;

    [['Issuer DID', data.issuerDid], ['Holder DID', data.holderDid]].forEach(([label, value]) => {
        if (!value || value === '—') return;
        doc.setFontSize(7);
        doc.setTextColor(100, 116, 139);
        doc.text(label, margin, y);
        y += 4;
        doc.setFontSize(7.5);
        doc.setTextColor(16, 185, 129);
        doc.setFont('helvetica', 'normal');
        const lines = doc.splitTextToSize(value, colW);
        doc.text(lines, margin, y);
        y += lines.length * 4.5 + 3;
    });

    y += 3;
    doc.setDrawColor(226, 232, 240);
    doc.line(margin, y, pageW - margin, y);
    y += 8;

    // Credential ID
    doc.setFontSize(9);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text('Credential ID', margin, y);
    y += 5;
    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(16, 185, 129);
    const credLines = doc.splitTextToSize(data.credId, colW);
    doc.text(credLines, margin, y);
    y += credLines.length * 4.5 + 8;

    // PQC notice box
    doc.setFillColor(245, 243, 255);
    doc.setDrawColor(167, 139, 250);
    doc.roundedRect(margin, y, colW, 16, 2, 2, 'FD');
    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(109, 40, 217);
    doc.text('🔐 Post-Quantum Cryptography (PQC)', margin + 4, y + 6);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);
    doc.text('Signed using KAZ-SIGN-128 algorithm. Quantum-resistant digital signature.', margin + 4, y + 11);
    y += 22;

    // Footer
    doc.setFillColor(248, 250, 252);
    doc.rect(0, 280, pageW, 17, 'F');
    doc.setFontSize(7);
    doc.setTextColor(148, 163, 184);
    doc.text('This is a KAZ-SIGN Verifiable Credential. Verify at: ' + window.location.origin, margin, 287);
    doc.text('Generated: ' + new Date().toISOString().slice(0,19).replace('T',' '), pageW - margin, 287, { align: 'right' });
    doc.text('KAZ-SIGN System  |  did:kazsign  |  PQC Secured', pageW / 2, 293, { align: 'center' });

    // Save
    const fileName = 'KAZ-SIGN-Credential-' + (data.holderName || 'Credential').replace(/\s+/g, '-') + '-' + data.issuedAt + '.pdf';
    doc.save(fileName);
}

// ─── PDF Upload & Extract Credential ID ───────────────────────────────────────
let extractedCredentialId = null;

function handleFileDrop(event) {
    event.preventDefault();
    document.getElementById('drop-zone').classList.remove('border-purple-500');
    const file = event.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        processPDFFile(file);
    } else {
        showPDFStatus('error', 'Please drop a PDF file.');
    }
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (file) processPDFFile(file);
}

document.getElementById('drop-zone')?.addEventListener('click', () => {
    document.getElementById('pdf-upload').click();
});

async function processPDFFile(file) {
    showPDFStatus('loading', '⏳ Reading PDF...');

    try {
        const arrayBuffer = await file.arrayBuffer();

        // Extract text from PDF by reading the raw bytes
        // Look for the credential ID pattern (urn:uuid:...)
        const uint8Array  = new Uint8Array(arrayBuffer);
        const text        = new TextDecoder('latin1').decode(uint8Array);

        // Search for credential ID pattern
        const credIdMatch = text.match(/urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);

        if (credIdMatch) {
            extractedCredentialId = credIdMatch[0];
            showPDFStatus('success', '✓ Credential ID found: ' + extractedCredentialId);
            document.getElementById('verify-pdf-btn').disabled = false;
        } else {
            // Try to find it in decoded text differently
            showPDFStatus('error', '✗ No KAZ-SIGN Credential ID found in this PDF. Make sure you uploaded a KAZ-SIGN credential PDF.');
            document.getElementById('verify-pdf-btn').disabled = true;
        }
    } catch (err) {
        showPDFStatus('error', '✗ Could not read PDF: ' + err.message);
    }
}

function showPDFStatus(type, message) {
    const el = document.getElementById('pdf-status');
    el.classList.remove('hidden');
    el.className = 'rounded-lg px-4 py-3 text-xs ' + (
        type === 'success' ? 'bg-emerald-900/20 text-emerald-300 border border-emerald-700' :
        type === 'error'   ? 'bg-red-950/30 text-red-300 border border-red-800' :
                             'bg-slate-800 text-slate-400'
    );
    el.textContent = message;
}

function verifyFromPDF() {
    if (!extractedCredentialId) return;
    document.getElementById('pdf-credential-id').value = extractedCredentialId;
    document.getElementById('pdf-verify-form').submit();
}
</script>
</body>
</html>