#!/bin/bash

# Start Ad Impression + Click Tracking System
echo "🚀 Starting Ad Impression + Click Tracking System..."
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Creating from template..."
    cat > .env << EOF
REDIS_HOST=redis
REDIS_PORT=6379
DB_HOST=mariadb
DB_USER=root
DB_PASS=password
DB_NAME=ads
EOF
    echo "✅ Created .env file"
fi

# Start Docker Compose
echo "📦 Starting Docker containers..."
docker-compose up -d

# Wait for services to be ready
echo ""
echo "⏳ Waiting for services to be ready..."
sleep 5

# Check service status
echo ""
echo "📊 Service Status:"
docker-compose ps

echo ""
echo "✅ System started!"
echo ""
echo "🌐 Frontend: http://localhost:8080"
echo "📊 Redis: localhost:6379"
echo "🗄️  MariaDB: localhost:3306"
echo ""
echo "To start the impression worker, run: ./scripts/impression-worker.sh"
echo "To start the click worker, run: ./scripts/click-worker.sh"
echo "To stop the system, run: ./scripts/stop.sh"
