<?php

require_once __DIR__ . '/Config.php';

class RedisClient
{
    private static $instance = null;
    private $redis = null;

    private function __construct()
    {
        $config = Config::getInstance();
        $redisConfig = $config->getRedisConfig();

        try {
            $this->redis = new Redis();
            $this->redis->connect($redisConfig['host'], $redisConfig['port']);
            
            // Test connection
            if (!$this->redis->ping()) {
                throw new Exception("Redis connection failed");
            }
        } catch (Exception $e) {
            error_log("Redis connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->redis;
    }

    /**
     * Add event to Redis Stream
     * 
     * @param string $stream Stream name
     * @param array $fields Field-value pairs for the event
     * @return string|false Stream ID or false on failure
     */
    public function addToStream($stream, array $fields)
    {
        try {
            // Use * to auto-generate stream ID
            $id = $this->redis->xAdd($stream, '*', $fields);
            
            // Trim stream to keep it manageable (approximate trimming)
            // Keep last ~1M entries
            $this->redis->xTrim($stream, 'MAXLEN', '~', 1000000);
            
            return $id;
        } catch (Exception $e) {
            error_log("Redis stream add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read messages from stream using consumer group
     * 
     * @param string $stream Stream name
     * @param string $group Consumer group name
     * @param string $consumer Consumer name
     * @param int $count Number of messages to read
     * @param int $block Block time in milliseconds (0 = no blocking)
     * @return array|false Messages or false on failure
     */
    public function readGroup($stream, $group, $consumer, $count = 1000, $block = 1000)
    {
        try {
            // Read new messages using consumer group
            // xReadGroup(group, consumer, streams, count, block)
            $messages = $this->redis->xReadGroup(
                $group,
                $consumer,
                [$stream => '>'], // '>' means new messages
                $count,
                $block
            );
            
            return $messages ?: [];
        } catch (Exception $e) {
            // If group doesn't exist, create it
            if (strpos($e->getMessage(), 'NOGROUP') !== false || 
                strpos($e->getMessage(), 'BUSYGROUP') === false) {
                try {
                    // Create consumer group starting from beginning (0) or latest ($)
                    $this->redis->xGroup('CREATE', $stream, $group, '0', true);
                    // Retry reading
                    return $this->readGroup($stream, $group, $consumer, $count, $block);
                } catch (Exception $e2) {
                    // Group might already exist, that's okay
                    if (strpos($e2->getMessage(), 'BUSYGROUP') === false) {
                        error_log("Redis group creation error: " . $e2->getMessage());
                    }
                }
            }
            error_log("Redis read group error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Acknowledge processed messages
     * 
     * @param string $stream Stream name
     * @param string $group Consumer group name
     * @param array $ids Array of message IDs to acknowledge
     * @return int|false Number of acknowledged messages or false on failure
     */
    public function acknowledge($stream, $group, array $ids)
    {
        try {
            return $this->redis->xAck($stream, $group, $ids);
        } catch (Exception $e) {
            error_log("Redis ack error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete messages from stream
     * 
     * @param string $stream Stream name
     * @param array $ids Array of message IDs to delete
     * @return int|false Number of deleted messages or false on failure
     */
    public function deleteMessages($stream, array $ids)
    {
        try {
            $deleted = 0;
            foreach ($ids as $id) {
                $result = $this->redis->xDel($stream, $id);
                if ($result) {
                    $deleted++;
                }
            }
            return $deleted;
        } catch (Exception $e) {
            error_log("Redis delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending messages for a consumer group
     * 
     * @param string $stream Stream name
     * @param string $group Consumer group name
     * @param string|null $consumer Optional consumer name filter
     * @param int $count Number of messages to return
     * @return array|false Pending messages or false on failure
     */
    public function getPending($stream, $group, $consumer = null, $count = 1000)
    {
        try {
            $start = '-';
            $end = '+';
            
            if ($consumer) {
                return $this->redis->xPending($stream, $group, $start, $end, $count, $consumer);
            } else {
                return $this->redis->xPending($stream, $group, $start, $end, $count);
            }
        } catch (Exception $e) {
            error_log("Redis pending error: " . $e->getMessage());
            return false;
        }
    }
}

