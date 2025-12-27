<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';

class ReportController extends Controller
{
    private $reportService;
    private $taskModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportService = new ReportService();
        $this->taskModel = new Task();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
    }

    public function index() { $this->view('reports/index'); }

    public function taskReport()
    {
        $f = $this->getFilters();
        if ($this->currentUser['role'] === 'admin' || $this->currentUser['role'] === 'manager') {
            $tasks = $this->taskModel->all($f);
        } else {
            $tasks = $this->taskModel->all($f, null, null, $this->currentUser['id'], $this->currentUser['role']);
        }

        $this->view('reports/task_report', [
            'title' => 'Task Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'filters' => $f,
            'tasks' => $tasks,
            'statistics' => $this->reportService->calculateTaskStats($tasks)
        ]);
    }

    public function userReport()
    {
        if ($this->currentUser['role'] !== 'admin' && $this->currentUser['role'] !== 'manager') $this->errorRedirect("Access denied", "?action=index");
        
        $f = ['date_from' => $this->query('date_from'), 'date_to' => $this->query('date_to'), 'user_id' => $this->query('user_id')];
        $users = $this->reportService->getUserActivityData($f);

        $this->view('reports/user_report', [
            'title' => 'User Activity Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'filters' => $f,
            'users' => $users,
            'statistics' => $this->reportService->calculateUserStats($users)
        ]);
    }

    public function overdueReport()
    {
        $overdue = $this->taskModel->getOverdue($this->currentUser['id'], $this->currentUser['role']);
        $this->view('reports/overdue_report', [
            'title' => 'Overdue Tasks Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'tasks' => $overdue,
            'total_overdue' => count($overdue ?: [])
        ]);
    }

    private function getFilters()
    {
        return [
            'date_from' => $this->query('date_from'),
            'date_to' => $this->query('date_to'),
            'status' => $this->query('status'),
            'priority_id' => $this->query('priority_id'),
            'assigned_to' => $this->query('assigned_to'),
            'created_by' => $this->query('created_by')
        ];
    }
}

$action = $_GET['action'] ?? 'index';
$controller = new ReportController();

$methodName = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $action))));

if (method_exists($controller, $methodName)) {
    $controller->$methodName();
} else {
    $controller->index();
}
