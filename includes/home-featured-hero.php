<?php
if (empty($homeFeaturedProjects)) return;
require_once __DIR__.'/company-profile.php';
$featuredHeroEmail = publicCompanyProfile()['email'];

$featuredHeroText = static fn($value): string => is_scalar($value) ? trim((string) $value) : '';
$featuredHeroNumber = static fn($value): float => is_scalar($value) && is_numeric($value) && is_finite((float) $value) ? max(0, (float) $value) : 0;
$featuredHeroImage = static function ($value): string {
    if (!is_string($value) || strlen($value) > 2048) return '';
    if (preg_match('~^\./assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(?:png|jpg|jpeg|webp)$~i', $value)) {
        return is_file(dirname(__DIR__).'/'.substr($value, 2)) ? $value : '';
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) return '';
    $parts = parse_url($value);
    return $parts && strtolower($parts['scheme'] ?? '') === 'https' && !empty($parts['host']) && empty($parts['user']) && empty($parts['pass']) ? $value : '';
};
$featuredHeroArrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>';
$featuredHeroLocationIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$featuredHeroEmailIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>';
?>
<section class="rm-featured-hero" data-rm-featured-slider aria-label="Featured projects" aria-roledescription="carousel" tabindex="0">
    <div class="rm-featured-hero-slides" aria-live="off">
        <?php foreach ($homeFeaturedProjects as $index => $project):
            $slug = $project['slug'];
            $title = $featuredHeroText($project['title'] ?? '') ?: 'Featured project';
            $city = implode(', ', array_filter([
                $featuredHeroText($project['city'] ?? ''),
                $featuredHeroText($project['state'] ?? ''),
            ]));
            $isSample = in_array($project['is_sample'] ?? false, [true, 1, '1'], true);
            $description = $isSample
                ? 'Explore the vision, location and key details of this development. Contact our team for current availability, pricing and viewing information.'
                : (preg_replace('/\s+/u', ' ', strip_tags($featuredHeroText($project['overview'] ?? ''))) ?: 'Discover this development and its key details.');
            if (mb_strlen($description) > 175) $description = rtrim(mb_substr($description, 0, 172)).'...';
            $price = $featuredHeroNumber($project['price_from'] ?? 0);
            $image = '';
            foreach (array_merge([$project['image'] ?? ''], is_array($project['gallery'] ?? null) ? $project['gallery'] : []) as $candidate) {
                $image = $featuredHeroImage($candidate);
                if ($image !== '') break;
            }
            $imageSize = str_starts_with($image, './assets/')
                ? @getimagesize(dirname(__DIR__).'/'.substr($image, 2)) : false;
            $projectUrl = './project.php?slug='.rawurlencode($slug);
        ?>
        <article class="rm-featured-hero-slide <?= $index === 0 ? 'is-active' : '' ?>" data-rm-featured-slide role="group" aria-roledescription="slide" aria-label="<?= e(($index + 1).' of '.count($homeFeaturedProjects)) ?>" <?= $index > 0 ? 'hidden inert aria-hidden="true"' : 'aria-hidden="false"' ?>>
            <a class="rm-featured-hero-media" href="<?= e($projectUrl) ?>" aria-label="<?= e('View '.$title) ?>">
                <?php if ($image !== ''): ?>
                <img class="rm-featured-hero-image" src="<?= e($image) ?>" alt="" <?= $imageSize ? 'width="'.(int) $imageSize[0].'" height="'.(int) $imageSize[1].'"' : '' ?> <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
                <?php else: ?>
                <span class="rm-featured-hero-no-image" aria-hidden="true">ROYALMARK ESTATE</span>
                <?php endif; ?>
            </a>
            <div class="rm-featured-hero-shade" aria-hidden="true"></div>
            <div class="rm-featured-hero-copy">
                <span class="rm-featured-hero-eyebrow"><?= $isSample ? 'FEATURED CONCEPT' : 'FEATURED PROJECT' ?></span>
                <h1><?= e($title) ?></h1>
                <?php if ($city !== ''): ?><p class="rm-featured-hero-location"><?= $featuredHeroLocationIcon ?><?= e($city) ?></p><?php endif; ?>
                <?php if ($description !== ''): ?><p class="rm-featured-hero-description"><?= e($description) ?></p><?php endif; ?>
                <div class="rm-featured-hero-meta">
                    <span><?= e($featuredHeroText($project['development_status'] ?? '') ?: 'Project') ?></span>
                    <?php if ($price > 0): ?><strong><?= $isSample ? 'Illustrative' : 'Starting from' ?> PKR <?= e(number_format($price)) ?></strong><?php endif; ?>
                </div>
                <div class="rm-featured-hero-actions">
                    <a class="rm-featured-hero-primary" href="<?= e($projectUrl) ?>">Explore project <?= $featuredHeroArrow ?></a>
                    <a class="rm-featured-hero-secondary" href="./contact-us">Contact Us</a>
                    <a class="rm-featured-hero-email" href="mailto:<?= e($featuredHeroEmail) ?>"><?= $featuredHeroEmailIcon ?>Email Us</a>
                </div>
                <?php if ($isSample): ?><p class="rm-featured-hero-disclaimer">Illustrative concept only. Project details and pricing are subject to confirmation.</p><?php endif; ?>
            </div>
            <span class="rm-featured-hero-image-label"><?= e($featuredHeroText($project['type'] ?? '') ?: 'Development') ?></span>
        </article>
        <?php endforeach; ?>
    </div>
    <?php if (count($homeFeaturedProjects) > 1): ?>
    <nav class="rm-featured-hero-controls" aria-label="Featured project slides">
        <button type="button" data-rm-featured-prev aria-label="Previous featured project"><?= $featuredHeroArrow ?></button>
        <div class="rm-featured-hero-dots">
            <?php foreach ($homeFeaturedProjects as $index => $_): ?>
            <button type="button" data-rm-featured-dot="<?= $index ?>" aria-label="<?= e('Show featured project '.($index + 1)) ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>
        <button type="button" data-rm-featured-next aria-label="Next featured project"><?= $featuredHeroArrow ?></button>
        <button type="button" class="rm-featured-hero-toggle" data-rm-featured-toggle aria-label="Pause slideshow" aria-pressed="false"><span aria-hidden="true">Ⅱ</span></button>
    </nav>
    <?php endif; ?>
</section>
