<?php

require_once __DIR__ . '/Config.php';

class Database
{
    private static $instance = null;
    private $pdo = null;

    private function __construct()
    {
        $config = Config::getInstance();
        $dbConfig = $config->getDatabaseConfig();

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $dbConfig['host'],
            $dbConfig['name']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $dbConfig['user'],
                $dbConfig['pass'],
                $options
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
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
        return $this->pdo;
    }

    /**
     * Upsert impression stats
     * 
     * @param int $adId Ad ID
     * @param string $date Date in Y-m-d format
     * @param int $impressions Number of impressions to add
     * @return bool Success
     */
    public function upsertImpressionStats($adId, $date, $impressions)
    {
        $sql = "INSERT INTO ad_daily_impressions (ad_id, date, impressions)
                VALUES (:ad_id, :date, :impressions)
                ON DUPLICATE KEY UPDATE impressions = impressions + VALUES(impressions)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':ad_id' => $adId,
            ':date' => $date,
            ':impressions' => $impressions,
        ]);
    }

    /**
     * Upsert click stats
     * 
     * @param int $adId Ad ID
     * @param string $date Date in Y-m-d format
     * @param int $clicks Number of clicks to add
     * @return bool Success
     */
    public function upsertClickStats($adId, $date, $clicks)
    {
        $sql = "INSERT INTO ad_daily_clicks (ad_id, date, clicks)
                VALUES (:ad_id, :date, :clicks)
                ON DUPLICATE KEY UPDATE clicks = clicks + VALUES(clicks)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':ad_id' => $adId,
            ':date' => $date,
            ':clicks' => $clicks,
        ]);
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollback()
    {
        return $this->pdo->rollBack();
    }
}
