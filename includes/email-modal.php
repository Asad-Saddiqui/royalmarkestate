<?php if(defined('RM_EMAIL_MODAL'))return;define('RM_EMAIL_MODAL',true); ?>
<link rel="stylesheet" href="./assets/email-modal.css?v=<?= filemtime(dirname(__DIR__).'/assets/email-modal.css') ?>">
<script src="./assets/email-modal.js?v=<?= filemtime(dirname(__DIR__).'/assets/email-modal.js') ?>" defer></script>
<dialog class="rm-email-modal" aria-labelledby="rm-email-title" data-email-endpoint="./email-enquiry.php">
    <header><div><span>GET IN TOUCH</span><h2 id="rm-email-title">Send an email</h2></div><button type="button" data-email-close aria-label="Close email form">&times;</button></header>
    <div class="rm-email-body"><div class="rm-email-context" data-email-context></div><p data-email-feedback role="status" tabindex="-1"></p>
    <form data-email-form method="post"><input type="hidden" name="csrf"><input type="hidden" name="request_id">
    <div class="rm-email-fields"><label>Your name<input name="name" maxlength="100" autocomplete="name" required></label><label>Email address<input name="email" type="email" maxlength="254" autocomplete="email" required></label></div><label>Phone (optional)<input name="phone" type="tel" maxlength="40" autocomplete="tel"></label><label>Subject<input name="subject" maxlength="150" required></label><label>Your message<textarea name="message" rows="5" minlength="10" maxlength="5000" required placeholder="Tell us how we can help..."></textarea></label><button type="submit" data-email-send>Send email</button><small>Your email is sent directly. This message is not saved in our database.</small></form></div>
</dialog>
