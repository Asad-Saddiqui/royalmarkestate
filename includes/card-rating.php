<?php
require_once dirname(__DIR__).'/Config/Database.php';

function listingCardRating(string $kind, string $slug): void
{
    static $summaries = null;
    if ($summaries === null) {
        $summaries = [];
        $rows = Database::getConnection()->query("SELECT kind,listing_slug,COUNT(*) AS review_count,AVG(rating) AS average_rating FROM listing_reviews WHERE status='approved' GROUP BY kind,listing_slug");
        foreach ($rows as $row) $summaries[$row['kind'].':'.$row['listing_slug']] = $row;
    }
    $summary = $summaries[$kind.':'.$slug] ?? [];
    $count = (int) ($summary['review_count'] ?? 0);
    $average = (float) ($summary['average_rating'] ?? 0);
    $url = './'.($kind === 'project' ? 'project.php' : 'property.php').'?slug='.rawurlencode($slug).'#listing-reviews';
    $label = $count ? number_format($average, 1).' out of 5 from '.$count.' '.($count === 1 ? 'review' : 'reviews') : 'Not rated. Add the first review';
    ?>
    <a class="listing-card-rating" href="<?= e($url) ?>" aria-label="<?= e($label) ?>">
        <span class="listing-card-rating-stars" aria-hidden="true"><span>&#9733;&#9733;&#9733;&#9733;&#9733;</span><span class="listing-card-rating-fill" style="width:<?= number_format(min(100, max(0, $average * 20)), 2, '.', '') ?>%">&#9733;&#9733;&#9733;&#9733;&#9733;</span></span>
        <strong><?= $count ? e(number_format($average, 1)) : 'Not rated' ?></strong>
        <span class="listing-card-rating-count">(<?= $count ?> <?= $count === 1 ? 'review' : 'reviews' ?>)</span>
    </a>
    <?php
}
