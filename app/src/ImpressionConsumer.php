<?php

require_once __DIR__ . '/RedisClient.php';
require_once __DIR__ . '/Database.php';

class ImpressionConsumer
{
    private $redis;
    private $db;
    private $stream = 'impressions_stream';
    private $group = 'impression_group';
    private $consumer;
    private $batchSize = 1000;

    public function __construct($consumer = 'impression_worker_1', $batchSize = 1000)
    {
        $this->redis = RedisClient::getInstance();
        $this->db = Database::getInstance();
        $this->consumer = $consumer;
        $this->batchSize = $batchSize;
    }

    /**
     * Process messages from the impressions stream
     */
    public function process()
    {
        echo "Starting Impression Consumer...\n";
        echo "Stream: {$this->stream}\n";
        echo "Group: {$this->group}\n";
        echo "Consumer: {$this->consumer}\n";
        echo "Batch size: {$this->batchSize}\n\n";

        // Ensure consumer group exists
        $this->ensureGroup();

        while (true) {
            try {
                // Read messages from stream
                $messages = $this->redis->readGroup(
                    $this->stream,
                    $this->group,
                    $this->consumer,
                    $this->batchSize,
                    1000 // Block for 1 second
                );

                if (empty($messages)) {
                    continue;
                }

                // Process batch
                $this->processBatch($messages);

            } catch (Exception $e) {
                error_log("Error in impression consumer loop: " . $e->getMessage());
                sleep(1); // Wait before retrying
            }
        }
    }

    /**
     * Ensure consumer group exists
     */
    private function ensureGroup()
    {
        try {
            $redis = $this->redis->getConnection();
            // Try to create group, ignore if it already exists
            $redis->xGroup('CREATE', $this->stream, $this->group, '0', true);
        } catch (Exception $e) {
            // Group might already exist, that's okay
            if (strpos($e->getMessage(), 'BUSYGROUP') === false) {
                error_log("Error creating consumer group: " . $e->getMessage());
            }
        }
    }

    /**
     * Process a batch of messages
     * 
     * @param array $messages Messages from Redis Stream
     */
    private function processBatch(array $messages)
    {
        if (empty($messages)) {
            return;
        }

        // Extract messages from Redis response format
        $events = [];
        $messageIds = [];

        foreach ($messages as $streamName => $streamMessages) {
            foreach ($streamMessages as $id => $fields) {
                $messageIds[] = $id;
                
                if (isset($fields['ad_id']) && isset($fields['ts'])) {
                    $events[] = [
                        'ad_id' => (int)$fields['ad_id'],
                        'ts' => (int)$fields['ts'],
                        'id' => $id,
                    ];
                }
            }
        }

        if (empty($events)) {
            return;
        }

        echo "Processing batch of " . count($events) . " impression events...\n";

        // Aggregate impressions by ad_id and date
        $aggregated = [];
        foreach ($events as $event) {
            $adId = $event['ad_id'];
            $date = date('Y-m-d', $event['ts']);

            $key = $adId . '_' . $date;
            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'ad_id' => $adId,
                    'date' => $date,
                    'impressions' => 0,
                ];
            }
            $aggregated[$key]['impressions']++;
        }

        // Write to database
        try {
            $this->db->beginTransaction();

            foreach ($aggregated as $stats) {
                $this->db->upsertImpressionStats(
                    $stats['ad_id'],
                    $stats['date'],
                    $stats['impressions']
                );
            }

            $this->db->commit();

            echo "Successfully processed " . count($aggregated) . " unique ad/date combinations\n";

            // Acknowledge processed messages
            $this->redis->acknowledge($this->stream, $this->group, $messageIds);
            echo "Acknowledged " . count($messageIds) . " messages\n\n";

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error processing impression batch: " . $e->getMessage());
            throw $e;
        }
    }
}

