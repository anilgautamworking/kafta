#!/bin/bash

# Impression Consumer Worker Script
echo "👷 Starting Impression Consumer Worker..."
echo ""

# Check if PHP container is running
if ! docker ps | grep -q adly_php; then
    echo "❌ PHP container is not running. Please start the system first:"
    echo "   ./scripts/start.sh"
    exit 1
fi

# Run the worker inside PHP container
echo "Starting impression worker process..."
docker exec -it adly_php php /app/workers/run_impression_consumer.php

