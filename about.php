<?php
require __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/company-profile.php';

$aboutCompany=publicCompanyProfile();
$pageTitle='About';
$activePage='about';
$mainClass='company-about';
$extraCss=['./assets/about.css'];
$extraJs=['./assets/about-scroll.js'];
$aboutIcon=static function(string $name):string{
    $paths=[
        'arrow'=>'<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'location'=>'<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'home'=>'<path d="m3 10 9-7 9 7v10H15v-7H9v7H3Z"/>',
        'projects'=>'<path d="M3 21V7l6-3v17M9 21V3h8v18M17 9h4v12M2 21h20M12 7h2M12 11h2M12 15h2"/>',
        'people'=>'<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6M18 21v-3a6 6 0 0 0-2-4"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'email'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'phone'=>'<path d="M8 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-4l-5-2-2 2a14 14 0 0 1-6-6l2-2-2-5Z"/>',
        'whatsapp'=>'<path d="m3 21 1.5-5a9 9 0 1 1 3.7 3.6L3 21Z"/><path d="m9 7-1 1c0 4 4 8 8 8l1-1-2-2-1 1a7 7 0 0 1-4-4l1-1-2-2Z"/>',
        'globe'=>'<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>',
        'facebook'=>'<path d="M14 21V13h3l.5-4H14V7a2 2 0 0 1 2-2h2V2h-3a5 5 0 0 0-5 5v2H7v4h3v8"/>',
        'instagram'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'linkedin'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 10v7M7 7h.01M11 17v-7m0 3a3 3 0 0 1 6 0v4"/>',
        'tiktok'=>'<path d="M14 3v12a5 5 0 1 1-4-4M14 3c0 4 3 6 6 6M14 7c2 3 4 3 6 3"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??'').'</svg>';
};
$aboutServices=[
    ['home','Find your next property','Explore homes and commercial spaces. Compare locations, features and prices to find an option that fits your needs.','Browse properties','./property-listing'],
    ['projects','Explore new possibilities','Discover projects, review their available plans and information, and ask the questions that matter to you.','Explore projects','./projects'],
    ['people','Connect with the right people','Meet our agents and share your requirements for help with buying, selling, renting or arranging a property visit.','Meet our agents','./agents'],
];
$aboutValues=[
    ['Your needs come first','Tell us about your preferred location, budget and the spaces that matter to you.'],
    ['Details you can explore','Review property information, photos, amenities and available documents before taking the next step.'],
    ['A conversation at every step','Ask questions, discuss your options and request a viewing with our team.'],
];
require __DIR__.'/includes/header.php';
?>
<section class="company-about-hero about-reveal" aria-labelledby="about-title">
    <div class="company-about-copy">
        <span class="eyebrow">ABOUT <?= e($aboutCompany['name']) ?></span>
        <h1 id="about-title">Property decisions,<br><em>made personal.</em></h1>
        <p class="company-about-tagline"><?= e($aboutCompany['tagline']) ?></p>
        <div class="company-about-description"><?php foreach(preg_split('/\R\s*\R/u',$aboutCompany['description'])?:[] as $paragraph): ?><p><?= nl2br(e($paragraph)) ?></p><?php endforeach; ?></div>
        <div class="company-about-actions"><a class="button" href="./contact-us">Talk to our team <?= $aboutIcon('arrow') ?></a><a class="company-about-secondary" href="./property-listing">Explore properties <?= $aboutIcon('arrow') ?></a></div>
        <?php if($aboutCompany['location']!==''): ?><div class="company-about-location"><?= $aboutIcon('location') ?><span><?= e($aboutCompany['location']) ?></span></div><?php endif; ?>
    </div>
    <figure class="company-about-visual">
        <img src="./assets/site-media/2384b258a862dba546.png" width="1536" height="1024" alt="Modern residential architecture in warm evening light" fetchpriority="high" decoding="async">
        <figcaption class="company-about-image-note"><strong><?= e($aboutCompany['name']) ?></strong><small><?= e($aboutCompany['tagline']) ?></small></figcaption>
    </figure>
</section>

<section class="company-about-section about-reveal" aria-labelledby="about-services-title">
    <div class="company-about-heading"><span class="eyebrow">HOW WE CAN HELP</span><h2 id="about-services-title">Room for every kind of move.</h2><p>From your first search to your next enquiry, explore the options that fit your plans.</p></div>
    <div class="company-about-service-grid"><?php foreach($aboutServices as [$icon,$title,$text,$link,$url]): ?>
        <a class="company-about-service" href="<?= e($url) ?>"><span class="company-about-service-icon"><?= $aboutIcon($icon) ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p><span class="company-about-service-link"><?= e($link) ?> <?= $aboutIcon('arrow') ?></span></a>
    <?php endforeach; ?></div>
</section>

<section class="company-about-story company-about-section about-reveal" aria-labelledby="about-approach-title">
    <figure><img src="./assets/site-media/2a691d3def925ca222.png" width="1536" height="1024" alt="A bright, welcoming living room with natural light" loading="lazy" decoding="async"></figure>
    <div><span class="eyebrow">OUR APPROACH</span><h2 id="about-approach-title">Clear information.<br>A confident next step.</h2><p>A property is more than an address. It is a place to live, work or build your next chapter. At <?= e($aboutCompany['name']) ?>, we start by understanding what you are looking for.</p>
        <ul class="company-about-values"><?php foreach($aboutValues as [$title,$text]): ?><li><span class="company-about-value-icon"><?= $aboutIcon('check') ?></span><div><strong><?= e($title) ?></strong><p><?= e($text) ?></p></div></li><?php endforeach; ?></ul>
    </div>
</section>

<section class="company-about-section about-reveal" aria-labelledby="about-process-title">
    <div class="company-about-heading"><span class="eyebrow">YOUR PROPERTY JOURNEY</span><h2 id="about-process-title">A simpler way to take the next step.</h2></div>
    <ol class="company-about-process">
        <li><span class="company-about-step" aria-hidden="true">01</span><h3>Share what you need</h3><p>Start with your preferred area, property type and budget. We are here to listen.</p></li>
        <li><span class="company-about-step" aria-hidden="true">02</span><h3>Explore your options</h3><p>Browse properties and projects, review the details and make your own shortlist.</p></li>
        <li><span class="company-about-step" aria-hidden="true">03</span><h3>Start a conversation</h3><p>Send an enquiry or request a visit. Our team can help you discuss what comes next.</p></li>
    </ol>
</section>

<section class="company-about-office company-about-section about-reveal" aria-labelledby="about-company-title">
    <div class="company-about-office-copy"><span class="eyebrow">OUR COMPANY</span><h2 id="about-company-title"><?= e($aboutCompany['name']) ?></h2><p><?= e($aboutCompany['tagline']) ?></p><p>Whether you have a specific property in mind or are just beginning your search, get in touch to discuss your plans.</p>
        <?php if($aboutCompany['facebook']!==''||$aboutCompany['instagram']!==''||$aboutCompany['linkedin']!==''||$aboutCompany['tiktok']!==''): ?><div class="company-about-socials" aria-label="Company social profiles"><?php foreach(['facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','tiktok'=>'TikTok'] as $key=>$label): ?><?php if($aboutCompany[$key]!==''): ?><a href="<?= e($aboutCompany[$key]) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($aboutCompany['name'].' on '.$label) ?>"><?= $aboutIcon($key) ?></a><?php endif; ?><?php endforeach; ?></div><?php endif; ?>
    </div>
    <dl class="company-about-details">
        <?php if($aboutCompany['address']!==''||$aboutCompany['location']!==''): ?><div class="company-about-detail-wide"><dt><?= $aboutIcon('location') ?> <?= $aboutCompany['address']!==''?'Office location':'Location' ?></dt><dd><?php if($aboutCompany['address']!==''): ?><?= nl2br(e($aboutCompany['address'])) ?><br><?php endif; ?><?= e($aboutCompany['location']) ?><?php if($aboutCompany['address']!==''&&$aboutCompany['zip']!==''): ?> <?= e($aboutCompany['zip']) ?><?php endif; ?></dd></div><?php endif; ?>
        <?php if($aboutCompany['hours']!==''): ?><div class="company-about-detail-wide"><dt><?= $aboutIcon('clock') ?> Office hours</dt><dd><?= e($aboutCompany['hours']) ?></dd></div><?php endif; ?>
        <div class="company-about-detail-wide"><dt><?= $aboutIcon('email') ?> Email</dt><dd><a href="mailto:<?= e($aboutCompany['email']) ?>"><?= e($aboutCompany['email']) ?></a></dd></div>
        <?php if($aboutCompany['phone']!==''): ?><div><dt><?= $aboutIcon('phone') ?> Phone</dt><dd><a href="<?= e($aboutCompany['phone_url']) ?>"><?= e($aboutCompany['phone']) ?></a></dd></div><?php endif; ?>
        <?php if($aboutCompany['whatsapp']!==''): ?><div><dt><?= $aboutIcon('whatsapp') ?> WhatsApp</dt><dd><a href="<?= e($aboutCompany['whatsapp_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($aboutCompany['whatsapp']) ?></a></dd></div><?php endif; ?>
        <?php if($aboutCompany['website']!==''): ?><div class="company-about-detail-wide"><dt><?= $aboutIcon('globe') ?> Website</dt><dd><a href="<?= e($aboutCompany['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($aboutCompany['website_label']) ?> &nearr;</a></dd></div><?php endif; ?>
    </dl>
</section>

<section class="company-about-cta about-reveal" aria-labelledby="about-contact-title"><div><h2 id="about-contact-title">Let us talk about your next move.</h2><p>Tell us what you are looking for. The next step starts with a conversation.</p></div><div class="company-about-actions"><a class="button" href="./contact-us">Contact us <?= $aboutIcon('arrow') ?></a><a class="company-about-secondary" href="mailto:<?= e($aboutCompany['email']) ?>"><?= $aboutIcon('email') ?> Email us</a></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
