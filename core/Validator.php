<?php

class Validator
{
    private $errors = [];
    private $data = [];

    /**
     * Validate input data against rules
     */
    public function validate($data, $rules)
    {
        $this->errors = [];
        $this->data = $data;

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRulesArray = explode('|', $fieldRules);

            foreach ($fieldRulesArray as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single rule
     */
    private function validateRule($field, $value, $rule)
    {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParam = $ruleParts[1] ?? null;

        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $ruleParam)) {
                $this->addError($field, $ruleName, $ruleParam);
            }
        }
    }

    /**
     * Required validation
     */
    private function validateRequired($field, $value)
    {
        if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
            return false;
        }
        return true;
    }

    /**
     * Email validation
     */
    private function validateEmail($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Minimum length validation
     */
    private function validateMin($field, $value, $param)
    {
        if (is_string($value)) {
            return strlen($value) >= (int)$param;
        } elseif (is_numeric($value)) {
            return $value >= (int)$param;
        }
        return false;
    }

    /**
     * Maximum length validation
     */
    private function validateMax($field, $value, $param)
    {
        if (is_string($value)) {
            return strlen($value) <= (int)$param;
        } elseif (is_numeric($value)) {
            return $value <= (int)$param;
        }
        return false;
    }

    /**
     * Exact length validation
     */
    private function validateLength($field, $value, $param)
    {
        return is_string($value) && strlen($value) === (int)$param;
    }

    /**
     * Numeric validation
     */
    private function validateNumeric($field, $value)
    {
        return is_numeric($value);
    }

    /**
     * Integer validation
     */
    private function validateInteger($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Alpha validation (letters only)
     */
    private function validateAlpha($field, $value)
    {
        return is_string($value) && ctype_alpha($value);
    }

    /**
     * Alpha numeric validation
     */
    private function validateAlphaNum($field, $value)
    {
        return is_string($value) && ctype_alnum($value);
    }

    /**
     * Alpha numeric with dashes and underscores
     */
    private function validateAlphaDash($field, $value)
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9_-]+$/', $value);
    }

    /**
     * URL validation
     */
    private function validateUrl($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Date validation
     */
    private function validateDate($field, $value)
    {
        $date = date_parse($value);
        return $date['error_count'] === 0 && $date['warning_count'] === 0;
    }

    /**
     * Date format validation
     */
    private function validateDateFormat($field, $value, $param)
    {
        $date = DateTime::createFromFormat($param, $value);
        return $date !== false;
    }

    /**
     * Before date validation
     */
    private function validateBefore($field, $value, $param)
    {
        $date1 = strtotime($value);
        $date2 = strtotime($param);
        return $date1 < $date2;
    }

    /**
     * After date validation
     */
    private function validateAfter($field, $value, $param)
    {
        $date1 = strtotime($value);
        $date2 = strtotime($param);
        return $date1 > $date2;
    }

    /**
     * In array validation
     */
    private function validateIn($field, $value, $param)
    {
        $options = explode(',', $param);
        return in_array($value, $options);
    }

    /**
     * Not in array validation
     */
    private function validateNotIn($field, $value, $param)
    {
        $options = explode(',', $param);
        return !in_array($value, $options);
    }

    /**
     * Regular expression validation
     */
    private function validateRegex($field, $value, $param)
    {
        return preg_match($param, $value);
    }

    /**
     * Password strength validation
     */
    private function validatePassword($field, $value)
    {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
        return strlen($value) >= 8 &&
               preg_match('/[A-Z]/', $value) &&
               preg_match('/[a-z]/', $value) &&
               preg_match('/[0-9]/', $value) &&
               preg_match('/[^a-zA-Z0-9]/', $value);
    }

    /**
     * Confirmed validation (for password confirmation)
     */
    private function validateConfirmed($field, $value)
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }

    /**
     * Unique validation (check if value exists in database)
     */
    private function validateUnique($field, $value, $param)
    {
        // param format: table,column or table,column,ignore_id
        $parts = explode(',', $param);
        $table = $parts[0];
        $column = $parts[1];
        $ignoreId = $parts[2] ?? null;

        require_once __DIR__ . '/../core/Database.php';
        $db = new Database();

        $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $params = [$value];

        if ($ignoreId) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        $db->prepare($sql);
        $db->execute($params);
        $result = $db->getRow();

        return ($result['count'] ?? 0) === 0;
    }

    /**
     * Exists validation (check if value exists in database)
     */
    private function validateExists($field, $value, $param)
    {
        // param format: table,column
        $parts = explode(',', $param);
        $table = $parts[0];
        $column = $parts[1];

        require_once __DIR__ . '/../core/Database.php';
        $db = new Database();

        $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $db->prepare($sql);
        $db->execute([$value]);
        $result = $db->getRow();

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Add error message
     */
    private function addError($field, $rule, $param = null)
    {
        $message = $this->getErrorMessage($field, $rule, $param);
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get error message for rule
     */
    private function getErrorMessage($field, $rule, $param = null)
    {
        $messages = [
            'required' => 'The :field field is required.',
            'email' => 'The :field field must be a valid email address.',
            'min' => 'The :field field must be at least :param characters.',
            'max' => 'The :field field may not be greater than :param characters.',
            'length' => 'The :field field must be exactly :param characters.',
            'numeric' => 'The :field field must be a number.',
            'integer' => 'The :field field must be an integer.',
            'alpha' => 'The :field field may only contain letters.',
            'alpha_num' => 'The :field field may only contain letters and numbers.',
            'alpha_dash' => 'The :field field may only contain letters, numbers, dashes and underscores.',
            'url' => 'The :field field must be a valid URL.',
            'date' => 'The :field field is not a valid date.',
            'date_format' => 'The :field field does not match the format :param.',
            'before' => 'The :field field must be a date before :param.',
            'after' => 'The :field field must be a date after :param.',
            'in' => 'The selected :field is invalid.',
            'not_in' => 'The selected :field is invalid.',
            'regex' => 'The :field field format is invalid.',
            'password' => 'The :field field must contain at least 8 characters with uppercase, lowercase, number and special character.',
            'confirmed' => 'The :field field confirmation does not match.',
            'unique' => 'The :field has already been taken.',
            'exists' => 'The selected :field is invalid.'
        ];

        $message = $messages[$rule] ?? 'The :field field is invalid.';

        return str_replace([':field', ':param'], [$field, $param], $message);
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get errors for specific field
     */
    public function getErrorsFor($field)
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Get first error for specific field
     */
    public function getFirstError($field)
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if field has errors
     */
    public function hasErrors($field = null)
    {
        if ($field) {
            return isset($this->errors[$field]);
        }
        return !empty($this->errors);
    }

    /**
     * Get sanitized data
     */
    public function getSanitizedData()
    {
        return $this->sanitizeData($this->data);
    }

    /**
     * Sanitize data
     */
    private function sanitizeData($data)
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize single value
     */
    private function sanitizeValue($value)
    {
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    /**
     * Validate file upload
     */
    public function validateFile($file, $rules)
    {
        $errors = [];

        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
            return $errors;
        }

        foreach ($rules as $rule) {
            $ruleParts = explode(':', $rule);
            $ruleName = $ruleParts[0];
            $ruleParam = $ruleParts[1] ?? null;

            switch ($ruleName) {
                case 'max_size':
                    $maxSize = (int)$ruleParam * 1024 * 1024; // Convert MB to bytes
                    if ($file['size'] > $maxSize) {
                        $errors[] = "File size must be less than {$ruleParam}MB.";
                    }
                    break;

                case 'mimes':
                    $allowedTypes = explode(',', $ruleParam);
                    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($fileType, $allowedTypes)) {
                        $errors[] = "File type must be: " . implode(', ', $allowedTypes);
                    }
                    break;

                case 'image':
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($fileType, $allowedTypes)) {
                        $errors[] = "File must be an image.";
                    }
                    break;
            }
        }

        return $errors;
    }
}
