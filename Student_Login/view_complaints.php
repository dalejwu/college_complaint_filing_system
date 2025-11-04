<?php
include('../db.php');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Complaints</title>
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

    <nav>
        <a href="../file_complaint.php">File a Complaint</a>
        <a href="view_complaints.php">View Complaints</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="filter-container">
        <form method="GET" action="" style="display: inline-block;">
            <label for="status_filter">Filter by Status:</label>
            <select name="status" id="status_filter">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="In Progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                <option value="Resolved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
            </select>
            <button type="submit">Filter</button>
            <?php if (isset($_GET['status'])): ?>
                <a href="view_complaints.php" style="margin-left: 10px; color: #dc3545; text-decoration: none;">Clear Filter</a>
            <?php endif; ?>
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
                    
                    echo "<tr data-complaint-id='{$row['id']}'>
                        <td>{$row['id']}</td>
                        <td>{$row['title']}</td>
                        <td>{$category_display}</td>
                        <td>" . substr($row['description'], 0, 100) . "...</td>
                        <td><span class='status-badge status-$status_class'>{$row['status']}</span></td>
                        <td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>
                        <td>{$attachments_html}</td>
                        <td>" . ($row['admin_reply'] ? $row['admin_reply'] : 'No admin note yet') . "</td>
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
                    <label>Category:</label>
                    <select name="category" id="edit_category" required onchange="toggleEditCustomCategory()">
                        <option value="">Select a category</option>
                        <option value="Facility">Facility</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Administrative">Administrative</option>
                        <option value="Other">Others (please specify)</option>
                    </select>
                </div>
                <div class="form-group" id="edit_custom_category_group" style="display: none;">
                    <label>Please specify category:</label>
                    <input type="text" id="edit_custom_category" name="custom_category" placeholder="Enter custom category">
                </div>
                <div class="form-group">
                    <label>Who are the respondents?:</label>
                    <select name="respondents" id="edit_respondents" required>
                        <option value="">Select an option</option>
                        <option value="Single Person">Single Person</option>
                        <option value="Multiple People">Multiple People</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" id="edit_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Attach New Images (Optional):</label>
                    <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/gif,image/jpg" />
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">You can select multiple images. Maximum 5MB per file.</small>
                </div>
                <button type="submit">Update Complaint</button>
            </form>
        </div>
    </div>

<script>
// Close modal function
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Edit complaint function
function editComplaint(id) {
    // Fetch complaint data
    fetch('get_complaint.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            // Populate edit form
            document.getElementById('edit_complaint_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            
            // Handle category
            let category = data.category;
            if (category && category.startsWith('Others: ')) {
                category = category.substring(8);
                document.getElementById('edit_category').value = 'Other';
                document.getElementById('edit_custom_category').value = category;
                document.getElementById('edit_custom_category_group').style.display = 'block';
            } else {
                document.getElementById('edit_category').value = data.category;
                document.getElementById('edit_custom_category_group').style.display = 'none';
            }
            
            document.getElementById('edit_description').value = data.description;
            
            // Handle respondents
            if (data.respondents) {
                document.getElementById('edit_respondents').value = data.respondents;
            }
            
            // Show modal
            document.getElementById('editComplaintModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching complaint:', error);
            alert('Error loading complaint data');
        });
}

function toggleEditCustomCategory() {
    const category = document.getElementById('edit_category').value;
    const customCategoryGroup = document.getElementById('edit_custom_category_group');
    const customCategory = document.getElementById('edit_custom_category');
    
    if (category === 'Other') {
        customCategoryGroup.style.display = 'block';
        customCategory.setAttribute('required', 'required');
    } else {
        customCategoryGroup.style.display = 'none';
        customCategory.removeAttribute('required');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = ['editComplaintModal'];
    modals.forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}
</script>

</div>
</body>
</html>
