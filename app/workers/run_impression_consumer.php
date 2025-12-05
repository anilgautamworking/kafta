<?php

/**
 * Impression Consumer Worker Entry Point
 * Run this script to start processing impressions from Redis Streams
 */

require_once __DIR__ . '/../src/ImpressionConsumer.php';

try {
    $consumer = new ImpressionConsumer('impression_worker_1', 1000);
    $consumer->process();
} catch (Exception $e) {
    error_log("Fatal error in impression consumer: " . $e->getMessage());
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

