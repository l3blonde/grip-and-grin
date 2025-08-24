-- Minimal GDPR Compliance Tables for Read-Only News Site

-- Simple cookie acceptance tracking (no user accounts required)
CREATE TABLE IF NOT EXISTS cookie_acceptances (
                                                  id INT PRIMARY KEY AUTO_INCREMENT,
                                                  ip_address VARCHAR(45) NOT NULL,
                                                  accepted_essential BOOLEAN DEFAULT TRUE,
                                                  accepted_analytics BOOLEAN DEFAULT FALSE,
                                                  user_agent TEXT,
                                                  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                  INDEX idx_ip_address (ip_address),
                                                  INDEX idx_created_at (created_at)
);

-- Privacy policy versions (required for legal compliance)
CREATE TABLE IF NOT EXISTS privacy_policy_versions (
                                                       id INT PRIMARY KEY AUTO_INCREMENT,
                                                       version VARCHAR(10) NOT NULL,
                                                       content TEXT NOT NULL,
                                                       effective_date DATE NOT NULL,
                                                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                       is_active BOOLEAN DEFAULT FALSE,
                                                       INDEX idx_version (version),
                                                       INDEX idx_effective_date (effective_date)
);

-- Insert initial privacy policy version
INSERT INTO privacy_policy_versions (version, content, effective_date, is_active) VALUES
    ('1.0', 'Privacy policy for read-only classic car magazine site', CURDATE(), TRUE);
