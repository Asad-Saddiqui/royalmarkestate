<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';

function bookingCurrentUser(): ?array
{
    $id = $_SESSION['admin_id'] ?? null;
    if (!is_scalar($id) || !ctype_digit((string)$id) || (int)$id < 1) return null;
    $query = Database::getConnection()->prepare('SELECT id,name,email,role,is_blocked,session_version FROM admin_users WHERE id=?');
    $query->execute([(int)$id]);
    $user = $query->fetch();
    if (!$user || $user['is_blocked'] || (int)($_SESSION['admin_session_version'] ?? -1) !== (int)$user['session_version']) return null;
    return $user;
}

function bookingAuthorize(?int $ownerId): array
{
    $user = bookingCurrentUser();
    if (!$user || ($ownerId === null && $user['role'] !== 'admin') || ($ownerId !== null && (int)$user['id'] !== $ownerId && $user['role'] !== 'admin')) {
        throw new InvalidArgumentException('You do not have access to this booking.');
    }
    return $user;
}

function bookingList(?int $userId = null, string $kind = ''): array
{
    bookingAuthorize($userId);
    if (!in_array($kind, ['', 'property', 'project'], true)) throw new InvalidArgumentException('Invalid booking type.');
    $conditions = []; $params = [];
    if ($userId !== null) {$conditions[] = 'user_id=?'; $params[] = $userId;}
    if ($kind !== '') {$conditions[] = 'kind=?'; $params[] = $kind;}
    $query = Database::getConnection()->prepare('SELECT * FROM bookings'.($conditions ? ' WHERE '.implode(' AND ', $conditions) : '').' ORDER BY created_at DESC,id DESC');
    $query->execute($params);
    return $query->fetchAll();
}

function bookingFind(int $id, ?int $userId = null): ?array
{
    bookingAuthorize($userId);
    $query = Database::getConnection()->prepare('SELECT * FROM bookings WHERE id=?'.($userId !== null ? ' AND user_id=?' : ''));
    $query->execute($userId !== null ? [$id, $userId] : [$id]);
    return $query->fetch() ?: null;
}

