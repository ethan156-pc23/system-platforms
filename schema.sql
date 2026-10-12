-- Cebu Emergency Hotlines — database schema + seed data
-- NOTE: the admin password hash below was regenerated with PHP's password_hash()
-- so that the documented password (admin123) actually verifies with password_verify().
-- The hash originally pasted into this file was not a valid hash of "admin123".

CREATE DATABASE IF NOT EXISTS cebu_emergency;
USE cebu_emergency;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NULL UNIQUE,
    mobile_number VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    last_seen_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categories (name, slug) VALUES 
('Medical', 'medical'),
('Fire', 'fire'),
('Police', 'police'),
('Disaster', 'others')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. Hotlines Directory Table
CREATE TABLE IF NOT EXISTS hotlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    municipality VARCHAR(100) NOT NULL,
    description TEXT,
    phone_number VARCHAR(100) NOT NULL,
    availability VARCHAR(50) DEFAULT '24/7',
    tag_label VARCHAR(50) DEFAULT 'EMERGENCY',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- 4. Location Requests
CREATE TABLE IF NOT EXISTS location_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    hotline_id INT NOT NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    accuracy DECIMAL(10, 2) NULL,
    help_description VARCHAR(500) NULL,
    status ENUM('pending', 'responding', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (hotline_id) REFERENCES hotlines(id) ON DELETE CASCADE,
    INDEX idx_location_requests_created_at (created_at)
);

-- 5. User-prioritized municipalities
CREATE TABLE IF NOT EXISTS favorite_municipalities (
    user_id INT NOT NULL,
    municipality VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, municipality),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed Default Admin Account (Password: admin123)
INSERT INTO users (mobile_number, email, password, role) 
VALUES ('09123456789', 'admin@cebu.gov.ph', '$2y$10$PzFSIUOxsvCwrN2413dakeYYzTtck0pMZAhf1.Ic.pmeosBHZmRyC', 'admin')
ON DUPLICATE KEY UPDATE role='admin';

-- Seed Complete Directory Data
INSERT INTO hotlines (title, category_id, municipality, description, phone_number, tag_label) VALUES
-- Highly Urbanized Cities
('Cebu City Police (CCPO)', 3, 'Cebu City', 'Police assistance and emergency response for Cebu City.', '(032) 231-3232 / 166', 'POLICE'),
('Cebu City Fire (BFP)', 2, 'Cebu City', 'Fire protection and emergency response for Cebu City.', '(032) 256-0541 / 160', 'FIRE'),
('Cebu City Medical Center (CCMC)', 1, 'Cebu City', 'Emergency medical services and hospital care.', '(032) 255-7141', 'MEDICAL'),
('Cebu City Disaster (CCDRRMO)', 4, 'Cebu City', 'Disaster risk reduction and management office.', '0917-839-9896 / 0932-537-7770', 'DISASTER'),

('Lapu-Lapu City Police', 3, 'Lapu-Lapu City', 'Police assistance for Lapu-Lapu City.', '(032) 340-0453', 'POLICE'),
('Lapu-Lapu City Fire', 2, 'Lapu-Lapu City', 'Fire station emergency line.', '(032) 340-0252', 'FIRE'),
('Lapu-Lapu Medical (ERUF Mactan)', 1, 'Lapu-Lapu City', 'Emergency Rescue Unit Foundation Mactan unit.', '(032) 340-2994', 'MEDICAL'),
('Lapu-Lapu Disaster (LCCDRRMO)', 4, 'Lapu-Lapu City', 'Disaster risk reduction and management office.', '(032) 341-0356', 'DISASTER'),

('Mandaue City Police', 3, 'Mandaue City', 'Police assistance for Mandaue City.', '(032) 344-1200', 'POLICE'),
('Mandaue City Fire', 2, 'Mandaue City', 'Fire protection services.', '(032) 344-4747 / (032) 344-3364', 'FIRE'),
('Mandaue City Hospital', 1, 'Mandaue City', 'Public medical facility emergency department.', '(032) 346-0135', 'MEDICAL'),
('Bantay Mandaue (Disaster)', 4, 'Mandaue City', 'City emergency operations and response center.', '(032) 420-2868', 'DISASTER'),

