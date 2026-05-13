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
$role      = $_SESSION['role'] ?? 'holder';
$did       = $_SESSION['did']  ?? ('did:kazsign:' . hash('sha256', $_SESSION['username'] ?? ''));
$roleColor = match($role) {
    'issuer'   => 'border-blue-700 bg-blue-900/30 text-blue-300',
    'verifier' => 'border-purple-700 bg-purple-900/30 text-purple-300',
    default    => 'border-emerald-700 bg-emerald-900/30 text-emerald-300',
};
$roleIcon = match($role) { 'issuer' => '🏛', 'verifier' => '🔍', default => '👤' };

$credentialFields = [
    'AcademicCredential' => [
        ['key' => 'name',              'label' => 'Name',                 'type' => 'text',     'placeholder' => 'e.g. RAJA HAZEERA NAJWA BINTI RAJA HAIRUL NIZAM'],
        ['key' => 'awardNameEnglish',  'label' => 'Award Name (English)', 'type' => 'text',     'placeholder' => 'e.g. BACHELOR OF EDUCATION WITH HONOURS'],
        ['key' => 'awardNameMalay',    'label' => 'Award Name (Malay)',   'type' => 'textarea', 'placeholder' => 'e.g. SARJANA MUDA PENDIDIKAN DENGAN KEPUJIAN'],
        ['key' => 'certificateSerial', 'label' => 'Certificate Serial No','type' => 'text',     'placeholder' => 'e.g. 88720802'],
        ['key' => 'senateDate',        'label' => 'Senate Date',          'type' => 'text',     'placeholder' => 'e.g. AUGUST 28, 2024'],
        ['key' => 'convocationYear',   'label' => 'Convocation Year',     'type' => 'text',     'placeholder' => 'e.g. 2024'],
    ],
    'EmploymentCredential' => [
        ['key' => 'name',           'label' => 'Full Name',        'type' => 'text',     'placeholder' => 'e.g. Ahmad Ali bin Hassan'],
        ['key' => 'position',       'label' => 'Position',         'type' => 'text',     'placeholder' => 'e.g. Software Engineer'],
        ['key' => 'department',     'label' => 'Department',       'type' => 'text',     'placeholder' => 'e.g. Information Technology'],
        ['key' => 'organisation',   'label' => 'Organisation',     'type' => 'text',     'placeholder' => 'e.g. Petronas Berhad'],
        ['key' => 'employeeId',     'label' => 'Employee ID',      'type' => 'text',     'placeholder' => 'e.g. EMP-2024-001'],
        ['key' => 'startDate',      'label' => 'Start Date',       'type' => 'text',     'placeholder' => 'e.g. JANUARY 1, 2020'],
        ['key' => 'employmentType', 'label' => 'Employment Type',  'type' => 'text',     'placeholder' => 'e.g. Permanent / Contract'],
    ],
    'IdentityCredential' => [
        ['key' => 'name',        'label' => 'Full Name',      'type' => 'text',     'placeholder' => 'e.g. Ahmad Ali bin Hassan'],
        ['key' => 'idNumber',    'label' => 'IC / ID Number', 'type' => 'text',     'placeholder' => 'e.g. 991234-01-5678'],
        ['key' => 'dateOfBirth', 'label' => 'Date of Birth',  'type' => 'text',     'placeholder' => 'e.g. DECEMBER 34, 1999'],
        ['key' => 'nationality', 'label' => 'Nationality',    'type' => 'text',     'placeholder' => 'e.g. Malaysian'],
        ['key' => 'gender',      'label' => 'Gender',         'type' => 'text',     'placeholder' => 'Male / Female'],
        ['key' => 'address',     'label' => 'Address',        'type' => 'textarea', 'placeholder' => 'Full address'],
    ],
    'MedicalCredential' => [
        ['key' => 'name',       'label' => 'Patient Name',  'type' => 'text',     'placeholder' => 'e.g. Ahmad Ali'],
        ['key' => 'patientId',  'label' => 'Patient ID',    'type' => 'text',     'placeholder' => 'e.g. PAT-2024-001'],
        ['key' => 'diagnosis',  'label' => 'Diagnosis',     'type' => 'text',     'placeholder' => 'e.g. Hypertension'],
        ['key' => 'treatment',  'label' => 'Treatment',     'type' => 'textarea', 'placeholder' => 'e.g. Prescribed medication...'],
        ['key' => 'doctorName', 'label' => 'Doctor Name',   'type' => 'text',     'placeholder' => 'e.g. Dr. Siti Aminah'],
        ['key' => 'hospital',   'label' => 'Hospital',      'type' => 'text',     'placeholder' => 'e.g. Hospital Kuala Lumpur'],
        ['key' => 'visitDate',  'label' => 'Visit Date',    'type' => 'text',     'placeholder' => 'e.g. MAY 13, 2025'],
    ],
];
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
                <span class="inline-flex items-center gap-1.5 rounded-full border <?= $roleColor ?>
                             px-2.5 py-0.5 text-[10px] font-semibold capitalize">
                    <?= $roleIcon ?> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if (!empty($_SESSION['private_key'])): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-800
                                 bg-emerald-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>PQC Signing ON
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700
                                 bg-slate-800 px-2.5 py-0.5 text-[10px] font-semibold text-slate-500">
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
            <?= $flash['type'] === 'success'
                ? 'border-emerald-700 bg-emerald-900/30 text-emerald-300'
                : 'border-red-800 bg-red-950/50 text-red-300' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-2 rounded-full border <?= $roleColor ?>
                     px-3 py-1 text-xs font-semibold capitalize">
            <?= $roleIcon ?> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> Dashboard
        </span>
        <?php if (empty($_SESSION['private_key'])): ?>
            <span class="text-xs text-yellow-400">
                ⚠ No PQC key —
                <a href="<?= $base ?>/logout" class="underline hover:text-yellow-300">re-login with key</a>
                to enable signing.
            </span>
        <?php endif; ?>
    </div>

