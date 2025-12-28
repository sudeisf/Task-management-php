<?php

require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../core/Validator.php';

class ProjectService
{
    private $projectModel;
    private $userModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
    }

    public function validateProject($data)
    {
        $validator = new Validator();
        $rules = [
            'name' => 'required|max:255',
            'description' => 'max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ];

        $labels = [
            'name' => 'Project Name',
            'description' => 'Description',
            'start_date' => 'Start Date',
            'end_date' => 'End Date'
        ];

        if (!$validator->validate($data, $rules, $labels)) {
            return ['errors' => $validator->getErrors(), 'data' => false];
        }

        $sanitized = $validator->getSanitizedData();
        $errors = [];
        $today = date('Y-m-d');
        
        // Validate start_date is not in the past
        $startDate = null;
        if (!empty($data['start_date'])) {
            $startDate = date('Y-m-d', strtotime($data['start_date']));
            if ($startDate < $today) {
                $errors['start_date'] = ['Start date cannot be in the past.'];
            }
        }
        
        // Validate end_date is not in the past
        $endDate = null;
        if (!empty($data['end_date'])) {
            $endDate = date('Y-m-d', strtotime($data['end_date']));
            if ($endDate < $today) {
                $errors['end_date'] = ['End date cannot be in the past.'];
            }
            // Also ensure end_date is after start_date if both are set
            if ($startDate && $endDate < $startDate) {
                $errors['end_date'] = ['End date must be after start date.'];
            }
        }
        
        if (!empty($errors)) {
            return ['errors' => $errors, 'data' => false];
        }
        
        return [
            'errors' => [],
            'data' => [
                'name' => $sanitized['name'],
                'description' => $sanitized['description'] ?? '',
                'status' => $sanitized['status'] ?? 'active',
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    public function getProjectsByRole($userId, $userRole, $filters)
    {
        if ($userRole === 'member') return $this->projectModel->getByMember($userId, $filters);
        if ($userRole === 'manager') return $this->projectModel->getByManager($userId, $filters);
        return $this->projectModel->all($filters);
    }
}