/** Save an enquiry and its listing snapshot atomically, after the form verifies CAPTCHA. */
function bookingCreate(string $kind, array $listing, array $input, ?int $userId): array
{
    if (!in_array($kind, ['property', 'project'], true)) throw new InvalidArgumentException('Choose a valid listing.');
    $current = bookingCurrentUser();
    if (($userId !== null && (!$current || (int)$current['id'] !== $userId)) || ($current && $userId === null)) throw new InvalidArgumentException('Your account session changed. Reload the form and try again.');
    $values = [];
    foreach (['name'=>100,'email'=>254,'phone'=>40,'message'=>5000,'request_type'=>20,'preferred_date'=>10,'preferred_time'=>5,'request_id'=>32] as $field => $limit) {
        $value = $input[$field] ?? '';
        if (!is_string($value) || mb_strlen(trim($value)) > $limit || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) throw new InvalidArgumentException('Please enter a valid '.str_replace('_',' ',$field).'.');
        $values[$field] = trim($value);
    }
    if ($values['name'] === '') throw new InvalidArgumentException('Enter your full name.');
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
    if (!preg_match('/^[+0-9 ()\.\-]+$/D', $values['phone']) || !preg_match('/^[0-9]{7,15}$/D', preg_replace('/\D/', '', $values['phone']))) throw new InvalidArgumentException('Enter a valid contact phone number.');
    if (!in_array($values['request_type'], ['viewing','call','information'], true)) throw new InvalidArgumentException('Choose how we can help with your request.');
    $zone = new DateTimeZone('Asia/Karachi');
    $now = new DateTimeImmutable('now', $zone); $today = $now->setTime(0,0);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['preferred_date'], $zone);
    if (!$date || $date->format('Y-m-d') !== $values['preferred_date'] || $date < $today || $date > $today->modify('+365 days')) throw new InvalidArgumentException('Choose a preferred date from today to the next 365 days.');
    if ($values['preferred_time'] !== '') {
        $time = DateTimeImmutable::createFromFormat('!H:i', $values['preferred_time'], $zone);
        if (!$time || $time->format('H:i') !== $values['preferred_time']) throw new InvalidArgumentException('Choose a valid preferred time.');
        if ($date->setTime((int)$time->format('H'), (int)$time->format('i')) <= $now) throw new InvalidArgumentException('Choose a future preferred time in Pakistan Standard Time.');
    }
    if (!preg_match('/^[a-f0-9]{32}$/D', $values['request_id'])) throw new InvalidArgumentException('Reload the form before sending another request.');
    $slug = $listing['slug'] ?? '';
    if (!is_string($slug) || strlen($slug) > 200 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) throw new InvalidArgumentException('This listing is unavailable.');
    $db = Database::getConnection();
    $sessionSecret = $_SESSION['booking_request_secret'] ??= bin2hex(random_bytes(32));
    $requestKey = hash_hmac('sha256', $values['request_id'], $sessionSecret);
    $requestOwner = hash('sha256', $sessionSecret.':'.($userId ?? 'guest'));
    $bucket = hash('sha256', $userId !== null ? 'user:'.$userId : 'address:'.($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $db->beginTransaction();
    try {
        // Lock the listing so unpublishing cannot race an accepted request.
        $source = $db->prepare('SELECT payload FROM '.($kind === 'project' ? 'projects' : 'properties').' WHERE slug=? FOR UPDATE');
        $source->execute([$slug]); $payload = $source->fetchColumn();
        $record = $payload !== false ? json_decode($payload, true, 512, JSON_THROW_ON_ERROR) : null;
        if (!is_array($record) || ($record['status'] ?? 'published') !== 'published') throw new InvalidArgumentException('This listing is no longer available for new requests.');
        $existing = $db->prepare('SELECT * FROM bookings WHERE request_key=? AND request_owner=? FOR UPDATE');
        $existing->execute([$requestKey, $requestOwner]);
        if ($result = $existing->fetch()) {$db->commit();return $result;}
        $stamp = $now->format('Y-m-d H:i:s');
        $db->prepare('INSERT IGNORE INTO booking_limits (bucket,window_start,attempts) VALUES (?,?,0)')->execute([$bucket,$stamp]);
        $limit = $db->prepare('SELECT window_start,attempts FROM booking_limits WHERE bucket=? FOR UPDATE');$limit->execute([$bucket]);$rate = $limit->fetch();
        $fresh = new DateTimeImmutable($rate['window_start'], $zone) > $now->modify('-15 minutes');
        if ($fresh && (int)$rate['attempts'] >= 5) throw new InvalidArgumentException('You have sent several requests. Please wait 15 minutes before sending another.');
        $db->prepare('UPDATE booking_limits SET attempts=?,window_start=? WHERE bucket=?')->execute([$fresh ? (int)$rate['attempts']+1 : 1, $fresh ? $rate['window_start'] : $stamp, $bucket]);
        $image = is_string($record['image'] ?? null) && preg_match('~^\./assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(?:png|jpg|jpeg|webp)$~iD', $record['image']) ? $record['image'] : '';
        $price = $kind === 'property' ? (float)($record['price'] ?? 0) : (float)($record['price_from'] ?? 0);
        $priceText = $price > 0 ? 'Rs '.number_format($price, 2).($kind === 'property' && ($record['purpose'] ?? '') === 'Rent' ? ' / month' : '') : 'Price on request';
        $reference = 'RM-'.($kind === 'property' ? 'P' : 'J').'-'.$now->format('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
        $params = [$reference,$requestKey,$requestOwner,$userId,$kind,$slug,mb_substr((string)($record['title'] ?? $slug),0,200),mb_substr((string)($record['city'] ?? ''),0,100),$image,$priceText,$values['name'],$values['email'],$values['phone'],$values['message'],$values['request_type'],$values['preferred_date'],$values['preferred_time'] !== '' ? $values['preferred_time'].':00' : null,'Asia/Karachi','pending','',$stamp,$stamp];
        $insert = $db->prepare('INSERT INTO bookings (reference,request_key,request_owner,user_id,kind,listing_slug,listing_title,listing_city,listing_image,listing_price,name,email,phone,message,request_type,preferred_date,preferred_time,timezone,status,admin_note,created_at,updated_at) VALUES ('.implode(',',array_fill(0,count($params),'?')).')');
        $insert->execute($params);$id = (int)$db->lastInsertId();
        $message = $values['message'] !== '' ? $values['message'] : 'I would like more information about this '.$kind.'.';
        $message .= "\n\nRequest: ".$reference."\nPreferred date: ".$values['preferred_date'].($values['preferred_time'] !== '' ? ' '.$values['preferred_time'] : '').' (Pakistan time)';
        $db->prepare('INSERT INTO contact_messages (name,email,phone,interest,message) VALUES (?,?,?,?,?)')->execute([$values['name'],$values['email'],$values['phone'],mb_substr(($kind === 'project' ? 'Project: ' : '').($record['title'] ?? $slug),0,120),$message]);
        $contactId=(int)$db->lastInsertId();
        $visitorId=function_exists('visitorCurrentId')?visitorCurrentId():null;
        $db->prepare("INSERT INTO contact_message_meta (message_id,user_id,visitor_id,source,status,admin_note,updated_at) VALUES (?,?,?,?,'new','',?)")->execute([$contactId,$userId,$visitorId,$kind,$stamp]);
        $read = $db->prepare('SELECT * FROM bookings WHERE id=?');$read->execute([$id]);$result = $read->fetch();
        $db->commit();return $result;
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();throw $error;
    }
}

function bookingUpdateStatus(int $id, string $status, ?int $actorUserId = null): void
{
    bookingAuthorize($actorUserId);
    if (!in_array($status,['pending','confirmed','completed','cancelled'],true) || ($actorUserId !== null && $status !== 'cancelled')) throw new InvalidArgumentException('Choose a valid booking action.');
    bookingChange($id, $status, null, $actorUserId);
}

function bookingAdminUpdate(int $id, string $status, string $note): void
{
    bookingAuthorize(null);
    if (!in_array($status,['pending','confirmed','completed','cancelled'],true) || mb_strlen($note) > 3000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$note)) throw new InvalidArgumentException('Enter a valid status and team update (up to 3,000 characters).');
    bookingChange($id, $status, trim($note), null);
}

function bookingChange(int $id, string $status, ?string $note, ?int $ownerId): void
{
    // This internal helper repeats authorization to keep direct calls safe too.
    bookingAuthorize($ownerId);
    if (!in_array($status,['pending','confirmed','completed','cancelled'],true) || ($ownerId !== null && ($status !== 'cancelled' || $note !== null))) throw new InvalidArgumentException('Choose a valid booking action.');
    $db = Database::getConnection();$db->beginTransaction();
    try {
        $query = $db->prepare('SELECT status FROM bookings WHERE id=?'.($ownerId !== null ? ' AND user_id=?' : '').' FOR UPDATE');
        $query->execute($ownerId !== null ? [$id,$ownerId] : [$id]);$old = $query->fetchColumn();
        if ($old === false) throw new InvalidArgumentException('This booking is unavailable in your account.');
        if ($ownerId !== null && !in_array($old,['pending','confirmed'],true)) throw new InvalidArgumentException('Only pending or confirmed requests can be cancelled.');
        $stamp = (new DateTimeImmutable('now',new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
        $sql = 'UPDATE bookings SET status=?,updated_at=?'.($note !== null ? ',admin_note=?' : '').' WHERE id=?';
        $params = [$status,$stamp];if ($note !== null) $params[] = $note;$params[] = $id;
        $db->prepare($sql)->execute($params);$db->commit();
    } catch (Throwable $error) {if ($db->inTransaction()) $db->rollBack();throw $error;}
}
