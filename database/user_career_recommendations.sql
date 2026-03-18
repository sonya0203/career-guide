CREATE TABLE IF NOT EXISTS user_career_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_result_id INT NOT NULL,
    career_id INT NOT NULL,
    match_percentage INT NOT NULL DEFAULT 0,
    match_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_test_result (test_result_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;