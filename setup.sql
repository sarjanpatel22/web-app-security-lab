-- Database setup for the vulnerable web app lab.
-- Run as: sudo mysql < setup.sql

CREATE DATABASE IF NOT EXISTS vulnapp;
CREATE USER IF NOT EXISTS 'vulnuser'@'localhost' IDENTIFIED BY 'vulnpass123';
GRANT ALL PRIVILEGES ON vulnapp.* TO 'vulnuser'@'localhost';
FLUSH PRIVILEGES;

USE vulnapp;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  bio TEXT
);

-- Plaintext passwords ON PURPOSE (itself a vulnerability; fixed with password_hash()).
INSERT INTO users (username, password, bio) VALUES
  ('admin', 'S3cr3tAdm1n!', 'Site administrator'),
  ('rick',  'Wubbalubbadubdub', 'I am pickle Rick'),
  ('morty', 'jessica123', 'aw geez');
