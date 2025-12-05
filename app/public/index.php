<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Impression + Click Tracking System - Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .section {
            margin-bottom: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h2 {
            color: #444;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .ad-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .ad-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .ad-banner:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .ad-banner h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .ad-banner p {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .stats-section {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .info-box code {
            background: rgba(0,0,0,0.05);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            margin: 5px;
            transition: background 0.2s;
        }

        button:hover {
            background: #5568d3;
        }

        .log {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .log-entry {
            margin-bottom: 5px;
        }

        .log-success {
            color: #4ec9b0;
        }

        .log-error {
            color: #f48771;
        }

        .log-info {
            color: #569cd6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Ad Impression + Click Tracking System</h1>
        <p class="subtitle">Real-time tracking using Redis Streams + PHP + MariaDB</p>

        <div class="section">
            <h2>📊 Demo Ad Banners</h2>
            <p>Click on ads to track clicks. Impressions are tracked automatically when ads come into view.</p>
            
            <div class="ad-container">
                <div class="ad-banner" data-ad-id="1" onclick="trackClick(1)">
                    <h3>Ad Banner #1</h3>
                    <p>Premium Product Advertisement</p>
                    <small>Click to track click</small>
                </div>
                
                <div class="ad-banner" data-ad-id="2" onclick="trackClick(2)" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3>Ad Banner #2</h3>
                    <p>Special Offer - Limited Time</p>
                    <small>Click to track click</small>
                </div>
                
                <div class="ad-banner" data-ad-id="3" onclick="trackClick(3)" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3>Ad Banner #3</h3>
                    <p>New Collection Launch</p>
                    <small>Click to track click</small>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button onclick="trackImpression(4)">Track Impression #4</button>
                <button onclick="trackClick(4)">Track Click #4</button>
                <button onclick="trackImpression(5)">Track Impression #5</button>
                <button onclick="trackClick(5)">Track Click #5</button>
            </div>
        </div>

        <div class="section stats-section">
            <h2>📈 Tracking Status</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Tracking Method</div>
                    <div class="stat-value">Redis Streams</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Impression Endpoint</div>
                    <div class="stat-value">/track_impression.php</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Click Endpoint</div>
                    <div class="stat-value">/track_click.php</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Message Queue</div>
                    <div class="stat-value">Redis Streams</div>
                </div>
            </div>

            <div class="info-box">
                <strong>ℹ️ How it works:</strong><br>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li>Events are sent to Redis Streams (impressions_stream or clicks_stream)</li>
                    <li>Consumer workers process events in batches of 1000</li>
                    <li>Events are aggregated by ad_id and date</li>
                    <li>Aggregated counts are written to MariaDB via UPSERT</li>
                    <li>Messages are acknowledged after successful processing</li>
                </ol>
            </div>
        </div>

        <div class="section">
            <h2>📝 Activity Log</h2>
            <div id="log" class="log">
                <div class="log-entry log-info">System initialized. Ready to track impressions and clicks...</div>
            </div>
        </div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            entry.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }

        function trackImpression(adId) {
            fetch(`/track_impression.php?ad_id=${adId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        addLog(`Impression tracked: ad_id=${adId}, stream_id=${data.stream_id}`, 'success');
                    } else {
                        addLog(`Failed to track impression: ${data.error}`, 'error');
                    }
                })
                .catch(error => {
                    addLog(`Error tracking impression: ${error.message}`, 'error');
                });
        }

        function trackClick(adId) {
            fetch(`/track_click.php?ad_id=${adId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        addLog(`Click tracked: ad_id=${adId}, stream_id=${data.stream_id}`, 'success');
                    } else {
                        addLog(`Failed to track click: ${data.error}`, 'error');
                    }
                })
                .catch(error => {
                    addLog(`Error tracking click: ${error.message}`, 'error');
                });
        }

        // Track impressions when ads come into view
        document.addEventListener('DOMContentLoaded', function() {
            const ads = document.querySelectorAll('[data-ad-id]');
            const tracked = new Set();

            ads.forEach(function(ad) {
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const adId = entry.target.getAttribute('data-ad-id');
                            const key = `impression_${adId}`;
                            
                            if (!tracked.has(key)) {
                                tracked.add(key);
                                trackImpression(adId);
                                addLog(`Ad #${adId} entered viewport - impression tracked`, 'info');
                            }
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(ad);
            });
        });
    </script>
</body>
</html>
