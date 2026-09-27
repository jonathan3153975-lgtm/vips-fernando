<?php
/**
 * Aviso de resultado de um POST (PRG). Le e descarta o flash, entao aparece
 * uma unica vez mesmo com F5.
 *
 * @var callable $e
 */
$notice = $session->pull('notice');
$error = $session->pull('error');

if (is_string($notice) && $notice !== ''):
    ?>
    <div class="card notice ok"><?= $e($notice) ?></div>
<?php endif; ?>

<?php if (is_string($error) && $error !== ''): ?>
    <div class="card notice bad"><?= $e($error) ?></div>
<?php endif; ?>
