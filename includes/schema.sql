-- Table structure for table `users`
-- This table stores user information for authentication and identification.

CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT, -- Unique identifier for the user
  `username` VARCHAR(255) UNIQUE NOT NULL, -- Unique username for login
  `password_hash` VARCHAR(255) NOT NULL, -- Hashed password for security
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Timestamp of user creation
);

-- Table structure for table `work_hours`
-- This table stores work hour entries for users.

CREATE TABLE `work_hours` (
  `id` INT PRIMARY KEY AUTO_INCREMENT, -- Unique identifier for the work hour entry
  `user_id` INT NOT NULL, -- Foreign key referencing the user who logged the hours
  `start_time` DATETIME NOT NULL, -- Timestamp when the work period started
  `end_time` DATETIME NULL, -- Timestamp when the work period ended (can be NULL if ongoing)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp of work hour entry creation
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE -- Ensures data integrity; if a user is deleted, their work hours are also deleted.
);
