<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../core/Validator.php';

class AdminService
{
    private $userModel;
    private $activityModel;

    public function __construct()
    {
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
        $this->activityModel = new Activity();
    }

    public function validateUser($data, $isNew = true)
    {
        $validator = new Validator();
        $rules = [
            'full_name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,manager,member',
            'status' => 'required|in:active,inactive'
        ];

        if ($isNew) {
            $rules['password'] = 'required|min:8';
        } elseif (isset($data['change_password']) && !empty($data['new_password'])) {
            $rules['new_password'] = 'min:8';
        }

        if (!$validator->validate($data, $rules)) {
            return ['errors' => $validator->getErrors(), 'data' => false];
        }

        $sanitized = $validator->getSanitizedData();
        
        // Custom password complexity check if needed (omitted for brevity but can be added here)
        
        return ['errors' => [], 'data' => $sanitized];
    }

    public function logActivity($userId, $type, $details)
    {
        $this->activityModel->log($userId, null, $type, $details);
    }
}
