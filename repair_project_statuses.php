<?php
// repair_project_statuses.php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Project.php';

$projectModel = new Project();
$db = new Database();

// Get all project IDs
$sql = "SELECT id, name FROM projects";
$db->prepare($sql);
$db->execute([]);
$projects = $db->getRows();

echo "Found " . count($projects) . " projects to check.\n";

foreach ($projects as $project) {
    echo "Updating project: " . $project['name'] . " (ID: " . $project['id'] . ")... ";
    if ($projectModel->updateProjectStatus($project['id'])) {
        echo "Done.\n";
    } else {
        echo "Failed.\n";
    }
}

echo "All projects updated.\n";
