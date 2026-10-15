<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/Config/visitor-tracking.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
if($_SERVER['REQUEST_METHOD']==='GET'&&($_GET['action']??'')==='token'){
    echo json_encode(['csrf'=>$_SESSION['visitor_tracking_csrf']??=bin2hex(random_bytes(32))],JSON_THROW_ON_ERROR);exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') {header('Allow: POST');http_response_code(405);echo json_encode(['error'=>'POST required.']);exit;}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>4096) {http_response_code(413);echo json_encode(['error'=>'Request too large.']);exit;}
$csrf=$_POST['csrf']??null;
if (!is_string($csrf)||!is_string($_SESSION['visitor_tracking_csrf']??null)||!hash_equals($_SESSION['visitor_tracking_csrf'],$csrf)) {http_response_code(403);echo json_encode(['error'=>'Invalid request.']);exit;}
try {
    foreach (['visitor_id','view_key','kind','key'] as $field) if (!is_string($_POST[$field]??null)) throw new InvalidArgumentException('Invalid page view.');
    $result=visitorTrackView($_POST['visitor_id'],$_POST['view_key'],$_POST['kind'],$_POST['key']);
    session_write_close();echo json_encode($result,JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {http_response_code(422);echo json_encode(['error'=>$error->getMessage()]);}
catch (Throwable $error) {error_log('Visitor page view could not be saved.');http_response_code(503);echo json_encode(['error'=>'View reporting is temporarily unavailable.']);}
