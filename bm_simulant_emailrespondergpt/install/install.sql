-- Conversations Table
CREATE TABLE IF NOT EXISTS `ps_email_ai_conversations` (
    `id_conversation` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `received_message` TEXT NOT NULL,
    `ai_response` TEXT NOT NULL,
    `status` ENUM('pending', 'processed', 'failed') NOT NULL DEFAULT 'pending',
    `detected_language` VARCHAR(5) DEFAULT NULL,
    `response_time` INT DEFAULT NULL,
    `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_upd` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`date_add`, `date_upd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs Table
CREATE TABLE IF NOT EXISTS `ps_email_ai_logs` (
    `id_log` INT AUTO_INCREMENT PRIMARY KEY,
    `log_type` ENUM('info', 'warning', 'error', 'success') NOT NULL DEFAULT 'info',
    `message` TEXT NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`log_type`),
    INDEX `idx_date` (`date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Logs Table
CREATE TABLE IF NOT EXISTS `ps_email_ai_api_logs` (
    `id_api_log` INT AUTO_INCREMENT PRIMARY KEY,
    `endpoint` VARCHAR(255) NOT NULL,
    `request` TEXT NOT NULL,
    `response` TEXT NOT NULL,
    `status_code` INT NOT NULL,
    `execution_time` FLOAT NOT NULL,
    `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_endpoint` (`endpoint`),
    INDEX `idx_status` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled Responses Table
CREATE TABLE IF NOT EXISTS `ps_email_ai_scheduled_responses` (
    `id_scheduled_response` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `send_time` DATETIME NOT NULL,
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_upd` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_send_time` (`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;