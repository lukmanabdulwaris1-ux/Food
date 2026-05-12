-- AyoFoods Database Schema
CREATE DATABASE IF NOT EXISTS ayofoods;
USE ayofoods;

CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('food','drink','dessert') DEFAULT 'food',
    image_url VARCHAR(255),
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    items JSON NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','preparing','delivered','cancelled') DEFAULT 'pending',
    payment_method ENUM('cash','card','transfer') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed menu items
INSERT INTO menu_items (name, description, price, category, image_url) VALUES
('Jollof Rice & Chicken', 'Smoky Nigerian jollof rice with grilled chicken', 2500.00, 'food', 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400'),
('Fried Rice & Beef', 'Colorful fried rice with tender beef chunks', 2200.00, 'food', 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'),
('Pounded Yam & Egusi', 'Smooth pounded yam with rich egusi soup', 3000.00, 'food', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400'),
('Grilled Fish', 'Whole tilapia grilled with spices and herbs', 3500.00, 'food', 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400'),
('Suya Platter', 'Spiced beef skewers with onions and tomatoes', 2000.00, 'food', 'https://images.unsplash.com/photo-1529042410759-befb1204b468?w=400'),
('Burger & Fries', 'Juicy beef burger with crispy fries', 2800.00, 'food', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400'),
('Peppered Snail', 'Tender snails in spicy pepper sauce', 4000.00, 'food', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400'),
('Shawarma', 'Chicken shawarma with garlic sauce and veggies', 1800.00, 'food', 'https://images.unsplash.com/photo-1561651823-34feb02250e4?w=400'),
('Chapman', 'Classic Nigerian Chapman cocktail drink', 800.00, 'drink', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400'),
('Zobo Drink', 'Chilled hibiscus drink with ginger', 500.00, 'drink', 'https://images.unsplash.com/photo-1497534446932-c925b458314e?w=400'),
('Fresh Juice', 'Freshly blended fruit juice of the day', 700.00, 'drink', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400'),
('Smoothie Bowl', 'Thick fruit smoothie with granola toppings', 1500.00, 'drink', 'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?w=400'),
('Chin Chin', 'Crunchy fried dough snack', 600.00, 'dessert', 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=400'),
('Puff Puff', 'Soft deep-fried dough balls', 500.00, 'dessert', 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400'),
('Ice Cream', 'Creamy vanilla and chocolate ice cream', 1200.00, 'dessert', 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400');
