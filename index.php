<?php
date_default_timezone_set('Asia/Jakarta');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. API RSVP Endpoint
if ($uri === '/api/rsvp' || $uri === '/api/rsvp.php' || strpos($uri, 'api/rsvp') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $dataPath = __DIR__ . '/data/rsvp.json';

    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0777, true);
    }

    if ($method === 'POST') {
        // Read input (support JSON or standard form-data)
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (!$input) {
            $input = $_POST;
        }

        $nama = trim($input['nama'] ?? '');
        $kehadiran = trim($input['kehadiran'] ?? '');
        $doa = trim($input['doa'] ?? ($input['pesan'] ?? ''));

        if ($nama === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Nama wajib diisi.']);
            exit;
        }

        // Fitur Reset Data RSVP oleh Admin
        if (strcasecmp($nama, 'Admin') === 0 && $doa === '@reset-data-rsvp') {
            file_put_contents($dataPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            echo json_encode([
                'success' => true,
                'reset' => true,
                'message' => 'Seluruh data RSVP telah berhasil dihapus (reset).'
            ]);
            exit;
        }

        if (!in_array($kehadiran, ['hadir', 'tidak_hadir', 'Ya, saya akan hadir', 'Tidak bisa Hadir'], true)) {
            $kehadiran = 'hadir';
        }

        // Normalise status
        if ($kehadiran === 'Ya, saya akan hadir') {
            $kehadiran = 'hadir';
        } elseif ($kehadiran === 'Tidak bisa Hadir') {
            $kehadiran = 'tidak_hadir';
        }

        $cleanNama = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
        $cleanDoa = htmlspecialchars($doa, ENT_QUOTES, 'UTF-8');

        $bulanIndo = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $formattedDate = date('j') . ' ' . $bulanIndo[(int)date('n')] . ' ' . date('Y') . ', ' . date('H:i') . ' WIB';

        $newEntry = [
            'id' => 'rsvp_' . time() . '_' . bin2hex(random_bytes(3)),
            'nama' => $cleanNama,
            'kehadiran' => $kehadiran,
            'doa' => $cleanDoa !== '' ? $cleanDoa : 'Turut mendoakan kelancaran dan kebahagiaan kedua mempelai.',
            'waktu' => $formattedDate
        ];

        $rsvpList = [];
        if (file_exists($dataPath)) {
            $content = file_get_contents($dataPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $rsvpList = $decoded;
            }
        }

        // Insert at beginning (newest first)
        array_unshift($rsvpList, $newEntry);
        file_put_contents($dataPath, json_encode($rsvpList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        echo json_encode(['success' => true, 'message' => 'Konfirmasi & doa Anda berhasil dikirim!', 'data' => $newEntry]);
        exit;
    }

    // GET: Retrieve all RSVPs + stats
    $rsvpList = [];
    if (file_exists($dataPath)) {
        $content = file_get_contents($dataPath);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $rsvpList = $decoded;
        }
    }

    $countHadir = 0;
    $countTidakHadir = 0;
    foreach ($rsvpList as $item) {
        if (($item['kehadiran'] ?? '') === 'hadir') {
            $countHadir++;
        } else {
            $countTidakHadir++;
        }
    }

    echo json_encode([
        'success' => true,
        'total' => count($rsvpList),
        'hadir' => $countHadir,
        'tidak_hadir' => $countTidakHadir,
        'data' => $rsvpList
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Serve widget iframe requests
if (strpos($uri, 'website-element-widget') !== false) {
    header('Content-Type: text/html; charset=utf-8');
    $pageIndex = $_GET['pageIndex'] ?? 1;
    if ($pageIndex == 2) {
        readfile(__DIR__ . '/widget_2.html');
    } else {
        readfile(__DIR__ . '/widget_1.html');
    }
    exit;
}

// 3. Serve footer request (empty)
if ($uri === '/_footer' || strpos($uri, '/_footer') === 0) {
    header('Content-Type: text/html; charset=utf-8');
    echo '';
    exit;
}

// 4. Serve static files if requested through PHP
$filePath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'm3u' => 'application/x-mpegURL',
        'm3u8' => 'application/x-mpegURL',
        'mp4' => 'video/mp4',
        'mp3' => 'audio/mpeg',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'json' => 'application/json',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($filePath);
    exit;
}

// Return 404 for missing static assets so browser does not parse HTML as JS/CSS
if (strpos($uri, '/_assets/') !== false || preg_match('/\.(js|css|png|jpe?g|gif|svg|woff2?|ttf|eot|mp4|m3u8|m3u|json|ico|map)$/i', $uri)) {
    http_response_code(404);
    exit;
}

// 5. Default SPA fallback: render index.html for all routes
header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/index.html');
