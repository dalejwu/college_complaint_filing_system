<?php
include('../includes/db.php');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../Login/index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch Student Name
$student_query = $conn->query("SELECT name FROM students WHERE id = $student_id");
$student_name = ($student_query && $student_query->num_rows > 0) ? $student_query->fetch_assoc()['name'] : 'Student';

// Fetch Stats
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0
];

$queries = [
    'total' => "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND deleted_at IS NULL",
    'pending' => "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'Pending' AND deleted_at IS NULL",
    'in_progress' => "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'In Progress' AND deleted_at IS NULL",
    'resolved' => "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'Resolved' AND deleted_at IS NULL"
];

foreach ($queries as $key => $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stats[$key] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }

        /* Temporary overrides if needed, most styles are in CSS/style.css */
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Admin-Style Header -->
        <div class="dashboard-header">
            <div>
                <h2>Student Dashboard</h2>
                <p class="login-subtitle">Welcome back, <?php echo htmlspecialchars($student_name); ?>!</p>
            </div>
            
            <div class="admin-actions">
                <a href="../file_complaint.php" class="admin-btn" style="display: inline-flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    File Complaint
                </a>
                <a href="profile.php" class="admin-btn">Profile</a>
                <a href="actions/logout.php" class="admin-btn danger">Logout</a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="dashboard-stats" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 40px;">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity-section">
            <div class="section-title">
                <span>Recent Activity</span>
                <a href="view_complaints.php" class="view-all-link">
                    View All Complaints
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            <div class="recent-activity-grid">
                <?php
                $recent_stmt = $conn->prepare("SELECT * FROM complaints WHERE student_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 3");
                $recent_stmt->bind_param("i", $student_id);
                $recent_stmt->execute();
                $recent_result = $recent_stmt->get_result();

                if ($recent_result->num_rows > 0) {
                    while ($row = $recent_result->fetch_assoc()) {
                        $status_class = strtolower(str_replace(' ', '-', $row['status']));
                        $date = date('M d, Y', strtotime($row['created_at']));

                        echo "
                        <a href='view_complaints.php' class='activity-card'>
                            <div class='activity-header'>
                                <span class='status-badge status-{$status_class}' style='margin:0;'>{$row['status']}</span>
                                <span class='activity-date'>{$date}</span>
                            </div>
                            <div class='activity-title'>" . htmlspecialchars($row['title']) . "</div>
                            <div class='activity-desc'>" . htmlspecialchars(strip_tags($row['description'])) . "</div>
                            <div class='activity-footer'>
                                <span>ID: #{$row['id']}</span>
                                <span style='color: var(--primary-color); font-weight: 500;'>View Details &rarr;</span>
                            </div>
                        </a>";
                    }
                } else {
                    echo "
                    <div style='grid-column: 1/-1; text-align: center; padding: 40px; background: white; border-radius: 12px; border: 1px dashed #e5e7eb;'>
                        <p style='color: var(--text-secondary); margin-bottom: 15px;'>No complaints found.</p>
                        <a href='../file_complaint.php' class='btn-primary' style='display: inline-block;'>File Your First Complaint</a>
                    </div>";
                }
                $recent_stmt->close();
                ?>
            </div>
        </div>
    </div>
</body>

</html>