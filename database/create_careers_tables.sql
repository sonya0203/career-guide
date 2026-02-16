-- Career Recommendations Feature Tables
-- Run this in career_guide_db database

USE career_guide_db;

-- =============================================
-- 1. Master Careers Table
-- =============================================
CREATE TABLE IF NOT EXISTS careers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    match_description TEXT,
    required_skills VARCHAR(500) NOT NULL,
    salary_min INT NOT NULL DEFAULT 0,
    salary_max INT NOT NULL DEFAULT 0,
    category VARCHAR(50) NOT NULL COMMENT 'Maps to user stream: science, commerce, arts, engineering, medical, business, computer-science',
    work_type VARCHAR(30) DEFAULT 'hybrid',
    growth_outlook VARCHAR(255) DEFAULT 'Strong',
    education_required VARCHAR(255) DEFAULT 'Bachelor''s Degree',
    job_responsibilities TEXT,
    tools_technologies VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 2. User Test Results Table
-- =============================================
CREATE TABLE IF NOT EXISTS user_test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    answers_json TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. User Career Recommendations Table
-- =============================================
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

-- =============================================
-- 4. Seed Careers Data
-- =============================================
INSERT INTO careers (title, description, match_description, required_skills, salary_min, salary_max, category, work_type, growth_outlook, education_required, job_responsibilities, tools_technologies) VALUES

-- Computer Science / IT
('Frontend Developer', 'Design and build user interfaces for websites and web applications using modern frameworks and technologies.', 'Matches your skills in React, logical reasoning, and creativity.', 'HTML,CSS,JavaScript,React,TypeScript', 70000, 90000, 'computer-science', 'remote', 'Very Strong – 25% growth expected', 'Bachelor''s in Computer Science or related field', 'Build responsive web interfaces\nCollaborate with designers and backend developers\nOptimize applications for speed and scalability\nWrite clean, maintainable code\nParticipate in code reviews', 'VS Code,React,Git,Figma,Chrome DevTools'),

('Data Scientist', 'Analyze complex datasets to extract insights and build predictive models that drive business decisions.', 'Matches your statistical knowledge and problem-solving skills.', 'Python,SQL,Machine Learning,Statistics,Data Visualization', 80000, 120000, 'computer-science', 'hybrid', 'Very Strong – 35% growth expected', 'Master''s in Data Science, Statistics, or related field', 'Collect and clean large datasets\nBuild predictive models using ML algorithms\nCreate data visualizations and dashboards\nPresent findings to stakeholders\nCollaborate with engineering teams', 'Python,Jupyter,TensorFlow,Tableau,SQL,Pandas'),

('Full Stack Developer', 'Build complete web applications from frontend to backend, managing databases and server infrastructure.', 'Matches your programming skills and technical knowledge.', 'JavaScript,Node.js,React,MongoDB,REST APIs', 75000, 110000, 'computer-science', 'remote', 'Strong – 22% growth expected', 'Bachelor''s in Computer Science or related field', 'Develop frontend and backend components\nDesign and manage databases\nBuild and maintain RESTful APIs\nDeploy and monitor applications\nTroubleshoot and debug issues', 'Node.js,React,MongoDB,Docker,AWS,Git'),

('Cybersecurity Analyst', 'Protect organizations from cyber threats by monitoring systems, identifying vulnerabilities, and responding to incidents.', 'Matches your analytical thinking and problem-solving abilities.', 'Network Security,Ethical Hacking,Firewalls,SIEM,Risk Assessment', 75000, 115000, 'computer-science', 'onsite', 'Very Strong – 33% growth expected', 'Bachelor''s in Cybersecurity or Computer Science', 'Monitor network traffic for threats\nConduct vulnerability assessments\nImplement security protocols\nRespond to security incidents\nTrain staff on security awareness', 'Wireshark,Nessus,Splunk,Kali Linux,Metasploit'),

-- Engineering
('Mechanical Engineer', 'Design, develop, and test mechanical devices, engines, and machines for various industries.', 'Matches your engineering knowledge and analytical skills.', 'CAD,Thermodynamics,Material Science,SolidWorks,Problem Solving', 65000, 95000, 'engineering', 'onsite', 'Moderate – 7% growth expected', 'Bachelor''s in Mechanical Engineering', 'Design mechanical components and systems\nConduct stress analysis and simulations\nOversee prototyping and manufacturing\nCollaborate with cross-functional teams\nEnsure compliance with safety standards', 'SolidWorks,AutoCAD,ANSYS,MATLAB,3D Printing'),

('Civil Engineer', 'Plan, design, and oversee construction of infrastructure projects like roads, bridges, and buildings.', 'Matches your structural knowledge and project management skills.', 'Structural Analysis,AutoCAD,Project Management,Surveying,Construction', 60000, 90000, 'engineering', 'onsite', 'Moderate – 8% growth expected', 'Bachelor''s in Civil Engineering', 'Design infrastructure projects\nConduct site surveys and inspections\nManage construction timelines and budgets\nEnsure structural integrity and safety\nPrepare technical reports', 'AutoCAD,Revit,SAP2000,MS Project,GIS'),

