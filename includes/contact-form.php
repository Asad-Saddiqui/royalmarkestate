<?php
declare(strict_types=1);

function contactFormRender(array $state):void
{
    $values=$state['values'];
    $bookingCaptchaScope='contact:general';
    ?>
<section class="contact-message-panel" id="contact-message" aria-labelledby="contact-form-title">
    <div class="contact-form-heading"><span class="contact-kicker">WE'RE HERE TO HELP</span><h2 id="contact-form-title">Send us a message</h2><p>Fields marked <span aria-hidden="true">*</span> are required.</p></div>
    <?php if($state['success']): ?><div class="contact-feedback is-success" role="status" tabindex="-1"><strong>Thank you for getting in touch.</strong><p>Message received. Our team will review it.</p><?php if(($state['success']['reference']??'')!==''): ?><small>Your reference: <b><?= e($state['success']['reference']) ?></b></small><?php endif; ?></div><?php endif; ?>
    <?php if($state['errors']): ?><div class="contact-feedback is-error" role="alert" tabindex="-1"><strong>Please check your message.</strong><ul><?php foreach($state['errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form class="contact-form" method="post" action="./contact-us#contact-message" data-contact-form>
        <input type="hidden" name="csrf_token" value="<?= e($state['csrf']) ?>"><input type="hidden" name="request_id" value="<?= e($state['request_id']) ?>">
        <div class="contact-honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
            <label for="contact-name">Full name <span aria-hidden="true">*</span><input id="contact-name" name="name" autocomplete="name" maxlength="100" required value="<?= e($values['name']) ?>" placeholder="Your full name"></label>
            <label for="contact-email">Email address <span aria-hidden="true">*</span><input id="contact-email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?= e($values['email']) ?>" placeholder="you@example.com"></label>
            <label for="contact-phone">Phone number <small>(optional)</small><input id="contact-phone" name="phone" type="tel" autocomplete="tel" maxlength="40" value="<?= e($values['phone']) ?>" placeholder="Include country code"></label>
            <label for="contact-interest">Subject <small>(optional)</small><input id="contact-interest" name="interest" maxlength="120" value="<?= e($values['interest']) ?>" placeholder="Property, project or general question"></label>
        </div>
        <label for="contact-message-body">Your message <span aria-hidden="true">*</span><textarea id="contact-message-body" name="message" rows="5" minlength="10" maxlength="5000" required placeholder="Tell us how we can help..."><?= e($values['message']) ?></textarea><small>10&ndash;5,000 characters. Please include details that will help us answer your question.</small></label>
        <?php require __DIR__.'/booking-captcha.php'; ?>
        <div class="contact-submit-row"><button class="button contact-submit" type="submit"><span>Send message</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 3-7 18-4-7-7-4L21 3ZM21 3 10 14"/></svg></button><p>Your contact details stay with your message so our team can follow up.</p></div>
        <div class="contact-network-feedback contact-feedback is-error" hidden role="alert" tabindex="-1"></div>
    </form>
</section>
    <?php
}
