<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$qs  = $_SERVER['QUERY_STRING'] ?? '';
$file = __DIR__ . $uri;

// Serve real static files / existing files directly.
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Helper: locate a sibling PHP file for a given directory and base name (case-insensitive).
$findPhp = function ($absDir, $name) {
    $php = $absDir . '/' . $name . '.php';
    if (file_exists($php)) return $php;
    if (!is_dir($absDir)) return null;
    $needle = strtolower($name) . '.php';
    foreach (scandir($absDir) as $entry) {
        if (strtolower($entry) === $needle) {
            return $absDir . '/' . $entry;
        }
    }
    return null;
};

// 1) If the request still ends in .html, redirect to the clean (extensionless) URL
//    so the address bar hides the file extension. Only do this for safe (GET/HEAD)
//    requests so that form POSTs to *.html endpoints keep working.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (($method === 'GET' || $method === 'HEAD')
    && preg_match('#^(.*/)([A-Za-z0-9_\-]+)\.html$#', $uri, $m)) {
    $clean = $m[1] . $m[2];
    if ($qs !== '') $clean .= '?' . $qs;
    header('Location: ' . $clean, true, 301);
    exit;
}

// 2) Extensionless route OR a *.html POST: map /foo(.html) -> foo.php
if (preg_match('#^(.*/)([A-Za-z0-9_\-]+)(?:\.html)?$#', $uri, $m)) {
    $dir  = $m[1];
    $name = $m[2];
    $absDir = rtrim(__DIR__ . $dir, '/');
    if ($absDir === '') { $absDir = __DIR__; }

    $php = $findPhp($absDir, $name);

    // Trailing-id pattern: e.g. showTicket12 -> showTicket.php?id=12
    //                     or  vr-1          -> vr.php?id=1
    if ((!$php) && preg_match('#^(.+?)-?(\d+)$#', $name, $mm) && is_dir($absDir)) {
        $base   = rtrim($mm[1], '-');
        $idpart = $mm[2];
        $aliases = [
            'showorder'    => 'openorder.php',
            'checkrdp'     => 'check2rdp.php',
            'checkcpanel'  => 'check2cp.php',
            'checkshell'   => 'check2shell.php',
            'checksmtp'    => 'check2smtp.php',
            'checkmailer'  => 'check2mailer.php',
            'checkpm'      => 'check2pm.php',
        ];
        $php = $findPhp($absDir, $base);
        if (!$php && isset($aliases[strtolower($base)])) {
            $cand = $absDir . '/' . $aliases[strtolower($base)];
            if (file_exists($cand)) $php = $cand;
        }
        if ($php) {
            $_GET['id'] = $idpart;
            $_REQUEST['id'] = $idpart;
        }
    }

    if ($php && file_exists($php)) {
        $rel = substr($php, strlen(__DIR__));
        $_SERVER['SCRIPT_FILENAME'] = $php;
        $_SERVER['SCRIPT_NAME'] = $rel;
        chdir(dirname($php));
        require $php;
        return true;
    }
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

if (is_dir($file)) {
    foreach (['index.php', 'index.html'] as $idx) {
        if (file_exists($file . '/' . $idx)) {
            $_SERVER['SCRIPT_FILENAME'] = $file . '/' . $idx;
            require $file . '/' . $idx;
            return true;
        }
    }
}

http_response_code(404);
echo "404 Not Found";
return true;
