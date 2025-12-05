<?php

require_once __DIR__ . '/RedisClient.php';

class ImpressionProducer
{
    private $redis;
    private $stream = 'impressions_stream';

    public function __construct()
    {
        $this->redis = RedisClient::getInstance();
    }

    /**
     * Track an ad impression
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
     * Track multiple impressions in batch
     * 
     * @param array $impressions Array of ['ad_id' => int, 'ts' => int]
     * @return array Array of stream IDs
     */
    public function trackBatch(array $impressions)
    {
        $ids = [];
        foreach ($impressions as $impression) {
            $id = $this->track(
                $impression['ad_id'],
                $impression['ts'] ?? null
            );
            if ($id) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

