-- Add avatar column to users table
-- This migration adds the avatar column for profile image uploads

USE task_manager;

ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER profile_picture;
