<?php
session_start();
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Contact Us'; $activePage='contact';
if (empty($_SESSION['contact_csrf'])) $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
$errors=[]; $values=['name'=>'','email'=>'','phone'=>'','interest'=>'','message'=>''];
if (is_string($_GET['interest'] ?? null)) $values['interest'] = mb_substr(trim($_GET['interest']),0,120);
$success = !empty($_SESSION['contact_success']); unset($_SESSION['contact_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 foreach ($values as $key => $value) $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
 $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
 if (!hash_equals($_SESSION['contact_csrf'], $token)) { http_response_code(403); $errors[]='Your form session expired. Please try again.'; }
 if ($values['name']==='' || mb_strlen($values['name'])>100) $errors[]='Enter your name (up to 100 characters).';
 if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email'])>254) $errors[]='Enter a valid email address.';
 if (mb_strlen($values['phone'])>40) $errors[]='Keep the phone number within 40 characters.';
 if (mb_strlen($values['interest'])>120) $errors[]='Keep the subject within 120 characters.';
 if ($values['message']==='' || mb_strlen($values['message'])>5000) $errors[]='Enter a message (up to 5,000 characters).';
 if (!$errors) {
  try {
   $db=Database::getConnection();
   $statement=$db->prepare('INSERT INTO contact_messages (name,email,phone,interest,message) VALUES (:name,:email,:phone,:interest,:message)');
   $statement->execute($values);
   $_SESSION['contact_success']=true; $_SESSION['contact_csrf']=bin2hex(random_bytes(32));
   header('Location: ./contact.php',true,303); exit;
  } catch (PDOException $exception) { error_log('Contact enquiry failed: '.$exception->getMessage()); http_response_code(503); $errors[]='We could not save your enquiry. Please try again shortly.'; }
 } elseif (http_response_code()!==403) http_response_code(422);
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><span class="eyebrow">LET US TALK</span><h1>Your next move<br><em>starts here.</em></h1><p>Tell us what you have in mind. Use the form below to send a property or project enquiry.</p></section>
<div class="contact-layout"><aside class="contact-aside"><h2>What are you looking for?</h2><p>A home, a plot or space for your business? Share the details that matter to you.</p><div class="contact-note"><strong>Include a few helpful details</strong><p>Property type, preferred area and any questions you would like to discuss.</p></div><a class="text-link" href="./property-listing">Explore properties &rarr;</a></aside>
<form class="contact-form" method="post" action="./contact.php">
<?php if($success): ?><div class="notice success" role="status">Your enquiry has been saved. Thank you for contacting Royalmark Estate.</div><?php endif; ?>
<?php if($errors): ?><div class="notice error" role="alert"><strong>Please check your enquiry:</strong><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['contact_csrf']) ?>">
<div class="form-grid"><label>Full name <span>*</span><input name="name" autocomplete="name" maxlength="100" required value="<?= e($values['name']) ?>"></label><label>Email address <span>*</span><input name="email" type="email" autocomplete="email" maxlength="254" required value="<?= e($values['email']) ?>"></label><label>Phone number <small>(optional)</small><input name="phone" type="tel" autocomplete="tel" maxlength="40" value="<?= e($values['phone']) ?>"></label><label>Interested in <small>(optional)</small><input name="interest" maxlength="120" value="<?= e($values['interest']) ?>" placeholder="Property or project"></label></div>
<label>Your message <span>*</span><textarea name="message" rows="6" maxlength="5000" required><?= e($values['message']) ?></textarea></label>
<p class="muted">Your details will be stored with your enquiry. This form does not send an email.</p><button class="button" type="submit">Send enquiry &rarr;</button>
</form></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
