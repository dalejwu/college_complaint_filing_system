<?php
include('../db.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if columns exist, if not show error message
$columns_check = $conn->query("SHOW COLUMNS FROM complaints LIKE 'attachments'");
$columns_exist = ($columns_check->num_rows > 0);

if (!$columns_exist) {
    $_SESSION['error'] = "Database needs migration! <a href='run_migration.php' style='color: white; font-weight: bold; text-decoration: underline;'>Click here to run migration automatically</a> or run the SQL manually in phpMyAdmin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border: 2px solid rgba(31, 64, 50, 0.18);
            border-radius: 16px;
            padding: 28px 16px;
            text-align: center;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease, border-color 180ms ease;
        }
        .stat-card h3 {
            margin: 0 0 8px;
            font-size: 40px;
            line-height: 1;
            color: #0f5132;
        }
        .stat-card p {
            margin: 0;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: #253b2e;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.10);
            border-color: rgba(31, 64, 50, 0.30);
        }
        .stat-card.active {
            background: linear-gradient(180deg, #f0fff6, #e8fff2);
            border-color: #2f6f4f;
            box-shadow: 0 10px 22px rgba(23, 92, 56, 0.15);
        }
        .inline-actions { display: flex; gap: 12px; align-items: center; justify-content: center; width: 100%; }
        .btn-approve { background: #198754; color: #fff; border: none; padding: 10px 16px; border-radius: 999px; cursor: pointer; min-width: 120px; text-align: center; }
        .btn-approve:hover { background: #157347; }
        .btn-deny { background: #dc3545; color: #fff; border: none; padding: 10px 16px; border-radius: 999px; cursor: pointer; min-width: 120px; text-align: center; }
        .btn-deny:hover { background: #bb2d3b; }
        .action-buttons { display: flex; flex-direction: column; gap: 8px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <h2>Admin Dashboard</h2>
        <p class="login-subtitle">Welcome back, <?php echo $_SESSION['admin_user']; ?>!</p>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="admin-actions">
            <a href="#complaints" class="admin-btn primary" onclick="showSection('complaints')">Manage Complaints</a>
            <a href="#students" class="admin-btn secondary" onclick="showSection('students')">Manage Students</a>
            <a href="#reports" class="admin-btn tertiary" onclick="showSection('reports')">Reports</a>
            <a href="#trash" class="admin-btn warning" onclick="showSection('trash')">Trash</a>
            <a href="logout.php" class="admin-btn danger">Logout</a>
        </div>
    </div>

    <div class="dashboard-stats" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
        <div id="card-total" class="stat-card" onclick="showAllComplaints()" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['total'] : '0';
            ?></h3>
            <p>Total Complaints</p>
        </div>
        <div id="card-pending" class="stat-card" onclick="filterComplaintsByStatus('pending')" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as pending FROM complaints WHERE status='Pending' AND deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['pending'] : '0';
            ?></h3>
            <p>Pending</p>
        </div>
        <div id="card-approved" class="stat-card" onclick="filterComplaintsByStatus('approved')" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as approved FROM complaints WHERE status='Approved' AND deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['approved'] : '0';
            ?></h3>
            <p>Approved</p>
        </div>
        <div id="card-denied" class="stat-card" onclick="filterComplaintsByStatus('denied')" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as denied FROM complaints WHERE status='Denied' AND deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['denied'] : '0';
            ?></h3>
            <p>Denied</p>
        </div>
        <div id="card-in-progress" class="stat-card" onclick="filterComplaintsByStatus('in-progress')" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as in_progress FROM complaints WHERE status='In Progress' AND deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['in_progress'] : '0';
            ?></h3>
            <p>In Progress</p>
        </div>
        <div id="card-resolved" class="stat-card" onclick="filterComplaintsByStatus('resolved')" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as resolved FROM complaints WHERE status='Resolved' AND deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['resolved'] : '0';
            ?></h3>
            <p>Resolved</p>
        </div>
        <div id="card-students" class="stat-card" onclick="showSection('students')" style="cursor: pointer;">
            <h3><?php 
                $result = $conn->query("SELECT COUNT(*) as students FROM students WHERE deleted_at IS NULL");
                echo $result ? $result->fetch_assoc()['students'] : '0';
            ?></h3>
            <p>Total Students</p>
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
            $allowedStatusesHeading = ['all','Approved','Denied','Pending','In Progress','Resolved'];
            if (!in_array($statusFilter, $allowedStatusesHeading)) { $statusFilter = 'Approved'; }
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
$allowedStatuses = ['all','Approved','Denied','Pending','In Progress','Resolved'];
if (!in_array($statusFilter, $allowedStatuses)) { $statusFilter = 'Approved'; }

if ($statusFilter === 'all') {
    $result = $conn->query("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.deleted_at IS NULL ORDER BY c.created_at DESC");
} else {
    $stmtComplaints = $conn->prepare("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.status = ? AND c.deleted_at IS NULL ORDER BY c.created_at DESC");
    $stmtComplaints->bind_param("s", $statusFilter);
    $stmtComplaints->execute();
    $result = $stmtComplaints->get_result();
}

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
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
                    <form class='action-form' method='POST' action='update_status.php' style='margin-right:8px;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <select name='status' onchange='this.form.submit()'>
                            <option value='Pending'" . ($row['status'] == 'Pending' ? ' selected' : '') . ">Pending</option>
                            <option value='Approved'" . ($row['status'] == 'Approved' ? ' selected' : '') . ">Approved</option>
                            <option value='Denied'" . ($row['status'] == 'Denied' ? ' selected' : '') . ">Denied</option>
                            <option value='In Progress'" . ($row['status'] == 'In Progress' ? ' selected' : '') . ">In Progress</option>
                            <option value='Resolved'" . ($row['status'] == 'Resolved' ? ' selected' : '') . ">Resolved</option>
                        </select>
                    </form>
                    <button class='btn-view' onclick='viewComplaint({$row['id']})'>View</button>
                    <button class='btn-reply' onclick='openAddUpdate({$row['id']})'>Add Update</button>
                    <button class='btn-delete' onclick='deleteComplaint({$row['id']})'>Delete</button>
                </div>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>No complaints found.</td></tr>";
}
if (isset($stmtComplaints)) { $stmtComplaints->close(); }
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
                        while($student = $students_result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$student['id']}</td>
                                <td>{$student['name']}</td>
                                <td>{$student['email']}</td>
                                <td><span class='badge'>{$student['complaint_count']}</span></td>
                                <td>" . date('M d, Y', strtotime($student['created_at'])) . "</td>
                                <td>
                                    <div class='action-buttons'>
                                        <button class='btn-view' onclick='viewStudent({$student['id']})'>View</button>
                                        <button class='btn-edit' onclick='editStudent({$student['id']})'>Edit</button>
                                        <button class='btn-delete' onclick='deleteStudent({$student['id']})'>Delete</button>
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
        </div>
        
        <div class="reports-grid">
            <div class="report-card">
                <h4>Complaints by Category</h4>
                <div class="chart-container">
                    <?php
                    $category_stats = $conn->query("SELECT category, COUNT(*) as count FROM complaints WHERE deleted_at IS NULL GROUP BY category");
                    while($stat = $category_stats->fetch_assoc()) {
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
                    while($recent = $recent_complaints->fetch_assoc()) {
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

        <h3 style="color: var(--text-primary); margin: 24px 0 12px;">Review Complaints</h3>
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
                                    <span class='inline-actions'>
                                        <button class='btn-approve' onclick='approveComplaint({$r['id']})'>Approve</button>
                                        <button class='btn-deny' onclick='openDenyModal({$r['id']})'>Deny</button>
                                    </span>
                                    <button class='btn-view' onclick='viewComplaint({$r['id']})'>View</button>
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

    <!-- TRASH SECTION -->
    <div id="trash-section" class="dashboard-section">
        <div class="section-header">
            <h3>Trash - Deleted Records</h3>
            <div class="section-actions">
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
                            while($row = $deleted_complaints->fetch_assoc()) {
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
                                            <button class='btn-restore' onclick='restoreComplaint({$row['id']})'>Restore</button>
                                            <button class='btn-delete' onclick='permanentDeleteComplaint({$row['id']})'>Permanent Delete</button>
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
                            while($student = $deleted_students->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$student['id']}</td>
                                    <td>{$student['name']}</td>
                                    <td>{$student['email']}</td>
                                    <td><span class='badge'>{$student['complaint_count']}</span></td>
                                    <td>" . date('M d, Y H:i', strtotime($student['deleted_at'])) . "</td>
                                    <td>
                                        <div class='action-buttons'>
                                            <button class='btn-restore' onclick='restoreStudent({$student['id']})'>Restore</button>
                                            <button class='btn-delete' onclick='permanentDeleteStudent({$student['id']})'>Permanent Delete</button>
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
        <form method="POST" action="admin_actions.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_complaint">
            <div class="form-group">
                <label>Student:</label>
                <select name="student_id" required>
                    <?php
                    $students = $conn->query("SELECT * FROM students WHERE deleted_at IS NULL ORDER BY name");
                    while($student = $students->fetch_assoc()) {
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
                            while($c = $cats->fetch_assoc()) {
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

<!-- View Complaint Modal -->
<div id="viewComplaintModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeModal('viewComplaintModal')">&times;</span>
        <h3>Complaint Details</h3>
        <div class="view-details">
            <div class="detail-row">
                <strong>ID:</strong> <span id="view-id"></span>
            </div>
            <div class="detail-row">
                <strong>Student:</strong> <span id="view-student"></span>
            </div>
            <div class="detail-row">
                <strong>Title:</strong> <span id="view-title"></span>
            </div>
            <div class="detail-row">
                <strong>Category:</strong> <span id="view-category"></span>
            </div>
            <div class="detail-row" id="view-custom-category-row" style="display: none;">
                <strong>Specification:</strong> <span id="view-custom-category"></span>
            </div>
            <div class="detail-row">
                <strong>Description:</strong> 
                <p id="view-description"></p>
            </div>
            <div class="detail-row">
                <strong>Status:</strong> <span id="view-status"></span>
            </div>
            <div class="detail-row">
                <strong>Date Filed:</strong> <span id="view-date"></span>
            </div>
            <div class="detail-row" id="attachments-row" style="display: none;">
                <strong>Attachments:</strong> 
                <div id="view-attachments" class="attachments-gallery"></div>
            </div>
            <div class="detail-row">
                <strong>Admin's Note:</strong> 
                <div id="admin-note-container">
                    <p id="view-reply"></p>
                    <div id="admin-note-actions" style="margin-top: 10px; display: none;">
                        <button class="btn-edit" onclick="editAdminNote()" style="margin-right: 10px;">Edit Note</button>
                        <button class="btn-delete" onclick="deleteAdminNote()">Delete Note</button>
                    </div>
                </div>
            </div>
        </div>
        <button onclick="closeModal('viewComplaintModal')" style="margin-top: 20px;">Close</button>
    </div>
</div>

<!-- Add Admin Note Modal -->
<div id="replyComplaintModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('replyComplaintModal')">&times;</span>
        <h3>Add Admin Note</h3>
        <form method="POST" action="admin_actions.php">
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
        <form method="POST" action="admin_actions.php" enctype="multipart/form-data">
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
        <form method="POST" action="admin_actions.php">
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
        <form method="POST" action="admin_actions.php" style="margin-top: 20px;">
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
        <form method="POST" action="admin_actions.php">
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
        <form method="POST" action="admin_actions.php">
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
        <form method="POST" action="admin_actions.php">
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

function viewComplaint(id) {
    fetch('get_complaint.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewComplaintModal').style.display = 'block';
            document.getElementById('view-id').textContent = data.id;
            document.getElementById('view-student').textContent = data.student_name || 'Unknown';
            document.getElementById('view-title').textContent = data.title;
            
            // Parse category display
            let categoryDisplay = data.category || 'Other';
            let customCategory = '';
            
            if (data.category && data.category.startsWith('Others: ')) {
                categoryDisplay = 'Other';
                customCategory = data.category.substring(8);
            }
            
            document.getElementById('view-category').textContent = categoryDisplay;
            
            // Show custom category specification if it exists
            const customCategoryRow = document.getElementById('view-custom-category-row');
            if (customCategory) {
                document.getElementById('view-custom-category').textContent = customCategory;
                customCategoryRow.style.display = 'flex';
            } else {
                customCategoryRow.style.display = 'none';
            }
            
            document.getElementById('view-description').textContent = data.description;
            document.getElementById('view-status').textContent = data.status;
            document.getElementById('view-date').textContent = data.created_at;
            document.getElementById('view-reply').textContent = data.admin_reply || 'No admin note yet';
            
            // Show/hide admin note actions
            const noteActions = document.getElementById('admin-note-actions');
            if (data.admin_reply && data.admin_reply.trim() !== '') {
                noteActions.style.display = 'block';
            } else {
                noteActions.style.display = 'none';
            }
            
            // Display attachments if they exist
            const attachmentsRow = document.getElementById('attachments-row');
            const attachmentsDiv = document.getElementById('view-attachments');
            
            if (data.attachments_array && data.attachments_array.length > 0) {
                attachmentsDiv.innerHTML = '';
                data.attachments_array.forEach((attachment, index) => {
                    if (attachment.trim()) {
                        const attachmentUrl = '../' + attachment.trim();
                        const img = document.createElement('img');
                        img.src = attachmentUrl;
                        img.className = 'complaint-image';
                        img.alt = 'Attachment ' + (index + 1);
                        img.style.cssText = 'max-width: 200px; max-height: 200px; margin: 5px; border-radius: 8px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.1);';
                        img.onclick = function() {
                            window.open(attachmentUrl, '_blank');
                        };
                        attachmentsDiv.appendChild(img);
                    }
                });
                attachmentsRow.style.display = 'flex';
            } else {
                attachmentsRow.style.display = 'none';
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
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete_complaint&id=' + id
        }).then(() => {
            window.location.hash = currentHash;
            location.reload();
        });
    }
}

function viewStudent(id) {
    fetch('get_student.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewStudentModal').style.display = 'block';
            document.getElementById('view_student_id').textContent = data.id;
            document.getElementById('view_student_name').textContent = data.name;
            document.getElementById('view_student_email').textContent = data.email;
            document.getElementById('view_student_password').textContent = data.password || 'N/A';
            
            fetch('get_student_stats.php?id=' + id)
                .then(response => response.json())
                .then(stats => {
                    document.getElementById('view_student_complaints').textContent = stats.complaint_count || 0;
                    if (stats.created_at) {
                        const date = new Date(stats.created_at);
                        document.getElementById('view_student_join_date').textContent = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    } else {
                        document.getElementById('view_student_join_date').textContent = 'N/A';
                    }
                })
                .catch(error => console.error('Error:', error));
        })
        .catch(error => console.error('Error:', error));
}

function editStudent(id) {
    fetch('get_student.php?id=' + id)
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
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete_student&id=' + id
        }).then(() => location.reload());
    }
}

async function approveStudent(id) {
    const body = new URLSearchParams();
    body.set('action', 'approve_student');
    body.set('id', id);
    try {
        await fetch('admin_actions.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body });
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
        await fetch('admin_actions.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body });
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
        await fetch('admin_actions.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body });
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
    if (!ids.length) { alert('Nothing to restore in this tab.'); return; }
    if (!confirm(`Restore all ${type === 'complaints' ? 'deleted complaints' : 'deleted students'}?`)) return;
    const action = type === 'complaints' ? 'restore_complaint' : 'restore_student';
    const bodyFor = id => `action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`;
    const ops = ids.map(id => fetch('admin_actions.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: bodyFor(id) }));
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
    if (!ids.length) { alert('Nothing to delete in this tab.'); return; }
    if (!confirm(`Permanently delete ALL ${type === 'complaints' ? 'deleted complaints' : 'deleted students'}? This cannot be undone!`)) return;
    const action = type === 'complaints' ? 'permanent_delete_complaint' : 'permanent_delete_student';
    const bodyFor = id => `action=${encodeURIComponent(action)}&id=${encodeURIComponent(id)}`;
    const ops = ids.map(id => fetch('admin_actions.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: bodyFor(id) }));
    try {
        await Promise.all(ops);
    } finally {
        window.location.hash = '#trash';
        location.reload();
    }
}

function restoreComplaint(id) {
    if (confirm('Are you sure you want to restore this complaint?')) {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=restore_complaint&id=' + id
        }).then(() => {
            window.location.hash = '#trash';
            location.reload();
        });
    }
}

function restoreStudent(id) {
    if (confirm('Are you sure you want to restore this student and all their complaints?')) {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=restore_student&id=' + id
        }).then(() => {
            window.location.hash = '#trash';
            location.reload();
        });
    }
}

function permanentDeleteComplaint(id) {
    if (confirm('Are you sure you want to permanently delete this complaint? This action cannot be undone!')) {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=permanent_delete_complaint&id=' + id
        }).then(() => {
            window.location.hash = '#trash';
            location.reload();
        });
    }
}

function permanentDeleteStudent(id) {
    if (confirm('Are you sure you want to permanently delete this student and all their complaints? This action cannot be undone!')) {
        fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
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
</script>
</body>
</html>
