<?php
require_once dirname(__DIR__).'/Config/company-team.php';
$aboutTeam=companyTeam();
$aboutCeo=null;$aboutMembers=[];
foreach($aboutTeam as $person){if($person['kind']==='ceo')$aboutCeo=$person;else $aboutMembers[]=$person;}
$teamPortrait=static function(array $person):void { ?>
    <div class="company-team-portrait"><?php if($person['photo']): ?><img src="<?= e($person['photo']) ?>" alt="<?= e($person['name']) ?>" width="480" height="540" loading="lazy"><?php else: ?><span aria-hidden="true"><?= e(mb_strtoupper(mb_substr($person['name'],0,1))) ?></span><?php endif; ?></div>
<?php };
$teamLinks=static function(array $person):void { ?>
    <?php if($person['email']||$person['linkedin']): ?><div class="company-team-links"><?php if($person['email']): ?><a href="mailto:<?= e($person['email']) ?>">Email <?= e($person['name']) ?></a><?php endif; ?><?php if($person['linkedin']): ?><a href="<?= e($person['linkedin']) ?>" target="_blank" rel="noopener noreferrer">Professional profile &nearr;</a><?php endif; ?></div><?php endif; ?>
<?php };
if($aboutTeam): ?>
<section class="company-about-section company-team" id="about-team" aria-labelledby="about-team-title">
    <header class="company-team-heading"><span class="eyebrow">THE PEOPLE BEHIND ROYALMARK</span><h2 id="about-team-title">Meet our leadership &amp; team</h2><p>Get to know the people helping you take your next property step.</p></header>
    <?php if($aboutCeo): ?><article class="company-team-ceo"><?php $teamPortrait($aboutCeo); ?><div class="company-team-ceo-copy"><span class="eyebrow">A MESSAGE FROM OUR CEO</span><h3><?= e($aboutCeo['name']) ?></h3><p class="company-team-position"><?= e($aboutCeo['position']) ?></p><?php if($aboutCeo['bio']): ?><p class="company-team-bio"><?= nl2br(e($aboutCeo['bio'])) ?></p><?php endif; ?><?php $teamLinks($aboutCeo); ?></div></article><?php endif; ?>
    <?php if($aboutMembers): ?><div class="company-team-grid"><?php foreach($aboutMembers as $person): ?><article class="company-team-card"><?php $teamPortrait($person); ?><div class="company-team-card-copy"><h3><?= e($person['name']) ?></h3><p class="company-team-position"><?= e($person['position']) ?></p><?php if($person['bio']): ?><p class="company-team-bio"><?= nl2br(e($person['bio'])) ?></p><?php endif; ?><?php $teamLinks($person); ?></div></article><?php endforeach; ?></div><?php endif; ?>
</section>
<?php endif; ?>
