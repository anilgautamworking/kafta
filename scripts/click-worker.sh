#!/bin/bash

# Click Consumer Worker Script
echo "👷 Starting Click Consumer Worker..."
echo ""

# Check if PHP container is running
if ! docker ps | grep -q adly_php; then
    echo "❌ PHP container is not running. Please start the system first:"
    echo "   ./scripts/start.sh"
    exit 1
fi

# Run the worker inside PHP container
echo "Starting click worker process..."
docker exec -it adly_php php /app/workers/run_click_consumer.php