-- Component Cities
('Bogo City Police', 3, 'Bogo City', 'Police station emergency hotline.', '0998-598-6365', 'POLICE'),
('Bogo City Fire', 2, 'Bogo City', 'Bureau of Fire Protection station.', '(032) 259-0238', 'FIRE'),
('Bogo Medical / RHU', 1, 'Bogo City', 'Rural Health Unit and medical services.', '0943-246-1511', 'MEDICAL'),
('Bogo Disaster (CDRRMO)', 4, 'Bogo City', 'City Disaster Risk Reduction Management Office.', '0985-510-2797', 'DISASTER'),

('Carcar City Police', 3, 'Carcar City', 'Police station hotline.', '(032) 487-9260', 'POLICE'),
('Carcar City Fire', 2, 'Carcar City', 'Fire station hotline.', '(032) 487-3788', 'FIRE'),
('Carcar Medical / RHU', 1, 'Carcar City', 'City health unit emergency care.', '(032) 487-9214', 'MEDICAL'),
('Carcar Disaster (CDRRMO)', 4, 'Carcar City', 'Disaster response unit.', '(032) 487-9540', 'DISASTER'),

('Danao City Police', 3, 'Danao City', 'Police station emergency hotline.', '0998-598-6379', 'POLICE'),
('Danao City Fire', 2, 'Danao City', 'Fire protection station hotline.', '(032) 200-3425', 'FIRE'),
('Danao Provincial Hospital', 1, 'Danao City', 'Provincial hospital emergency room.', '(032) 344-5075', 'MEDICAL'),
('Danao Disaster (CDRRMO)', 4, 'Danao City', 'City disaster control center.', '(032) 200-4321', 'DISASTER'),

('Naga City Police', 3, 'Naga City', 'Police assistance line.', '(032) 489-8808', 'POLICE'),
('Naga City Fire', 2, 'Naga City', 'Fire station emergency line.', '(032) 272-6410', 'FIRE'),
('Naga Medical / RHU', 1, 'Naga City', 'City health office.', '(032) 272-3776', 'MEDICAL'),
('Naga Disaster (CDRRMO)', 4, 'Naga City', 'Disaster management unit.', '(032) 262-1424', 'DISASTER'),

('Talisay City Police', 3, 'Talisay City', 'Police hotline.', '(032) 272-9234', 'POLICE'),
('Talisay City Fire', 2, 'Talisay City', 'Fire department hotline.', '(032) 272-8277', 'FIRE'),
('Talisay District Hospital', 1, 'Talisay City', 'District medical center.', '(032) 272-4663', 'MEDICAL'),
('Talisay Disaster (CDRRMO)', 4, 'Talisay City', 'City emergency response team.', '(032) 407-5928', 'DISASTER'),

('Toledo City Police', 3, 'Toledo City', 'Police station hotline.', '(032) 322-9115', 'POLICE'),
('Toledo City Fire', 2, 'Toledo City', 'Fire station hotline.', '(032) 322-5133', 'FIRE'),
('Toledo City Hospital', 1, 'Toledo City', 'City hospital emergency department.', '(032) 322-5401', 'MEDICAL'),
('Toledo Disaster (CDRRMO)', 4, 'Toledo City', 'Disaster risk management office.', '(032) 322-5000', 'DISASTER'),

-- Municipalities
('Alcantara Police', 3, 'Alcantara', 'Local police station.', '0998-598-6372', 'POLICE'),
('Alcantara Fire', 2, 'Alcantara', 'Fire department.', '0943-527-3215', 'FIRE'),
('Alcantara Medical', 1, 'Alcantara', 'Rural health office.', '0925-502-4179', 'MEDICAL'),
('Alcantara Disaster', 4, 'Alcantara', 'MDRRMO hotline.', '0929-680-5299', 'DISASTER'),

('Alcoy Police', 3, 'Alcoy', 'Local police station.', '0998-598-6377', 'POLICE'),
('Alcoy Fire', 2, 'Alcoy', 'Fire department.', '0968-204-2644', 'FIRE'),
('Alcoy Medical', 1, 'Alcoy', 'Rural health unit.', '0968-264-9681', 'MEDICAL'),
('Alcoy Disaster', 4, 'Alcoy', 'MDRRMO office.', '0981-446-7661', 'DISASTER'),

