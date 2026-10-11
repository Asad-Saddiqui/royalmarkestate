<?php
declare(strict_types=1);
function passwordResetEmailHtml(string $link,string $logo='cid:royalmark-logo'): string
{
    $safe=htmlspecialchars($link,ENT_QUOTES,'UTF-8');$image=htmlspecialchars($logo,ENT_QUOTES,'UTF-8');$year=date('Y');
    return <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset your password | Royalmark Estate</title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#202124;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your secure password reset link is ready. Valid for 30 minutes.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;"><tr><td align="center" style="padding:36px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border:1px solid #e5e7eb;border-top:4px solid #d70000;border-radius:4px;">
<tr><td align="center" style="padding:30px 24px 24px;border-bottom:1px solid #eeeeee;"><img src="$image" width="142" height="83" alt="Royalmark Estate" style="display:block;width:142px;height:83px;border:0;"></td></tr>
<tr><td style="padding:32px 28px 14px;"><p style="margin:0 0 14px;color:#d70000;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;">ACCOUNT SECURITY</p><h1 style="margin:0 0 18px;font-size:28px;line-height:36px;font-weight:700;color:#202124;">Reset your password</h1><p style="margin:0;font-size:15px;line-height:25px;color:#626975;">We received a request to reset the password for your Royalmark Estate account. Choose a new password using the button below.</p></td></tr>
<tr><td style="padding:14px 28px 28px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#d70000" style="border-radius:3px;mso-padding-alt:15px 26px;"><a href="$safe" style="display:inline-block;padding:15px 26px;color:#ffffff;background:#d70000;font-size:14px;line-height:20px;font-weight:bold;text-decoration:none;border-radius:3px;">Reset my password &rarr;</a></td></tr></table></td></tr>
<tr><td style="padding:0 28px 26px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff4f4;border-left:3px solid #d70000;"><tr><td style="padding:14px 16px;font-size:13px;line-height:21px;color:#6a3c3c;"><strong style="color:#9f2424;">Valid for 30 minutes</strong><br>This secure link can only be used once.</td></tr></table></td></tr>
<tr><td style="padding:0 28px 26px;"><p style="margin:0;font-size:13px;line-height:22px;color:#626975;">Didn't request a password reset? You can safely ignore this email. Your password will remain unchanged.</p></td></tr>
<tr><td style="padding:22px 28px;border-top:1px solid #eeeeee;background:#fafafa;"><p style="margin:0 0 9px;font-size:12px;line-height:20px;color:#626975;">If the button doesn't work, copy and paste this link into your browser:</p><p style="margin:0;font-size:11px;line-height:18px;word-break:break-all;overflow-wrap:anywhere;"><a href="$safe" style="color:#b90000;text-decoration:underline;word-break:break-all;">$safe</a></p></td></tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;"><tr><td align="center" style="padding:24px 16px;font-size:11px;line-height:20px;color:#8a9099;">&copy; $year Royalmark Estate<br>This is an automated security email. Please do not reply.</td></tr></table>
</td></tr></table></body></html>
HTML;
}
