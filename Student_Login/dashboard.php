<?php
include('../db.php');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        .filter-container {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .filter-container label {
            margin-right: 10px;
            font-weight: 500;
        }
        .filter-container select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }
        .filter-container button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-container button:hover {
            background: #0056b3;
        }
        .no-results {
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <h2>My Complaints</h2>
        <p class="login-subtitle">View all your submitted complaints</p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message success" style="margin: 20px 0;">
            <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <nav>
        <a href="../file_complaint.php">File a Complaint</a>
        <a href="view_complaints.php">View Complaints</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="filter-container">
        <form method="GET">
            <label>Filter by Status:</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="Denied" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Denied') ? 'selected' : ''; ?>>Denied</option>
                <option value="In Progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                <option value="Resolved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>

    <div class="dashboard-table">
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Description</th>
                <th>Status</th>
                <th>Date Filed</th>
                <th>Attachments</th>
                <th>Admin's Note</th>
                <th>Latest Admin Update</th>
                <th>Actions</th>
            </tr>

            <?php
            $student_id = $_SESSION['student_id'];
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $status = $_GET['status'];
                $stmt = $conn->prepare("SELECT * FROM complaints WHERE student_id = ? AND status = ? AND deleted_at IS NULL ORDER BY created_at DESC");
                $stmt->bind_param("is", $student_id, $status);
            } else {
                $stmt = $conn->prepare("SELECT * FROM complaints WHERE student_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
                $stmt->bind_param("i", $student_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $status_class = strtolower(str_replace(' ', '-', $row['status']));
                    
                    // Handle attachments
                    $attachments_html = 'No attachments';
                    if (!empty($row['attachments'])) {
                        $attachments = explode(',', $row['attachments']);
                        $attachments_html = '<div style="display: flex; gap: 5px; flex-wrap: wrap;">';
                        foreach ($attachments as $attachment) {
                            $attachment = trim($attachment);
                            if (!empty($attachment)) {
                                $attachments_html .= "<a href='../{$attachment}' target='_blank' style='display: inline-block;'>
                                    <img src='../{$attachment}' alt='Attachment' style='width: 40px; height: 40px; object-fit: cover; border-radius: 4px; cursor: pointer;'>
                                </a>";
                            }
                        }
                        $attachments_html .= '</div>';
                    }
                    
                    // Always allow edit
                    $edit_btn = "<button class='btn-edit' onclick='editComplaint({$row['id']})'>Edit</button>";
                    
                    // Parse category display - handle both "Other" and "Others: [custom]"
                    $category = !empty($row['category']) ? $row['category'] : 'Other';
                    $display_category = $category;
                    $custom_category = '';
                    
                    // Handle "Others: [custom]" format - extract custom specification
                    if (!empty($row['category']) && strpos($row['category'], 'Others: ') === 0) {
                        $display_category = 'Other';
                        $custom_category = substr($row['category'], 8);
                    }
                    
                    $category_display = "<span class='category-badge'>{$display_category}</span>";
                    if ($custom_category) {
                        $category_display .= "<br><small style='font-size: 11px; color: #6b7280; font-style: italic;'>($custom_category)</small>";
                    }
                    
                    // Fetch latest admin update (if updates table exists)
                    $latest_update_html = 'No updates yet';
                    $hasUpdatesTbl = $conn->query("SHOW TABLES LIKE 'complaint_updates'");
                    if ($hasUpdatesTbl && $hasUpdatesTbl->num_rows > 0) {
                        $luStmt = $conn->prepare("SELECT status, message, attachments, created_at FROM complaint_updates WHERE complaint_id = ? ORDER BY created_at DESC LIMIT 1");
                        $cid = $row['id'];
                        $luStmt->bind_param("i", $cid);
                        $luStmt->execute();
                        $luRes = $luStmt->get_result();
                        if ($luRes && $luRes->num_rows > 0) {
                            $lu = $luRes->fetch_assoc();
                            $lu_status = htmlspecialchars($lu['status'] ?? '');
                            $lu_msg = htmlspecialchars($lu['message'] ?? '');
                            $lu_date = date('M d, Y H:i', strtotime($lu['created_at']));
                            $links = '';
                            if (!empty($lu['attachments'])) {
                                $files = explode(',', $lu['attachments']);
                                $lnks = [];
                                foreach ($files as $f) {
                                    $f = trim($f);
                                    if ($f) { $lnks[] = "<a href='../{$f}' target='_blank'>file</a>"; }
                                }
                                if (!empty($lnks)) { $links = ' - ' . implode(', ', $lnks); }
                            }
                            $latest_update_html = "<div><strong>{$lu_status}</strong>: {$lu_msg}<br><small>{$lu_date}{$links}</small></div>";
                        }
                        $luStmt->close();
                    }

                    echo "<tr data-complaint-id='{$row['id']}'>
                        <td>{$row['id']}</td>
                        <td>{$row['title']}</td>
                        <td>{$category_display}</td>
                        <td>" . substr($row['description'], 0, 100) . "...</td>
                        <td><span class='status-badge status-$status_class'>{$row['status']}</span></td>
                        <td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>
                        <td>{$attachments_html}</td>
                        <td>" . ($row['admin_reply'] ? $row['admin_reply'] : 'No admin note yet') . "</td>
                        <td>{$latest_update_html}</td>
                        <td>{$edit_btn}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9' class='text-center'>No complaints found. <a href='../file_complaint.php'>File your first complaint</a></td></tr>";
            }
            $stmt->close();
            ?>
        </table>
    </div>
</div>

<!-- Edit Complaint Modal -->
<div id="editComplaintModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editComplaintModal')">&times;</span>
        <h3>Edit Complaint</h3>
        <form method="POST" action="update_complaint.php" enctype="multipart/form-data">
            <input type="hidden" name="complaint_id" id="edit_complaint_id">
            <div class="form-group">
                <label>Title:</label>
                <input type="text" name="title" id="edit_title" required>
            </div>
            <div class="form-group">
                <label>What Category?</label>
                <input type="text" name="what_category" id="edit_category" required placeholder="Enter the category">
            </div>
            <div class="form-group">
                <label>Who are the respondents?:</label>
                <select name="respondents" id="edit_respondents" required>
                    <option value="">Select an option</option>
                    <option value="Facility">Facility</option>
                    <option value="Faculty">Faculty</option>
                    <option value="Administrative">Administrative</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="form-group" id="edit_respondent_detail_group" style="display: none;">
                <label>Please specify:</label>
                <select id="edit_respondent_detail" name="respondent_detail">
                    <option value="">Select an option</option>
                    <option value="Single Person">Single Person</option>
                    <option value="Multiple People">Multiple People</option>
                </select>
            </div>
            <div class="form-group" id="edit_respondent_count_group" style="display: none;">
                <label>How many respondents? (optional):</label>
                <input type="number" name="respondent_count" id="edit_respondent_count" min="1" placeholder="Enter a number or leave blank">
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" id="edit_description" required></textarea>
            </div>
            <div class="form-group">
                <label>Add More Images (Optional):</label>
                <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/gif,image/jpg" />
                <small style="color: var(--text-muted); display: block; margin-top: 5px;">You can select multiple images. Maximum 5MB per file.</small>
            </div>
            <button type="submit">Update Complaint</button>
        </form>
    </div>
</div>

<script>
function editComplaint(id) {
    fetch('get_complaint.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            // Populate edit form
            document.getElementById('edit_complaint_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            
            // Set free-text category
            document.getElementById('edit_category').value = data.category || '';
            
            document.getElementById('edit_description').value = data.description;
            
            // Handle respondents
            if (data.respondents) {
                const respSel = document.getElementById('edit_respondents');
                const detailSel = document.getElementById('edit_respondent_detail');
                if (data.respondents === 'Single Person' || data.respondents === 'Multiple People') {
                    respSel.value = 'Others';
                    document.getElementById('edit_respondent_detail_group').style.display = 'block';
                    detailSel.value = data.respondents;
                } else {
                    respSel.value = data.respondents;
                }
            }
            // Respondent count
            if (data.respondents === 'Multiple People') {
                document.getElementById('edit_respondent_count_group').style.display = 'block';
                if (data.respondent_count) {
                    document.getElementById('edit_respondent_count').value = data.respondent_count;
                } else {
                    document.getElementById('edit_respondent_count').value = '';
                }
            } else {
                document.getElementById('edit_respondent_count_group').style.display = 'none';
                document.getElementById('edit_respondent_count').value = '';
            }
            // Detail selector visibility
            if (document.getElementById('edit_respondents').value !== 'Others') {
                document.getElementById('edit_respondent_detail_group').style.display = 'none';
            }
            
            // Show modal
            document.getElementById('editComplaintModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching complaint:', error);
            alert('Error loading complaint data');
        });
}

// no custom category toggling; free-text now

document.getElementById('edit_respondents').addEventListener('change', function() {
    const grp = document.getElementById('edit_respondent_count_group');
    const input = document.getElementById('edit_respondent_count');
    const detailGroup = document.getElementById('edit_respondent_detail_group');
    const detailSel = document.getElementById('edit_respondent_detail');
    if (this.value === 'Others') {
        detailGroup.style.display = 'block';
    } else {
        detailGroup.style.display = 'none';
        detailSel.value = '';
    }
    const effective = this.value === 'Others' ? detailSel.value : this.value;
    if (effective === 'Multiple People') {
        grp.style.display = 'block';
    } else {
        grp.style.display = 'none';
        input.value = '';
    }
});

document.getElementById('edit_respondent_detail').addEventListener('change', function() {
    const grp = document.getElementById('edit_respondent_count_group');
    const input = document.getElementById('edit_respondent_count');
    if (this.value === 'Multiple People') {
        grp.style.display = 'block';
    } else {
        grp.style.display = 'none';
        input.value = '';
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = ['editComplaintModal'];
    modals.forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>
</body>
</html>
