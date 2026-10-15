<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/Config/booking-captcha.php';
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');
$action = is_string($_GET['action'] ?? null) ? $_GET['action'] : 'image';
if ($action === 'image' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
    $image = bookingCaptchaImage($id);
    if ($image === null) {http_response_code(404);exit;}
    header('Content-Type: image/svg+xml; charset=utf-8');
    header("Content-Security-Policy: default-src 'none'; style-src 'none'; sandbox");
    session_write_close();
    echo $image;
    exit;
}
header('Content-Type: application/json; charset=utf-8');
if ($action !== 'refresh' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: '.($action === 'refresh' ? 'POST' : 'GET'));
    http_response_code(405);
    echo json_encode(['error'=>'This verification action is unavailable.']);exit;
}
$csrf = $_POST['captcha_csrf'] ?? null;
if (!is_string($csrf) || !hash_equals(bookingCaptchaToken(), $csrf)) {
    http_response_code(403);echo json_encode(['error'=>'Reload the form to refresh verification.']);exit;
}
$scope = is_string($_POST['scope'] ?? null) ? $_POST['scope'] : '';
if (!bookingCaptchaScopeValid($scope)) {
    http_response_code(422);echo json_encode(['error'=>'This verification form is invalid.']);exit;
}
if (!bookingCaptchaRefreshAllowed()) {
    http_response_code(429);echo json_encode(['error'=>'Please wait a few minutes before requesting another code.']);exit;
}
$oldId = is_string($_POST['captcha_id'] ?? null) ? $_POST['captcha_id'] : '';
if (($_SESSION['booking_captchas'][$oldId]['scope'] ?? '') === $scope) unset($_SESSION['booking_captchas'][$oldId]);
$id = bookingCaptchaCreate($scope);
session_write_close();
echo json_encode(['id'=>$id,'image'=>'./booking-captcha.php?action=image&id='.$id],JSON_THROW_ON_ERROR);
