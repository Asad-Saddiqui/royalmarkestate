<?php
require_once dirname(__DIR__).'/Config/booking-captcha.php';
$captchaId = bookingCaptchaCreate($bookingCaptchaScope);
$captchaInputId = 'booking-code-'.$captchaId;
?>
<fieldset class="booking-captcha" data-booking-captcha data-scope="<?= e($bookingCaptchaScope) ?>" data-captcha-csrf="<?= e(bookingCaptchaToken()) ?>" data-endpoint="./booking-captcha.php?action=refresh">
<legend>Security check</legend>
<div class="booking-captcha-image-row"><img src="./booking-captcha.php?action=image&amp;id=<?= e($captchaId) ?>" width="224" height="76" alt="Five-character security code" data-captcha-image><button type="button" data-captcha-refresh aria-label="Get a new security code" title="Get a new code"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 7a7 7 0 0 1 12-1l2 3M4 15l2 3a7 7 0 0 0 12-1"/></svg></button></div>
<input type="hidden" name="captcha_id" value="<?= e($captchaId) ?>">
<label for="<?= e($captchaInputId) ?>">Type the code above<input id="<?= e($captchaInputId) ?>" name="captcha_answer" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" minlength="5" maxlength="5" required placeholder="5 characters" aria-describedby="<?= e($captchaInputId) ?>-hint" data-captcha-answer></label>
<small id="<?= e($captchaInputId) ?>-hint">Letters are not case sensitive. Code expires in 10 minutes.</small>
<span class="booking-captcha-feedback" aria-live="polite" data-captcha-feedback></span>
<noscript><small>Reload this page if you need a different code.</small></noscript>
</fieldset>