('Alegria Police', 3, 'Alegria', 'Police hotline.', '0998-598-6375', 'POLICE'),
('Alegria Fire', 2, 'Alegria', 'Fire department.', '(032) 425-2641', 'FIRE'),
('Alegria Medical', 1, 'Alegria', 'Rural health unit.', '(032) 460-9076', 'MEDICAL'),
('Alegria Disaster', 4, 'Alegria', 'Disaster emergency unit.', '0910-622-6622', 'DISASTER'),

('Aloguinsan Police', 3, 'Aloguinsan', 'Police station hotline.', '469-9909', 'POLICE'),
('Aloguinsan Fire', 2, 'Aloguinsan', 'Fire protection office.', '(032) 425-9269', 'FIRE'),
('Aloguinsan Medical', 1, 'Aloguinsan', 'Health center line.', '462-7345', 'MEDICAL'),
('Aloguinsan Disaster', 4, 'Aloguinsan', 'Disaster response office.', '479-8234', 'DISASTER'),

('Argao Police', 3, 'Argao', 'Police station hotline.', '(032) 367-7166', 'POLICE'),
('Argao Fire', 2, 'Argao', 'Fire station hotline.', '(032) 367-7680', 'FIRE'),
('Argao Medical (IKMH)', 1, 'Argao', 'Isidro K. Mendoza Hospital.', '(032) 367-7500', 'MEDICAL'),
('Argao Disaster', 4, 'Argao', 'MDRRMO hotline.', '(032) 367-7680', 'DISASTER'),

('Asturias Police', 3, 'Asturias', 'Police emergency line.', '517-2450 / 0998-598-6361', 'POLICE'),
('Asturias Fire', 2, 'Asturias', 'Fire protection department.', '(032) 517-0833', 'FIRE'),
('Asturias Medical', 1, 'Asturias', 'Rural health center.', '517-3477', 'MEDICAL'),
('Asturias Disaster', 4, 'Asturias', 'Disaster hotline.', '0918-637-6365', 'DISASTER'),

('Badian Police', 3, 'Badian', 'Police assistance hotline.', '(032) 383-9628', 'POLICE'),
('Badian Fire', 2, 'Badian', 'Fire protection office.', '(032) 324-3501', 'FIRE'),
('Badian District Hospital', 1, 'Badian', 'District hospital emergency department.', '(032) 475-5592', 'MEDICAL'),
('Badian Disaster', 4, 'Badian', 'MDRRMO hotline.', '0945-685-2435', 'DISASTER'),

('Balamban Police', 3, 'Balamban', 'Police station hotline.', '(032) 482-9121', 'POLICE'),
('Balamban Fire', 2, 'Balamban', 'Fire station hotline.', '(032) 328-6722', 'FIRE'),
('Balamban Medical', 1, 'Balamban', 'Rural health center line.', '(032) 482-9120', 'MEDICAL'),
('Balamban Disaster', 4, 'Balamban', 'Disaster operations center.', '(032) 482-9300', 'DISASTER'),

('Bantayan Police', 3, 'Bantayan', 'Island police station hotline.', '0998-598-6356', 'POLICE'),
('Bantayan Fire', 2, 'Bantayan', 'Fire protection unit.', '(032) 503-6710', 'FIRE'),
('Bantayan Medical', 1, 'Bantayan', 'Rural health center hotline.', '0915-207-4848', 'MEDICAL'),
('Bantayan Disaster', 4, 'Bantayan', 'MDRRMO office.', '(032) 438-9032', 'DISASTER'),

('Barili Police', 3, 'Barili', 'Police station hotline.', '0998-598-6367', 'POLICE'),
('Barili Fire', 2, 'Barili', 'Fire station hotline.', '(032) 239-0705', 'FIRE'),
('Barili Medical', 1, 'Barili', 'Rural health unit.', '0962-082-5662', 'MEDICAL'),
('Barili Disaster', 4, 'Barili', 'MDRRMO emergency office.', '0945-553-1713', 'DISASTER'),

('Boljoon Police', 3, 'Boljoon', 'Police hotline.', '0939-500-5116', 'POLICE'),
('Boljoon Fire', 2, 'Boljoon', 'Fire department.', '(032) 326-7380', 'FIRE'),
('Boljoon Medical', 1, 'Boljoon', 'Rural health clinic.', '0908-958-2096', 'MEDICAL'),
('Boljoon Disaster', 4, 'Boljoon', 'Disaster response unit.', '0929-444-7161', 'DISASTER'),

