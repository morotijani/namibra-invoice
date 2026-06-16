CREATE DATABASE IF NOT EXISTS namibra_invoice;
USE namibra_invoice;

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('new', 'pending', 'partial payment', 'full paid', 'completed') DEFAULT 'new',
    bill_to_name VARCHAR(255) NOT NULL,
    bill_to_town_city VARCHAR(255) NOT NULL,
    bill_to_region_country VARCHAR(255) NOT NULL,
    bill_to_phone VARCHAR(50) NOT NULL,
    project_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_due DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(500) NOT NULL,
    quantity INT NOT NULL,
    rate DECIMAL(10, 2) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);
