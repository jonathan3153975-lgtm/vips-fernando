<?php
/** @var array<string, mixed> $settings */
/** @var \App\Support\TenantFormatter $formatter */
/** @var string $csrfField */
/** @var bool $canManageSettings */
/** @var array<string, mixed>|null $currentUser */

$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$activeNav = 'settings';

$currencies = ['BRL' => 'Real (BRL)', 'USD' => 'Dolar (USD)', 'EUR' => 'Euro (EUR)', 'GBP' => 'Libra (GBP)'];

$timezones = [
    'America/Sao_Paulo' => 'America/Sao_Paulo (Brasilia)',
    'America/Manaus' => 'America/Manaus',
    'America/Fortaleza' => 'America/Fortaleza',
    'America/Rio_Branco' => 'America/Rio_Branco',
    'America/New_York' => 'America/New_York',
    'America/Mexico_City' => 'America/Mexico_City',
    'Europe/Lisbon' => 'Europe/Lisbon',
    'Europe/London' => 'Europe/London',
    'Europe/Madrid' => 'Europe/Madrid',
    'UTC' => 'UTC',
];

$languages = ['pt-BR' => 'Portugues (pt-BR)', 'en-US' => 'English (en-US)', 'es-ES' => 'Espanol (es-ES)'];

$dateFormats = [
    'd/m/Y' => '31/12/2026 (d/m/Y)',
    'd/m/Y H:i' => '31/12/2026 23:59 (d/m/Y H:i)',
    'd-m-Y' => '31-12-2026 (d-m-Y)',
    'Y-m-d' => '2026-12-31 (Y-m-d)',
    'm/d/Y' => '12/31/2026 (m/d/Y)',
];

$currentCurrency = (string) ($settings['currency'] ?? 'BRL');
$currentTimezone = (string) ($settings['timezone'] ?? 'America/Sao_Paulo');
$currentLanguage = (string) ($settings['language'] ?? 'pt-BR');
$currentFormat = (string) ($settings['date_format'] ?? 'd/m/Y');

// Valor atual fora das listas nao pode sumir do select.
if (!isset($currencies[$currentCurrency])) {
    $currencies[$currentCurrency] = $currentCurrency;
}
if (!isset($timezones[$currentTimezone])) {
    $timezones = [$currentTimezone => $currentTimezone] + $timezones;
}
if (!isset($languages[$currentLanguage])) {
    $languages[$currentLanguage] = $currentLanguage;
}
if (!isset($dateFormats[$currentFormat])) {
    $dateFormats[$currentFormat] = $currentFormat;
}

$sampleDate = '2026-09-27 14:30:00';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracoes | ImportControl</title>
    <?php require __DIR__ . '/../partials/styles.php'; ?>
</head>
<body>
    <header>
        <div>
            <div class="eyebrow">Tenant</div>
            <strong><?= $e($currentUser['tenant_name'] ?? '') ?></strong>
        </div>
        <form method="post" action="/logout">
            <?= $csrfField ?>
            <button type="submit">Sair</button>
        </form>
    </header>

    <div class="shell">
        <?php require __DIR__ . '/../partials/notice.php'; ?>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <main>
            <section class="card">
                <div class="eyebrow">Administracao / configuracoes</div>
                <h1>Configuracoes do tenant</h1>
                <p class="muted">
                    Moeda, fuso horario, idioma e formato de data usados na exibicao do sistema.
                </p>
            </section>

            <section class="card">
                <h2>Como aparece hoje</h2>
                <p>
                    <span class="badge">Data: <?= $e($formatter->date($sampleDate, true)) ?></span>
                    <span class="badge">Valor: <?= $e($formatter->money(1234.5)) ?></span>
                    <span class="badge">Fuso: <?= $e($formatter->timezone()) ?></span>
                </p>
                <p class="muted">Previa a partir das configuracoes salvas.</p>
            </section>

            <section class="card">
                <h2>Editar</h2>
                <?php if (!$canManageSettings): ?>
                    <p class="muted">Voce nao tem permissao para alterar as configuracoes.</p>
                <?php else: ?>
                    <form method="post" action="/configuracoes">
                        <?= $csrfField ?>
                        <div class="grid">
                            <label>
                                <div class="eyebrow">Moeda</div>
                                <select name="currency">
                                    <?php foreach ($currencies as $code => $label): ?>
                                        <option value="<?= $e($code) ?>"<?= $code === $currentCurrency ? ' selected' : '' ?>>
                                            <?= $e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <div class="eyebrow">Fuso horario</div>
                                <select name="timezone">
                                    <?php foreach ($timezones as $zone => $label): ?>
                                        <option value="<?= $e($zone) ?>"<?= $zone === $currentTimezone ? ' selected' : '' ?>>
                                            <?= $e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <div class="eyebrow">Idioma</div>
                                <select name="language">
                                    <?php foreach ($languages as $code => $label): ?>
                                        <option value="<?= $e($code) ?>"<?= $code === $currentLanguage ? ' selected' : '' ?>>
                                            <?= $e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <div class="eyebrow">Formato de data</div>
                                <select name="date_format">
                                    <?php foreach ($dateFormats as $format => $label): ?>
                                        <option value="<?= $e($format) ?>"<?= $format === $currentFormat ? ' selected' : '' ?>>
                                            <?= $e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>

                        <div class="row" style="margin-top: 16px;">
                            <button type="submit">Salvar configuracoes</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
