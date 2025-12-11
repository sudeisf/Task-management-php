<?php
/**
 * Reports Index View
 * Main reports page with links to different report types
 */
?>

<div class="dashboard-container">
    <!-- Reports Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">Reports & Analytics</h1>
            <p class="dashboard-subtitle">Generate and view various reports to track performance and productivity.</p>
        </div>
    </div>

    <!-- Report Types Grid -->
    <div class="row g-4">
        <!-- Task Report Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 report-card">
                <div class="card-body">
                    <div class="report-icon mb-3">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <h5 class="card-title">Task Report</h5>
                    <p class="card-text text-muted">
                        Generate comprehensive reports on tasks with customizable filters for status, priority, category, and date range.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=task_report" class="btn btn-primary">
                        <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                    </a>
                </div>
            </div>
        </div>

        <!-- User Activity Report Card -->
        <?php if (hasPermission($userRole, 'manage_users')): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 report-card">
                <div class="card-body">
                    <div class="report-icon mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="card-title">User Activity Report</h5>
                    <p class="card-text text-muted">
                        View detailed user activity including tasks created, completed, comments, and file uploads.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=user_report" class="btn btn-primary">
                        <i class="bi bi-file-earmark-person me-2"></i>Generate Report
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Productivity Report Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 report-card">
                <div class="card-body">
                    <div class="report-icon mb-3">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h5 class="card-title">Productivity Report</h5>
                    <p class="card-text text-muted">
                        Track productivity trends over time with metrics on task creation, completion rates, and daily averages.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=productivity_report" class="btn btn-primary">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Overdue Tasks Report Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 report-card">
                <div class="card-body">
                    <div class="report-icon mb-3 text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h5 class="card-title">Overdue Tasks Report</h5>
                    <p class="card-text text-muted">
                        View all overdue tasks with details on how many days they're overdue and who they're assigned to.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=overdue_report" class="btn btn-danger">
                        <i class="bi bi-file-earmark-x me-2"></i>Generate Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>About Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Export Options</h6>
                            <p class="text-muted small">
                                All reports can be exported to CSV format for further analysis in spreadsheet applications.
                                Simply click the "Export CSV" button on any report page.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Filtering</h6>
                            <p class="text-muted small">
                                Use the filter options on each report page to customize the data displayed.
                                Filters include date ranges, status, priority, categories, and user assignments.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.report-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #000;
}

.report-icon.text-danger {
    background-color: #ffe6e6;
}
</style>
