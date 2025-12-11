-- Seed Admin Account
-- Email: berhanufiro@gmail.com
-- Password: fb29371234

USE task_manager;

-- Hash the password (bcrypt hash for 'fb29371234')
SET @hashed_password = '$2y$12$y4yx2SsUjv3aXdTyJ69S6.rgw6pxOXx/CAvvvf41dI6V/5k0AHa4S';

-- Insert admin user
INSERT INTO users (full_name, email, password, role_id, status, created_at) 
VALUES (
    'Berhanu Firo',
    'berhanufiro@gmail.com',
    @hashed_password,
    1,  -- Admin role
    'active',
    NOW()
)
ON DUPLICATE KEY UPDATE 
    role_id = 1,  -- Update to admin if email exists
    password = @hashed_password;

-- Verify the admin was created
SELECT id, full_name, email, role_id, created_at 
FROM users 
WHERE email = 'berhanufiro@gmail.com';

-- Show role name
SELECT u.id, u.full_name, u.email, r.name as role, u.created_at
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.email = 'berhanufiro@gmail.com';
