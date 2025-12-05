# Ad Impression + Click Tracking System

A high-performance, scalable ad tracking system built with **Redis Streams**, PHP, and MariaDB. Designed to handle 10M+ events per day with zero data loss and exactly-once processing.

## 🏗️ Architecture

```
Website → PHP Endpoints → Redis Streams → Consumer Workers → MariaDB
```

- **Frontend**: JavaScript tracking sends events to PHP endpoints
- **API**: PHP endpoints (`track_impression.php`, `track_click.php`) receive events and publish to Redis Streams
- **Message Queue**: Redis Streams for durable, scalable event streaming
- **Workers**: PHP consumers process messages in batches and aggregate to database
- **Database**: MariaDB stores daily aggregated impression and click counts

## 📋 Prerequisites

- Docker & Docker Compose
- 4GB+ RAM available
- Ports 8080, 6379, 3306 available

## 🚀 Quick Start

### 1. Start the System

```bash
chmod +x scripts/*.sh
./scripts/start.sh
```

This will:
- Start Redis with persistence enabled
- Start MariaDB with schema initialization
- Start PHP-FPM and Nginx containers
- Create Redis Streams automatically when first events arrive

### 2. Start the Workers

In separate terminals:

**Impression Worker:**
```bash
./scripts/impression-worker.sh
```

**Click Worker:**
```bash
./scripts/click-worker.sh
```

The workers will:
- Consume messages from Redis Streams (`impressions_stream` and `clicks_stream`)
- Process messages in batches of 1000
- Aggregate events by `ad_id` and `date`
- Write aggregated counts to MariaDB using UPSERT
- Acknowledge messages after successful processing

### 3. Access the Frontend

Open your browser and navigate to:

```
http://localhost:8080
```

You'll see a demo page with ad banners. Click on ads to track clicks, and impressions are tracked automatically when ads come into view.

## 📁 Project Structure

```
.
├── app/
│   ├── public/
│   │   ├── index.php              # Demo frontend page
│   │   ├── track_impression.php   # Impression tracking endpoint
│   │   └── track_click.php        # Click tracking endpoint
│   ├── src/
│   │   ├── Config.php             # Configuration manager
│   │   ├── RedisClient.php        # Redis Streams wrapper
│   │   ├── ImpressionProducer.php # Impression event producer
│   │   ├── ClickProducer.php      # Click event producer
│   │   ├── ImpressionConsumer.php # Impression batch consumer
│   │   ├── ClickConsumer.php      # Click batch consumer
│   │   └── Database.php           # Database wrapper (PDO)
│   └── workers/
│       ├── run_impression_consumer.php  # Impression worker entry point
│       └── run_click_consumer.php       # Click worker entry point
├── docker/
│   ├── php.Dockerfile             # PHP 8.2 + Redis extension
│   ├── nginx.Dockerfile           # Nginx web server
│   ├── nginx.conf                 # Nginx configuration
│   ├── mariadb.Dockerfile         # MariaDB database
│   └── redis.conf                 # Redis configuration
├── sql/
│   └── schema.sql                 # Database schema
├── scripts/
│   ├── start.sh                   # Start all services
│   ├── stop.sh                    # Stop all services
│   ├── impression-worker.sh       # Run impression consumer
│   └── click-worker.sh            # Run click consumer
├── docker-compose.yml              # Docker orchestration
├── .env                            # Environment variables
└── README.md                       # This file
```

## 🔧 Configuration

Edit `.env` file to customize:

```env
REDIS_HOST=redis
REDIS_PORT=6379
DB_HOST=mariadb
DB_USER=root
DB_PASS=password
DB_NAME=ads
```

## 📊 Database Schema

### ad_daily_impressions
```sql
CREATE TABLE ad_daily_impressions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  date DATE NOT NULL,
  impressions BIGINT DEFAULT 0,
  UNIQUE KEY(ad_id, date)
);
```

### ad_daily_clicks
```sql
CREATE TABLE ad_daily_clicks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  date DATE NOT NULL,
  clicks BIGINT DEFAULT 0,
  UNIQUE KEY(ad_id, date)
);
```

The workers use `UPSERT` to handle duplicate keys:
```sql
INSERT INTO ad_daily_impressions(ad_id, date, impressions)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE impressions = impressions + VALUES(impressions);
```

