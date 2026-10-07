-- Fabcam Technologies — Leads / Enquiries module
-- Run once against an existing database (db.sql already includes these tables for fresh installs).

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

-- Discussion log: one row per conversation held with the account
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
