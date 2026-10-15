<?php
declare(strict_types=1);

/** Session-bound, single-use verification images; no external CAPTCHA service. */
function bookingCaptchaToken(): string
{
    return $_SESSION['booking_captcha_csrf'] ??= bin2hex(random_bytes(32));
}

function bookingCaptchaPrune(): void
{
    $challenges = $_SESSION['booking_captchas'] ?? [];
    if (!is_array($challenges)) $challenges = [];
    foreach ($challenges as $id => $challenge) {
        if (!is_array($challenge) || (int)($challenge['expires_at'] ?? 0) < time()) unset($challenges[$id]);
    }
    $_SESSION['booking_captchas'] = array_slice($challenges, -10, null, true);
}

function bookingCaptchaScopeValid(string $scope): bool
{
    return strlen($scope) <= 210 && preg_match('/^(?:property|project):[a-z0-9]+(?:-[a-z0-9]+)*$/D', $scope) === 1;
}

function bookingCaptchaCreate(string $scope): string
{
    if (!bookingCaptchaScopeValid($scope)) throw new InvalidArgumentException('Invalid verification form.');
    bookingCaptchaPrune();
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 5; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    $id = bin2hex(random_bytes(16));
    $secret = $_SESSION['booking_captcha_secret'] ??= bin2hex(random_bytes(32));
    $_SESSION['booking_captchas'][$id] = [
        'scope' => $scope,
        'hash' => hash_hmac('sha256', $code, $secret),
        'expires_at' => time() + 600,
        'image' => bookingCaptchaSvg($code),
    ];
    bookingCaptchaPrune();
    return $id;
}

function bookingCaptchaVerify(string $scope, string $id, string $answer): bool
{
    bookingCaptchaPrune();
    if (!preg_match('/^[a-f0-9]{32}$/D', $id) || !bookingCaptchaScopeValid($scope)) return false;
    $challenge = $_SESSION['booking_captchas'][$id] ?? null;
    if (!is_array($challenge) || ($challenge['scope'] ?? '') !== $scope) return false;
    // Every attempt consumes this challenge, including an incorrect answer.
    unset($_SESSION['booking_captchas'][$id]);
    $answer = strtoupper(trim($answer));
    if (strlen($answer) !== 5 || !preg_match('/^[A-Z2-9]{5}$/D', $answer)) return false;
    return hash_equals($challenge['hash'], hash_hmac('sha256', $answer, $_SESSION['booking_captcha_secret'] ?? ''));
}

function bookingCaptchaImage(string $id): ?string
{
    bookingCaptchaPrune();
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) return null;
    $challenge = $_SESSION['booking_captchas'][$id] ?? null;
    return is_array($challenge) && is_string($challenge['image'] ?? null) ? $challenge['image'] : null;
}

function bookingCaptchaRefreshAllowed(): bool
{
    $now = time();
    $bucket = $_SESSION['booking_captcha_refreshes'] ?? [];
    $bucket = is_array($bucket) ? array_values(array_filter($bucket, static fn($stamp) => is_int($stamp) && $stamp > $now - 300)) : [];
    if (count($bucket) >= 30) return false;
    $bucket[] = $now;
    $_SESSION['booking_captcha_refreshes'] = $bucket;
    return true;
}

/** Draw bitmap glyphs as paths, keeping the answer out of HTML and SVG text. */
function bookingCaptchaSvg(string $code): string
{
    $glyphs = [
        'A'=>['01110','10001','10001','11111','10001','10001','10001'],
        'B'=>['11110','10001','10001','11110','10001','10001','11110'],
        'C'=>['01111','10000','10000','10000','10000','10000','01111'],
        'D'=>['11110','10001','10001','10001','10001','10001','11110'],
        'E'=>['11111','10000','10000','11110','10000','10000','11111'],
        'F'=>['11111','10000','10000','11110','10000','10000','10000'],
        'G'=>['01111','10000','10000','10111','10001','10001','01111'],
        'H'=>['10001','10001','10001','11111','10001','10001','10001'],
        'J'=>['00111','00010','00010','00010','10010','10010','01100'],
        'K'=>['10001','10010','10100','11000','10100','10010','10001'],
        'L'=>['10000','10000','10000','10000','10000','10000','11111'],
        'M'=>['10001','11011','10101','10101','10001','10001','10001'],
        'N'=>['10001','11001','11001','10101','10011','10011','10001'],
        'P'=>['11110','10001','10001','11110','10000','10000','10000'],
        'Q'=>['01110','10001','10001','10001','10101','10010','01101'],
        'R'=>['11110','10001','10001','11110','10100','10010','10001'],
        'S'=>['01111','10000','10000','01110','00001','00001','11110'],
        'T'=>['11111','00100','00100','00100','00100','00100','00100'],
        'U'=>['10001','10001','10001','10001','10001','10001','01110'],
        'V'=>['10001','10001','10001','10001','10001','01010','00100'],
        'W'=>['10001','10001','10001','10101','10101','11011','10001'],
        'X'=>['10001','10001','01010','00100','01010','10001','10001'],
        'Y'=>['10001','10001','01010','00100','00100','00100','00100'],
        'Z'=>['11111','00001','00010','00100','01000','10000','11111'],
        '2'=>['01110','10001','00001','00010','00100','01000','11111'],
        '3'=>['11110','00001','00001','01110','00001','00001','11110'],
        '4'=>['00010','00110','01010','10010','11111','00010','00010'],
        '5'=>['11111','10000','10000','11110','00001','00001','11110'],
        '6'=>['01110','10000','10000','11110','10001','10001','01110'],
        '7'=>['11111','00001','00010','00100','01000','01000','01000'],
        '8'=>['01110','10001','10001','01110','10001','10001','01110'],
        '9'=>['01110','10001','10001','01111','00001','00001','01110'],
    ];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 224 76" width="224" height="76"><rect width="224" height="76" rx="3" fill="#fff9fa"/>';
    for ($i = 0; $i < 80; $i++) $svg .= '<circle cx="'.random_int(2,222).'" cy="'.random_int(2,74).'" r="'.(random_int(5,12)/10).'" fill="#b48d94" opacity=".35"/>';
    $colours = ['#343b4d','#642b3b','#354857','#592e42'];
    foreach (str_split($code) as $index => $character) {
        $path = '';
        foreach ($glyphs[$character] as $row => $bits) {
            foreach (str_split($bits) as $column => $bit) {
                if ($bit === '1') $path .= 'M'.($column*4).' '.($row*4).'h4v4h-4z';
            }
        }
        $x = 24 + $index*38 + random_int(-2,2); $y = 23 + random_int(-4,4);
        $svg .= '<g transform="translate('.$x.' '.$y.')"><path transform="rotate('.random_int(-12,12).' 10 14)" d="'.$path.'" fill="'.$colours[random_int(0,3)].'"/></g>';
    }
    for ($i = 0; $i < 3; $i++) $svg .= '<path d="M0 '.random_int(10,66).'Q112 '.random_int(0,76).' 224 '.random_int(10,66).'" fill="none" stroke="#bc7282" stroke-width=".8" opacity=".5"/>';
    return $svg.'</svg>';
}
