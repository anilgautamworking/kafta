<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/ImpressionProducer.php';

try {
    // Get ad_id from GET or POST
    $adId = null;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ad_id'])) {
        $adId = (int)$_GET['ad_id'];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if ($data && isset($data['ad_id'])) {
            $adId = (int)$data['ad_id'];
        } elseif (isset($_POST['ad_id'])) {
            $adId = (int)$_POST['ad_id'];
        }
    }

    if (!$adId || $adId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing ad_id parameter']);
        exit;
    }

    // Track impression
    $producer = new ImpressionProducer();
    $streamId = $producer->track($adId);

    if ($streamId) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'ad_id' => $adId,
            'stream_id' => $streamId,
            'ts' => time(),
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to track impression']);
    }
} catch (Exception $e) {
    error_log("Track impression error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

