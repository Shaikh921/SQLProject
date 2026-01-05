-- Create DB
CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE library_db;

-- Authors
CREATE TABLE authors (
  author_id INT AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(150) NOT NULL
);

-- Categories
CREATE TABLE book_categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL
);

-- Books
CREATE TABLE books (
  book_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author_id INT NOT NULL,
  category_id INT NOT NULL,
  publisher VARCHAR(200),
  publication_year YEAR,
  isbn VARCHAR(20) UNIQUE,
  quantity_total INT NOT NULL DEFAULT 1,
  quantity_available INT NOT NULL DEFAULT 1,
  shelf_location VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES authors(author_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (category_id) REFERENCES book_categories(category_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Members
CREATE TABLE members (
  member_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) UNIQUE,
  phone VARCHAR(20),
  address VARCHAR(255),
  membership_type ENUM('student','faculty') DEFAULT 'student',
  password_hash VARCHAR(255) DEFAULT NULL,
  join_date DATE DEFAULT (CURRENT_DATE)
);

-- Admins
CREATE TABLE admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) UNIQUE NOT NULL,
  name VARCHAR(120) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('librarian','assistant') DEFAULT 'librarian',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Issue table
CREATE TABLE issue_books (
  issue_id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  member_id INT NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE,
  fine_amount DECIMAL(10,2) DEFAULT 0.00,
  status ENUM('issued','returned') DEFAULT 'issued',
  FOREIGN KEY (book_id) REFERENCES books(book_id),
  FOREIGN KEY (member_id) REFERENCES members(member_id),
  INDEX (book_id),
  INDEX (member_id)
);

-- Reservation
CREATE TABLE reservations (
  reservation_id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  member_id INT NOT NULL,
  reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved ENUM('pending','notified','cancelled') DEFAULT 'pending',
  FOREIGN KEY (book_id) REFERENCES books(book_id),
  FOREIGN KEY (member_id) REFERENCES members(member_id)
);

-- Activity log
CREATE TABLE activity_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT,
  action VARCHAR(200),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Sample data
INSERT INTO authors (author_name) VALUES 
('J. K. Rowling'), 
('George Orwell'), 
('Cormen, Leiserson, Rivest');

INSERT INTO book_categories (category_name) VALUES 
('Fiction'), 
('History'), 
('Computer Science');

INSERT INTO books (title, author_id, category_id, publisher, publication_year, isbn, quantity_total, quantity_available, shelf_location) VALUES 
('Harry Potter and the Sorcerer''s Stone', 1, 1, 'Bloomsbury', 1997, '9780747532699', 5, 5, 'A-1'),
('1984', 2, 2, 'Secker & Warburg', 1949, '9780451524935', 3, 3, 'B-3'),
('Introduction to Algorithms', 3, 3, 'MIT Press', 2009, '9780262033848', 2, 2, 'C-2');

-- Insert members with password hash for 'member123'
INSERT INTO members (name, email, phone, address, membership_type, password_hash) VALUES 
('Rahul Sharma', 'rahul@example.com', '9876543210', 'Mumbai', 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Dr. Aisha Khan', 'aisha@example.com', '9123456780', 'Pune', 'faculty', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create initial admin (password: Admin@123)
-- Note: This hash is for 'password'. After importing, run fix_admin_password.php to set correct password
-- Or manually update with: UPDATE admins SET password_hash = '$2y$10$[your_hash]' WHERE username = 'admin';
INSERT INTO admins (username, name, password_hash, role) VALUES 
('admin', 'Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian');

-- View: currently issued books
CREATE OR REPLACE VIEW v_current_issues AS
SELECT i.issue_id, i.book_id, b.title, i.member_id, m.name AS member_name, i.issue_date, i.due_date, i.status
FROM issue_books i
JOIN books b ON i.book_id = b.book_id
JOIN members m ON i.member_id = m.member_id
WHERE i.status = 'issued';

-- Stored Procedure: issue_book
DROP PROCEDURE IF EXISTS sp_issue_book;
DELIMITER $$
CREATE PROCEDURE sp_issue_book(IN p_book_id INT, IN p_member_id INT, IN p_days INT)
BEGIN
  DECLARE v_available INT;
  SELECT quantity_available INTO v_available FROM books WHERE book_id = p_book_id FOR UPDATE;
  IF v_available IS NULL OR v_available <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Book not available';
  ELSE
    START TRANSACTION;
      UPDATE books SET quantity_available = quantity_available - 1 WHERE book_id = p_book_id;
      INSERT INTO issue_books (book_id, member_id, issue_date, due_date, status)
        VALUES (p_book_id, p_member_id, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL p_days DAY), 'issued');
    COMMIT;
  END IF;
END$$
DELIMITER ;

-- Trigger: after return
DROP TRIGGER IF EXISTS trg_after_return;
DELIMITER $$
CREATE TRIGGER trg_after_return AFTER UPDATE ON issue_books
FOR EACH ROW
BEGIN
  IF NEW.status = 'returned' AND OLD.status = 'issued' THEN
    UPDATE books SET quantity_available = quantity_available + 1 WHERE book_id = NEW.book_id;
  END IF;
END$$
DELIMITER ;

-- Function: compute fine
DROP FUNCTION IF EXISTS fn_compute_fine;
DELIMITER $$
CREATE FUNCTION fn_compute_fine(p_due DATE, p_return DATE)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
  DECLARE days_late INT DEFAULT 0;
  IF p_return IS NULL THEN
    RETURN 0.00;
  END IF;
  IF p_return > p_due THEN
    SET days_late = DATEDIFF(p_return, p_due);
    RETURN days_late * 5.00;
  ELSE
    RETURN 0.00;
  END IF;
END$$
DELIMITER ;
