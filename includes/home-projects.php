<?php
// $homeProjects contains published records from the project database.
$featuredHomeProjects = $homeProjects;
usort($featuredHomeProjects, static function (array $a, array $b): int {
    return ((int) !empty($b['featured']) <=> (int) !empty($a['featured']))
        ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));
});
$featuredHomeProjects = array_slice($featuredHomeProjects, 0, 6);
$homeProjectArrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>';
?>
<section class="rm-native-section rm-home-projects" id="home-projects" aria-labelledby="home-projects-title">
    <header class="rm-home-projects-heading">
        <div class="rm-native-heading">
            <span>PROJECTS &amp; COMMUNITIES</span>
            <h2 id="home-projects-title">Explore our projects</h2>
            <p>Find a development that fits your plans. Explore its location, developer, plans and available project information.</p>
        </div>
        <a class="rm-home-projects-view-all" href="./projects">View all projects <?= $homeProjectArrow ?></a>
    </header>
    <?php if ($featuredHomeProjects): ?>
    <div class="rm-home-projects-grid">
        <?php foreach ($featuredHomeProjects as $homeProject):
            $projectUrl = './project.php?slug='.rawurlencode($homeProject['slug']);
            $projectTitle = trim((string) ($homeProject['title'] ?? '')) ?: 'Explore this project';
            $projectStage = trim((string) ($homeProject['development_status'] ?? '')) ?: 'Development updates coming soon';
            $projectProgress = max(0, min(100, (int) ($homeProject['progress'] ?? 0)));
            $projectSample = !empty($homeProject['is_sample']);
            $projectStageClass = match ($projectStage) { 'Completed' => 'complete', 'Upcoming' => 'upcoming', default => 'building' };
        ?>
        <article class="rm-home-project-card">
            <a class="rm-home-project-image" href="<?= e($projectUrl) ?>" aria-label="<?= e('Explore '.$projectTitle) ?>">
                <?php if (!empty($homeProject['image'])): ?>
                <img src="<?= e($homeProject['image']) ?>" alt="<?= e($projectTitle) ?>" width="600" height="400" loading="lazy" decoding="async">
                <?php else: ?>
                <span class="rm-home-project-placeholder"><?= listingIcon('building') ?><span>Project photos coming soon</span></span>
                <?php endif; ?>
                <span class="rm-home-project-stage <?= e($projectStageClass) ?>"><?= e($projectStage) ?></span>
                <?php if ($projectSample): ?><span class="rm-home-project-sample">Sample concept</span><?php endif; ?>
            </a>
            <div class="rm-home-project-content">
                <span class="rm-home-project-type"><?= listingIcon('building') ?><?= e(trim((string) ($homeProject['type'] ?? '')) ?: 'Development project') ?></span>
                <h3><a href="<?= e($projectUrl) ?>"><?= e($projectTitle) ?></a></h3>
                <p class="rm-home-project-location"><?= listingIcon('location') ?><?= e(trim((string) ($homeProject['city'] ?? '')) ?: 'Location to be confirmed') ?></p>
                <p class="rm-home-project-developer"><?= !empty($homeProject['developer_name']) ? 'By '.e($homeProject['developer_name']) : 'Developer information coming soon' ?></p>
                <div class="rm-home-project-price">
                    <?php if ((float) ($homeProject['price_from'] ?? 0) > 0): ?>
                    <span>Starting from · PKR</span><strong>Rs <?= number_format((float) $homeProject['price_from']) ?></strong>
                    <?php else: ?><span>Contact us for current pricing</span><strong>Price on request</strong><?php endif; ?>
                </div>
                <div class="rm-home-project-progress">
                    <div><span><?= $projectSample ? 'Illustrative progress' : 'Development progress' ?></span><strong><?= $projectProgress ?>%</strong></div>
                    <progress value="<?= $projectProgress ?>" max="100" aria-label="<?= e($projectTitle.' '.($projectSample ? 'illustrative' : 'development').' progress') ?>"><?= $projectProgress ?>%</progress>
                </div>
                <a class="rm-home-project-link" href="<?= e($projectUrl) ?>">Explore project <?= $homeProjectArrow ?></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rm-home-projects-empty"><?= listingIcon('building') ?><h3>New projects are on the way.</h3><p>Speak with our team about the location and type of development you are looking for.</p><a href="./contact-us">Contact our team <?= $homeProjectArrow ?></a></div>
    <?php endif; ?>
</section>