<?php if ($role === 'issuer'): ?>
<!-- ============================================================ ISSUER -->

    <!-- DID + PQC Identity Card -->
    <div class="rounded-xl border border-blue-800 bg-blue-900/10 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold text-blue-300 uppercase tracking-widest">
                🔑 Issuer Identity (DID + PQC Key)
            </h2>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-purple-700 bg-purple-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-purple-300">PQC</span>
                <span class="rounded-full border border-blue-700 bg-blue-900/30 px-2.5 py-0.5 text-[10px] font-semibold text-blue-300">KAZ-SIGN v1</span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 text-[10px]">
            <div class="rounded-lg bg-slate-800/60 px-3 py-2 space-y-0.5">
                <p class="text-slate-500 uppercase tracking-wider font-semibold">Organisation</p>
                <p class="text-slate-200"><?= htmlspecialchars($issuer_profile['organisation'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="rounded-lg bg-slate-800/60 px-3 py-2 space-y-0.5">
                <p class="text-slate-500 uppercase tracking-wider font-semibold">Algorithm</p>
                <p class="text-purple-300">KAZ-SIGN (Post-Quantum)</p>
            </div>
            <div class="rounded-lg bg-slate-800/60 px-3 py-2 space-y-0.5">
                <p class="text-slate-500 uppercase tracking-wider font-semibold">Key Status</p>
                <p class="<?= !empty($_SESSION['private_key']) ? 'text-emerald-400' : 'text-yellow-400' ?>">
                    <?= !empty($_SESSION['private_key']) ? 'Active — Signing ON' : 'No key in session' ?>
                </p>
            </div>
        </div>

        <!-- DID -->
        <div class="space-y-1.5">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">
                Decentralized Identifier (DID)
            </p>
            <div class="flex items-center gap-2">
                <code class="flex-1 rounded-lg bg-slate-800 px-4 py-2.5 text-xs font-mono text-emerald-300 break-all">
                    <?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>
                </code>
                <button onclick="copyText('<?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>', this)"
                        class="shrink-0 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-[10px]
                               font-semibold text-slate-300 hover:border-emerald-600 hover:text-emerald-300 transition-all">
                    Copy DID
                </button>
            </div>
        </div>

        <!-- Public key preview -->
        <?php if (!empty($issuer_profile['public_key'])): ?>
        <div class="space-y-1.5">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">
                PQC Public Key (KAZ-SIGN)
            </p>
            <div class="rounded-lg bg-slate-800 px-4 py-2.5 text-[10px] font-mono text-slate-400 break-all">
                <?= htmlspecialchars(substr($issuer_profile['public_key'], 0, 80), ENT_QUOTES, 'UTF-8') ?>…
                <button onclick="togglePubKey()" class="ml-2 text-emerald-500 hover:underline">show full</button>
            </div>
            <div id="full-pubkey" class="hidden rounded-lg bg-slate-800 px-4 py-2.5 text-[10px] font-mono text-slate-400 break-all">
                <?= htmlspecialchars($issuer_profile['public_key'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Issue credential form -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-100">Issue a Verifiable Credential</h2>
            <p class="text-xs text-slate-500 mt-1">Select a holder and credential type, fill in the fields, then sign and issue.</p>
        </div>

        <?php if (empty($holders)): ?>
            <div class="rounded-lg border border-yellow-800 bg-yellow-900/20 px-4 py-3 text-xs text-yellow-300">
                No holders registered yet. Ask a holder to create an account first.
            </div>
        <?php else: ?>
        <form action="<?= $base ?>/credentials/issue" method="POST" class="space-y-6" id="issue-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="subject_data" id="subject_data_hidden" />

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Holder</label>
                    <select name="holder_id" required
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                                   text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
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
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                                   text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        <option value="AcademicCredential">Academic Credential</option>
                        <option value="EmploymentCredential">Employment Credential</option>
                        <option value="IdentityCredential">Identity Credential</option>
                        <option value="MedicalCredential">Medical Credential</option>
                    </select>
                </div>
            </div>

            <?php foreach ($credentialFields as $credType => $fields): ?>
            <div id="fields-<?= $credType ?>"
                 class="space-y-4 <?= $credType !== 'AcademicCredential' ? 'hidden' : '' ?>">
                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold border-b border-slate-800 pb-2">
                    Subject Fields
                </p>
                <?php foreach (array_chunk($fields, 2) as $pair): ?>
                <div class="grid grid-cols-<?= count($pair) === 2 ? '2' : '1' ?> gap-4">
                    <?php foreach ($pair as $f): ?>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            <?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <?php if ($f['type'] === 'textarea'): ?>
                        <textarea data-field-key="<?= htmlspecialchars($f['key'], ENT_QUOTES, 'UTF-8') ?>"
                                  data-cred-type="<?= $credType ?>"
                                  placeholder="<?= htmlspecialchars($f['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                                  rows="3"
                                  class="subject-field w-full rounded-lg border border-slate-700 bg-slate-800
                                         px-4 py-2.5 text-sm text-slate-100 placeholder-slate-600 resize-none
                                         focus:border-emerald-500 focus:outline-none"></textarea>
                        <?php else: ?>
                        <input type="text"
                               data-field-key="<?= htmlspecialchars($f['key'], ENT_QUOTES, 'UTF-8') ?>"
                               data-cred-type="<?= $credType ?>"
                               placeholder="<?= htmlspecialchars($f['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                               class="subject-field w-full rounded-lg border border-slate-700 bg-slate-800
                                      px-4 py-2.5 text-sm text-slate-100 placeholder-slate-600
                                      focus:border-emerald-500 focus:outline-none" />
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

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
        <h2 class="text-sm font-semibold text-slate-100">
            Issued Credentials
            <span class="ml-2 rounded-full border border-slate-700 bg-slate-800 px-2 py-0.5 text-[10px] text-slate-500">
                <?= count($credentials) ?>
            </span>
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
                        $jld        = json_decode($c['jsonld'] ?? '{}', true) ?: [];
                        $type       = implode(', ', array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential'));
                        $holderName = $c['holder_name'] ?? 'Unknown';
                        $s          = $c['status'] ?? 'issued';
                    ?>
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-slate-500"><?= (int)$c['id'] ?></td>
                            <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($holderName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($type ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 font-mono text-slate-500 max-w-[160px] truncate"
                                title="<?= htmlspecialchars($c['credential_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(substr($c['credential_id'] ?? '', 0, 26), ENT_QUOTES, 'UTF-8') ?>…
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                                    <?= $s === 'verified' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400'
                                      : ($s === 'rejected' ? 'border-red-800 bg-red-950/40 text-red-400'
                                      : 'border-blue-700 bg-blue-900/30 text-blue-400') ?>">
                                    <?= ucfirst($s) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars(substr($c['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button onclick="toggleJsonLd(<?= (int)$c['id'] ?>, this)"
                                        class="text-emerald-400 hover:underline text-[10px]">View JSON-LD</button>
                            </td>
                        </tr>
                        <tr id="jsonld-<?= (int)$c['id'] ?>" class="hidden">
                            <td colspan="7" class="px-4 py-4 bg-slate-800/60">
                                <pre class="text-[10px] text-emerald-300 whitespace-pre-wrap break-all leading-relaxed"><?= htmlspecialchars(
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
<!-- ============================================================ HOLDER -->

    <!-- Holder DID info -->
    <div class="rounded-xl border border-emerald-800/50 bg-emerald-900/10 p-4 space-y-2">
        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Your Decentralized Identifier (DID)</p>
        <div class="flex items-center gap-2">
            <code class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-xs font-mono text-emerald-300 break-all">
                <?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>
            </code>
            <button onclick="copyText('<?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>', this)"
                    class="shrink-0 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-[10px]
                           font-semibold text-slate-300 hover:border-emerald-600 hover:text-emerald-300 transition-all">
                Copy
            </button>
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="text-sm font-semibold text-slate-100">
            My Credentials
            <span class="ml-2 rounded-full border border-slate-700 bg-slate-800 px-2 py-0.5 text-[10px] text-slate-500">
                <?= count($credentials) ?>
            </span>
        </h2>
        <?php if (empty($credentials)): ?>
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-16 text-center space-y-2">
                <p class="text-sm text-slate-500">No credentials issued to you yet.</p>
                <p class="text-xs text-slate-600">An issuer will send credentials once they create one for you.</p>
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
            ?>
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-400">
                                <?= htmlspecialchars($type ?: 'VerifiableCredential', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Issued by <span class="text-slate-300"><?= htmlspecialchars($issuerName, ENT_QUOTES, 'UTF-8') ?></span>
                                · <?= htmlspecialchars(substr($c['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-[10px] text-slate-600 font-mono mt-0.5 truncate max-w-xs"
                               title="<?= htmlspecialchars($issuerDid, ENT_QUOTES, 'UTF-8') ?>">
                                Issuer DID: <?= htmlspecialchars($issuerDid, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                            <?= $s === 'verified' ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400'
                              : ($s === 'rejected' ? 'border-red-800 bg-red-950/40 text-red-400'
                              : 'border-blue-700 bg-blue-900/30 text-blue-400') ?>">
                            <?= ucfirst($s) ?>
                        </span>
                    </div>

                    <div class="rounded-lg bg-slate-800 p-4 space-y-1.5">
                        <?php if (empty($subject)): ?>
                            <p class="text-xs text-slate-500">No subject data.</p>
                        <?php else: ?>
                            <?php foreach ($subject as $key => $val): ?>
                                <?php if ($key === 'id') continue; ?>
                                <div class="flex gap-4 text-xs">
                                    <span class="text-slate-500 w-36 shrink-0"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-slate-300"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <code class="text-[10px] font-mono text-slate-600 truncate flex-1"
                              title="<?= htmlspecialchars($c['credential_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($c['credential_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </code>
                        <div class="flex items-center gap-3 shrink-0">
                            <button onclick="copyText('<?= htmlspecialchars($c['credential_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>', this)"
                                    class="text-[10px] text-slate-400 hover:text-slate-200 hover:underline">Copy ID</button>
                            <button onclick="toggleJsonLd(<?= (int)$c['id'] ?>, this)"
                                    class="text-[10px] text-emerald-400 hover:underline">View JSON-LD</button>
                        </div>
                    </div>

                    <div id="jsonld-<?= (int)$c['id'] ?>" class="hidden">
                        <pre class="rounded-lg bg-slate-800 p-4 text-[10px] text-emerald-300
                                    whitespace-pre-wrap break-all leading-relaxed overflow-x-auto"><?= htmlspecialchars(
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
<!-- ============================================================ VERIFIER -->

    <!-- Verifier DID info -->
    <div class="rounded-xl border border-purple-800/50 bg-purple-900/10 p-4 space-y-2">
        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Your Decentralized Identifier (DID)</p>
        <div class="flex items-center gap-2">
            <code class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-xs font-mono text-purple-300 break-all">
                <?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>
            </code>
            <button onclick="copyText('<?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?>', this)"
                    class="shrink-0 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-[10px]
                           font-semibold text-slate-300 hover:border-purple-600 hover:text-purple-300 transition-all">
                Copy
            </button>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-5">
        <div>
            <h2 class="text-sm font-semibold text-slate-100">Verify a Credential</h2>
            <p class="text-xs text-slate-500 mt-1">Enter the Credential ID given by the holder to verify its authenticity.</p>
        </div>
        <form action="<?= $base ?>/credentials/verify" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Credential ID</label>
                <input name="credential_id" type="text" required
                       placeholder="urn:uuid:xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                              text-sm font-mono text-slate-100 focus:border-purple-500 focus:outline-none" />
            </div>
            <button type="submit"
                    class="rounded-lg bg-purple-600 px-5 py-2 text-xs font-bold uppercase tracking-widest
                           text-white hover:bg-purple-500 active:scale-95 transition-all">
                Verify Credential
            </button>
        </form>
    </div>

    <?php if (!empty($result)):
        $cred  = $result['credential'] ?? [];
        $jld   = $result['jsonld']     ?? [];
        $subj  = $jld['credentialSubject'] ?? [];
        $types = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
    ?>
    <div class="rounded-xl border <?= ($result['verified'] ?? false) ? 'border-emerald-700' : 'border-red-800' ?>
                bg-slate-900 p-6 space-y-5">
        <h2 class="text-sm font-semibold <?= ($result['verified'] ?? false) ? 'text-emerald-300' : 'text-red-300' ?>">
            <?= ($result['verified'] ?? false) ? '✓ Credential Verified' : '✗ Verification Failed' ?>
        </h2>
        <div class="space-y-2">
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">File integrity (SHA-256)</span>
                <span class="text-[10px] font-bold rounded-full px-3 py-0.5 border
                    <?= ($result['hash_intact'] ?? false) ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400' : 'border-red-800 bg-red-950/40 text-red-400' ?>">
                    <?= ($result['hash_intact'] ?? false) ? 'Intact' : 'MODIFIED' ?>
                </span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">KAZ-SIGN PQC Signature</span>
                <span class="text-[10px] font-bold rounded-full px-3 py-0.5 border
                    <?= ($result['signature_valid'] ?? false) ? 'border-emerald-700 bg-emerald-900/30 text-emerald-400' : 'border-red-800 bg-red-950/40 text-red-400' ?>">
                    <?= ($result['signature_valid'] ?? false) ? 'Valid' : 'Invalid' ?>
                </span>
            </div>
        </div>
        <div class="rounded-lg bg-slate-800 p-4 space-y-2">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mb-2">Credential Details</p>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-36 shrink-0">Type</span>
                <span class="text-slate-300"><?= htmlspecialchars(implode(', ', $types) ?: '—', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-36 shrink-0">Issuer DID</span>
                <span class="text-slate-300 font-mono break-all"><?= htmlspecialchars($jld['issuer']['id'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-36 shrink-0">Issued by</span>
                <span class="text-slate-300"><?= htmlspecialchars($jld['issuer']['name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex gap-4 text-xs">
                <span class="text-slate-500 w-36 shrink-0">Issued on</span>
                <span class="text-slate-300"><?= htmlspecialchars(substr($cred['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php foreach ($subj as $key => $val): ?>
                <?php if ($key === 'id') continue; ?>
                <div class="flex gap-4 text-xs">
                    <span class="text-slate-500 w-36 shrink-0"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-slate-300"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div>
            <button onclick="toggleJsonLd('verify-result', this)"
                    class="text-xs text-slate-500 hover:text-slate-300">▶ Show full JSON-LD</button>
            <div id="jsonld-verify-result" class="hidden mt-3">
                <pre class="rounded-lg bg-slate-800 p-4 text-[10px] text-emerald-300
                            whitespace-pre-wrap break-all leading-relaxed overflow-x-auto"><?= htmlspecialchars(
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

function toggleJsonLd(id, btn) {
    const el     = document.getElementById('jsonld-' + id);
    const hidden = el.classList.toggle('hidden');
    if (btn) {
        btn.textContent = hidden
            ? btn.textContent.replace('Hide','View').replace('▼','▶')
            : btn.textContent.replace('View','Hide').replace('▶','▼');
    }
}

function togglePubKey() {
    const el = document.getElementById('full-pubkey');
    el.classList.toggle('hidden');
}

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}
</script>
</body>
</html>