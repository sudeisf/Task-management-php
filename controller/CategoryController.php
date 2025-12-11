<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../helpers/functions.php';

class CategoryController
{
    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    public function create()
    {
        // Check if logged in
        if (!Auth::check()) {
            redirect(BASE_URL . '/views/auth/login.php');
        }

        $view = __DIR__ . '/../views/categories/create.php';
        if (file_exists($view)) {
            require_once $view;
        } else {
            die("View does not exist: " . $view);
        }
    }

    public function store()
    {
        // Check if logged in
        if (!Auth::check()) {
            redirect(BASE_URL . '/views/auth/login.php');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize POST data
            $_POST = sanitize($_POST);

            $data = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description']),
                'user_id' => Session::get('user_id')
            ];

            // Validate name
            if (empty($data['name'])) {
                setFlashMessage('Category name is required', 'error');
                redirect(BASE_URL . '/controller/CategoryController.php?action=create');
                return;
            }

            // Create category
            if ($this->categoryModel->create($data)) {
                setFlashMessage('Category created successfully', 'success');
                redirect(BASE_URL . '/controller/TaskController.php?action=create');
            } else {
                setFlashMessage('Something went wrong', 'error');
                redirect(BASE_URL . '/controller/CategoryController.php?action=create');
            }
        } else {
            redirect(BASE_URL . '/controller/CategoryController.php?action=create');
        }
    }
}

// Router for CategoryController
if (isset($_GET['action'])) {
    $controller = new CategoryController();
    $action = $_GET['action'];

    switch ($action) {
        case 'create':
            $controller->create();
            break;
        case 'store':
            $controller->store();
            break;
        default:
            // redirect('index.php');
            break;
    }
}
