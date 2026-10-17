<?php
require_once __DIR__.'/company-profile.php';
$footerCompany=publicCompanyProfile();
$footerDescription=preg_replace('/\s+/u',' ',$footerCompany['description'])??$footerCompany['description'];
if(mb_strlen($footerDescription)>240)$footerDescription=rtrim(mb_substr($footerDescription,0,237)).'…';
$footerSocials=array_filter([
    'facebook'=>['Facebook',$footerCompany['facebook']],
    'instagram'=>['Instagram',$footerCompany['instagram']],
    'linkedin'=>['LinkedIn',$footerCompany['linkedin']],
    'tiktok'=>['TikTok',$footerCompany['tiktok']],
],static fn(array $profile):bool=>$profile[1]!=='');
$footerIcon=static function(string $name):string{
    $paths=[
        'arrow'=>'<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'email'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone'=>'<path d="M8 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-4l-5-2-2 2a14 14 0 0 1-6-6l2-2-2-5Z"/>',
        'whatsapp'=>'<path d="m3 21 1.5-5a9 9 0 1 1 3.7 3.6L3 21Z"/><path d="m9 7-1 1c0 4 4 8 8 8l1-1-2-2-1 1a7 7 0 0 1-4-4l1-1-2-2Z"/>',
        'location'=>'<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'globe'=>'<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>',
        'facebook'=>'<path d="M14 21V13h3l.5-4H14V7a2 2 0 0 1 2-2h2V2h-3a5 5 0 0 0-5 5v2H7v4h3v8"/>',
        'instagram'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'linkedin'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 10v7M7 7h.01M11 17v-7m0 3a3 3 0 0 1 6 0v4"/>',
        'tiktok'=>'<path d="M14 3v12a5 5 0 1 1-4-4M14 3c0 4 3 6 6 6M14 7c2 3 4 3 6 3"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??'').'</svg>';
};
$footerOfficeParts=array_filter([$footerCompany['address'],$footerCompany['location']],static fn(string $part):bool=>$part!=='');
$footerOffice=implode("\n",$footerOfficeParts);
if($footerCompany['address']!==''&&$footerCompany['zip']!=='')$footerOffice.=' '.$footerCompany['zip'];
?>
<footer class="rm-company-footer" aria-label="Company footer">
    <div class="rm-footer-container">
        <div class="rm-footer-top"><div><span class="rm-footer-eyebrow">YOUR NEXT CHAPTER STARTS HERE</span><h2>Let us find your next address.</h2><p>Explore your options with <?= e($footerCompany['name']) ?>. Tell us what you have in mind.</p></div><a class="rm-footer-cta" href="./contact-us">Talk to our team <?= $footerIcon('arrow') ?></a></div>
        <div class="rm-footer-grid">
            <div class="rm-footer-brand">
                <a class="rm-footer-logo" href="./" aria-label="<?= e($footerCompany['name'].' home') ?>"><img src="./assets/royalmark-logo.png" alt="<?= e($footerCompany['name']) ?>" width="150" height="88" loading="lazy" decoding="async"></a>
                <strong class="rm-footer-company-name"><?= e($footerCompany['name']) ?></strong><p class="rm-footer-tagline"><?= e($footerCompany['tagline']) ?></p><p class="rm-footer-description"><?= e($footerDescription) ?></p>
                <?php if($footerSocials): ?><div class="rm-footer-socials" aria-label="Company social profiles"><?php foreach($footerSocials as $key=>[$label,$url]): ?><a data-social="<?= e($key) ?>" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($footerCompany['name'].' on '.$label) ?>" title="<?= e($label) ?>"><?= $footerIcon($key) ?></a><?php endforeach; ?></div><?php endif; ?>
            </div>
            <nav class="rm-footer-navigation" aria-labelledby="rm-footer-explore-title"><h3 id="rm-footer-explore-title">Explore</h3><ul><li><a href="./property-listing">Properties</a></li><li><a href="./projects">Projects</a></li><li><a href="./agents">Our agents</a></li><li><a href="./blog">Blog &amp; insights</a></li></ul></nav>
            <nav class="rm-footer-navigation" aria-labelledby="rm-footer-company-title"><h3 id="rm-footer-company-title">Company</h3><ul><li><a href="./about-us">About us</a></li><li><a href="./contact-us">Contact us</a></li><li><a href="mailto:<?= e($footerCompany['email']) ?>">Email our team</a></li></ul></nav>
            <div class="rm-footer-contact"><h3>Get in touch</h3><ul class="rm-footer-details">
                <li><span class="rm-footer-detail-icon"><?= $footerIcon('email') ?></span><div><span class="rm-footer-detail-label">Email</span><a href="mailto:<?= e($footerCompany['email']) ?>"><?= e($footerCompany['email']) ?></a></div></li>
                <?php if($footerCompany['phone']!==''): ?><li><span class="rm-footer-detail-icon"><?= $footerIcon('phone') ?></span><div><span class="rm-footer-detail-label">Phone</span><a href="<?= e($footerCompany['phone_url']) ?>"><?= e($footerCompany['phone']) ?></a></div></li><?php endif; ?>
                <?php if($footerCompany['whatsapp']!==''): ?><li><span class="rm-footer-detail-icon"><?= $footerIcon('whatsapp') ?></span><div><span class="rm-footer-detail-label">WhatsApp</span><a href="<?= e($footerCompany['whatsapp_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($footerCompany['whatsapp']) ?></a></div></li><?php endif; ?>
                <?php if($footerOffice!==''): ?><li><span class="rm-footer-detail-icon"><?= $footerIcon('location') ?></span><div><span class="rm-footer-detail-label"><?= $footerCompany['address']!==''?'Office location':'Location' ?></span><p><?= nl2br(e($footerOffice)) ?></p></div></li><?php endif; ?>
                <?php if($footerCompany['hours']!==''): ?><li><span class="rm-footer-detail-icon"><?= $footerIcon('clock') ?></span><div><span class="rm-footer-detail-label">Office hours</span><p><?= e($footerCompany['hours']) ?></p></div></li><?php endif; ?>
                <?php if($footerCompany['website']!==''): ?><li><span class="rm-footer-detail-icon"><?= $footerIcon('globe') ?></span><div><span class="rm-footer-detail-label">Website</span><a href="<?= e($footerCompany['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($footerCompany['website_label']) ?> &nearr;</a></div></li><?php endif; ?>
            </ul></div>
        </div>
        <div class="rm-footer-bottom"><small>&copy; <?= date('Y') ?> <?= e($footerCompany['name']) ?>. All rights reserved.</small><a href="./about-us">About our company <?= $footerIcon('arrow') ?></a></div>
    </div>
</footer>
<script src="./assets/company-footer.js?v=<?= filemtime(dirname(__DIR__).'/assets/company-footer.js') ?>" defer></script>
