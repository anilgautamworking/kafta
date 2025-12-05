<?php

require_once __DIR__ . '/RedisClient.php';

class ClickProducer
{
    private $redis;
    private $stream = 'clicks_stream';

    public function __construct()
    {
        $this->redis = RedisClient::getInstance();
    }

    /**
     * Track an ad click
     * 
     * @param int $adId Ad ID
     * @param int|null $timestamp Unix timestamp (null = current time)
     * @return string|false Stream ID or false on failure
     */
    public function track($adId, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $fields = [
            'ad_id' => (string)$adId,
            'ts' => (string)$timestamp,
        ];

        return $this->redis->addToStream($this->stream, $fields);
    }

    /**
     * Track multiple clicks in batch
     * 
     * @param array $clicks Array of ['ad_id' => int, 'ts' => int]
     * @return array Array of stream IDs
     */
    public function trackBatch(array $clicks)
    {
        $ids = [];
        foreach ($clicks as $click) {
            $id = $this->track(
                $click['ad_id'],
                $click['ts'] ?? null
            );
            if ($id) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

