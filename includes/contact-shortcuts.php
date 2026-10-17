<?php
$contactPayload=Database::getConnection()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();
$contactCompany=json_decode($contactPayload?:'{}',true);if(!is_array($contactCompany))$contactCompany=[];
$contactEmail=is_string($contactCompany['email']??null)?trim($contactCompany['email']):'';
if(!filter_var($contactEmail,FILTER_VALIDATE_EMAIL)||str_ends_with(strtolower($contactEmail),'@example.com'))$contactEmail='royalmarkestate@gmail.com';
$contactPhone=is_string($contactCompany['whatsapp']??null)?preg_replace('/[^0-9]/','',$contactCompany['whatsapp']):'';
$contactWhatsapp=preg_match('/^[1-9][0-9]{7,14}$/',$contactPhone)?'https://wa.me/'.$contactPhone:'./contact-us';
?>
<link rel="stylesheet" href="./assets/contact-shortcuts.css?v=<?= filemtime(dirname(__DIR__).'/assets/contact-shortcuts.css') ?>">
<nav class="rm-contact-shortcuts" aria-label="Quick contact">
<a class="rm-contact-shortcut rm-contact-whatsapp" href="<?= e($contactWhatsapp) ?>" target="_blank" rel="noopener" aria-label="Chat with Royalmark Estate on WhatsApp" title="WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.9 11.9 0 0 0 12.05 0C5.46 0 .1 5.36.1 11.95c0 2.1.55 4.16 1.6 5.97L0 24l6.26-1.64a11.9 11.9 0 0 0 5.79 1.48h.01c6.59 0 11.95-5.36 11.95-11.95a11.86 11.86 0 0 0-3.49-8.41ZM12.06 21.82a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.72.98.99-3.63-.24-.37a9.88 9.88 0 0 1-1.52-5.26c0-5.48 4.45-9.93 9.9-9.93a9.83 9.83 0 0 1 7.03 2.91 9.84 9.84 0 0 1 2.9 7.03c0 5.47-4.45 9.86-9.94 9.86Zm5.45-7.42c-.3-.15-1.77-.88-2.04-.97-.28-.1-.48-.15-.68.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.8-1.49-1.78-1.66-2.08-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.59-.49-.51-.67-.52h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.09 4.49.71.31 1.27.49 1.71.63.72.23 1.37.2 1.88.12.57-.08 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35Z"/></svg><span class="rm-contact-tooltip">WhatsApp</span></a>
<a class="rm-contact-shortcut rm-contact-email" href="mailto:<?= e($contactEmail) ?>" aria-label="Email Royalmark Estate" title="Email"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><span class="rm-contact-tooltip">Email us</span></a>
</nav>

<?php require_once __DIR__.'/email-modal.php'; ?>