-- Science
('Research Scientist', 'Conduct experiments and research to advance scientific knowledge in areas like biology, chemistry, or physics.', 'Matches your research aptitude and analytical thinking.', 'Research Methodology,Data Analysis,Lab Techniques,Scientific Writing,Critical Thinking', 60000, 100000, 'science', 'onsite', 'Strong – 17% growth expected', 'PhD or Master''s in a scientific discipline', 'Design and conduct experiments\nAnalyze experimental data\nPublish findings in scientific journals\nApply for research grants\nCollaborate with research teams', 'MATLAB,SPSS,R,Lab Equipment,Microsoft Office'),

('Biotechnologist', 'Apply biological systems and organisms to develop products and technologies for healthcare, agriculture, and industry.', 'Matches your biology knowledge and innovation skills.', 'Molecular Biology,Genetics,Bioinformatics,Lab Skills,Research', 55000, 85000, 'science', 'hybrid', 'Strong – 20% growth expected', 'Master''s in Biotechnology or related field', 'Conduct biotechnology research\nDevelop new biological products\nPerform genetic analysis\nEnsure regulatory compliance\nCollaborate with pharmaceutical teams', 'PCR,CRISPR,Bioinformatics Tools,BLAST,Lab Equipment'),

-- Commerce / Business
('Financial Analyst', 'Evaluate financial data, create forecasts, and provide investment recommendations to guide business decisions.', 'Matches your numerical aptitude and analytical skills.', 'Financial Modeling,Excel,Accounting,Data Analysis,Communication', 60000, 95000, 'commerce', 'hybrid', 'Strong – 9% growth expected', 'Bachelor''s in Finance, Commerce, or Economics', 'Analyze financial statements\nCreate financial models and forecasts\nPrepare investment recommendations\nMonitor market trends\nPresent findings to management', 'Excel,Bloomberg Terminal,SAP,QuickBooks,Power BI'),

('Business Consultant', 'Advise organizations on strategy, operations, and efficiency to help them achieve their business goals.', 'Matches your business acumen and communication skills.', 'Strategy,Problem Solving,Communication,Data Analysis,Project Management', 70000, 120000, 'business', 'hybrid', 'Strong – 14% growth expected', 'MBA or Bachelor''s in Business Administration', 'Analyze business processes\nDevelop strategic recommendations\nPresent findings to executives\nManage client relationships\nConduct market research', 'PowerPoint,Excel,Tableau,CRM Tools,Microsoft Office'),

('Marketing Manager', 'Plan and execute marketing strategies to promote products and services, driving brand awareness and revenue.', 'Matches your creativity and communication skills.', 'Digital Marketing,SEO,Content Strategy,Analytics,Social Media', 65000, 100000, 'business', 'hybrid', 'Strong – 10% growth expected', 'Bachelor''s in Marketing or Business', 'Develop marketing campaigns\nManage social media presence\nAnalyze campaign performance\nCollaborate with design and sales teams\nManage marketing budgets', 'Google Analytics,HubSpot,Hootsuite,Canva,Mailchimp'),

-- Arts
('Digital Marketer', 'Create and manage online marketing campaigns across social media, search engines, and email to grow brands.', 'Matches your communication, creativity, and analysis skills.', 'SEO,Content Creation,Social Media,Google Ads,Analytics', 60000, 80000, 'arts', 'remote', 'Very Strong – 23% growth expected', 'Bachelor''s in Marketing, Communications, or Arts', 'Create and manage digital campaigns\nOptimize content for SEO\nManage social media accounts\nTrack and report campaign metrics\nCollaborate with design teams', 'Google Analytics,Canva,Hootsuite,Mailchimp,WordPress'),

('UX/UI Designer', 'Design intuitive and visually appealing user experiences for digital products like apps and websites.', 'Matches your creativity and user-centric thinking.', 'Figma,Adobe XD,User Research,Wireframing,Prototyping', 65000, 95000, 'arts', 'remote', 'Strong – 16% growth expected', 'Bachelor''s in Design, Fine Arts, or HCI', 'Conduct user research and testing\nCreate wireframes and prototypes\nDesign visual interfaces\nCollaborate with developers\nIterate based on user feedback', 'Figma,Adobe XD,Sketch,InVision,Miro'),

-- Medical
('Healthcare Administrator', 'Manage healthcare facilities, coordinate services, and ensure efficient operations within hospitals and clinics.', 'Matches your organizational and management skills.', 'Healthcare Management,Leadership,Communication,Budgeting,Compliance', 60000, 90000, 'medical', 'onsite', 'Strong – 28% growth expected', 'Master''s in Healthcare Administration or related field', 'Manage daily hospital operations\nCoordinate medical staff and services\nEnsure regulatory compliance\nManage facility budgets\nImprove patient care quality', 'EHR Systems,SAP,Microsoft Office,Project Management Tools'),

('Pharmacist', 'Dispense medications, counsel patients, and ensure safe use of pharmaceutical products.', 'Matches your medical knowledge and attention to detail.', 'Pharmacology,Patient Care,Drug Interactions,Communication,Attention to Detail', 80000, 120000, 'medical', 'onsite', 'Moderate – 5% growth expected', 'Doctor of Pharmacy (Pharm.D.)', 'Dispense prescription medications\nCounsel patients on drug usage\nMonitor drug interactions\nManage pharmacy inventory\nCollaborate with healthcare providers', 'Pharmacy Management Systems,Drug Databases,EHR Systems');
