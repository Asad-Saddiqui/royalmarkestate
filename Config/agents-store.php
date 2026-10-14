<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

function agentDefaults(): array
{
    return ['slug' => '', 'name' => '', 'position' => '', 'email' => '', 'phone' => '', 'whatsapp' => '', 'city' => '', 'specialities' => [], 'languages' => [], 'bio' => '', 'photo' => '', 'photo_alt' => '', 'experience_years' => 0, 'sort_order' => 0, 'status' => 'unpublished', 'facebook' => '', 'instagram' => '', 'linkedin' => '', 'is_sample' => false];
}

function agentValidSlug(string $slug): bool
{
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) === 1 && strlen($slug) <= 180;
}

function agentRecord(array $row): array
{
    $payload = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) throw new RuntimeException('Agent information is invalid.');
    $record = array_merge(agentDefaults(), $payload);
    $record['slug'] = $row['slug'];
    $record['id'] = (int)$row['id'];
    return $record;
}

function agentData(bool $includeUnpublished = false): array
{
    $records = [];
    foreach (Database::getConnection()->query('SELECT id,slug,payload FROM agents ORDER BY id DESC') as $row) {
        $record = agentRecord($row);
        if ($includeUnpublished || $record['status'] === 'published') $records[] = $record;
    }
    usort($records, static fn(array $a, array $b): int => ((int)$a['sort_order'] <=> (int)$b['sort_order']) ?: ((int)$b['id'] <=> (int)$a['id']));
    return $records;
}

function agentFind(string $slug, bool $includeUnpublished = false): ?array
{
    if (!agentValidSlug($slug)) return null;
    $statement = Database::getConnection()->prepare('SELECT id,slug,payload FROM agents WHERE slug=?');
    $statement->execute([$slug]);
    $row = $statement->fetch();
    if (!$row) return null;
    $record = agentRecord($row);
    return $includeUnpublished || $record['status'] === 'published' ? $record : null;
}

function agentValidated(array $input): array
{
    $record = agentDefaults();
    $limits = ['slug' => 180, 'name' => 120, 'position' => 120, 'email' => 254, 'phone' => 40, 'whatsapp' => 40, 'city' => 100, 'bio' => 10000, 'photo' => 2048, 'photo_alt' => 250, 'facebook' => 2048, 'instagram' => 2048, 'linkedin' => 2048, 'status' => 20];
    foreach ($limits as $field => $limit) {
        $value = $input[$field] ?? $record[$field];
        if (!is_string($value) || mb_strlen(trim($value)) > $limit || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            throw new InvalidArgumentException('Please provide a valid ' . str_replace('_', ' ', $field) . '.');
        }
        $record[$field] = trim($value);
    }
    foreach (['name', 'position', 'city', 'bio'] as $field) {
        if ($record[$field] === '') throw new InvalidArgumentException(ucfirst($field) . ' is required.');
    }
    if ($record['slug'] === '') {
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $record['name']);
        $record['slug'] = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name === false ? $record['name'] : $name)), '-');
    }
    if (!agentValidSlug($record['slug'])) throw new InvalidArgumentException('Use lowercase letters, numbers and single hyphens for the agent URL.');
    if (!in_array($record['status'], ['published', 'unpublished'], true)) throw new InvalidArgumentException('Please select a valid publication status.');
    if ($record['email'] !== '' && !filter_var($record['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Please enter a valid email address.');
    foreach (['phone', 'whatsapp'] as $field) {
        if ($record[$field] !== '' && (!preg_match('/^[+0-9 ()\.\-]+$/D', $record[$field]) || strlen(preg_replace('/\D/', '', $record[$field])) < 7 || strlen(preg_replace('/\D/', '', $record[$field])) > 15)) {
            throw new InvalidArgumentException('Please enter a valid ' . ($field === 'whatsapp' ? 'WhatsApp number with country code' : 'phone number') . '.');
        }
    }
    foreach (['facebook', 'instagram', 'linkedin'] as $field) {
        if ($record[$field] !== '' && (!filter_var($record[$field], FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($record[$field], PHP_URL_SCHEME)), ['http', 'https'], true))) {
            throw new InvalidArgumentException('Please enter a full http or https ' . ucfirst($field) . ' URL.');
        }
    }
    if ($record['photo'] !== '') {
        $remote = filter_var($record['photo'], FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($record['photo'], PHP_URL_SCHEME)), ['http', 'https'], true);
        $local = preg_match('~^\./assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)$~D', $record['photo']);
        if (!$remote && !$local) throw new InvalidArgumentException('Please upload a valid JPG, PNG or WebP agent photo.');
    }
    foreach (['experience_years' => 100, 'sort_order' => 9999] as $field => $max) {
        $value = $input[$field] ?? 0;
        if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0 || (int)$value > $max) {
            throw new InvalidArgumentException('Please enter a valid ' . str_replace('_', ' ', $field) . ' between 0 and ' . $max . '.');
        }
        $record[$field] = (int)$value;
    }
    foreach (['specialities', 'languages'] as $field) {
        $values = $input[$field] ?? [];
        if (!is_array($values) || count($values) > 30) throw new InvalidArgumentException('Please add no more than 30 ' . $field . '.');
        $unique = [];
        foreach ($values as $value) {
            if (!is_string($value) || mb_strlen(trim($value)) > 100 || preg_match('/[\x00-\x1F\x7F]/', $value)) throw new InvalidArgumentException('Please enter valid ' . $field . '.');
            $value = trim($value);
            if ($value !== '') $unique[mb_strtolower($value)] ??= $value;
        }
        $record[$field] = array_values($unique);
    }
    $flag = filter_var($input['is_sample'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($flag === null) throw new InvalidArgumentException('Please select a valid sample profile option.');
    $record['is_sample'] = $flag;
    return $record;
}

/** Save a new profile or update the locked original profile, and return its URL slug. */
function agentSave(array $agent, ?string $originalSlug = null): string
{
    $record = agentValidated($agent);
    if ($originalSlug !== null && !agentValidSlug($originalSlug)) throw new InvalidArgumentException('Invalid original agent URL.');
    $db = Database::getConnection();
    $old = null;
    $db->beginTransaction();
    try {
        if ($originalSlug !== null) {
            $statement = $db->prepare('SELECT id,slug,payload FROM agents WHERE slug=? FOR UPDATE');
            $statement->execute([$originalSlug]);
            $row = $statement->fetch();
            if (!$row) throw new RuntimeException('This agent no longer exists. Please refresh the directory.');
            $old = agentRecord($row);
            $db->prepare('UPDATE agents SET slug=?,payload=? WHERE id=?')->execute([$record['slug'], json_encode($record, JSON_THROW_ON_ERROR), $row['id']]);
        } else {
            $db->prepare('INSERT INTO agents (slug,payload) VALUES (?,?)')->execute([$record['slug'], json_encode($record, JSON_THROW_ON_ERROR)]);
        }
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        if ($exception instanceof PDOException && ($exception->errorInfo[1] ?? null) === 1062) throw new InvalidArgumentException('An agent already uses this URL. Please choose a different URL.', 0, $exception);
        throw $exception;
    }
    if ($old && $old['photo'] !== $record['photo']) agentCleanupUploads([$old['photo']]);
    return $record['slug'];
}

function agentSetPublication(string $slug, string $status): void
{
    if (!agentValidSlug($slug) || !in_array($status, ['published', 'unpublished'], true)) throw new InvalidArgumentException('Invalid agent visibility.');
    $db = Database::getConnection();
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT payload FROM agents WHERE slug=? FOR UPDATE');
        $statement->execute([$slug]);
        $payload = $statement->fetchColumn();
        if ($payload === false) throw new RuntimeException('Agent not found.');
        $record = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $record['status'] = $status;
        $db->prepare('UPDATE agents SET payload=? WHERE slug=?')->execute([json_encode($record, JSON_THROW_ON_ERROR), $slug]);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        throw $exception;
    }
}

