<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/Config/Database.php';
date_default_timezone_set('Asia/Karachi');

$db = Database::getConnection();
$slug = 'seaside-living';
$projectQuery = $db->prepare('SELECT payload FROM projects WHERE slug=?');
$projectQuery->execute([$slug]);
$project = json_decode((string)$projectQuery->fetchColumn(), true);
if (!$project || empty($project['is_sample'])) throw new RuntimeException('The sample project Seaside Living was not found.');

$comments = [
    'The project overview is easy to follow and the location details are helpful.',
    'The sample floor plans provide a useful starting point for comparing layouts.',
    'The gallery presents the concept clearly. More interior examples would help.',
    'The amenities section is informative and the enquiry form is straightforward.',
    'The pricing guide is easy to read. I would like more detail about payment options.',
    'The development timeline is useful when reviewing the sample project.',
    'The location map and nearby facilities are useful for exploring the concept.',
    'The project information is well organised and easy to browse on a phone.',
    'The sample concept looks interesting. Additional unit specifications would help.',
    'The developer information and downloadable documents are helpful references.',
];
$exists = $db->prepare('SELECT 1 FROM listing_reviews WHERE reviewer_key=?');
$insert = $db->prepare("INSERT INTO listing_reviews (kind,listing_slug,reviewer_key,user_id,visitor_id,name,email,rating,body,status,created_at,updated_at) VALUES ('project',?,?,NULL,NULL,?,?,?,?,'approved',?,?)");
$added = 0;
$db->beginTransaction();
try {
    for ($i = 1; $i <= 100; $i++) {
        $key = hash('sha256', 'demo-reviews-v1|project|'.$slug.'|'.$i);
        $exists->execute([$key]);
        if ($exists->fetchColumn()) continue;
        $stamp = (new DateTimeImmutable())->modify('-'.(100 - $i).' hours')->format('Y-m-d H:i:s');
        $rating = [3,4,5,4,5,3,4,5,2,4][($i-1)%10];
        $insert->execute([$slug,$key,sprintf('Demo Reviewer %03d',$i),sprintf('demo-review-%03d@example.invalid',$i),$rating,'[DEMO REVIEW — fictional feedback for testing] '.$comments[($i-1)%count($comments)],$stamp,$stamp]);
        $added++;
    }
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    throw $error;
}
echo "Added $added demo reviews to Seaside Living (project: $slug).".PHP_EOL;
