USE foleni_db;

CREATE TABLE IF NOT EXISTS queue_entries (

    id INT AUTO_INCREMENT PRIMARY KEY,

    customer_name VARCHAR(100) NOT NULL,

    service VARCHAR(100) NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'Waiting',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);