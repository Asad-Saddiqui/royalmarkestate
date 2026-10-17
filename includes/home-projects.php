<?php
// Home showcases only published projects explicitly selected by the administrator.
$featuredHomeProjects = projectFeaturedData(is_array($homeProjects ?? null) ? $homeProjects : []);
$homeProjectArrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>';
$homeProjectStar = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 3 2.8 5.7 6.3.9-4.6 4.4 1.1 6.3-5.6-3-5.6 3 1.1-6.3L2.9 9.6l6.3-.9z"/></svg>';
$homeProjectText = static fn($value): string => is_scalar($value) ? trim((string) $value) : '';
$homeProjectNumber = static function ($value): float {
    return is_scalar($value) && is_numeric($value) && is_finite((float) $value) ? max(0, (float) $value) : 0;
};
$homeProjectImage = static function ($value): string {
    if (!is_string($value) || strlen($value) > 2048) return '';
    if (preg_match('~^\./assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(png|jpg|jpeg|webp)$~i', $value)) {
        return is_file(dirname(__DIR__).'/'.substr($value, 2)) ? $value : '';
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) return '';
    $parts = parse_url($value);
    return $parts && strtolower($parts['scheme'] ?? '') === 'https' && !empty($parts['host']) && empty($parts['user']) && empty($parts['pass']) ? $value : '';
};
?>
<section class="rm-native-section rm-home-projects" id="home-projects" aria-labelledby="home-projects-title">
    <header class="rm-home-projects-heading">
        <div class="rm-native-heading">
            <span>SELECTED DEVELOPMENTS</span>
            <h2 id="home-projects-title">Featured Projects</h2>
            <p>Explore our selected developments, from the people behind them to pricing, plans and the places nearby.</p>
        </div>
        <a class="rm-home-projects-view-all" href="./projects">View all projects <?= $homeProjectArrow ?></a>
    </header>
    <?php if ($featuredHomeProjects): ?>
    <div class="rm-home-projects-grid">
        <?php foreach ($featuredHomeProjects as $homeProject):
            $projectSlug = $homeProjectText($homeProject['slug'] ?? '');
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,199}$/', $projectSlug)) continue;
            $projectUrl = './project.php?slug='.rawurlencode($projectSlug);
            $projectTitle = $homeProjectText($homeProject['title'] ?? '') ?: 'Explore this project';
            $projectStage = $homeProjectText($homeProject['development_status'] ?? '') ?: 'Updates coming soon';
            $projectProgress = (int) min(100, $homeProjectNumber($homeProject['progress'] ?? 0));
            $projectSample = in_array($homeProject['is_sample'] ?? false, [true, 1, '1'], true);
            $projectStageClass = match ($projectStage) { 'Completed' => 'complete', 'Upcoming' => 'upcoming', default => 'building' };
            $projectLocation = implode(', ', array_unique(array_filter([
                $homeProjectText($homeProject['city'] ?? ''), $homeProjectText($homeProject['state'] ?? ''),
            ])));
            $projectImage = $homeProjectImage($homeProject['image'] ?? '');
            $projectFrom = $homeProjectNumber($homeProject['price_from'] ?? 0);
            $projectTo = $homeProjectNumber($homeProject['price_to'] ?? 0);
            $projectPrice = $projectFrom > 0 ? 'Rs '.number_format($projectFrom) : 'Price on request';
            if ($projectFrom > 0 && $projectTo > $projectFrom) $projectPrice .= ' to Rs '.number_format($projectTo);
            elseif ($projectFrom <= 0 && $projectTo > 0) $projectPrice = 'Up to Rs '.number_format($projectTo);
        ?>
        <article class="rm-home-project-card">
            <a class="rm-home-project-image" href="<?= e($projectUrl) ?>" aria-label="<?= e('Explore '.$projectTitle) ?>">
                <?php if ($projectImage): ?>
                <img src="<?= e($projectImage) ?>" alt="<?= e($projectTitle) ?>" width="600" height="400" loading="lazy" decoding="async">
                <?php else: ?>
                <span class="rm-home-project-placeholder"><?= listingIcon('building') ?><span>Project photos coming soon</span></span>
                <?php endif; ?>
                <span class="rm-home-project-labels"><span class="rm-home-project-stage <?= e($projectStageClass) ?>"><?= e($projectStage) ?></span><span class="rm-home-project-featured"><?= $homeProjectStar ?>Featured</span></span>
                <?php if ($projectSample): ?><span class="rm-home-project-sample">Sample concept</span><?php endif; ?>
            </a>
            <div class="rm-home-project-content">
                <span class="rm-home-project-type"><?= listingIcon('building') ?><?= e($homeProjectText($homeProject['type'] ?? '') ?: 'Development project') ?></span>
                <h3><a href="<?= e($projectUrl) ?>"><?= e($projectTitle) ?></a></h3>
                <a class="rm-home-project-location" href="<?= e($projectUrl.'#project-location') ?>"><?= listingIcon('location') ?><span><?= e($projectLocation ?: 'Location to be confirmed') ?></span><?= $homeProjectArrow ?></a>
                <?php if ($homeProjectText($homeProject['developer_name'] ?? '')): ?><a class="rm-home-project-developer" href="<?= e($projectUrl.'#project-developer') ?>">By <?= e($homeProjectText($homeProject['developer_name'])) ?></a><?php else: ?><p class="rm-home-project-developer">Developer information coming soon</p><?php endif; ?>
                <div class="rm-home-project-price"><span><?= $projectFrom > 0 || $projectTo > 0 ? ($projectSample ? 'Illustrative price range · PKR' : 'Price range · PKR') : 'Contact us for current pricing' ?></span><strong><?= e($projectPrice) ?></strong></div>
                <div class="rm-home-project-progress"><div><span><?= $projectSample ? 'Illustrative progress' : 'Development progress' ?></span><strong><?= $projectProgress ?>%</strong></div><progress value="<?= $projectProgress ?>" max="100" aria-label="<?= e($projectTitle.' '.($projectSample ? 'illustrative' : 'development').' progress') ?>"><?= $projectProgress ?>%</progress></div>
                <a class="rm-home-project-link" href="<?= e($projectUrl) ?>">View project details <?= $homeProjectArrow ?></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rm-home-projects-empty"><?= listingIcon('building') ?><h3>Featured projects are coming soon.</h3><p>Browse all published projects, or speak with our team about the location and development you have in mind.</p><div><a href="./projects">View all projects <?= $homeProjectArrow ?></a><a href="./contact-us">Contact our team <?= $homeProjectArrow ?></a></div></div>
    <?php endif; ?>
</section>
