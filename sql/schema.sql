-- Ad Impression + Click Tracking System Database Schema

CREATE DATABASE IF NOT EXISTS ads;
USE ads;

-- Table for daily impression counts
CREATE TABLE IF NOT EXISTS ad_daily_impressions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  date DATE NOT NULL,
  impressions BIGINT DEFAULT 0,
  UNIQUE KEY(ad_id, date),
  INDEX idx_date (date),
  INDEX idx_ad_id (ad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for daily click counts
CREATE TABLE IF NOT EXISTS ad_daily_clicks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  date DATE NOT NULL,
  clicks BIGINT DEFAULT 0,
  UNIQUE KEY(ad_id, date),
  INDEX idx_date (date),
  INDEX idx_ad_id (ad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
