<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Validator.php';

class Controller
{
    protected $currentUser;

    public function __construct()
    {
        Session::start();
        
        if (!Auth::check()) {
            $this->redirect(BASE_URL . "/views/auth/login.php");
        }

        $this->currentUser = Auth::user();
    }

    /**
     * Redirect to a given URL
     */
    protected function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    /**
     * Redirect with an error message
     */
    protected function errorRedirect($message, $url = null)
    {
        $_SESSION['error'] = $message;
        $url = $url ?? $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        $this->redirect($url);
    }

    /**
     * Redirect with a success message
     */
    protected function successRedirect($message, $url)
    {
        $_SESSION['success'] = $message;
        $this->redirect($url);
    }

    /**
     * Render a view part
     */
    protected function view($viewPath, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . "/../views/$viewPath.php";
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Handle common data validation
     */
    protected function validate($data, $rules)
    {
        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['form_data'] = $data;
            return false;
        }
        return $validator->getSanitizedData();
    }
    
    /**
     * Get POST data safely
     */
    protected function post($key = null, $default = null)
    {
        if ($key === null) return $_POST;
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data safely
     */
    protected function query($key = null, $default = null)
    {
        if ($key === null) return $_GET;
        return $_GET[$key] ?? $default;
    }
}
