<?php
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataDir = dirname(__DIR__) . '/data';
$dataPath = $dataDir . '/rsvp.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

if ($method === 'POST') {
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

    if (!in_array($kehadiran, ['hadir', 'tidak_hadir', 'Ya, saya akan hadir', 'Tidak bisa Hadir'], true)) {
        $kehadiran = 'hadir';
    }

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
