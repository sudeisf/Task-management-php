<?php
/**
 * Alert Component for Flash Messages
 * Displays success, error, warning, and info messages
 */

require_once __DIR__ . '/../../helpers/functions.php';

$flashMessage = getFlashMessage();
if ($flashMessage):
    $alertClass = 'alert-' . ($flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']);
?>
<div class="container-fluid mt-3">
    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
        <i class="bi <?php echo getAlertIcon($flashMessage['type']); ?> me-2"></i>
        <?php echo htmlspecialchars($flashMessage['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<?php
/**
 * Get appropriate icon for alert type
 */
function getAlertIcon($type) {
    switch ($type) {
        case 'success':
            return 'bi-check-circle-fill';
        case 'error':
        case 'danger':
            return 'bi-exclamation-triangle-fill';
        case 'warning':
            return 'bi-exclamation-circle-fill';
        case 'info':
        default:
            return 'bi-info-circle-fill';
    }
}
?>