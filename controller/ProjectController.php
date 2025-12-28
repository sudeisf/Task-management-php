<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/ProjectService.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../services/AttachmentService.php';
require_once __DIR__ . '/../models/Attachment.php';

class ProjectController extends Controller
{
    private $projectService;
    private $projectModel;
    private $taskModel;
    private $userModel;
    private $permissionModel;
    private $attachmentService;

    public function __construct()
    {
        parent::__construct();
        $this->projectService = new ProjectService();
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
        $this->permissionModel = new Permission();
        $this->attachmentService = new AttachmentService();
    }

    public function index()
    {
        $filters = $this->buildFilters();
        $projects = $this->projectService->getProjectsByRole($this->currentUser['id'], $this->currentUser['role'], $filters);
        $this->view('projects/index', ['projects' => $projects, 'userRole' => $this->currentUser['role']]);
    }

    public function create()
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission");
        
        $usersResult = $this->userModel->getAll();
        $users = [];
        while ($u = $usersResult->fetch_assoc()) {
            $users[] = $u;
        }
        
        $this->view('projects/create', [
            'users' => $users,
            'formData' => $_SESSION['form_data'] ?? [],
            'errors' => $_SESSION['errors'] ?? []
        ]);
        unset($_SESSION['form_data'], $_SESSION['errors']);
    }

    public function store()
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission");
        
        $val = $this->projectService->validateProject($_POST);
        if (!$val['data']) {
            $_SESSION['errors'] = $val['errors'];
            $_SESSION['form_data'] = $_POST;
            $this->redirect("?action=create");
        }

        $data = $val['data'];
        $data['created_by'] = $this->currentUser['id'];
        
        if ($projectId = $this->projectModel->create($data)) {
            // Assign managers
            if (!empty($_POST['managers'])) {
                foreach ($_POST['managers'] as $mid) $this->projectModel->assignUser($projectId, $mid, 'manager');
            }

            // Handle file upload
            if (isset($_FILES['attachment']) && !empty($_FILES['attachment']['name'])) {
                $res = $this->attachmentService->handleProjectUpload($_FILES['attachment'], $projectId, $this->currentUser['id'], $this->currentUser['role']);
                if (isset($res['error'])) {
                    // Log error but don't fail project creation, just warn user
                    $this->setFlashMessage('warning', "Project created but file upload failed: " . $res['error']);
                } else {
                    (new Activity())->log($this->currentUser['id'], null, 'file_uploaded_to_project', "Uploaded file for project ID: $projectId");
                }
            }

            $this->successRedirect("Project created", "?action=show&id=$projectId");
        }
        $this->errorRedirect("Failed to create", "?action=create");
    }

    public function show($id)
    {
        $project = $this->projectModel->find($id);
        if (!$project) $this->errorRedirect("Not found");
        if (!$this->projectModel->hasAccess($id, $this->currentUser['id'], $this->currentUser['role'])) $this->errorRedirect("No access");

        $viewData = [
            'project' => $project,
            'teamMembers' => $this->projectModel->getTeamMembers($id),
            'statistics' => $this->projectModel->getStatistics($id),
            'tasks' => $this->taskModel->getByProject($id),
            'attachments' => (new Attachment())->getByProject($id),
            'userRole' => $this->currentUser['role']
        ];

        if ($this->currentUser['role'] === 'admin') {
            $usersResult = $this->userModel->getAll();
            $users = [];
            while ($u = $usersResult->fetch_assoc()) {
                $users[] = $u;
            }
            $viewData['users'] = $users;
        }

        $this->view('projects/show', $viewData);
    }

    public function edit($id)
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission");
        $project = $this->projectModel->find($id);
        if (!$project) $this->errorRedirect("Not found");

        $usersResult = $this->userModel->getAll();
        $users = [];
        while ($u = $usersResult->fetch_assoc()) {
            $users[] = $u;
        }

        $this->view('projects/edit', [
            'project' => $project,
            'users' => $users,
            'currentManagers' => $this->projectModel->getManagers($id)
        ]);
    }

    public function update($id)
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission");
        $project = $this->projectModel->find($id);
        if (!$project) $this->errorRedirect("Not found");

        $val = $this->projectService->validateProject($_POST);
        if (!$val['data']) {
            $_SESSION['errors'] = $val['errors'];
            $this->errorRedirect("Please fix the errors below.", "?action=edit&id=$id");
        }

        if ($this->projectModel->update($id, $val['data'])) {
            $currentManagers = $this->projectModel->getManagers($id);
            foreach ($currentManagers as $m) $this->projectModel->removeUser($id, $m['id']);
            if (!empty($_POST['managers'])) {
                foreach ($_POST['managers'] as $mid) $this->projectModel->assignUser($id, $mid, 'manager');
            }

            // Handle file upload
            if (isset($_FILES['attachment']) && !empty($_FILES['attachment']['name'])) {
                $res = $this->attachmentService->handleProjectUpload($_FILES['attachment'], $id, $this->currentUser['id'], $this->currentUser['role']);
                if (isset($res['error'])) {
                    $this->setFlashMessage('warning', "Project updated but file upload failed: " . $res['error']);
                } else {
                    (new Activity())->log($this->currentUser['id'], null, 'file_uploaded_to_project', "Uploaded file for project ID: $id");
                }
            }

            $this->successRedirect("Updated", "?action=show&id=$id");
        }
        $this->errorRedirect("Failed", "?action=edit&id=$id");
    }

    public function delete($id)
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission");
        if ($this->projectModel->delete($id)) $this->successRedirect("Deleted", "?action=index");
        $this->errorRedirect("Failed");
    }

    public function assignUser($projectId)
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission", "?action=show&id=$projectId");
        $userId = $this->post('user_id');
        if (!$userId) $this->errorRedirect("User ID required", "?action=show&id=$projectId");
        
        // Enforce: Project role must match Global role
        $user = $this->userModel->getById($userId);
        if (!$user) $this->errorRedirect("User not found", "?action=show&id=$projectId");
        
        $globalRole = strtolower($user['role']);
        $projectRole = ($globalRole === 'admin' || $globalRole === 'manager') ? 'manager' : 'member';
        
        if ($this->projectModel->assignUser($projectId, $userId, $projectRole)) {
            $this->successRedirect("User assigned as " . ucfirst($projectRole), "?action=show&id=$projectId");
        }
        $this->errorRedirect("Failed to assign user", "?action=show&id=$projectId");
    }

    public function removeUser($projectId)
    {
        if (!$this->permissionModel->canManageProjects($this->currentUser['id'])) $this->errorRedirect("No permission", "?action=show&id=$projectId");
        $userId = $this->post('user_id');
        if (!$userId) $this->errorRedirect("User ID required", "?action=show&id=$projectId");

        if ($this->projectModel->removeUser($projectId, $userId)) $this->successRedirect("User removed", "?action=show&id=$projectId");
        $this->errorRedirect("Failed", "?action=show&id=$projectId");
    }

    private function buildFilters()
    {
        $filters = [];
        if ($status = $this->query('status')) {
            $filters['status'] = ($status === 'planning' || $status === 'todo') ? ['planning', 'active'] : $status;
        }
        if ($search = $this->query('search')) $filters['search'] = trim($search);
        return $filters;
    }
}

$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;
$controller = new ProjectController();

$methodName = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $action))));

if (method_exists($controller, $methodName)) {
    $controller->$methodName($id);
} else {
    $controller->index();
}
