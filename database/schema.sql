-- Ilumina Database Schema
-- Sistema PWA para gestão da iluminação pública

CREATE DATABASE IF NOT EXISTS ilumina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ilumina;

-- Teams table
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20) UNIQUE NOT NULL,
    role ENUM('admin', 'manager', 'technician', 'citizen') DEFAULT 'citizen',
    team_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

-- Tickets table
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('open', 'assigned', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    address TEXT,
    photo_url VARCHAR(500),
    citizen_phone VARCHAR(20),
    citizen_name VARCHAR(255),
    assigned_team_id INT,
    due_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_location (latitude, longitude),
    INDEX idx_due_date (due_date)
);

-- Ticket comments table for tracking updates
CREATE TABLE ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Auth tokens table for phone verification
CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    token VARCHAR(6) NOT NULL,
    role ENUM('citizen', 'manager') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_token (phone, token),
    INDEX idx_expires_at (expires_at)
);

-- Insert default data
INSERT INTO teams (name, description) VALUES 
('Equipe Norte', 'Responsável pela região norte da cidade'),
('Equipe Sul', 'Responsável pela região sul da cidade'),
('Equipe Centro', 'Responsável pela região central da cidade');

INSERT INTO users (name, email, password, phone, role, team_id) VALUES 
('Admin Sistema', 'admin@ilumina.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+5511999999999', 'admin', 1),
('Gestor Norte', 'gestor.norte@ilumina.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+5511888888888', 'manager', 1),
('Técnico João', 'joao@ilumina.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+5511777777777', 'technician', 1);