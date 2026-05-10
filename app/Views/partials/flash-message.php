<?php
$successMessage = $app->session()->getFlash('success');
$errorMessage = $app->session()->getFlash('error');
$infoMessage = $app->session()->getFlash('info');

$renderFlash = function (string $type, mixed $message): void {
    if ($message === null || $message === '') {
        return;
    }

    $messages = is_array($message) ? $message : [$message];

    foreach ($messages as $item) {
        if (!is_string($item) || trim($item) === '') {
            continue;
        }
        ?>
        <div class="flash-message flash-<?= $this->escape($type) ?>">
            <?= $this->escape($item) ?>
        </div>
        <?php
    }
};

$renderFlash('success', $successMessage);
$renderFlash('error', $errorMessage);
$renderFlash('info', $infoMessage);
?>
