<?php
include('../includes/db.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if columns exist, if not show error message
$columns_check = $conn->query("SHOW COLUMNS FROM complaints LIKE 'attachments'");
$columns_exist = ($columns_check->num_rows > 0);

if (!$columns_exist) {
    $_SESSION['error'] = "Database needs migration! <a href='actions/run_migration.php' style='color: white; font-weight: bold; text-decoration: underline;'>Click here to run migration automatically</a> or run the SQL manually in phpMyAdmin.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/style.css?v=<?php echo time(); ?>">

</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2>Admin Dashboard</h2>
            <p class="login-subtitle">Welcome back, <?php echo $_SESSION['admin_user']; ?>!</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message success"><?php echo $_SESSION['message'];
                                                unset($_SESSION['message']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="admin-actions">
                <a href="#complaints" class="admin-btn" onclick="showSection('complaints')">Manage Complaints</a>
                <a href="#students" class="admin-btn" onclick="showSection('students')">Manage Students</a>
                <a href="#reports" class="admin-btn" onclick="showSection('reports')">Reports</a>
                <a href="#trash" class="admin-btn" onclick="showSection('trash')">Trash</a>
                <a href="actions/logout.php" class="admin-btn danger">Logout</a>
            </div>
        </div>

        <div class="dashboard-stats" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
            <div id="card-total" class="stat-card primary" onclick="showAllComplaints()">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['total'] : '0';
                                            ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
            </div>

            <div id="card-pending" class="stat-card warning" onclick="filterComplaintsByStatus('pending')">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as pending FROM complaints WHERE status='Pending' AND deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['pending'] : '0';
                                            ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>

            <div id="card-approved" class="stat-card success" onclick="filterComplaintsByStatus('approved')">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as approved FROM complaints WHERE status='Approved' AND deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['approved'] : '0';
                                            ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>

            <div id="card-denied" class="stat-card danger" onclick="filterComplaintsByStatus('denied')">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as denied FROM complaints WHERE status='Denied' AND deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['denied'] : '0';
                                            ?></div>
                    <div class="stat-label">Denied</div>
                </div>
            </div>

            <div id="card-in-progress" class="stat-card info" onclick="filterComplaintsByStatus('in-progress')">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as in_progress FROM complaints WHERE status='In Progress' AND deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['in_progress'] : '0';
                                            ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>

            <div id="card-resolved" class="stat-card purple" onclick="filterComplaintsByStatus('resolved')">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as resolved FROM complaints WHERE status='Resolved' AND deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['resolved'] : '0';
                                            ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>

            <div id="card-students" class="stat-card indigo" onclick="showSection('students')">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php
                                            $result = $conn->query("SELECT COUNT(*) as students FROM students WHERE deleted_at IS NULL");
                                            echo $result ? $result->fetch_assoc()['students'] : '0';
                                            ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
        </div>

        <!-- KPI Section -->
        <div class="kpi-section" style="max-width: 1200px; margin: 0 auto 32px auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
            <!-- Resolution Rate -->
            <div class="kpi-card" style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; color: var(--primary-color); font-size: 18px;">Resolution Rate</h3>
                <?php
                $total_query = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE deleted_at IS NULL");
                $total = $total_query->fetch_assoc()['total'];

                $resolved_query = $conn->query("SELECT COUNT(*) as resolved FROM complaints WHERE status='Resolved' AND deleted_at IS NULL");
                $resolved = $resolved_query->fetch_assoc()['resolved'];

                $rate = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;
                ?>
                <div style="font-size: 36px; font-weight: 700; color: var(--success-color); margin: 10px 0;"><?php echo $rate; ?>%</div>
                <div style="width: 100%; background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?php echo $rate; ?>%; background: var(--success-color); height: 100%;"></div>
                </div>
                <p style="margin: 10px 0 0; font-size: 13px; color: var(--text-secondary);">of all complaints resolved</p>
            </div>

            <!-- Avg Resolution Time -->
            <div class="kpi-card" style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; color: var(--primary-color); font-size: 18px;">Avg. Resolution Time</h3>
                <?php
                // Calculate average time in days for resolved complaints
                $time_query = $conn->query("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days FROM complaints WHERE status='Resolved' AND deleted_at IS NULL");
                $avg_days = $time_query->fetch_assoc()['avg_days'];
                $avg_days = $avg_days ? round($avg_days, 1) : 0;
                ?>
                <div style="font-size: 36px; font-weight: 700; color: var(--info-color); margin: 10px 0;"><?php echo $avg_days; ?> <span style="font-size: 16px; color: var(--text-secondary); font-weight: 500;">days</span></div>
                <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">from submission to resolution</p>
            </div>

            <!-- Top Categories -->
            <div class="kpi-card" style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; color: var(--primary-color); font-size: 18px;">Top Categories</h3>
                <ul style="list-style: none; padding: 0; margin: 15px 0 0;">
                    <?php
                    $cat_query = $conn->query("SELECT category, COUNT(*) as count FROM complaints WHERE deleted_at IS NULL GROUP BY category ORDER BY count DESC LIMIT 3");
                    if ($cat_query->num_rows > 0) {
                        while ($cat = $cat_query->fetch_assoc()) {
                            $pct = $total > 0 ? round(($cat['count'] / $total) * 100) : 0;
                            echo "<li style='margin-bottom: 12px;'>
                                <div style='display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 14px; font-weight: 500;'>
                                    <span>{$cat['category']}</span>
                                    <span>{$cat['count']}</span>
                                </div>
                                <div style='width: 100%; background: #e9ecef; height: 6px; border-radius: 3px; overflow: hidden;'>
                                    <div style='width: {$pct}%; background: var(--primary-color); height: 100%; opacity: 0.8;'></div>
                                </div>
                            </li>";
                        }
                    } else {
                        echo "<li style='color: var(--text-secondary); font-size: 14px;'>No data available</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- COMPLAINTS SECTION -->
        <div id="complaints-section" class="dashboard-section">
            <div class="section-header">
                <h3>Complaint Management</h3>
                <div class="section-actions">
                    <button class="btn-primary" onclick="showAddComplaintModal()">Add Complaint</button>
                </div>
            </div>

            <?php
            // Determine selected status early so the heading can use it
            if (!isset($statusFilter)) {
                $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'Approved';
                $allowedStatusesHeading = ['all', 'Approved', 'Denied', 'Pending', 'In Progress', 'Resolved'];
                if (!in_array($statusFilter, $allowedStatusesHeading)) {
                    $statusFilter = 'Approved';
                }
            }
            $headingStatus = $statusFilter === 'all' ? 'All' : $statusFilter;
            echo "<h3 style=\"color: var(--text-primary); margin-bottom: 20px; font-size: 24px;\">{$headingStatus} Complaints</h3>";
            ?>
            <div class="dashboard-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        // Ensure status ENUM includes Denied and Approved before querying
                        $col = $conn->query("SHOW COLUMNS FROM complaints LIKE 'status'");
                        if ($col && $row = $col->fetch_assoc()) {
                            $enumType = $row['Type'];
                            if (strpos($enumType, "'Denied'") === false || strpos($enumType, "'Approved'") === false) {
                                $conn->query("ALTER TABLE `complaints` MODIFY `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT 'Pending'");
                            }
                        }

                        // Server-side status filter
                        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'Approved';
                        $allowedStatuses = ['all', 'Approved', 'Denied', 'Pending', 'In Progress', 'Resolved'];
                        if (!in_array($statusFilter, $allowedStatuses)) {
                            $statusFilter = 'Approved';
                        }

                        if ($statusFilter === 'all') {
                            $result = $conn->query("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.deleted_at IS NULL ORDER BY c.created_at DESC");
                        } else {
                            $stmtComplaints = $conn->prepare("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.status = ? AND c.deleted_at IS NULL ORDER BY c.created_at DESC");
                            $stmtComplaints->bind_param("s", $statusFilter);
                            $stmtComplaints->execute();
                            $result = $stmtComplaints->get_result();
                        }

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_class = strtolower(str_replace(' ', '-', $row['status']));
                                $description_short = strlen($row['description']) > 50 ? substr($row['description'], 0, 50) . '...' : $row['description'];

                                // Parse category - check if it has custom category
                                $category = !empty($row['category']) ? $row['category'] : 'Other';
                                $display_category = $category;
                                $custom_category = '';

                                // Handle "Others: [custom]" format - extract custom specification
                                if (!empty($row['category']) && strpos($row['category'], 'Others: ') === 0) {
                                    $display_category = 'Other';
                                    $custom_category = substr($row['category'], 8);
                                }

                                $category_badge = "<span class='category-badge'>{$display_category}</span>";
                                if ($custom_category) {
                                    $category_badge .= "<br><small style='font-size: 11px; color: var(--text-muted); font-style: italic;'>($custom_category)</small>";
                                }

                                echo "<tr>
            <td>{$row['id']}</td>
            <td>" . ($row['student_name'] ? $row['student_name'] : 'Unknown') . "</td>
            <td>{$row['title']}</td>
            <td>{$category_badge}</td>
            <td title='{$row['description']}'>{$description_short}</td>
            <td><span class='status-badge status-$status_class'>{$row['status']}</span></td>
            <td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>
            <td>
                <div class='action-buttons'>
                    <form class='action-form' method='POST' action='actions/update_status.php' style='margin-right:8px;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <select name='status' onchange='this.form.submit()'>
                            <option value='Pending'" . ($row['status'] == 'Pending' ? ' selected' : '') . ">Pending</option>
                            <option value='Approved'" . ($row['status'] == 'Approved' ? ' selected' : '') . ">Approved</option>
                            <option value='Denied'" . ($row['status'] == 'Denied' ? ' selected' : '') . ">Denied</option>
                            <option value='In Progress'" . ($row['status'] == 'In Progress' ? ' selected' : '') . ">In Progress</option>
                            <option value='Resolved'" . ($row['status'] == 'Resolved' ? ' selected' : '') . ">Resolved</option>
                        </select>
                    </form>
                    <div class='inline-actions'>
                        <button class='action-icon-btn btn-view' onclick='viewComplaint({$row['id']})' title='View Details'>
                            <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\" />
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\" />
                            </svg>
                        </button>
                        <button class='action-icon-btn btn-reply' onclick='openAddUpdate({$row['id']})' title='Add Update'>
                            <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z\" />
                            </svg>
                        </button>
                        <button class='action-icon-btn btn-delete' onclick='deleteComplaint({$row['id']})' title='Delete to Trash'>
                            <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\" />
                            </svg>
                        </button>
                    </div>
                </div>
            </td>
        </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>No complaints found.</td></tr>";
                        }
                        if (isset($stmtComplaints)) {
                            $stmtComplaints->close();
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- STUDENTS SECTION -->
        <div id="students-section" class="dashboard-section">
            <div class="section-header">
                <h3>Student Management</h3>
                <div class="section-actions">
                    <button class="btn-primary" onclick="showAddStudentModal()">Add Student</button>
                </div>
            </div>

            <div class="dashboard-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Complaints</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $students_result = $conn->query("SELECT s.*, COUNT(c.id) as complaint_count FROM students s LEFT JOIN complaints c ON s.id = c.student_id AND c.deleted_at IS NULL WHERE s.deleted_at IS NULL GROUP BY s.id ORDER BY s.created_at DESC");

                        if ($students_result->num_rows > 0) {
                            while ($student = $students_result->fetch_assoc()) {
                                echo "<tr>
                                <td>{$student['id']}</td>
                                <td>{$student['name']}</td>
                                <td>{$student['email']}</td>
                                <td><span class='badge'>{$student['complaint_count']}</span></td>
                                <td>" . date('M d, Y', strtotime($student['created_at'])) . "</td>
                                <td>
                                    <div class='action-buttons'>
                                        <div class='inline-actions'>
                                            <button class='action-icon-btn btn-view' onclick='viewStudent({$student['id']})' title='View Profile'>
                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\" />
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\" />
                                                </svg>
                                            </button>
                                            <button class='action-icon-btn btn-edit' onclick='editStudent({$student['id']})' title='Edit Student'>
                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\" />
                                                </svg>
                                            </button>
                                            <button class='action-icon-btn btn-delete' onclick='deleteStudent({$student['id']})' title='Delete Student'>
                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>No students found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>



        <!-- REPORTS SECTION -->
        <div id="reports-section" class="dashboard-section active">
            <div class="section-header">
                <h3>Reports & Analytics</h3>
                <div class="section-actions">
                    <button class="btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print Dashboard</button>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="trash-tabs" style="margin-bottom: 20px;">
                <button class="tab-btn active" onclick="showReportTab('analytics')">Analytics</button>
                <button class="tab-btn" onclick="showReportTab('lists')">Lists & Records</button>
                <button class="tab-btn" onclick="showReportTab('sales')">Sales (Placeholder)</button>
            </div>

            <!-- Analytics Tab -->
            <div id="report-analytics" class="report-tab-content active">
                <div class="reports-grid">
                    <div class="report-card">
                        <h4>Complaints by Category</h4>
                        <div class="chart-container">
                            <?php
                            $category_stats = $conn->query("SELECT category, COUNT(*) as count FROM complaints WHERE deleted_at IS NULL GROUP BY category");
                            while ($stat = $category_stats->fetch_assoc()) {
                                echo "<div class='chart-item'>
                                <span class='chart-label'>{$stat['category']}</span>
                                <span class='chart-value'>{$stat['count']}</span>
                            </div>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="report-card">
                        <h4>Recent Activity</h4>
                        <div class="activity-list">
                            <?php
                            $recent_complaints = $conn->query("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.deleted_at IS NULL ORDER BY c.created_at DESC LIMIT 5");
                            while ($recent = $recent_complaints->fetch_assoc()) {
                                echo "<div class='activity-item'>
                                <div class='activity-content'>
                                    <strong>{$recent['student_name']}</strong> filed a complaint: {$recent['title']}
                                    <small>" . date('M d, Y H:i', strtotime($recent['created_at'])) . "</small>
                                </div>
                            </div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 24px 0 12px;">Pending Review</h3>
                <div class="dashboard-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rev = $conn->query("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.status='Pending' AND c.deleted_at IS NULL ORDER BY c.created_at DESC");
                            if ($rev && $rev->num_rows > 0) {
                                while ($r = $rev->fetch_assoc()) {
                                    $status_class = strtolower(str_replace(' ', '-', $r['status']));
                                    echo "<tr>
                                <td>{$r['id']}</td>
                                <td>" . ($r['student_name'] ? $r['student_name'] : 'Unknown') . "</td>
                                <td>{$r['title']}</td>
                                <td><span class='status-badge status-$status_class'>{$r['status']}</span></td>
                                <td>" . date('M d, Y', strtotime($r['created_at'])) . "</td>
                                <td>
                                    <div class='action-buttons'>
                                        <div class='inline-actions'>
                                            <button class='action-icon-btn btn-approve' onclick='approveComplaint({$r['id']})' title='Approve'>
                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M5 13l4 4L19 7\" />
                                                </svg>
                                            </button>
                                            <button class='action-icon-btn btn-deny' onclick='openDenyModal({$r['id']})' title='Deny'>
                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M6 18L18 6M6 6l12 12\" />
                                                </svg>
                                            </button>
                                            <button class='action-icon-btn btn-view' onclick='viewComplaint({$r['id']})' title='View Details'>
                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\" />
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No complaints to review.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Lists Tab -->
            <div id="report-lists" class="report-tab-content" style="display: none;">
                <div class="report-card" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h4>All Students List</h4>
                        <button class="btn-secondary" onclick="printDiv('student-list-table')">Print List</button>
                    </div>
                    <div id="student-list-table" class="dashboard-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $all_students = $conn->query("SELECT * FROM students WHERE deleted_at IS NULL ORDER BY name ASC");
                                while ($s = $all_students->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$s['id']}</td>
                                        <td>{$s['name']}</td>
                                        <td>{$s['email']}</td>
                                        <td>" . date('M d, Y', strtotime($s['created_at'])) . "</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h4>All Complaints List</h4>
                        <button class="btn-secondary" onclick="printDiv('complaint-list-table')">Print List</button>
                    </div>
                    <div id="complaint-list-table" class="dashboard-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $all_complaints = $conn->query("SELECT * FROM complaints WHERE deleted_at IS NULL ORDER BY created_at DESC");
                                while ($c = $all_complaints->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$c['id']}</td>
                                        <td>{$c['title']}</td>
                                        <td>{$c['category']}</td>
                                        <td>{$c['status']}</td>
                                        <td>" . date('M d, Y', strtotime($c['created_at'])) . "</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sales Tab (Placeholder) -->
            <div id="report-sales" class="report-tab-content" style="display: none;">
                <div class="report-card">
                    <h4>Sales Report</h4>
                    <p style="color: var(--text-secondary); padding: 20px; text-align: center;">
                        Sales module is currently under development. <br>
                        This section will track any financial transactions or item sales if applicable.
                    </p>
                </div>
            </div>
        </div>

        <!-- TRASH SECTION -->
        <div id="trash-section" class="dashboard-section">
            <div class="section-header">
                <h3>Trash - Deleted Records</h3>
                <div class="section-actions" style="display: flex; gap: 10px; align-items: center;">
                    <div id="trash-filter-container" style="display: none;">
                        <input type="text" id="trash-search-input" placeholder="Search deleted items..." onkeyup="filterTrashTable()" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; width: 200px;">
                    </div>
                    <button class="btn-primary" onclick="showTrashFilter()">Filter</button>
                    <button class="btn-restore" onclick="restoreAllTrash()" title="Restore all items in current tab">Restore All</button>
                    <button class="btn-delete" onclick="deleteAllTrash()" title="Permanently delete all items in current tab">Delete All</button>
                </div>
            </div>

            <div class="trash-tabs">
                <button class="tab-btn active" onclick="showTrashTab('complaints')">Deleted Complaints</button>
                <button class="tab-btn" onclick="showTrashTab('students')">Deleted Students</button>
            </div>

            <!-- Deleted Complaints Tab -->
            <div id="trash-complaints" class="trash-tab-content active">
                <div class="dashboard-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Deleted Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $deleted_complaints = $conn->query("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.deleted_at IS NOT NULL ORDER BY c.deleted_at DESC");

                            if ($deleted_complaints->num_rows > 0) {
                                while ($row = $deleted_complaints->fetch_assoc()) {
                                    $status_class = strtolower(str_replace(' ', '-', $row['status']));
                                    $description_short = strlen($row['description']) > 50 ? substr($row['description'], 0, 50) . '...' : $row['description'];

                                    // Parse category - check if it has custom category
                                    $category = !empty($row['category']) ? $row['category'] : 'Other';
                                    $display_category = $category;
                                    $custom_category = '';

                                    // Handle "Others: [custom]" format - extract custom specification
                                    if (!empty($row['category']) && strpos($row['category'], 'Others: ') === 0) {
                                        $display_category = 'Other';
                                        $custom_category = substr($row['category'], 8);
                                    }

                                    $category_badge = "<span class='category-badge'>{$display_category}</span>";
                                    if ($custom_category) {
                                        $category_badge .= "<br><small style='font-size: 11px; color: var(--text-muted); font-style: italic;'>($custom_category)</small>";
                                    }

                                    echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>" . ($row['student_name'] ? $row['student_name'] : 'Unknown') . "</td>
                                    <td>{$row['title']}</td>
                                    <td>{$category_badge}</td>
                                    <td title='{$row['description']}'>{$description_short}</td>
                                    <td><span class='status-badge status-$status_class'>{$row['status']}</span></td>
                                    <td>" . date('M d, Y H:i', strtotime($row['deleted_at'])) . "</td>
                                    <td>
                                        <div class='action-buttons'>
                                            <div class='inline-actions'>
                                                <button class='action-icon-btn btn-restore' onclick='restoreComplaint({$row['id']})' title='Restore'>
                                                    <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15\" />
                                                    </svg>
                                                </button>
                                                <button class='action-icon-btn btn-delete' onclick='permanentDeleteComplaint({$row['id']})' title='Permanently Delete'>
                                                    <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No deleted complaints found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Deleted Students Tab -->
            <div id="trash-students" class="trash-tab-content">
                <div class="dashboard-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Complaints</th>
                                <th>Deleted Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $deleted_students = $conn->query("SELECT s.*, COUNT(c.id) as complaint_count FROM students s LEFT JOIN complaints c ON s.id = c.student_id AND c.deleted_at IS NOT NULL WHERE s.deleted_at IS NOT NULL GROUP BY s.id ORDER BY s.deleted_at DESC");

                            if ($deleted_students->num_rows > 0) {
                                while ($student = $deleted_students->fetch_assoc()) {
                                    echo "<tr>
                                    <td>{$student['id']}</td>
                                    <td>{$student['name']}</td>
                                    <td>{$student['email']}</td>
                                    <td><span class='badge'>{$student['complaint_count']}</span></td>
                                    <td>" . date('M d, Y H:i', strtotime($student['deleted_at'])) . "</td>
                                    <td>
                                        <div class='action-buttons'>
                                            <div class='inline-actions'>
                                                <button class='action-icon-btn btn-restore' onclick='restoreStudent({$student['id']})' title='Restore'>
                                                    <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15\" />
                                                    </svg>
                                                </button>
                                                <button class='action-icon-btn btn-delete' onclick='permanentDeleteStudent({$student['id']})' title='Permanently Delete'>
                                                    <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
                                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No deleted students found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <div id="addComplaintModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addComplaintModal')">&times;</span>
            <h3>Add New Complaint</h3>
            <form method="POST" action="actions/admin_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_complaint">
                <div class="form-group">
                    <label>Student:</label>
                    <select name="student_id" required>
                        <?php
                        $students = $conn->query("SELECT * FROM students WHERE deleted_at IS NULL ORDER BY name");
                        while ($student = $students->fetch_assoc()) {
                            echo "<option value='{$student['id']}'>{$student['name']} ({$student['email']})</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Category:</label>
                    <select name="category" required id="admin_category" onchange="toggleAdminCustomCategory()">
                        <?php
                        $hasCategoriesTable = $conn->query("SHOW TABLES LIKE 'categories'");
                        $printed = false;
                        if ($hasCategoriesTable && $hasCategoriesTable->num_rows > 0) {
                            $cats = $conn->query("SELECT name FROM categories ORDER BY name");
                            if ($cats) {
                                while ($c = $cats->fetch_assoc()) {
                                    $name = htmlspecialchars($c['name']);
                                    echo "<option value=\"{$name}\">{$name}</option>";
                                    $printed = true;
                                }
                            }
                        }
                        if (!$printed) {
                            echo "<option value=\"Facility\">Facility</option>\n";
                            echo "<option value=\"Faculty\">Faculty</option>\n";
                            echo "<option value=\"Administrative\">Administrative</option>\n";
                        }
                        ?>
                        <option value="Other">Others (please specify)</option>
                    </select>
                </div>
                <div class="form-group" id="admin_custom_category_group" style="display: none;">
                    <label>Please specify category:</label>
                    <input type="text" id="admin_custom_category" name="custom_category" placeholder="Enter custom category">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Attach Images (Optional):</label>
                    <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/gif,image/jpg" />
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">You can select multiple images. Maximum 5MB per file.</small>
                </div>
                <button type="submit">Add Complaint</button>
            </form>
        </div>
    </div>

    <!-- View Complaint Modal (Enhanced Ticket View) -->
    <div id="viewComplaintModal" class="modal">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; max-width: 700px; padding: 0;">
            <div style="background: white; padding: 24px 30px; border-bottom: 1px solid #f3f4f6; display:flex; justify-content: space-between; align-items: start;">
                <div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span id="view_id_display">#000</span>
                        <span style="width: 4px; height: 4px; background: #d1d5db; border-radius: 50%;"></span>
                        <span id="view_date_display">Oct 24, 2023</span>
                    </div>
                    <h3 style="margin: 0; color: var(--text-primary); font-size: 1.5rem; line-height: 1.3;">Complaint Details</h3>
                </div>
                <span class="close" onclick="closeModal('viewComplaintModal')" style="font-size: 28px; color: #9ca3af; cursor: pointer; line-height: 24px;">&times;</span>
            </div>

            <div style="padding: 30px; max-height: 70vh; overflow-y: auto;">
                <!-- Status Banner -->
                <div style="background: var(--bg-primary); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color);">
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 4px;">Current Status</div>
                        <div id="view_status_badge">
                            <span class="status-badge status-pending">Pending</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 4px;">Student</div>
                        <div id="view_student_display" style="font-weight: 600; color: var(--text-primary);">Student Name</div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Subject</label>
                    <div id="view_title_display" style="padding: 12px 16px; background: var(--bg-primary); border-radius: 8px; border: 1px solid transparent; font-weight: 500;"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Category</label>
                        <div id="view_category_display" style="padding: 12px 16px; background: var(--bg-primary); border-radius: 8px;"></div>
                    </div>
                    <div id="view_custom_category_group" style="display:none;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Specification</label>
                        <div id="view_custom_category_display" style="padding: 12px 16px; background: var(--bg-primary); border-radius: 8px;"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Description</label>
                    <div id="view_description_display" style="padding: 16px; background: var(--bg-primary); border-radius: 8px; line-height: 1.6; white-space: pre-wrap;"></div>
                </div>

                <!-- Attachments -->
                <div id="attachments_section" style="margin-bottom: 24px; display: none;">
                    <label style="display: block; font-weight: 600; margin-bottom: 12px; color: var(--text-primary); font-size: 0.95rem;">Attachments</label>
                    <div id="view_attachments_gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 12px;"></div>
                </div>

                <!-- Admin Interaction Section -->
                <div style="border-top: 1px solid #f3f4f6; padding-top: 24px; margin-top: 24px;">
                    <h4 style="margin: 0 0 16px 0; font-size: 1.1rem; color: var(--text-primary);">Admin Actions</h4>

                    <!-- Admin Note Display/Edit -->
                    <div id="admin_note_display_container" style="margin-bottom: 20px; display: none;">
                        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 16px; border-radius: 8px; position: relative;">
                            <strong style="color: #047857; display: block; margin-bottom: 8px;">Your Note:</strong>
                            <p id="view_admin_note_text" style="margin: 0; color: #064e3b;"></p>
                            <div style="margin-top: 12px; display: flex; gap: 10px;">
                                <button onclick="editAdminNote()" class="admin-btn" style="padding: 6px 12px; font-size: 0.85rem;">Edit Note</button>
                                <button onclick="deleteAdminNote()" class="admin-btn danger" style="padding: 6px 12px; font-size: 0.85rem;">Delete</button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button id="btn_reply" onclick="replyToComplaint(currentComplaintId)" class="admin-btn" style="display: flex; align-items: center; gap: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Add Note
                        </button>
                        <button onclick="openAddUpdate(currentComplaintId)" class="admin-btn" style="display: flex; align-items: center; gap: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Update Status
                        </button>
                        <button onclick="openDenyModal(currentComplaintId)" class="admin-btn danger" style="background: white; border: 1px solid #ef4444; color: #ef4444;">
                            Deny
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Note Modal -->
    <div id="replyComplaintModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('replyComplaintModal')">&times;</span>
            <h3>Add Admin Note</h3>
            <form method="POST" action="actions/admin_actions.php">
                <input type="hidden" name="action" value="reply_to_complaint">
                <input type="hidden" name="id" id="reply_id">
                <div class="form-group">
                    <label>Admin's Note:</label>
                    <textarea name="reply" required placeholder="Enter your note here..."></textarea>
                </div>
                <button type="submit">Add Note</button>
            </form>
        </div>
    </div>

    <!-- Add Update Modal -->
    <div id="addUpdateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addUpdateModal')">&times;</span>
            <h3>Add Complaint Update</h3>
            <form method="POST" action="actions/admin_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_update">
                <input type="hidden" name="id" id="update_complaint_id">
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Denied">Denied</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Update Description:</label>
                    <textarea name="message" required placeholder="Describe the action or progress..."></textarea>
                </div>
                <div class="form-group">
                    <label>Attach Files (optional):</label>
                    <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/gif,image/jpg,application/pdf" />
                </div>
                <button type="submit">Save Update</button>
            </form>
        </div>
    </div>

    <!-- Edit Admin Note Modal -->
    <div id="editAdminNoteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editAdminNoteModal')">&times;</span>
            <h3>Edit Admin Note</h3>
            <form method="POST" action="actions/admin_actions.php">
                <input type="hidden" name="action" value="edit_admin_note">
                <input type="hidden" name="id" id="edit_note_id">
                <div class="form-group">
                    <label>Admin's Note:</label>
                    <textarea name="reply" id="edit_note_text" required placeholder="Enter your note here..."></textarea>
                </div>
                <button type="submit">Update Note</button>
            </form>
        </div>
    </div>

    <!-- Delete Admin Note Modal -->
    <div id="deleteAdminNoteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteAdminNoteModal')">&times;</span>
            <h3>Delete Admin Note</h3>
            <p>Are you sure you want to delete this admin note? This action cannot be undone.</p>
            <form method="POST" action="actions/admin_actions.php" style="margin-top: 20px;">
                <input type="hidden" name="action" value="delete_admin_note">
                <input type="hidden" name="id" id="delete_note_id">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-delete">Yes, Delete</button>
                    <button type="button" onclick="closeModal('deleteAdminNoteModal')" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deny Complaint Modal -->
    <div id="denyComplaintModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('denyComplaintModal')">&times;</span>
            <h3>Deny Complaint</h3>
            <form method="POST" action="actions/admin_actions.php">
                <input type="hidden" name="action" value="deny_with_reason">
                <input type="hidden" name="id" id="deny_complaint_id">
                <input type="hidden" name="active_section" value="reports">
                <div class="form-group">
                    <label>Reason for Denial:</label>
                    <textarea name="reason" id="deny_reason" required placeholder="Provide a short reason..."></textarea>
                </div>
                <button type="submit" class="btn-deny">Deny Complaint</button>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editStudentModal')">&times;</span>
            <h3>Edit Student</h3>
            <form method="POST" action="actions/admin_actions.php">
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="id" id="edit_student_id">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" id="edit_student_name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="edit_student_email" required>
                </div>
                <div class="form-group">
                    <label>Password (leave blank to keep current):</label>
                    <input type="password" name="password" id="edit_student_password" placeholder="Enter new password">
                </div>
                <button type="submit">Update Student</button>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addStudentModal')">&times;</span>
            <h3>Add New Student</h3>
            <form method="POST" action="actions/admin_actions.php">
                <input type="hidden" name="action" value="add_student">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Add Student</button>
            </form>
        </div>
    </div>

    <!-- View Student Modal -->
    <div id="viewStudentModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal('viewStudentModal')">&times;</span>
            <h3>Student Details</h3>
            <div class="view-details">
                <div class="detail-row">
                    <strong>Student ID:</strong> <span id="view_student_id"></span>
                </div>
                <div class="detail-row">
                    <strong>Student Name:</strong> <span id="view_student_name"></span>
                </div>
                <div class="detail-row">
                    <strong>Email Address:</strong> <span id="view_student_email"></span>
                </div>
                <div class="detail-row">
                    <strong>Password:</strong> <span id="view_student_password" style="font-family: monospace; background: #f5f5f5; padding: 4px 8px; border-radius: 4px;"></span>
                </div>
                <div class="detail-row">
                    <strong>Total Complaints:</strong> <span id="view_student_complaints"></span>
                </div>
                <div class="detail-row">
                    <strong>Join Date:</strong> <span id="view_student_join_date"></span>
                </div>
            </div>
            <button onclick="closeModal('viewStudentModal')" style="margin-top: 20px;">Close</button>
        </div>
    </div>



    <script>
        function showSection(sectionName) {
            document.querySelectorAll('.dashboard-section').forEach(section => {
                section.classList.remove('active');
            });

            document.getElementById(sectionName + '-section').classList.add('active');

            // Update URL hash
            window.location.hash = '#' + sectionName;

            document.querySelectorAll('.admin-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Highlight the corresponding admin button
            const buttons = document.querySelectorAll('.admin-btn');
            buttons.forEach(btn => {
                if (btn.textContent.includes(sectionName.charAt(0).toUpperCase() + sectionName.slice(1).replace('-', ' '))) {
                    btn.classList.add('active');
                }
            });
        }

        function filterComplaintsByStatus(statusKey) {
            // Map UI keys to server-side canonical status values
            const statusMapToServer = {
                'all': 'all',
                'pending': 'Pending',
                'approved': 'Approved',
                'denied': 'Denied',
                'in-progress': 'In Progress',
                'resolved': 'Resolved'
            };
            const serverStatus = statusMapToServer[statusKey] || 'Approved';
            window.location.href = 'dashboard.php?status=' + encodeURIComponent(serverStatus) + '#complaints';
        }

        function showAllComplaints() {
            window.location.href = 'dashboard.php?status=all#complaints';
        }

        function showAddComplaintModal() {
            document.getElementById('addComplaintModal').style.display = 'block';
        }

        function showAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
        }


        function editAdminNote() {
            const complaintId = document.getElementById('view-id').textContent;
            const currentNote = document.getElementById('view-reply').textContent;

            document.getElementById('edit_note_id').value = complaintId;
            document.getElementById('edit_note_text').value = currentNote;
            document.getElementById('editAdminNoteModal').style.display = 'block';
        }

        // Category management removed per requirements

        function deleteAdminNote() {
            const complaintId = document.getElementById('view-id').textContent;
            document.getElementById('delete_note_id').value = complaintId;
            document.getElementById('deleteAdminNoteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        let currentComplaintId = null; // Global tracker for modals

        function viewComplaint(id) {
            currentComplaintId = id; // Set global ID
            fetch('actions/get_complaint.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('viewComplaintModal').style.display = 'block';

                    // Basic Info
                    document.getElementById('view_id_display').innerText = '#' + data.id;
                    document.getElementById('view_date_display').innerText = new Date(data.created_at).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    document.getElementById('view_student_display').innerText = data.student_name || 'N/A';
                    document.getElementById('view_title_display').innerText = data.title;
                    document.getElementById('view_description_display').innerText = data.description;

                    // Category
                    document.getElementById('view_category_display').innerText = data.category;
                    const customGroup = document.getElementById('view_custom_category_group');
                    if (data.category === 'Other' && data.custom_category) {
                        customGroup.style.display = 'block';
                        document.getElementById('view_custom_category_display').innerText = data.custom_category;
                    } else {
                        customGroup.style.display = 'none';
                    }

                    // Status Badge
                    const statusClass = data.status.toLowerCase().replace(' ', '-');
                    document.getElementById('view_status_badge').innerHTML = `<span class="status-badge status-${statusClass}">${data.status}</span>`;

                    // Attachments logic
                    const attachmentsDiv = document.getElementById('view_attachments_gallery');
                    const attachmentsSection = document.getElementById('attachments_section');
                    attachmentsDiv.innerHTML = '';

                    if (data.attachments && data.attachments.trim() !== '') {
                        attachmentsSection.style.display = 'block';
                        const files = data.attachments.split(',');
                        files.forEach(file => {
                            if (file.trim() !== "") {
                                const ext = file.split('.').pop().toLowerCase();
                                const fileUrl = '../' + file;

                                const link = document.createElement('a');
                                link.href = 'javascript:void(0)';
                                link.onclick = () => openImageModal(fileUrl);
                                link.style.display = 'flex';
                                link.style.alignItems = 'center';
                                link.style.justifyContent = 'center';
                                link.style.width = '80px';
                                link.style.height = '80px';
                                link.style.border = '1px solid #e5e7eb';
                                link.style.borderRadius = '8px';
                                link.style.overflow = 'hidden';
                                link.title = 'View Attachment';

                                if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                                    const img = document.createElement('img');
                                    img.src = fileUrl;
                                    img.style.width = '100%';
                                    img.style.height = '100%';
                                    img.style.objectFit = 'cover';
                                    link.appendChild(img);
                                } else {
                                    link.innerHTML = '📄';
                                    link.style.background = '#f9fafb';
                                    link.style.fontSize = '24px';
                                    link.href = fileUrl; // Direct link for docs
                                    link.onclick = null;
                                    link.target = '_blank';
                                }
                                attachmentsDiv.appendChild(link);
                            }
                        });
                    } else {
                        attachmentsSection.style.display = 'none';
                    }

                    // Admin Note Logic
                    const replyBtn = document.getElementById('btn_reply');
                    const noteContainer = document.getElementById('admin_note_display_container');
                    const noteText = document.getElementById('view_admin_note_text');

                    // We need to set the global IDs for the other modals (edit/delete note)
                    document.getElementById('reply_id').value = id;
                    document.getElementById('edit_note_id').value = id;
                    document.getElementById('delete_note_id').value = id;

                    if (data.admin_reply && data.admin_reply.trim() !== '') {
                        noteContainer.style.display = 'block';
                        replyBtn.style.display = 'none'; // Hide "Add Note" if exists
                        noteText.innerText = data.admin_reply;

                        // Populate the Edit Modal textarea ahead of time
                        const editInput = document.getElementById('edit_note_text');
                        if (editInput) editInput.value = data.admin_reply;

                    } else {
                        noteContainer.style.display = 'none';
                        replyBtn.style.display = 'flex'; // Show "Add Note"
                    }

                })
                .catch(error => console.error('Error:', error));
        }

        function replyToComplaint(id) {
            document.getElementById('replyComplaintModal').style.display = 'block';
            document.getElementById('reply_id').value = id;
        }

        function openAddUpdate(id) {
            document.getElementById('addUpdateModal').style.display = 'block';
            document.getElementById('update_complaint_id').value = id;
        }

        function deleteComplaint(id) {
            if (confirm('Are you sure you want to delete this complaint?')) {
                const currentHash = window.location.hash || '#complaints';
                fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete_complaint&id=' + id
                }).then(() => {
                    window.location.hash = currentHash;
                    location.reload();
                });
            }
        }

        function viewStudent(id) {
            fetch('actions/get_student.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('viewStudentModal').style.display = 'block';
                    document.getElementById('view_student_id').textContent = data.id;
                    document.getElementById('view_student_name').textContent = data.name;
                    document.getElementById('view_student_email').textContent = data.email;
                    document.getElementById('view_student_password').textContent = data.password || 'N/A';

                    fetch('actions/get_student_stats.php?id=' + id)
                        .then(response => response.json())
                        .then(stats => {
                            document.getElementById('view_student_complaints').textContent = stats.complaint_count || 0;
                            if (stats.created_at) {
                                const date = new Date(stats.created_at);
                                document.getElementById('view_student_join_date').textContent = date.toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric'
                                });
                            } else {
                                document.getElementById('view_student_join_date').textContent = 'N/A';
                            }
                        })
                        .catch(error => console.error('Error:', error));
                })
                .catch(error => console.error('Error:', error));
        }

        function editStudent(id) {
            fetch('actions/get_student.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editStudentModal').style.display = 'block';
                    document.getElementById('edit_student_id').value = data.id;
                    document.getElementById('edit_student_name').value = data.name;
                    document.getElementById('edit_student_email').value = data.email;
                    document.getElementById('edit_student_password').value = '';
                })
                .catch(error => console.error('Error:', error));
        }

        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete_student&id=' + id
                }).then(() => location.reload());
            }
        }

        async function approveStudent(id) {
            const body = new URLSearchParams();
            body.set('action', 'approve_student');
            body.set('id', id);
            try {
                await fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body
                });
            } finally {
                window.location.hash = '#students';
                location.reload();
            }
        }

        async function setStudentPending(id) {
            const body = new URLSearchParams();
            body.set('action', 'disapprove_student');
            body.set('id', id);
            try {
                await fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body
                });
            } finally {
                window.location.hash = '#students';
                location.reload();
            }
        }

        async function approveComplaint(id) {
            const body = new URLSearchParams();
            body.set('action', 'set_status');
            body.set('id', id);
            body.set('status', 'Approved');
            try {
                await fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body
                });
            } finally {
                window.location.hash = '#reports';
                location.reload();
            }
        }

        function openDenyModal(id) {
            document.getElementById('denyComplaintModal').style.display = 'block';
            document.getElementById('deny_complaint_id').value = id;
            document.getElementById('deny_reason').value = '';
        }

        function filterComplaints(status) {
            const rows = document.querySelectorAll('#complaints-section tbody tr');
            const statusMap = {
                'all': '',
                'pending': 'Pending',
                'approved': 'Approved',
                'denied': 'Denied',
                'in-progress': 'In Progress',
                'resolved': 'Resolved'
            };

            const filterStatus = statusMap[status];

            rows.forEach(row => {
                const statusBadge = row.querySelector('.status-badge');
                if (statusBadge) {
                    const rowStatus = statusBadge.textContent.trim();
                    if (status === 'all' || rowStatus === filterStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        function showTrashTab(tabName) {
            // Hide all trash tab contents
            document.querySelectorAll('.trash-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById('trash-' + tabName).classList.add('active');

            // Add active class to clicked tab button
            event.target.classList.add('active');
        }

        // Helpers for bulk actions in Trash
        function getActiveTrashType() {
            const active = document.querySelector('.trash-tab-content.active');
            if (!active) return 'complaints';
            return active.id === 'trash-students' ? 'students' : 'complaints';
        }

        function collectIdsFromActiveTrash() {
            const active = document.querySelector('.trash-tab-content.active');
            if (!active) return [];
            const rows = active.querySelectorAll('tbody tr');
            const ids = [];
            rows.forEach(row => {
                const firstCell = row.querySelector('td');
                if (firstCell) {
                    const id = firstCell.textContent.trim();
                    if (id && /^\d+$/.test(id)) ids.push(id);
                }
            });
            return ids;
        }

        async function restoreAllTrash() {
            const type = getActiveTrashType();
            const ids = collectIdsFromActiveTrash();
            if (!ids.length) {
                alert('Nothing to restore in this tab.');
                return;
            }
            if (!confirm(`Restore all ${type === 'complaints' ? 'deleted complaints' : 'deleted students'}?`)) return;
            const action = type === 'complaints' ? 'restore_complaint' : 'restore_student';
            const bodyFor = id => `action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`;
            const ops = ids.map(id => fetch('actions/admin_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: bodyFor(id)
            }));
            try {
                await Promise.all(ops);
            } finally {
                window.location.hash = '#trash';
                location.reload();
            }
        }

        async function deleteAllTrash() {
            const type = getActiveTrashType();
            const ids = collectIdsFromActiveTrash();
            if (!ids.length) {
                alert('Nothing to delete in this tab.');
                return;
            }
            if (!confirm(`Permanently delete ALL ${type === 'complaints' ? 'deleted complaints' : 'deleted students'}? This cannot be undone!`)) return;
            const action = type === 'complaints' ? 'permanent_delete_complaint' : 'permanent_delete_student';
            const bodyFor = id => `action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`;
            const ops = ids.map(id => fetch('actions/admin_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: bodyFor(id)
            }));
            try {
                await Promise.all(ops);
            } finally {
                window.location.hash = '#trash';
                location.reload();
            }
        }

        function showTrashFilter() {
            const container = document.getElementById('trash-filter-container');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                document.getElementById('trash-search-input').focus();
            } else {
                container.style.display = 'none';
            }
        }

        function filterTrashTable() {
            const input = document.getElementById('trash-search-input');
            const filter = input.value.toLowerCase();
            const activeTab = document.querySelector('.trash-tab-content.active');
            if (!activeTab) return;

            const rows = activeTab.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function restoreComplaint(id) {
            if (confirm('Are you sure you want to restore this complaint?')) {
                fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=restore_complaint&id=' + id
                }).then(() => {
                    window.location.hash = '#trash';
                    location.reload();
                });
            }
        }

        function restoreStudent(id) {
            if (confirm('Are you sure you want to restore this student and all their complaints?')) {
                fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=restore_student&id=' + id
                }).then(() => {
                    window.location.hash = '#trash';
                    location.reload();
                });
            }
        }

        function permanentDeleteComplaint(id) {
            if (confirm('Are you sure you want to permanently delete this complaint? This action cannot be undone!')) {
                fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=permanent_delete_complaint&id=' + id
                }).then(() => {
                    window.location.hash = '#trash';
                    location.reload();
                });
            }
        }

        function permanentDeleteStudent(id) {
            if (confirm('Are you sure you want to permanently delete this student and all their complaints? This action cannot be undone!')) {
                fetch('actions/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=permanent_delete_student&id=' + id
                }).then(() => {
                    window.location.hash = '#trash';
                    location.reload();
                });
            }
        }

        function toggleAdminCustomCategory() {
            const category = document.getElementById('admin_category').value;
            const customCategoryGroup = document.getElementById('admin_custom_category_group');
            const customCategory = document.getElementById('admin_custom_category');

            if (category === 'Other') {
                customCategoryGroup.style.display = 'block';
                customCategory.setAttribute('required', 'required');
            } else {
                customCategoryGroup.style.display = 'none';
                customCategory.removeAttribute('required');
            }
        }

        // Image Modal Functions
        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('img01');
            modal.style.display = "block";
            modalImg.src = src;
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
            const imageModal = document.getElementById('imageModal');
            if (event.target == imageModal) {
                imageModal.style.display = 'none';
            }
        }

        // Restore active section on page load
        window.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const sectionName = hash.replace('#', '').replace('-section', '');
                if (sectionName) {
                    showSection(sectionName);
                    if (sectionName === 'complaints') {
                        setActiveStat('all');
                    } else if (sectionName === 'students') {
                        setActiveStat(null);
                    }
                    return;
                }
            }
            // Default to reports when no hash present
            showSection('reports');
        });

        function setActiveStat(which) {
            const map = {
                'all': 'card-total',
                'pending': 'card-pending',
                'approved': 'card-approved',
                'in-progress': 'card-in-progress',
                'resolved': 'card-resolved'
            };
            document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('active'));
            if (which && map[which]) {
                const el = document.getElementById(map[which]);
                if (el) el.classList.add('active');
            }
        }

        function showReportTab(tabName) {
            // Hide all report tabs
            document.querySelectorAll('.report-tab-content').forEach(tab => {
                tab.style.display = 'none';
                tab.classList.remove('active');
            });

            // Remove active class from buttons
            document.querySelectorAll('#reports-section .tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            const selectedTab = document.getElementById('report-' + tabName);
            if (selectedTab) {
                selectedTab.style.display = 'block';
                selectedTab.classList.add('active');
            }

            // Set active button
            event.target.classList.add('active');
        }

        function printDiv(divId) {
            const printContents = document.getElementById(divId).innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h2 style="text-align: center; margin-bottom: 20px;">Report</h2>
                    ${printContents}
                </div>
            `;

            window.print();

            document.body.innerHTML = originalContents;
            location.reload(); // Reload to restore event listeners
        }
    </script>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('imageModal')">&times;</span>
            <img id="img01">
        </div>
    </div>
</body>

</html>