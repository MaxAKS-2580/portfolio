-- Run against the RDS MySQL instance:
--   mysql -h <RDS_HOST> -u <admin_user> -p < schema.sql

CREATE DATABASE IF NOT EXISTS student_portfolio
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE student_portfolio;

CREATE TABLE IF NOT EXISTS students (
  student_id   INT AUTO_INCREMENT PRIMARY KEY,
  full_name    VARCHAR(120) NOT NULL,
  university   VARCHAR(150) NOT NULL,
  program      VARCHAR(150) NOT NULL,
  grad_year    YEAR NOT NULL
);

CREATE TABLE IF NOT EXISTS academic_records (
  record_id    INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  course_code  VARCHAR(20) NOT NULL,
  course_name  VARCHAR(150) NOT NULL,
  semester     VARCHAR(20) NOT NULL,
  credits      DECIMAL(3,1) NOT NULL,
  grade        VARCHAR(3) NOT NULL,
  FOREIGN KEY (student_id) REFERENCES students(student_id)
);

-- Application user with least-privilege access (used by db_config.php,
-- NOT the RDS master user).
CREATE USER IF NOT EXISTS 'portfolio_app'@'%' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT SELECT ON student_portfolio.* TO 'portfolio_app'@'%';
FLUSH PRIVILEGES;

-- Seed data
INSERT INTO students (full_name, university, program, grad_year) VALUES
  ('Akshat Abhishek Singh', 'VIT-AP University', 'B.Tech Computer Science and Engineering', 2028);

INSERT INTO academic_records (student_id, course_code, course_name, semester, credits, grade) VALUES
  (1, 'MAT1011', 'Applied Statistics',            'Sem 3', 3.0, 'A'),
  (1, 'CSE2005', 'Object Oriented Programming',   'Sem 2', 4.0, 'A'),
  (1, 'CSE2006', 'Database Systems',              'Sem 3', 4.0, 'A'),
  (1, 'CSE3008', 'Computer Networks',             'Sem 4', 3.0, 'B+'),
  (1, 'CSE3011', 'Software Engineering',          'Sem 4', 3.0, 'A'),
  (1, 'CSE4001', 'Distributed Systems',           'Sem 5', 4.0, 'A');
