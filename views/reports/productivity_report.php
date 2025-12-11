<?php
/**
 * Productivity Report View
 * Displays productivity metrics over time
 */
?>

<div class="dashboard-container">
    <!-- Report Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title"><?php echo $title ?? 'Productivity Report'; ?></h1>
            <p class="dashboard-subtitle">
                Generated on <?php echo date('M d, Y H:i', strtotime($generated_at)); ?> 
                by <?php echo htmlspecialchars($generated_by); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=index" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Reports
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=productivity_report&format=csv<?php 
                if (!empty($filters['date_from'])) echo '&date_from=' . urlencode($filters['date_from']);
                if (!empty($filters['date_to'])) echo '&date_to=' . urlencode($filters['date_to']);
                if (!empty($filters['period'])) echo '&period=' . urlencode($filters['period']);
            ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Date Range</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/controller/ReportController.php" class="row g-3">
                <input type="hidden" name="action" value="productivity_report">
                
                <div class="col-md-4">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'))); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filters['date_to'] ?? date('Y-m-d')); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Productivity Data -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Productivity Metrics</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tasks Created</th>
                                <th>Tasks Completed</th>
                                <th>Completion Rate</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $previousRate = 0;
                            foreach ($data as $date => $metrics): 
                                $trend = '';
                                if ($previousRate > 0) {
                                    if ($metrics['completion_rate'] > $previousRate) {
                                        $trend = '<i class="bi bi-arrow-up text-success"></i>';
                                    } elseif ($metrics['completion_rate'] < $previousRate) {
                                        $trend = '<i class="bi bi-arrow-down text-danger"></i>';
                                    } else {
                                        $trend = '<i class="bi bi-dash text-muted"></i>';
                                    }
                                }
                                $previousRate = $metrics['completion_rate'];
                            ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($date)); ?></td>
                                    <td><?php echo $metrics['created'] ?? 0; ?></td>
                                    <td class="text-success">
                                        <strong><?php echo $metrics['completed'] ?? 0; ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $metrics['completion_rate']; ?>%">
                                                    <?php echo $metrics['completion_rate']; ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $trend; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6>Summary</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Total Tasks Created:</strong></p>
                            <p class="text-muted"><?php echo array_sum(array_column($data, 'created')); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Total Tasks Completed:</strong></p>
                            <p class="text-success"><?php echo array_sum(array_column($data, 'completed')); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Average Completion Rate:</strong></p>
                            <p class="text-primary">
                                <?php 
                                $avgRate = count($data) > 0 ? array_sum(array_column($data, 'completion_rate')) / count($data) : 0;
                                echo round($avgRate, 1); 
                                ?>%
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-3">No productivity data available for the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
