CREATE TABLE IF NOT EXISTS user_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    full_name VARCHAR(100) NOT NULL,
    age INT,
    highest_qualification VARCHAR(50),
    stream VARCHAR(50),
    skills TEXT,
    interests VARCHAR(50),
    work_type VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user
        FOREIGN KEY (user_id)
        REFERENCES auth_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;