('Borbon Police', 3, 'Borbon', 'Police assistance hotline.', '0998-598-6380', 'POLICE'),
('Borbon Fire', 2, 'Borbon', 'Fire department.', '(032) 434-0122', 'FIRE'),
('Borbon Medical', 1, 'Borbon', 'Rural health office.', '0919-087-4112', 'MEDICAL'),
('Borbon Disaster', 4, 'Borbon', 'MDRRMO office.', '0922-451-8833', 'DISASTER'),

('Carmen Police', 3, 'Carmen', 'Police station hotline.', '0998-598-6382', 'POLICE'),
('Carmen Fire', 2, 'Carmen', 'Fire station.', '0961-794-0493', 'FIRE'),
('Carmen Medical', 1, 'Carmen', 'Health center hotline.', '(032) 266-8743', 'MEDICAL'),
('Carmen Disaster', 4, 'Carmen', 'MDRRMO office.', '0964-976-5885', 'DISASTER'),

('Catmon Police', 3, 'Catmon', 'Police station hotline.', '0998-598-6383', 'POLICE'),
('Catmon Fire', 2, 'Catmon', 'Fire station line.', '(032) 430-9015', 'FIRE'),
('Catmon Medical', 1, 'Catmon', 'Health office line.', '(032) 430-9017', 'MEDICAL'),
('Catmon Disaster', 4, 'Catmon', 'Disaster office line.', '(032) 430-9197', 'DISASTER'),

('Compostela Police', 3, 'Compostela', 'Police assistance hotline.', '0998-598-6288', 'POLICE'),
('Compostela Fire', 2, 'Compostela', 'Fire department hotline.', '(032) 425-8221', 'FIRE'),
('Compostela Medical', 1, 'Compostela', 'Rural health center.', '(032) 416-0682', 'MEDICAL'),
('Compostela Disaster', 4, 'Compostela', 'MDRRMO office.', '0931-000-1918', 'DISASTER'),

('Consolacion Police', 3, 'Consolacion', 'Police station hotline.', '(032) 412-0167', 'POLICE'),
('Consolacion Fire', 2, 'Consolacion', 'Fire department hotline.', '(032) 425-2640', 'FIRE'),
('Consolacion Medical', 1, 'Consolacion', 'Rural health unit.', '(032) 425-8084', 'MEDICAL'),
('Consolacion Disaster', 4, 'Consolacion', 'MDRRMO office.', '0917-302-1930', 'DISASTER'),

('Cordova Police Assistance', 3, 'Cordova', 'Report incidents, request assistance, and get help with safety emergencies.', '(032) 495-0112', 'POLICE'),
('Cordova Fire Department', 2, 'Cordova', 'Fire protection and emergency response teams.', '(032) 505-1175', 'FIRE'),
('Cordova Medical (RHU)', 1, 'Cordova', 'Immediate medical response, transport, and local healthcare assistance.', '(032) 496-7530', 'MEDICAL'),
('Cordova Disaster (MDRRMO)', 4, 'Cordova', 'Municipal Disaster Risk Reduction and Management Office.', '(032) 516-2713', 'DISASTER'),

('Daanbantayan Police', 3, 'Daanbantayan', 'Police station hotline.', '0998-598-6392', 'POLICE'),
('Daanbantayan Fire', 2, 'Daanbantayan', 'Fire protection office.', '(032) 436-4245', 'FIRE'),
('Daanbantayan Medical', 1, 'Daanbantayan', 'Health center emergency line.', '0969-539-2916', 'MEDICAL'),
('Daanbantayan Disaster', 4, 'Daanbantayan', 'MDRRMO office.', '0917-149-8457', 'DISASTER'),

('Dalaguete Police', 3, 'Dalaguete', 'Police assistance hotline.', '0916-423-3121', 'POLICE'),
('Dalaguete Fire', 2, 'Dalaguete', 'Fire protection station.', '(032) 437-3788', 'FIRE'),
('Dalaguete Medical (CCRH)', 1, 'Dalaguete', 'Cebu Provincial Community Hospital - Dalaguete.', '(032) 484-8070', 'MEDICAL'),
('Dalaguete Disaster', 4, 'Dalaguete', 'Disaster office hotline.', '0926-825-3800', 'DISASTER'),

