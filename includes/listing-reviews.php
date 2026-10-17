<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/Config/listing-reviews.php';

function listingReviewHandle(string $kind, array $listing): array
{
    $slug=(string)$listing['slug'];$error='';$success=isset($_GET['reviewed']);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['reviews_offset'])) {
        $offset = filter_var($_GET['reviews_offset'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
        $limit = filter_var($_GET['reviews_limit'] ?? 10, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>10]]);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        if ($offset === false || $limit === false) {
            http_response_code(400); echo json_encode(['error'=>'Invalid review page.']); exit;
        }
        $reviews = listingReviewPage($kind, $slug, $offset, $limit);
        ob_start(); foreach ($reviews as $review) listingReviewArticle($review); $html = ob_get_clean();
        echo json_encode(['html'=>$html,'loaded'=>count($reviews)], JSON_THROW_ON_ERROR | JSON_HEX_TAG); exit;
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['review_form']??'')===$kind.':'.$slug){
        try{
            listingReviewSubmit($kind,$listing,$_POST);
            header('Location: ./'.($kind==='project'?'project.php':'property.php').'?slug='.rawurlencode($slug).'&reviewed=1#listing-reviews',true,303);exit;
        }catch(InvalidArgumentException $exception){http_response_code(422);$error=$exception->getMessage();}
        catch(Throwable $exception){http_response_code(500);error_log('Listing review failed: '.$exception->getMessage());$error='Your review could not be saved. Please try again.';}
    }
    return array_merge(listingReviewSummary($kind,$slug),['kind'=>$kind,'slug'=>$slug,'error'=>$error,'success'=>$success,'already_reviewed'=>listingReviewAlreadySubmitted($kind,$slug),'old'=>$_POST]);
}

function listingReviewStars(int $rating): string
{
    return '<span class="listing-review-stars" aria-label="'.$rating.' out of 5 stars">'.str_repeat('<span aria-hidden="true">★</span>',$rating).str_repeat('<span class="is-empty" aria-hidden="true">★</span>',5-$rating).'</span>';
}

function listingReviewArticle(array $review): void
{
    ?><article><header><div class="listing-review-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($review['name'],0,1))) ?></div><div><strong><?= e($review['name']) ?></strong><?= listingReviewStars((int)$review['rating']) ?></div><time datetime="<?= e(substr($review['created_at'],0,10)) ?>"><?= e(date('M j, Y',strtotime($review['created_at']))) ?></time></header><p><?= nl2br(e($review['body'])) ?></p></article><?php
}

function listingReviewBadge(array $state): void
{
    $average=(float)($state['average']??0);$count=(int)($state['count']??0);
    $reviewUrl='./'.(($state['kind']??'property')==='project'?'project.php':'property.php').'?slug='.rawurlencode((string)($state['slug']??'')).'#listing-reviews';
    ?><a class="listing-review-badge" href="<?= e($reviewUrl) ?>" aria-label="<?= $count?e(number_format($average,1).' out of 5 from '.$count.' reviews'):'Add the first rating' ?>"><?= listingReviewStars((int)round($average)) ?><strong><?= $count?e(number_format($average,1)):'Not rated' ?></strong><span><?= $count ?> <?= $count===1?'review':'reviews' ?></span></a><?php
}

