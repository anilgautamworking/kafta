<?php

/**
 * Click Consumer Worker Entry Point
 * Run this script to start processing clicks from Redis Streams
 */

require_once __DIR__ . '/../src/ClickConsumer.php';

try {
    $consumer = new ClickConsumer('click_worker_1', 1000);
    $consumer->process();
} catch (Exception $e) {
    error_log("Fatal error in click consumer: " . $e->getMessage());
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