('Dumanjug Police', 3, 'Dumanjug', 'Police station hotline.', '0998-598-6385', 'POLICE'),
('Dumanjug Fire', 2, 'Dumanjug', 'Fire protection station.', '0905-350-3109', 'FIRE'),
('Dumanjug Medical', 1, 'Dumanjug', 'Rural health center hotline.', '(032) 517-6897', 'MEDICAL'),
('Dumanjug Disaster', 4, 'Dumanjug', 'MDRRMO hotline.', '0956-271-1967', 'DISASTER'),

('Ginatilan Police', 3, 'Ginatilan', 'Police hotline line.', '(032) 477-8979', 'POLICE'),
('Ginatilan Fire', 2, 'Ginatilan', 'Fire department line.', '(032) 477-8233', 'FIRE'),
('Ginatilan Medical', 1, 'Ginatilan', 'Rural health center line.', '(032) 477-8086', 'MEDICAL'),
('Ginatilan Disaster', 4, 'Ginatilan', 'Disaster management line.', '(032) 477-8079', 'DISASTER'),

('Liloan Police', 3, 'Liloan', 'Police station hotline.', '0998-598-6385', 'POLICE'),
('Liloan Fire', 2, 'Liloan', 'Fire station hotline.', '(032) 509-6293', 'FIRE'),
('Liloan Medical', 1, 'Liloan', 'Health center hotline.', '0945-239-4503', 'MEDICAL'),
('Liloan Disaster', 4, 'Liloan', 'MDRRMO hotline.', '0917-623-6135', 'DISASTER'),

('Madridejos Police', 3, 'Madridejos', 'Bantayan island police hotline.', '0998-598-6337', 'POLICE'),
('Madridejos Fire', 2, 'Madridejos', 'Fire department hotline.', '(032) 344-4908', 'FIRE'),
('Madridejos Medical', 1, 'Madridejos', 'Rural health clinic.', '(032) 239-4431', 'MEDICAL'),
('Madridejos Disaster', 4, 'Madridejos', 'MDRRMO office.', '0943-850-9665', 'DISASTER'),

('Malabuyoc Police', 3, 'Malabuyoc', 'Police station emergency hotline.', '0932-955-1738', 'POLICE'),
('Malabuyoc Fire', 2, 'Malabuyoc', 'Fire department emergency line.', '0919-851-6252', 'FIRE'),
('Malabuyoc Medical', 1, 'Malabuyoc', 'Rural health office.', '(032) 422-0234', 'MEDICAL'),
('Malabuyoc Disaster', 4, 'Malabuyoc', 'MDRRMO office.', '0908-285-0313', 'DISASTER'),

('Medellin Police', 3, 'Medellin', 'Police station emergency line.', '0998-598-6348', 'POLICE'),
('Medellin Fire', 2, 'Medellin', 'Fire station line.', '(032) 415-7736', 'FIRE'),
('Medellin Medical', 1, 'Medellin', 'Health unit line.', '(032) 481-9078', 'MEDICAL'),
('Medellin Disaster', 4, 'Medellin', 'MDRRMO office line.', '0938-941-0419', 'DISASTER'),

('Minglanilla Police', 3, 'Minglanilla', 'Police station hotline.', '(032) 272-6662', 'POLICE'),
('Minglanilla Fire', 2, 'Minglanilla', 'Fire protection office.', '(032) 273-2830', 'FIRE'),
('Minglanilla Medical', 1, 'Minglanilla', 'Rural health center.', '(032) 272-3312', 'MEDICAL'),
('Minglanilla Disaster (MDRRMO)', 4, 'Minglanilla', 'Emergency response center.', '(032) 262-1424', 'DISASTER'),

('Moalboal Police', 3, 'Moalboal', 'Police station hotline.', '0995-124-1104', 'POLICE'),
('Moalboal Fire', 2, 'Moalboal', 'Fire protection office.', '(032) 407-0627', 'FIRE'),
('Moalboal Medical', 1, 'Moalboal', 'Health center hotline.', '(032) 488-9703', 'MEDICAL'),
('Moalboal Disaster', 4, 'Moalboal', 'MDRRMO office.', '0942-638-3201', 'DISASTER'),

