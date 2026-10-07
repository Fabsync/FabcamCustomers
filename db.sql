-- Fabcam Technologies — License Management System
-- MySQL 8.x compatible

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS lead_activities;
DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS licenses;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  UNIQUE NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('admin','sales') DEFAULT 'sales',
  is_active     TINYINT(1)    DEFAULT 1,
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  last_login    DATETIME      NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customers (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  customer_id     VARCHAR(20)   UNIQUE NOT NULL,
  company_name    VARCHAR(200)  NOT NULL,
  contact_person  VARCHAR(100),
  mobile          VARCHAR(20),
  email           VARCHAR(150),
  gst_number      VARCHAR(20),
  address         TEXT,
  created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  created_by      INT,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_customers_search (company_name(100), customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(150) NOT NULL,
  module       VARCHAR(100),
  description  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE licenses (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  customer_id     INT           NOT NULL,
  product_id      INT           NOT NULL,
  license_type    ENUM('single','multi','server','cloud') DEFAULT 'single',
  server_code     VARCHAR(100),
  lock_code       VARCHAR(100),
  machine_name    VARCHAR(200)  NULL,
  purchase_price  DECIMAL(12,2),
  purchase_date   DATE,
  expiry_date     DATE,
  license_status  ENUM('active','expired','grace','revoked') DEFAULT 'active',
  amc_cost        DECIMAL(12,2),
  renewal_date    DATE,
  amc_status      ENUM('active','expired','not_applicable') DEFAULT 'not_applicable',
  remarks         TEXT,
  updated_by      INT,
  last_updated    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE RESTRICT,
  FOREIGN KEY (updated_by)  REFERENCES users(id)     ON DELETE SET NULL,
  INDEX idx_licenses_status (license_status),
  INDEX idx_licenses_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Seed Data
-- -------------------------------------------------------

-- admin@fabcam.com  password: Admin@123
-- sales@fabcam.com  password: Sales@123
INSERT INTO users (name, email, password_hash, role, is_active) VALUES
('Administrator', 'admin@fabcam.com',
 '$2y$12$wDC5dJUqYzHuxr5pTKJeA.LG7q/IvdZxgcV9B1WBxQTg5fc1yAjNq',
 'admin', 1),
('Sales User', 'sales@fabcam.com',
 '$2y$12$4e6LfS05YUIzXCnNtCOufuoGErVpwGqtKqMhBJV4.1Trf0iFq9lT.',
 'sales', 1);

INSERT INTO products (product_name, module, description) VALUES
('Fabcam ERP',  'Core ERP',        'Enterprise Resource Planning suite'),
('Fabcam POS',  'Point of Sale',   'Retail point-of-sale terminal software'),
('Fabcam HRMS', 'HR Management',   'Human resource and payroll management');

INSERT INTO customers (customer_id, company_name, contact_person, mobile, email, gst_number, address, created_by) VALUES
('FAB-0001', 'Acme Corp Pvt Ltd',    'Rajesh Kumar',  '9876543210', 'rajesh@acmecorp.in',   '27AAACA0932F1ZJ', '123, MG Road, Mumbai, Maharashtra 400001', 1),
('FAB-0002', 'TechSoft Solutions',   'Priya Sharma',  '9123456789', 'priya@techsoft.in',    '29AADFT1234A1ZP', '45, Brigade Road, Bengaluru, Karnataka 560001', 1),
('FAB-0003', 'Sunrise Retail Ltd',   'Anand Mehta',   '9988776655', 'anand@sunriseretail.in','24AABCS1234D1ZM', '78, CG Road, Ahmedabad, Gujarat 380009', 2);

INSERT INTO licenses (customer_id, product_id, license_type, server_code, lock_code, machine_name, purchase_price, purchase_date, expiry_date, license_status, amc_cost, renewal_date, amc_status, updated_by) VALUES
(1, 1, 'single', 'SRV-ACM-001', 'LCK-4F9A2B', 'ACME-WS-001',    45000.00, '2024-01-15', '2026-01-15', 'active',   4500.00, '2026-01-15', 'active',         1),
(1, 2, 'multi',  'SRV-ACM-002', 'LCK-7D3E1C', 'ACME-WS-002',    28000.00, '2023-06-01', '2025-06-01', 'expired',  2800.00, '2025-06-01', 'expired',        1),
(2, 1, 'server', 'SRV-TSF-001', 'LCK-9B5F4A', 'TECHSOFT-SRV-01',85000.00, '2024-03-20', '2026-04-01', 'active',   8500.00, '2026-04-01', 'active',         1),
(3, 2, 'cloud',  NULL,          NULL,          NULL,             35000.00, '2025-01-10', '2026-07-01', 'grace',    3500.00, '2026-07-01', 'not_applicable', 2);

-- -------------------------------------------------------
-- Estimates
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS estimates (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  estimate_number VARCHAR(20)    UNIQUE NOT NULL,
  customer_id     INT            NOT NULL,
  estimate_date   DATE           NOT NULL,
  valid_until     DATE           NULL,
  subtotal        DECIMAL(14,2)  NOT NULL DEFAULT 0,
  discount_pct    DECIMAL(5,2)   NOT NULL DEFAULT 0,
  discount_amt    DECIMAL(14,2)  NOT NULL DEFAULT 0,
  taxable_amount  DECIMAL(14,2)  NOT NULL DEFAULT 0,
  tax_type        ENUM('none','cgst_sgst','igst') NOT NULL DEFAULT 'cgst_sgst',
  tax_rate        DECIMAL(5,2)   NOT NULL DEFAULT 0,
  cgst_amount     DECIMAL(14,2)  NOT NULL DEFAULT 0,
  sgst_amount     DECIMAL(14,2)  NOT NULL DEFAULT 0,
  igst_amount     DECIMAL(14,2)  NOT NULL DEFAULT 0,
  grand_total     DECIMAL(14,2)  NOT NULL DEFAULT 0,
  notes           TEXT           NULL,
  terms           TEXT           NULL,
  status          ENUM('draft','sent','accepted','cancelled') NOT NULL DEFAULT 'draft',
  created_by      INT            NULL,
  created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
  INDEX idx_estimates_customer (customer_id),
  INDEX idx_estimates_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS estimate_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  estimate_id  INT            NOT NULL,
  sl_no        INT            NOT NULL DEFAULT 1,
  description  VARCHAR(500)   NOT NULL,
  hsn_sac      VARCHAR(20)    NULL,
  quantity     DECIMAL(10,3)  NOT NULL DEFAULT 1,
  unit         VARCHAR(20)    NOT NULL DEFAULT 'Nos',
  unit_price   DECIMAL(14,2)  NOT NULL DEFAULT 0,
  amount       DECIMAL(14,2)  NOT NULL DEFAULT 0,
  FOREIGN KEY (estimate_id) REFERENCES estimates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Leads / Enquiries
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  lead_number     VARCHAR(20)    UNIQUE NOT NULL,
  customer_id     INT            NULL,
  company_name    VARCHAR(200)   NOT NULL,
  contact_person  VARCHAR(100),
  mobile          VARCHAR(20),
  email           VARCHAR(150),
  product_id      INT            NULL,
  requirement     TEXT,
  source          ENUM('phone','email','website','referral','walk_in','exhibition','existing_customer','other') NOT NULL DEFAULT 'phone',
  status          ENUM('new','contacted','follow_up','demo','quoted','negotiation','won','lost','not_interested') NOT NULL DEFAULT 'new',
  interest_level  ENUM('hot','warm','cold') NOT NULL DEFAULT 'warm',
  expected_value  DECIMAL(14,2)  NULL,
  next_follow_up  DATE           NULL,
  close_reason    TEXT           NULL,
  closed_at       DATETIME       NULL,
  assigned_to     INT            NULL,
  created_by      INT            NULL,
  created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE SET NULL,
  FOREIGN KEY (assigned_to) REFERENCES users(id)     ON DELETE SET NULL,
  FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
  INDEX idx_leads_status    (status),
  INDEX idx_leads_follow_up (next_follow_up)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_activities (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  lead_id         INT            NOT NULL,
  activity_date   DATE           NOT NULL,
  activity_type   ENUM('call','meeting','email','demo','visit','note') NOT NULL DEFAULT 'call',
  notes           TEXT           NOT NULL,
  status_before   VARCHAR(20)    NULL,
  status_after    VARCHAR(20)    NULL,
  interest_level  VARCHAR(10)    NULL,
  next_follow_up  DATE           NULL,
  created_by      INT            NULL,
  created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id)    REFERENCES leads(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_lead_activities_lead (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
