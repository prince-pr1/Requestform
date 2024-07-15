CREATE TABLE log_book (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(255),
    action VARCHAR(50),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    details TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


Product Table Triggers
-- Trigger 1 
DELIMITER //

CREATE TRIGGER after_product_insert
AFTER INSERT ON product
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('product', 'INSERT', NULL, CONCAT('Inserted product: ', NEW.product_name));
END //

CREATE TRIGGER after_product_update
AFTER UPDATE ON product
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('product', 'UPDATE', NULL, CONCAT('Updated product: ', NEW.product_name));
END //
-- Trigger 2
CREATE TRIGGER after_product_delete
AFTER DELETE ON product
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('product', 'DELETE', NULL, CONCAT('Deleted product: ', OLD.product_name));
END //

-- Trigger 3
DELIMITER ;


Users Table Triggers

DELIMITER //

CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('users', 'INSERT', NULL, CONCAT('Inserted user: ', NEW.username));
END //

CREATE TRIGGER after_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('users', 'UPDATE', NULL, CONCAT('Updated user: ', NEW.username));
END //

CREATE TRIGGER after_user_delete
AFTER DELETE ON users
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('users', 'DELETE', NULL, CONCAT('Deleted user: ', OLD.username));
END //

DELIMITER ;

-- Trigger 4

Request Table Triggers


DELIMITER //

CREATE TRIGGER after_request_insert
AFTER INSERT ON request
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('request', 'INSERT', NEW.rqst_by, CONCAT('Inserted request: ', NEW.rqst_title));
END //

-- Trigger 5

CREATE TRIGGER after_request_update
AFTER UPDATE ON request
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('request', 'UPDATE', NEW.rqst_by, CONCAT('Updated request: ', NEW.rqst_title));
END //

-- Trigger 6

CREATE TRIGGER after_request_delete
AFTER DELETE ON request
FOR EACH ROW
BEGIN
    INSERT INTO log_book (table_name, action, user_id, details)
    VALUES ('request', 'DELETE', OLD.rqst_by, CONCAT('Deleted request: ', OLD.rqst_title));
END //

DELIMITER ;