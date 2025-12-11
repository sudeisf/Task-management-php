<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../helpers/functions.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class ProjectController
{
    private $projectModel;
    private $userModel;
    private $permissionModel;
    private $currentUser;

    public function __construct()
    {
        $db = new Database();
        $this->projectModel = new Project();
        $this->userModel = new User($db->getConnection());
        $this->permissionModel = new Permission();
        $this->currentUser = Auth::user();
    }

    // ==================== LIST PROJECTS ====================
    
    public function index()
    {
        $userRole = $this->getUserRole();
        
        // Check permission
        if ($userRole === 'member') {
            // Members see projects through their tasks
            $projects = $this->projectModel->getByMember($this->currentUser['id']);
        } elseif ($userRole === 'manager') {
            // Managers see assigned projects
            $projects = $this->projectModel->getByManager($this->currentUser['id']);
        } else {
            // Admins see all projects
            $filters = $this->buildFilters();
            $projects = $this->projectModel->all($filters);
        }

        // Extract data for view
        extract([
            'projects' => $projects,
            'userRole' => $userRole
        ]);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/projects/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // ==================== CREATE PROJECT ====================
    
    public function create()
    {
        // Only admins can create projects
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to create projects.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Get users for assignment
        $users = $this->userModel->getAll();

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/projects/create.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Only admins can create projects
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to create projects.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Validate input
        $data = $this->validateProjectData($_POST);

        if (!$data) {
            redirect(BASE_URL . '/controller/ProjectController.php?action=create');
            return;
        }

        $data['created_by'] = $this->currentUser['id'];

        // Create project
        if ($projectId = $this->projectModel->create($data)) {
            // Assign managers if specified
            if (!empty($_POST['managers'])) {
                foreach ($_POST['managers'] as $managerId) {
                    $this->projectModel->assignUser($projectId, $managerId, 'manager');
                }
            }

            setFlashMessage('success', 'Project created successfully!');
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
        } else {
            setFlashMessage('error', 'Failed to create project.');
            redirect(BASE_URL . '/controller/ProjectController.php?action=create');
        }
    }

    // ==================== VIEW PROJECT ====================
    
    public function show($id)
    {
        $project = $this->projectModel->find($id);

        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        $userRole = $this->getUserRole();

        // Check access
        if (!$this->projectModel->hasAccess($id, $this->currentUser['id'], $userRole)) {
            setFlashMessage('error', 'You do not have access to this project.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Get project team
        $teamMembers = $this->projectModel->getTeamMembers($id);
        
        // Get project statistics
        $statistics = $this->projectModel->getStatistics($id);

        // Extract data for view
        extract([
            'project' => $project,
            'teamMembers' => $teamMembers,
            'statistics' => $statistics,
            'userRole' => $userRole
        ]);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/projects/show.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // ==================== EDIT PROJECT ====================
    
    public function edit($id)
    {
        // Only admins can edit projects
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to edit projects.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        $project = $this->projectModel->find($id);

        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Get users for assignment
        $users = $this->userModel->getAll();
        $currentManagers = $this->projectModel->getManagers($id);

        extract([
            'project' => $project,
            'users' => $users,
            'currentManagers' => $currentManagers
        ]);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/projects/edit.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Only admins can update projects
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to update projects.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        $project = $this->projectModel->find($id);

        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Validate input
        $data = $this->validateProjectData($_POST);

        if (!$data) {
            redirect(BASE_URL . '/controller/ProjectController.php?action=edit&id=' . $id);
            return;
        }

        // Update project
        if ($this->projectModel->update($id, $data)) {
            // Update managers
            // First, remove all current managers
            $currentManagers = $this->projectModel->getManagers($id);
            foreach ($currentManagers as $manager) {
                $this->projectModel->removeUser($id, $manager['id']);
            }

            // Then add new managers
            if (!empty($_POST['managers'])) {
                foreach ($_POST['managers'] as $managerId) {
                    $this->projectModel->assignUser($id, $managerId, 'manager');
                }
            }

            setFlashMessage('success', 'Project updated successfully!');
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $id);
        } else {
            setFlashMessage('error', 'Failed to update project.');
            redirect(BASE_URL . '/controller/ProjectController.php?action=edit&id=' . $id);
        }
    }

    // ==================== DELETE PROJECT ====================
    
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        // Only admins can delete projects
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to delete projects.');
            redirect(BASE_URL . '/controller/ProjectController.php');
            return;
        }

        if ($this->projectModel->delete($id)) {
            setFlashMessage('success', 'Project deleted successfully!');
        } else {
            setFlashMessage('error', 'Failed to delete project.');
        }

        redirect(BASE_URL . '/controller/ProjectController.php');
    }

    // ==================== TEAM MANAGEMENT ====================
    
    public function assignUser($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
            return;
        }

        // Only admins can assign users
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to assign users.');
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
            return;
        }

        $userId = $_POST['user_id'] ?? null;
        $role = $_POST['role'] ?? 'member';

        if (!$userId) {
            setFlashMessage('error', 'User ID is required.');
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
            return;
        }

        if ($this->projectModel->assignUser($projectId, $userId, $role)) {
            setFlashMessage('success', 'User assigned to project successfully!');
        } else {
            setFlashMessage('error', 'Failed to assign user to project.');
        }

        redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
    }

    public function removeUser($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
            return;
        }

        // Only admins can remove users
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) {
            setFlashMessage('error', 'You do not have permission to remove users.');
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
            return;
        }

        $userId = $_POST['user_id'] ?? null;

        if (!$userId) {
            setFlashMessage('error', 'User ID is required.');
            redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
            return;
        }

        if ($this->projectModel->removeUser($projectId, $userId)) {
            setFlashMessage('success', 'User removed from project successfully!');
        } else {
            setFlashMessage('error', 'Failed to remove user from project.');
        }

        redirect(BASE_URL . '/controller/ProjectController.php?action=show&id=' . $projectId);
    }

    // ==================== HELPER METHODS ====================
    
    private function getUserRole()
    {
        $sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$this->currentUser['id']]);
        $result = $db->getRow();
        return $result['name'] ?? 'member';
    }

    private function buildFilters()
    {
        $filters = [];

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }

        return $filters;
    }

    private function validateProjectData($data)
    {
        $errors = [];

        // Required fields
        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = 'Project name is required.';
        }

        // Name length
        if (strlen($data['name'] ?? '') > 255) {
            $errors[] = 'Project name must be less than 255 characters.';
        }

        // Validate dates if provided
        if (!empty($data['start_date'])) {
            $startDate = date('Y-m-d', strtotime($data['start_date']));
            if ($startDate === '1970-01-01' || $startDate === false) {
                $errors[] = 'Invalid start date.';
            }
            $data['start_date'] = $startDate;
        }

        if (!empty($data['end_date'])) {
            $endDate = date('Y-m-d', strtotime($data['end_date']));
            if ($endDate === '1970-01-01' || $endDate === false) {
                $errors[] = 'Invalid end date.';
            }
            $data['end_date'] = $endDate;
        }

        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            return false;
        }

        return [
            'name' => sanitize($data['name']),
            'description' => sanitize($data['description'] ?? ''),
            'status' => $data['status'] ?? 'active',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null
        ];
    }
}

// Handle routing
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

$controller = new ProjectController();

switch ($action) {
    case 'index':
        $controller->index();
        break;

    case 'create':
        $controller->create();
        break;

    case 'store':
        $controller->store();
        break;

    case 'show':
        if ($id) {
            $controller->show($id);
        } else {
            redirect(BASE_URL . '/controller/ProjectController.php');
        }
        break;

    case 'edit':
        if ($id) {
            $controller->edit($id);
        } else {
            redirect(BASE_URL . '/controller/ProjectController.php');
        }
        break;

    case 'update':
        if ($id) {
            $controller->update($id);
        } else {
            redirect(BASE_URL . '/controller/ProjectController.php');
        }
        break;

    case 'delete':
        if ($id) {
            $controller->delete($id);
        } else {
            redirect(BASE_URL . '/controller/ProjectController.php');
        }
        break;

    case 'assign_user':
        if ($id) {
            $controller->assignUser($id);
        } else {
            redirect(BASE_URL . '/controller/ProjectController.php');
        }
        break;

    case 'remove_user':
        if ($id) {
            $controller->removeUser($id);
        } else {
            redirect(BASE_URL . '/controller/ProjectController.php');
        }
        break;

    default:
        $controller->index();
        break;
}