function agentDelete(string $slug): void
{
    if (!agentValidSlug($slug)) throw new InvalidArgumentException('Invalid agent URL.');
    $db = Database::getConnection();
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT payload FROM agents WHERE slug=? FOR UPDATE');
        $statement->execute([$slug]);
        $payload = $statement->fetchColumn();
        if ($payload === false) throw new RuntimeException('Agent not found.');
        $record = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $db->prepare('DELETE FROM agents WHERE slug=?')->execute([$slug]);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        throw $exception;
    }
    agentCleanupUploads([$record['photo'] ?? '']);
}

/** Returns an empty string when no file was selected. */
function agentUploadPhoto(array $file): string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) return '';
    if ($error !== UPLOAD_ERR_OK) {
        $message = in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'The agent photo is too large. Please upload an image up to 5 MB.' : 'The agent photo could not be uploaded. Please try again.';
        throw new InvalidArgumentException($message);
    }
    $temp = $file['tmp_name'] ?? '';
    if (!is_string($temp) || !is_uploaded_file($temp)) throw new InvalidArgumentException('Please select a valid agent photo upload.');
    $size = filesize($temp);
    if ($size === false || $size <= 0 || $size > 5 * 1024 * 1024) throw new InvalidArgumentException('Please upload an agent photo up to 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $dimensions = @getimagesize($temp);
    if (!isset($extensions[$mime]) || !$dimensions || ($dimensions['mime'] ?? '') !== $mime) throw new InvalidArgumentException('Agent photos must be JPG, PNG or WebP images.');
    $directory = dirname(__DIR__) . '/assets/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('The agent photo folder could not be created.');
    $name = 'agent-' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($temp, $directory . '/' . $name)) throw new RuntimeException('The agent photo could not be saved. Please try again.');
    return './assets/uploads/' . $name;
}

/** Delete only unreferenced files created by agentUploadPhoto. */
function agentCleanupUploads(array $paths): void
{
    $owned = [];
    array_walk_recursive($paths, static function ($path) use (&$owned): void {
        if (is_string($path) && preg_match('~^\./assets/uploads/agent-[a-f0-9]{32}\.(?:jpg|png|webp)$~D', $path)) $owned[$path] = true;
    });
    if (!$owned) return;
    $base = realpath(dirname(__DIR__) . '/assets/uploads');
    if ($base === false) return;
    foreach (array_keys($owned) as $url) {
        try {
            $referenced = false;
            foreach (['agents', 'projects', 'properties', 'blog_posts', 'company_profile'] as $table) {
                $statement = Database::getConnection()->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE payload LIKE ?');
                $statement->execute(['%' . basename($url) . '%']);
                if ((int)$statement->fetchColumn() > 0) { $referenced = true; break; }
            }
            if ($referenced) continue;
            $path = realpath($base . '/' . basename($url));
            if ($path !== false && is_file($path) && str_starts_with(strtolower(str_replace('\\', '/', $path)), strtolower(str_replace('\\', '/', $base)) . '/')) {
                if (!unlink($path)) error_log('An unused agent photo could not be removed.');
            }
        } catch (Throwable $exception) {
            error_log('Agent photo cleanup could not complete.');
        }
    }
}
