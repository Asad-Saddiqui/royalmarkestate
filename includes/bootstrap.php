<?php
require_once dirname(__DIR__) . '/Config/Database.php';
date_default_timezone_set('Asia/Karachi');
if(session_status()!==PHP_SESSION_ACTIVE){$publicSessionPath=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php')),'/');if(str_ends_with($publicSessionPath,'/admin'))$publicSessionPath=dirname($publicSessionPath);session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','path'=>($publicSessionPath?:'').'/']);session_start();}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$siteRoutes = [
    'home' => ['', 'Home'], 'properties' => ['property-listing', 'Properties'],
    'projects' => ['projects', 'Projects'], 'contact' => ['contact-us', 'Contact Us'],
    'about' => ['about-us', 'About'], 'blog' => ['blog', 'Blog'],
];
function menuIcon(string $name): string {
    $paths = [
        'home'=>'<path d="m3 10 9-7 9 7v10H15v-7H9v7H3z"/>',
        'properties'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'projects'=>'<path d="M3 21V7l6-3v17M9 21V3h8v18M17 9h4v12M2 21h20M12 7h2M12 11h2M12 15h2"/>',
        'contact'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'about'=>'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
        'blog'=>'<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/>',
    ];
    return '<svg class="rm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? '').'</svg>';
}
