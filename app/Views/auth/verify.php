<?php $layout = 'layouts/main'; ?>
<section class="auth-page">
    <h1>Email Verification</h1>
    <p><?= $this->escape($message ?? 'Your verification status is unavailable.') ?></p>
    <p><a href="/login">Continue to login</a></p>
</section>
