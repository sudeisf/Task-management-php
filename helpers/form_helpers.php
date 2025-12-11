<?php

/**
 * Form Helper Functions
 * Utility functions for generating and handling forms
 */

/**
 * Generate CSRF token hidden input
 */
function csrf_field()
{
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Generate form opening tag
 */
function form_open($action = '', $method = 'post', $attributes = [])
{
    $html = '<form';

    if (!empty($action)) {
        $html .= ' action="' . htmlspecialchars($action) . '"';
    }

    $html .= ' method="' . htmlspecialchars(strtoupper($method)) . '"';

    // Add enctype for file uploads if method is post
    if (strtolower($method) === 'post' && (!isset($attributes['enctype']) || $attributes['enctype'] !== false)) {
        $html .= ' enctype="multipart/form-data"';
    }

    // Add other attributes
    foreach ($attributes as $key => $value) {
        if ($key !== 'enctype' && $value !== false) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }

    $html .= '>';

    // Add CSRF token for POST forms
    if (strtolower($method) === 'post') {
        $html .= csrf_field();
    }

    return $html;
}

/**
 * Generate form closing tag
 */
function form_close()
{
    return '</form>';
}

/**
 * Generate form input field
 */
function form_input($name, $value = '', $attributes = [])
{
    $defaults = [
        'type' => 'text',
        'name' => $name,
        'value' => $value,
        'class' => 'form-control'
    ];

    $attributes = array_merge($defaults, $attributes);

    // Add error class if field has errors
    if (isset($_SESSION['form_errors'][$name])) {
        $attributes['class'] .= ' is-invalid';
    }

    $html = '<input';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>';

    return $html;
}

/**
 * Generate form password field
 */
function form_password($name, $attributes = [])
{
    $attributes['type'] = 'password';
    return form_input($name, '', $attributes);
}

/**
 * Generate form email field
 */
function form_email($name, $value = '', $attributes = [])
{
    $attributes['type'] = 'email';
    return form_input($name, $value, $attributes);
}

/**
 * Generate form textarea
 */
function form_textarea($name, $value = '', $attributes = [])
{
    $defaults = [
        'name' => $name,
        'rows' => 3,
        'cols' => 40,
        'class' => 'form-control'
    ];

    $attributes = array_merge($defaults, $attributes);

    // Add error class if field has errors
    if (isset($_SESSION['form_errors'][$name])) {
        $attributes['class'] .= ' is-invalid';
    }

    $html = '<textarea';

    foreach ($attributes as $key => $val) {
        if ($key !== 'value') {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
    }

    $html .= '>' . htmlspecialchars($value) . '</textarea>';

    return $html;
}

/**
 * Generate form select dropdown
 */
function form_select($name, $options = [], $selected = '', $attributes = [])
{
    $defaults = [
        'name' => $name,
        'class' => 'form-select'
    ];

    $attributes = array_merge($defaults, $attributes);

    // Add error class if field has errors
    if (isset($_SESSION['form_errors'][$name])) {
        $attributes['class'] .= ' is-invalid';
    }

    $html = '<select';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>';

    foreach ($options as $value => $label) {
        $isSelected = ($value == $selected) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '"' . $isSelected . '>';
        $html .= htmlspecialchars($label);
        $html .= '</option>';
    }

    $html .= '</select>';

    return $html;
}

/**
 * Generate form checkbox
 */
function form_checkbox($name, $value = '1', $checked = false, $attributes = [])
{
    $defaults = [
        'type' => 'checkbox',
        'name' => $name,
        'value' => $value,
        'class' => 'form-check-input'
    ];

    $attributes = array_merge($defaults, $attributes);

    if ($checked) {
        $attributes['checked'] = 'checked';
    }

    $html = '<input';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>';

    return $html;
}

/**
 * Generate form radio button
 */
function form_radio($name, $value = '', $checked = false, $attributes = [])
{
    $defaults = [
        'type' => 'radio',
        'name' => $name,
        'value' => $value,
        'class' => 'form-check-input'
    ];

    $attributes = array_merge($defaults, $attributes);

    if ($checked) {
        $attributes['checked'] = 'checked';
    }

    $html = '<input';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>';

    return $html;
}

/**
 * Generate form file input
 */
function form_file($name, $attributes = [])
{
    $defaults = [
        'type' => 'file',
        'name' => $name,
        'class' => 'form-control'
    ];

    $attributes = array_merge($defaults, $attributes);

    // Add error class if field has errors
    if (isset($_SESSION['form_errors'][$name])) {
        $attributes['class'] .= ' is-invalid';
    }

    $html = '<input';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>';

    return $html;
}

/**
 * Generate form label
 */
function form_label($text, $for = '', $attributes = [])
{
    $defaults = [
        'class' => 'form-label'
    ];

    $attributes = array_merge($defaults, $attributes);

    $html = '<label';

    if (!empty($for)) {
        $html .= ' for="' . htmlspecialchars($for) . '"';
    }

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>' . htmlspecialchars($text) . '</label>';

    return $html;
}

/**
 * Generate form submit button
 */
function form_submit($text = 'Submit', $attributes = [])
{
    $defaults = [
        'type' => 'submit',
        'class' => 'btn btn-primary'
    ];

    $attributes = array_merge($defaults, $attributes);

    $html = '<button';

    foreach ($attributes as $key => $val) {
        if ($key !== 'value') {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
    }

    $html .= '>' . htmlspecialchars($text) . '</button>';

    return $html;
}

/**
 * Generate form button
 */
function form_button($text = 'Button', $attributes = [])
{
    $defaults = [
        'type' => 'button',
        'class' => 'btn btn-secondary'
    ];

    $attributes = array_merge($defaults, $attributes);

    $html = '<button';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>' . htmlspecialchars($text) . '</button>';

    return $html;
}

/**
 * Generate form validation error message
 */
function form_error($field)
{
    if (isset($_SESSION['form_errors'][$field])) {
        $error = $_SESSION['form_errors'][$field];
        return '<div class="invalid-feedback">' . htmlspecialchars($error) . '</div>';
    }

    return '';
}

/**
 * Generate form help text
 */
function form_help($text)
{
    return '<div class="form-text">' . htmlspecialchars($text) . '</div>';
}

/**
 * Generate form group wrapper
 */
function form_group($content, $label = '', $fieldName = '', $help = '')
{
    $html = '<div class="mb-3">';

    if (!empty($label)) {
        $html .= form_label($label, $fieldName) . "\n";
    }

    $html .= $content . "\n";

    if (!empty($fieldName)) {
        $html .= form_error($fieldName) . "\n";
    }

    if (!empty($help)) {
        $html .= form_help($help) . "\n";
    }

    $html .= '</div>';

    return $html;
}

/**
 * Generate Bootstrap form check wrapper (for checkboxes and radios)
 */
function form_check($input, $label = '', $attributes = [])
{
    $defaults = [
        'class' => 'form-check'
    ];

    $attributes = array_merge($defaults, $attributes);

    $html = '<div';

    foreach ($attributes as $key => $val) {
        $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }

    $html .= '>' . "\n";
    $html .= $input . "\n";

    if (!empty($label)) {
        $html .= '<label class="form-check-label">' . htmlspecialchars($label) . '</label>' . "\n";
    }

    $html .= '</div>';

    return $html;
}

/**
 * Set form validation errors
 */
function set_form_errors($errors)
{
    $_SESSION['form_errors'] = $errors;
}

/**
 * Clear form validation errors
 */
function clear_form_errors()
{
    unset($_SESSION['form_errors']);
}

/**
 * Check if form has errors
 */
function has_form_errors()
{
    return isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors']);
}

/**
 * Get all form errors
 */
function get_form_errors()
{
    return $_SESSION['form_errors'] ?? [];
}

/**
 * Validate CSRF token
 */
function validate_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';

        if (!verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}

/**
 * Generate priority select options
 */
function priority_options($selected = '')
{
    return [
        '' => 'Select Priority',
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High'
    ];
}

/**
 * Generate status select options
 */
function status_options($selected = '')
{
    return [
        '' => 'Select Status',
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'completed' => 'Completed'
    ];
}

/**
 * Generate role select options
 */
function role_options($selected = '')
{
    return [
        'member' => 'Member',
        'manager' => 'Manager',
        'admin' => 'Administrator'
    ];
}

/**
 * Generate user select options
 */
function user_options($selected = '', $includeEmpty = true)
{
    require_once __DIR__ . '/../models/User.php';
    $userModel = new User(require_once __DIR__ . '/../config/db.php');

    $options = [];
    if ($includeEmpty) {
        $options[''] = 'Select User';
    }

    $users = $userModel->getAll();
    while ($user = $users->fetch_assoc()) {
        $options[$user['id']] = $user['full_name'];
    }

    return $options;
}

/**
 * Generate category select options
 */
function category_options($selected = '', $includeEmpty = true)
{
    require_once __DIR__ . '/../models/Category.php';
    $categoryModel = new Category();

    $options = [];
    if ($includeEmpty) {
        $options[''] = 'Select Category';
    }

    $user_id = $_SESSION['user_id'] ?? null;
    $categories = $categoryModel->all($user_id);
    
    // Category::all() now returns array of rows (fetch_all), so we iterate over the array
    foreach ($categories as $category) {
        $options[$category['id']] = $category['name'];
    }

    return $options;
}
