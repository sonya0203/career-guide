-- =============================================
-- Drop and Recreate career_questions Table
-- with qualification, interest, and age limit columns
-- =============================================

USE career_guide_db;

DROP TABLE IF EXISTS career_questions;

CREATE TABLE IF NOT EXISTS career_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    qualification VARCHAR(100) DEFAULT NULL,
    interest VARCHAR(100) DEFAULT NULL,
    age_min INT DEFAULT NULL,
    age_max INT DEFAULT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_qualification (qualification),
    INDEX idx_interest (interest)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO career_questions (category, qualification, interest, age_min, age_max, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
('science', '12th', 'science', 15, 25, 'What is the powerhouse of the cell?', 'Nucleus', 'Mitochondria', 'Ribosome', 'Golgi apparatus', 'b'),
('science', '12th', 'science', 15, 25, 'Which gas is most abundant in the Earth\'s atmosphere?', 'Oxygen', 'Carbon Dioxide', 'Nitrogen', 'Argon', 'c'),
('commerce', '12th', 'business', 15, 25, 'What is the primary objective of financial accounting?', 'To minimize taxes', 'To provide information for decision making', 'To maximize sales', 'To track employee attendance', 'b'),
('commerce', '12th', 'business', 15, 25, 'Which of the following is an asset?', 'Accounts Payable', 'Loan', 'Cash', 'Capital', 'c'),
('arts', '12th', 'creative', 15, 25, 'Who painted the Mona Lisa?', 'Vincent van Gogh', 'Pablo Picasso', 'Leonardo da Vinci', 'Michelangelo', 'c'),
('arts', '12th', 'creative', 15, 25, 'Which is the study of human societies and cultures?', 'Psychology', 'Anthropology', 'Biology', 'Geology', 'b'),
('engineering', 'Graduate', 'technology', 20, 35, 'Which component is used to store charge in a circuit?', 'Resistor', 'Inductor', 'Capacitor', 'Diode', 'c'),
('engineering', 'Graduate', 'technology', 20, 35, 'What does CPU stand for?', 'Central Process Unit', 'Central Processing Unit', 'Computer Personal Unit', 'Central Processor Utility', 'b'),
('medical', 'Graduate', 'healthcare', 20, 35, 'Which organ is responsible for pumping blood?', 'Lungs', 'Brain', 'Heart', 'Liver', 'c'),
('medical', 'Graduate', 'healthcare', 20, 35, 'What is the largest organ in the human body?', 'Liver', 'Brain', 'Skin', 'Heart', 'c'),
('computer-science', '12th', 'technology', 15, 30, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Hyper Transfer Mark Language', 'Home Tool Markup Language', 'a'),
('computer-science', '12th', 'technology', 15, 30, 'Which language is primarily used for web styling?', 'Java', 'Python', 'CSS', 'C++', 'c');


