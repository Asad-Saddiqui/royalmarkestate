<?php
$heroCompanyPayload=Database::getConnection()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();
$heroCompany=json_decode($heroCompanyPayload?:'{}',true);
$heroEmail=is_array($heroCompany)&&is_string($heroCompany['email']??null)?trim($heroCompany['email']):'';
if(!filter_var($heroEmail,FILTER_VALIDATE_EMAIL)||str_ends_with(strtolower($heroEmail),'@example.com'))$heroEmail='royalmarkestate@gmail.com';
?>
<link rel="stylesheet" href="./assets/home-contact-buttons.css?v=2">
<div class="rm-hero-contact" aria-label="Contact Royalmark Estate">
<a class="rm-hero-contact-button" href="./contact-us"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/><path d="M8 12h8M8 8h5"/></svg><span>Contact Us</span></a>
<a class="rm-hero-contact-button" href="mailto:<?= e($heroEmail) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><span>Email Us</span></a>
</div>

