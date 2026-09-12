-- ============================================================
-- GlobeTrek Adventures
-- CSE 5009 Web Application Development
-- Database Schema + Development Seed Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS globetrek
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE globetrek;


-- ============================================================
-- USERS
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'staff', 'admin') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- PACKAGES
-- ============================================================

CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    activities TEXT,
    image VARCHAR(255),
    created_by INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_packages_created_by
        FOREIGN KEY (created_by)
        REFERENCES users(id)
) ENGINE=InnoDB;


-- ============================================================
-- ACCOMMODATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    hotel_name VARCHAR(150) NOT NULL,
    location VARCHAR(150) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    rating DECIMAL(2,1),

    CONSTRAINT fk_accommodations_package
        FOREIGN KEY (package_id)
        REFERENCES packages(id)
) ENGINE=InnoDB;


-- ============================================================
-- TRANSPORTATION
-- ============================================================

CREATE TABLE IF NOT EXISTS transportation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    provider VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_transportation_package
        FOREIGN KEY (package_id)
        REFERENCES packages(id)
) ENGINE=InnoDB;


-- ============================================================
-- BOOKINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    travel_date DATE NOT NULL,
    num_travelers INT NOT NULL,
    customization_notes TEXT,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT fk_bookings_package
        FOREIGN KEY (package_id)
        REFERENCES packages(id)
) ENGINE=InnoDB;


-- ============================================================
-- PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('card', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,

    CONSTRAINT fk_payments_booking
        FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
) ENGINE=InnoDB;


-- ============================================================
-- QUERIES
-- ============================================================

CREATE TABLE IF NOT EXISTS queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'answered') DEFAULT 'open',
    staff_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_queries_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
) ENGINE=InnoDB;


-- ============================================================
-- DEVELOPMENT SEED DATA
-- ============================================================

-- NOTE:
-- These are development accounts only.
-- Public registration creates customer accounts normally.
-- Passwords are stored as hashes.

INSERT IGNORE INTO users
    (id, full_name, email, password_hash, role, phone)
VALUES
    (
        1,
        'Nimal Perera',
        'nimal@example.com',
        '$2y$12$vEVIpH.YUUiG8J0lydiP1.aHkWdeu36TFXfSxdlJSA/AItsurv1qK',
        'customer',
        '0712345678'
    ),
    (
        2,
        'Tharushi Fernando',
        'tharushi@example.com',
        '$2y$12$vEVIpH.YUUiG8J0lydiP1.aHkWdeu36TFXfSxdlJSA/AItsurv1qK',
        'customer',
        '0771234567'
    ),
    (
        3,
        'Kasun Silva',
        'kasun.staff@example.com',
        '$2y$12$SVD49NBmdVY.qTVont2nN.5LpQG6PVNlT1SLtdlKuLm422yfWgEcu',
        'staff',
        '0765551234'
    ),
    (
        4,
        'Amal Wijesinghe',
        'amal.admin@example.com',
        '$2y$12$8N1H4.3JYjWfr8mllIcjveJbdOYJ24Xm965uQnyZmC9nZjEZ/TdAO',
        'admin',
        '0758884567'
    );


INSERT IGNORE INTO packages
    (
        id,
        title,
        destination,
        description,
        price,
        duration_days,
        activities,
        image,
        created_by,
        status
    )
VALUES
    (
        1,
        'Negombo Coastal Escape',
        'Negombo',
        'A relaxing coastal holiday combining beach activities, local cuisine and a scenic lagoon experience.',
        45000.00,
        3,
        'Beach relaxation, lagoon boat ride, seafood dining, city sightseeing',
        'negombo-coastal.jpg',
        3,
        'active'
    ),
    (
        2,
        'Kandy Cultural Discovery',
        'Kandy',
        'Explore the cultural heritage of Kandy with temple visits, traditional experiences and scenic viewpoints.',
        65000.00,
        4,
        'Temple of the Tooth, cultural dance, Kandy Lake, viewpoint visit',
        'kandy-cultural.jpg',
        3,
        'active'
    ),
    (
        3,
        'Ella Mountain Adventure',
        'Ella',
        'An adventure-focused trip through the hill country featuring hiking, waterfalls and spectacular mountain views.',
        80000.00,
        5,
        'Ella Rock hike, Nine Arches Bridge, Little Adams Peak, waterfall visit',
        'ella-adventure.jpg',
        3,
        'active'
    ),
    (
        4,
        'Galle Heritage Journey',
        'Galle',
        'Discover the historic southern coastline, including the famous Galle Fort and surrounding cultural attractions.',
        35000.00,
        3,
        'Galle Fort, lighthouse visit, coastal sightseeing, local market',
        'galle-heritage.jpg',
        3,
        'active'
    );


INSERT IGNORE INTO accommodations
    (
        package_id,
        hotel_name,
        location,
        price_per_night,
        rating
    )
VALUES
    (1, 'Jetwing Sea', 'Negombo', 18000.00, 4.5),
    (2, 'Earl''s Regency', 'Kandy', 22000.00, 4.4),
    (3, 'Mountain Heaven', 'Ella', 20000.00, 4.3),
    (4, 'Fort Bazaar', 'Galle', 25000.00, 4.6);


INSERT IGNORE INTO transportation
    (
        package_id,
        type,
        provider,
        price
    )
VALUES
    (1, 'Van', 'GlobeTrek Private Transport', 12000.00),
    (2, 'Van', 'GlobeTrek Private Transport', 18000.00),
    (3, 'Van', 'GlobeTrek Private Transport', 22000.00),
    (4, 'Van', 'GlobeTrek Private Transport', 14000.00);