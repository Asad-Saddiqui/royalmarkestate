<?php
require_once dirname(__DIR__).'/Config/email-compose.php';
$heroCompanyPayload=Database::getConnection()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();
$heroCompany=json_decode($heroCompanyPayload?:'{}',true);
$heroEmail=is_array($heroCompany)&&is_string($heroCompany['email']??null)?trim($heroCompany['email']):'';
if(!filter_var($heroEmail,FILTER_VALIDATE_EMAIL)||str_ends_with(strtolower($heroEmail),'@example.com'))$heroEmail='royalmarkestate@gmail.com';
?>
<div class="rm-hero-actions">
<a class="rm-hero-primary" href="./property-listing">
    <span class="rm-hero-primary-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 7 5 5-5 5m5-10 5 5-5 5"/></svg></span>
    <span>See All Properties</span>
    <svg class="rm-hero-primary-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
</a>
<div class="rm-hero-contact" aria-label="Contact Royalmark Estate">
<a class="rm-hero-contact-button rm-hero-contact-button--contact" href="./contact-us">
    <span class="rm-hero-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/><path d="M8 12h8M8 8h5"/></svg></span>
    <span class="rm-hero-contact-label">Contact Us</span>
    <svg class="rm-hero-contact-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
</a>
<a class="rm-hero-contact-button rm-hero-contact-button--email" data-email-direct href="<?= e(emailComposeUrl($heroEmail,'General enquiry')) ?>" target="_blank" rel="noopener noreferrer">
    <span class="rm-hero-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
    <span class="rm-hero-contact-label">Email Us</span>
    <svg class="rm-hero-contact-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
</a>
</div>
</div>

