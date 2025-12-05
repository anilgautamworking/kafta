#!/bin/bash

# Stop Ad Impression + Click Tracking System
echo "🛑 Stopping Ad Impression + Click Tracking System..."
echo ""

docker-compose down

echo ""
echo "✅ System stopped!"
echo ""
echo "To remove volumes (data), run: docker-compose down -v"