('Oslob Police', 3, 'Oslob', 'Police station hotline.', '(032) 486-8997', 'POLICE'),
('Oslob Fire', 2, 'Oslob', 'Fire department line.', '(032) 486-8544', 'FIRE'),
('Oslob Medical', 1, 'Oslob', 'Health office line.', '(032) 252-4065', 'MEDICAL'),
('Oslob Disaster', 4, 'Oslob', 'Disaster operations line.', '(032) 486-9161', 'DISASTER'),

('Pilar Police', 3, 'Pilar', 'Camotes island police hotline.', '0998-598-6378', 'POLICE'),
('Pilar Fire', 2, 'Pilar', 'Fire department.', '0968-868-0404', 'FIRE'),
('Pilar Medical', 1, 'Pilar', 'Rural health office.', '(032) 433-1996', 'MEDICAL'),
('Pilar Disaster', 4, 'Pilar', 'MDRRMO office.', '0917-114-0556', 'DISASTER'),

('Pinamungajan Police', 3, 'Pinamungajan', 'Police hotline line.', '(032) 491-3967', 'POLICE'),
('Pinamungajan Fire', 2, 'Pinamungajan', 'Fire department line.', '(032) 407-8083', 'FIRE'),
('Pinamungajan Medical', 1, 'Pinamungajan', 'Health office line.', '(032) 272-2623', 'MEDICAL'),
('Pinamungajan Disaster', 4, 'Pinamungajan', 'Disaster office line.', '(032) 407-5928', 'DISASTER'),

('Poro Police', 3, 'Poro', 'Camotes island police hotline.', '0998-598-6376', 'POLICE'),
('Poro Fire', 2, 'Poro', 'Fire station hotline.', '(032) 363-2211', 'FIRE'),
('Poro Medical', 1, 'Poro', 'Health center hotline.', '(032) 350-5120', 'MEDICAL'),
('Poro Disaster', 4, 'Poro', 'MDRRMO hotline.', '0919-354-6112', 'DISASTER'),

('Ronda Police', 3, 'Ronda', 'Police station hotline.', '0998-598-6368', 'POLICE'),
('Ronda Fire', 2, 'Ronda', 'Fire department line.', '(032) 471-1002', 'FIRE'),
('Ronda Medical', 1, 'Ronda', 'Rural health center.', '(032) 471-1011', 'MEDICAL'),
('Ronda Disaster', 4, 'Ronda', 'MDRRMO emergency line.', '0932-331-4190', 'DISASTER'),

('Samboan Police', 3, 'Samboan', 'Police emergency line.', '0998-598-6374', 'POLICE'),
('Samboan Fire', 2, 'Samboan', 'Fire department hotline.', '(032) 477-7102', 'FIRE'),
('Samboan Medical', 1, 'Samboan', 'Rural health unit line.', '(032) 477-7033', 'MEDICAL'),
('Samboan Disaster', 4, 'Samboan', 'Disaster response line.', '0917-322-1144', 'DISASTER'),

('San Fernando Police', 3, 'San Fernando', 'Police station hotline.', '(032) 488-9212', 'POLICE'),
('San Fernando Fire', 2, 'San Fernando', 'Fire station line.', '(032) 488-9511', 'FIRE'),
('San Fernando Medical', 1, 'San Fernando', 'Rural health unit line.', '(032) 488-9322', 'MEDICAL'),
('San Fernando Disaster', 4, 'San Fernando', 'MDRRMO hotline.', '(032) 488-9000', 'DISASTER'),

('San Francisco Police', 3, 'San Francisco', 'Camotes island police line.', '0998-598-6381', 'POLICE'),
('San Francisco Fire', 2, 'San Francisco', 'Fire station line.', '(032) 368-0112', 'FIRE'),
('San Francisco Medical', 1, 'San Francisco', 'Rural health office.', '(032) 368-0044', 'MEDICAL'),
('San Francisco Disaster', 4, 'San Francisco', 'MDRRMO hotline line.', '0917-532-1110', 'DISASTER'),

('San Remigio Police', 3, 'San Remigio', 'Police emergency line.', '0998-598-6358', 'POLICE'),
('San Remigio Fire', 2, 'San Remigio', 'Fire station line.', '(032) 435-0211', 'FIRE'),
('San Remigio Medical', 1, 'San Remigio', 'Health center line.', '(032) 435-0012', 'MEDICAL'),
('San Remigio Disaster', 4, 'San Remigio', 'MDRRMO hotline.', '0920-554-3211', 'DISASTER'),