## 🔌 API Endpoints

### Track Impression

**GET** `/track_impression.php?ad_id=123`

**POST** `/track_impression.php`
```json
{
  "ad_id": 123
}
```

**Response:**
```json
{
  "status": "success",
  "ad_id": 123,
  "stream_id": "1234567890-0",
  "ts": 1234567890
}
```

### Track Click

**GET** `/track_click.php?ad_id=123`

**POST** `/track_click.php`
```json
{
  "ad_id": 123
}
```

**Response:**
```json
{
  "status": "success",
  "ad_id": 123,
  "stream_id": "1234567890-0",
  "ts": 1234567890
}
```

## 🔍 How Redis Streams Work

### Streams Created

- `impressions_stream` - Stores all impression events
- `clicks_stream` - Stores all click events

### Consumer Groups

- `impression_group` - Consumer group for impressions
- `click_group` - Consumer group for clicks

### Event Format

Each event in the stream contains:
- `ad_id` - The ad identifier
- `ts` - Unix timestamp

### Stream Trimming

Streams are automatically trimmed to keep the last ~1,000,000 entries:
```php
XTRIM impressions_stream MAXLEN ~ 1000000
```

This prevents unlimited growth while preserving recent events for replay.

## 🔄 Replaying Messages

### View Pending Messages

```bash
docker exec -it adly_redis redis-cli XPENDING impressions_stream impression_group
```

### Read Pending Messages for a Consumer

```bash
docker exec -it adly_redis redis-cli XPENDING impressions_stream impression_group - + 100 impression_worker_1
```

### Replay from Beginning

To replay all messages from the beginning:

1. Delete the consumer group:
```bash
docker exec -it adly_redis redis-cli XGROUP DESTROY impressions_stream impression_group
```

2. Restart the worker - it will create a new group and read from the beginning

### Read Messages Manually

```bash
# Read last 10 messages
docker exec -it adly_redis redis-cli XREVRANGE impressions_stream + - COUNT 10

# Read from a specific ID
docker exec -it adly_redis redis-cli XREAD STREAMS impressions_stream 0
```

## 📈 Monitoring & Debugging

### Check Container Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f redis
docker-compose logs -f php
docker-compose logs -f mariadb
```

### Check Redis Streams

```bash
# List all streams
docker exec -it adly_redis redis-cli KEYS "*stream*"

# Get stream info
docker exec -it adly_redis redis-cli XINFO STREAM impressions_stream

# Get consumer group info
docker exec -it adly_redis redis-cli XINFO GROUPS impressions_stream

# Read messages (last 10)
docker exec -it adly_redis redis-cli XREVRANGE impressions_stream + - COUNT 10
```

### Check Database

```bash
# View impression stats
docker exec -it adly_mariadb mysql -uroot -ppassword ads -e "SELECT * FROM ad_daily_impressions ORDER BY date DESC LIMIT 10;"

# View click stats
docker exec -it adly_mariadb mysql -uroot -ppassword ads -e "SELECT * FROM ad_daily_clicks ORDER BY date DESC LIMIT 10;"

# View combined stats
docker exec -it adly_mariadb mysql -uroot -ppassword ads -e "
SELECT 
    i.ad_id, 
    i.date, 
    i.impressions, 
    COALESCE(c.clicks, 0) as clicks,
    ROUND(COALESCE(c.clicks, 0) * 100.0 / i.impressions, 2) as ctr
FROM ad_daily_impressions i
LEFT JOIN ad_daily_clicks c ON i.ad_id = c.ad_id AND i.date = c.date
ORDER BY i.date DESC, i.ad_id
LIMIT 20;"
```

### Test Tracking Endpoints

```bash
# Track impression
curl "http://localhost:8080/track_impression.php?ad_id=123"

# Track click
curl "http://localhost:8080/track_click.php?ad_id=123"

# Track via POST
curl -X POST http://localhost:8080/track_impression.php \
  -H "Content-Type: application/json" \
  -d '{"ad_id": 456}'
```

## 🛠️ Development

### Running Workers Manually

```bash
# Impression worker
docker exec -it adly_php php /app/workers/run_impression_consumer.php