function listingReviewRender(array $state): void
{
    $user=bookingCurrentUser();$old=is_array($state['old']??null)?$state['old']:[];
    $name=$user?($user['name']?:'Royalmark member'):(is_string($old['name']??null)?$old['name']:'');
    $email=$user?$user['email']:(is_string($old['email']??null)?$old['email']:'');
    $body=is_string($old['body']??null)?$old['body']:'';$selected=(int)($old['rating']??0);
    ?>
    <section class="listing-reviews pd-section" id="listing-reviews" aria-labelledby="listing-reviews-title">
      <?php if($state['success']): ?><p class="listing-review-notice is-success" role="status">Thank you. Your rating and review are now published.</p><?php endif; ?>
      <?php if($state['error']): ?><p class="listing-review-notice is-error" role="alert"><?= e($state['error']) ?></p><?php endif; ?>
      <div class="listing-review-layout"><?php if(!$state['already_reviewed']): ?><form class="listing-review-form" method="post" action="./<?= $state['kind']==='project'?'project.php':'property.php' ?>?slug=<?= rawurlencode($state['slug']) ?>#listing-reviews">
        <h3 class="listing-review-form-title">Add a review</h3>
        <input type="hidden" name="review_form" value="<?= e($state['kind'].':'.$state['slug']) ?>"><input type="hidden" name="review_csrf" value="<?= e(listingReviewCsrf()) ?>">
        <label class="listing-review-honeypot" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
        <fieldset class="listing-rating-field"><legend>Choose your rating</legend><div><?php for($rating=5;$rating>=1;$rating--): ?><input type="radio" id="review-rating-<?= e($state['kind']) ?>-<?= $rating ?>" name="rating" value="<?= $rating ?>" <?= $selected===$rating?'checked':'' ?> required><label for="review-rating-<?= e($state['kind']) ?>-<?= $rating ?>" title="<?= $rating ?> out of 5"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.8 2.85 5.78 6.38.93-4.62 4.5 1.09 6.36L12 17.37l-5.7 3 1.09-6.36-4.62-4.5 6.38-.93z"/></svg><span class="sr-only"><?= $rating ?> out of 5</span></label><?php endfor; ?></div></fieldset>
        <div class="listing-review-fields"><label>Your name<input name="name" maxlength="100" value="<?= e($name) ?>" <?= $user?'readonly':'' ?> required></label><label>Email address<input type="email" name="email" maxlength="254" value="<?= e($email) ?>" <?= $user?'readonly':'' ?> required></label></div>
        <label>Review<textarea name="body" minlength="10" maxlength="1500" rows="5" required placeholder="Tell others what stood out to you."><?= e($body) ?></textarea></label>
        <button class="button" type="submit">Publish rating &amp; review</button><small>Your email address stays private.</small>
      </form><?php else: ?><div class="listing-review-complete"><?= listingReviewStars(5) ?><strong>Thank you for sharing your feedback.</strong><p>You have already rated this <?= e($state['kind']) ?>. Each account or browser can submit one review.</p></div><?php endif; ?><div class="listing-review-list">
        <div class="listing-reviews-heading"><div><span>VERIFIED FEEDBACK</span><h2 id="listing-reviews-title">Ratings &amp; Reviews</h2><p>Share your experience with this <?= e($state['kind']) ?>.</p></div><div class="listing-rating-summary"><strong><?= $state['count']?e(number_format((float)$state['average'],1)):'New' ?></strong><?= listingReviewStars((int)round((float)$state['average'])) ?><small><?= (int)$state['count'] ?> <?= (int)$state['count']===1?'review':'reviews' ?></small></div></div>
        <div class="listing-review-items listing-review-list" data-review-items>
        <?php if(!$state['reviews']): ?><div class="listing-review-empty"><h3>Be the first to review</h3><p>No ratings have been added yet.</p></div><?php else: foreach($state['reviews'] as $review) listingReviewArticle($review); endif; ?>
        </div>
        <div class="listing-review-pagination" data-review-pagination data-total="<?= (int)$state['count'] ?>" data-loaded="<?= count($state['reviews']) ?>" data-url="<?= e('./'.($state['kind']==='project'?'project.php':'property.php').'?slug='.rawurlencode($state['slug'])) ?>">
          <p role="status" data-review-status>Showing <?= count($state['reviews']) ?> of <?= (int)$state['count'] ?> reviews</p>
          <?php if($state['count']>10): ?><button type="button" class="button" data-review-more>Load more</button><?php endif; ?>
          <?php if($state['count']>50): ?><button type="button" class="button" data-review-all>View all <?= (int)$state['count'] ?> reviews</button><?php endif; ?>
        </div>
      </div></div>
    </section><?php
}
