<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/bookings.php';
require_once __DIR__.'/booking-captcha.php';
require_once __DIR__.'/blog-store.php';

function commentCsrfToken(): string
{
    return $_SESSION['blog_comment_csrf'] ??= bin2hex(random_bytes(32));
}

/** Only approved comments are ever returned to the public. Email stays private. */
function commentPublished(int $postId): array
{
    $query = Database::getConnection()->prepare("SELECT id,name,body,created_at FROM blog_comments WHERE post_id=? AND status='approved' ORDER BY created_at DESC,id DESC");
    $query->execute([$postId]);
    return $query->fetchAll();
}

function commentSubmit(int $postId, array $input, ?int $userId = null): void
{
    $csrf = $input['comment_csrf'] ?? null;
    if (!is_string($csrf) || !hash_equals(commentCsrfToken(), $csrf)) throw new InvalidArgumentException('Your form expired. Reload the page and try again.');
    if (!is_string($input['website'] ?? '') || trim($input['website'] ?? '') !== '') throw new InvalidArgumentException('Your comment could not be accepted.');
    $current = bookingCurrentUser();
    if (($userId !== null && (!$current || (int)$current['id'] !== $userId)) || ($current && $userId === null)) throw new InvalidArgumentException('Your account session changed. Reload this page before commenting.');
    $values = [];
    foreach (['name'=>100,'email'=>254,'body'=>2000,'captcha_id'=>32,'captcha_answer'=>5] as $field=>$limit) {
        $value = $input[$field] ?? '';
        if (!is_string($value) || mb_strlen(trim($value)) > $limit || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) throw new InvalidArgumentException('Enter a valid '.str_replace('_',' ',$field).'.');
        $values[$field] = trim($value);
    }
    if ($current) {$values['name'] = $current['name'] ?: 'Royalmark member'; $values['email'] = $current['email'];}
    if ($values['name'] === '') throw new InvalidArgumentException('Enter your name.');
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
    if (mb_strlen($values['body']) < 10) throw new InvalidArgumentException('Write a comment of at least 10 characters.');
    $recent = $_SESSION['blog_comment_times'] ?? [];
    $recent = is_array($recent) ? array_values(array_filter($recent, static fn($time) => is_int($time) && $time > time()-900)) : [];
    if (count($recent) >= 5) throw new InvalidArgumentException('Please wait 15 minutes before adding another comment.');
    $db = Database::getConnection();
    $db->beginTransaction();
    try {
        $query = $db->prepare('SELECT id,slug,payload FROM blog_posts WHERE id=? FOR UPDATE');
        $query->execute([$postId]); $row = $query->fetch();
        if (!$row || !blogIsPublic(blogRecord($row))) throw new InvalidArgumentException('This article is no longer accepting comments.');
        // Visitor association may only come from a server-verified identity, never POST.
        $visitorId = function_exists('visitorCurrentId') ? visitorCurrentId() : null;
        if (!is_string($visitorId) || !preg_match('/^[a-f0-9-]{36}$/D', $visitorId)) $visitorId = null;
        if ($userId !== null || $visitorId !== null) {
            $rate = $db->prepare('SELECT COUNT(*) FROM blog_comments WHERE created_at>? AND '.($userId !== null ? 'user_id=?' : 'visitor_id=?'));
            $rate->execute([date('Y-m-d H:i:s',time()-900),$userId ?? $visitorId]);
            if ((int)$rate->fetchColumn() >= 5) throw new InvalidArgumentException('Please wait 15 minutes before adding another comment.');
        }
        if (!bookingCaptchaVerify('blog:'.$row['slug'], $values['captcha_id'], $values['captcha_answer'])) throw new InvalidArgumentException('The security code is incorrect or expired. Enter the new code below.');
        $stamp = date('Y-m-d H:i:s');
        $insert = $db->prepare("INSERT INTO blog_comments (post_id,user_id,visitor_id,name,email,body,status,created_at,updated_at) VALUES (?,?,?,?,?,?,'pending',?,?)");
        $insert->execute([$postId,$userId,$visitorId,$values['name'],$values['email'],$values['body'],$stamp,$stamp]);
        $db->commit();
        $recent[] = time(); $_SESSION['blog_comment_times'] = $recent;
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}