# Click worker
docker exec -it adly_php php /app/workers/run_click_consumer.php
```

### PHP Classes Usage

**ImpressionProducer:**
```php
require_once 'src/ImpressionProducer.php';
$producer = new ImpressionProducer();
$streamId = $producer->track($adId, $timestamp);
```

**ClickProducer:**
```php
require_once 'src/ClickProducer.php';
$producer = new ClickProducer();
$streamId = $producer->track($adId, $timestamp);
```

**ImpressionConsumer:**
```php
require_once 'src/ImpressionConsumer.php';
$consumer = new ImpressionConsumer('impression_worker_1', 1000);
$consumer->process(); // Runs indefinitely
```

## 🎯 Features

- ✅ **Zero Data Loss**: Redis Streams with AOF persistence
- ✅ **Exactly-Once Processing**: Consumer groups with acknowledgments
- ✅ **Batch Processing**: Processes 1000 messages at a time for efficiency
- ✅ **Automatic Trimming**: Streams trimmed to ~1M entries automatically
- ✅ **Replay Capability**: Can replay messages from any point
- ✅ **Separate Streams**: Impressions and clicks tracked separately
- ✅ **Error Handling**: Retries and error logging
- ✅ **Production-ready**: Clean architecture, proper error handling

## 🐛 Troubleshooting

### Redis not accessible
- Check if Redis container is running: `docker ps`
- Verify port 6379 is not in use: `lsof -i :6379`
- Check Redis logs: `docker-compose logs redis`

### Workers not processing messages
- Ensure workers are running: `./scripts/impression-worker.sh` and `./scripts/click-worker.sh`
- Check worker logs for errors
- Verify Redis streams exist: `docker exec -it adly_redis redis-cli KEYS "*stream*"`
- Check consumer groups: `docker exec -it adly_redis redis-cli XINFO GROUPS impressions_stream`

### Database connection errors
- Verify MariaDB is running: `docker ps | grep mariadb`
- Check database credentials in `.env`
- Check MariaDB logs: `docker-compose logs mariadb`

### PHP Redis extension not found
- Rebuild PHP container: `docker-compose build php`
- Check PHP container logs: `docker-compose logs php`
- Verify extension: `docker exec -it adly_php php -m | grep redis`

### Messages not being acknowledged
- Check if database writes are succeeding
- Verify consumer group exists: `docker exec -it adly_redis redis-cli XINFO GROUPS impressions_stream`
- Check pending messages: `docker exec -it adly_redis redis-cli XPENDING impressions_stream impression_group`

## 📈 Performance Considerations

- **Batch Size**: Default is 1000 messages. Adjust in consumer constructors
- **Stream Trimming**: Automatically keeps last ~1M entries per stream
- **Worker Scaling**: Run multiple worker instances with different consumer names for parallel processing
- **Database Indexing**: Schema includes indexes on `ad_id` and `date` for fast lookups
- **Redis Memory**: Configured with 2GB max memory and LRU eviction policy

## 🔐 Security Notes

This is a **development setup**. For production:

- Use strong database passwords
- Enable Redis authentication (AUTH)
- Use HTTPS for tracking endpoints
- Implement rate limiting
- Add input validation and sanitization
- Use environment-specific configurations
- Enable Redis TLS for encrypted connections

## 📝 How Trimming Works

Streams are automatically trimmed using approximate trimming (`~`):

```php
XTRIM impressions_stream MAXLEN ~ 1000000
```

The `~` means approximate trimming, which is faster and doesn't block. This keeps streams manageable while preserving recent events for replay and debugging.

## 🔄 Exactly-Once Processing

The system ensures exactly-once processing through:

1. **Consumer Groups**: Each worker belongs to a consumer group
2. **Message IDs**: Each message has a unique ID
3. **Acknowledgments**: Messages are acknowledged after successful database write
4. **Pending Messages**: Unacknowledged messages can be re-read
5. **Database UPSERT**: Prevents duplicate counting

If a worker crashes, unacknowledged messages remain in the pending list and can be processed by another worker or replayed.

## 📝 License

MIT License - feel free to use this project for learning and development.

## 🤝 Contributing

Contributions welcome! Please open an issue or submit a pull request.

---

**Built with ❤️ using Redis Streams, PHP, and MariaDB**