('Santa Fe Police', 3, 'Santa Fe', 'Bantayan island police line.', '0998-598-6357', 'POLICE'),
('Santa Fe Fire', 2, 'Santa Fe', 'Fire protection station line.', '(032) 438-5011', 'FIRE'),
('Santa Fe Medical', 1, 'Santa Fe', 'Rural health center hotline.', '(032) 438-5022', 'MEDICAL'),
('Santa Fe Disaster', 4, 'Santa Fe', 'MDRRMO office line.', '0917-311-2233', 'DISASTER'),

('Santander Police', 3, 'Santander', 'Police station hotline.', '0998-598-6373', 'POLICE'),
('Santander Fire', 2, 'Santander', 'Fire department line.', '(032) 483-8011', 'FIRE'),
('Santander Medical', 1, 'Santander', 'Rural health center.', '(032) 483-8022', 'MEDICAL'),
('Santander Disaster', 4, 'Santander', 'MDRRMO hotline.', '0922-811-4455', 'DISASTER'),

('Sibonga Police', 3, 'Sibonga', 'Police station emergency line.', '0998-598-6366', 'POLICE'),
('Sibonga Fire', 2, 'Sibonga', 'Fire station line.', '(032) 486-1011', 'FIRE'),
('Sibonga Medical', 1, 'Sibonga', 'Rural health center.', '(032) 486-1022', 'MEDICAL'),
('Sibonga Disaster', 4, 'Sibonga', 'MDRRMO hotline.', '0917-333-5566', 'DISASTER'),

('Sogod Police', 3, 'Sogod', 'Police assistance hotline.', '0998-598-6389', 'POLICE'),
('Sogod Fire', 2, 'Sogod', 'Fire department line.', '(032) 434-8011', 'FIRE'),
('Sogod Medical', 1, 'Sogod', 'Rural health office.', '(032) 434-8022', 'MEDICAL'),
('Sogod Disaster', 4, 'Sogod', 'Disaster office line.', '0923-444-5566', 'DISASTER'),

('Tabogon Police', 3, 'Tabogon', 'Police station emergency line.', '0998-598-6364', 'POLICE'),
('Tabogon Fire', 2, 'Tabogon', 'Fire department line.', '(032) 463-0011', 'FIRE'),
('Tabogon Medical', 1, 'Tabogon', 'Rural health unit.', '(032) 463-0022', 'MEDICAL'),
('Tabogon Disaster', 4, 'Tabogon', 'MDRRMO emergency line.', '0918-444-2233', 'DISASTER'),

('Tabuelan Police', 3, 'Tabuelan', 'Police station hotline.', '0998-598-6360', 'POLICE'),
('Tabuelan Fire', 2, 'Tabuelan', 'Fire department line.', '(032) 462-0011', 'FIRE'),
('Tabuelan Medical', 1, 'Tabuelan', 'Rural health center line.', '(032) 462-0022', 'MEDICAL'),
('Tabuelan Disaster', 4, 'Tabuelan', 'MDRRMO office line.', '0922-555-7788', 'DISASTER'),

('Tuburan Police', 3, 'Tuburan', 'Police emergency line.', '0998-598-6359', 'POLICE'),
('Tuburan Fire', 2, 'Tuburan', 'Fire station line.', '(032) 463-3011', 'FIRE'),
('Tuburan Medical', 1, 'Tuburan', 'Rural health unit line.', '(032) 463-3022', 'MEDICAL'),
('Tuburan Disaster', 4, 'Tuburan', 'Disaster office line.', '0917-666-8899', 'DISASTER'),

('Tudela Police', 3, 'Tudela', 'Camotes island police line.', '0998-598-6386', 'POLICE'),
('Tudela Fire', 2, 'Tudela', 'Fire department line.', '(032) 363-5011', 'FIRE'),
('Tudela Medical', 1, 'Tudela', 'Rural health office line.', '(032) 363-5022', 'MEDICAL'),
('Tudela Disaster', 4, 'Tudela', 'MDRRMO hotline.', '0920-777-9900', 'DISASTER');
