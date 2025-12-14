<?php
include('../includes/db.php');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../Login/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - Student Dashboard</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Hero Header with Nav -->
        <!-- Admin-Style Header -->
        <div class="dashboard-header">
            <div>
                <h2>My Complaints</h2>
                <p class="login-subtitle">View and manage your entire complaint history.</p>
            </div>

            <div class="admin-actions">
                <a href="dashboard.php" class="admin-btn">Dashboard</a>
                <a href="profile.php" class="admin-btn">Profile</a>
                <a href="actions/logout.php" class="admin-btn danger">Logout</a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; background: white; padding: 16px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="font-weight: 600; color: var(--text-primary); font-size: 1.1rem;">All Complaints</div>
            <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; width: auto;">
                <div style="position: relative;">
                    <select name="status" onchange="this.form.submit()" style="padding: 10px 36px 10px 16px; border-radius: 8px; border: 1px solid var(--border-color); font-family: 'Inter'; background: var(--bg-primary); appearance: none; cursor: pointer; color: var(--text-primary); font-weight: 500;">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Resolved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted);">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </div>
                <?php if (isset($_GET['status']) && $_GET['status'] != ''): ?>
                    <a href="view_complaints.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 500; padding: 8px 12px; border-radius: 6px; background: #f3f4f6; transition: background 0.2s;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="dashboard-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Admin Note</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                        while ($row = $result->fetch_assoc()) {
                            $status_class = strtolower(str_replace(' ', '-', $row['status']));
                            $date = date('M d, Y', strtotime($row['created_at']));

                            // Category Logic
                            $category = !empty($row['category']) ? $row['category'] : 'Other';
                            $display_category = $category;
                            $custom_category = '';
                            if (strpos($category, 'Others: ') === 0) {
                                $display_category = 'Other';
                                $custom_category = substr($category, 8);
                            }
                            $category_html = htmlspecialchars($display_category);
                            if ($custom_category) {
                                $category_html .= "<br><small style='color: var(--text-muted); font-size: 0.8rem;'>(" . htmlspecialchars($custom_category) . ")</small>";
                            }

                            echo "<tr>
                        <td><span style='color: var(--text-muted); font-weight: 500;'>#{$row['id']}</span></td>
                        <td><span style='font-weight: 600; color: var(--text-primary);'>" . htmlspecialchars($row['title']) . "</span></td>
                        <td>{$category_html}</td>
                        <td><span class='status-badge status-$status_class'>" . htmlspecialchars($row['status']) . "</span></td>
                        <td style='color: var(--text-secondary);'>{$date}</td>
                        <td style='max-width: 250px;' class='truncate' title='" . htmlspecialchars($row['admin_reply'] ?? '') . "'>" . htmlspecialchars($row['admin_reply'] ?? '—') . "</td>
                        <td>
                            <div class='action-buttons'>
                                <button class='action-icon-btn btn-view' onclick='editComplaint({$row['id']})' title='Edit Complaint'>
                                    <svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                        <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
                                        <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center; padding: 60px 20px;'>
                            <div style='color: var(--text-light); margin-bottom: 16px;'>
                                <svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'></circle><line x1='12' y1='8' x2='12' y2='12'></line><line x1='12' y1='16' x2='12.01' y2='16'></line></svg>
                            </div>
                            <p style='color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 16px;'>No complaints found.</p>
                            <a href='../file_complaint.php' class='btn-primary' style='display: inline-flex; align-items: center; gap: 8px;'>
                                <svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg>
                                File Your First Complaint
                            </a>
                        </td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- View/Edit Complaint Modal (Ticket Style) -->
        <div id="editComplaintModal" class="modal">
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
                    <span class="close" onclick="closeModal('editComplaintModal')" style="font-size: 28px; color: #9ca3af; cursor: pointer; line-height: 24px; transition: color 0.2s;">&times;</span>
                </div>

                <div style="padding: 30px; max-height: 70vh; overflow-y: auto;">
                    <form method="POST" action="actions/update_complaint.php" enctype="multipart/form-data">
                        <input type="hidden" name="complaint_id" id="edit_complaint_id">

                        <!-- Status Banner -->
                        <div style="background: var(--bg-primary); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 4px;">Current Status</div>
                                <div id="view_status_badge">
                                    <span class="status-badge status-pending">Pending</span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 4px;">Category</div>
                                <div id="view_category_display" style="font-weight: 600; color: var(--text-primary);">Academic</div>
                            </div>
                        </div>

                        <!-- Main Content Fields -->
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Subject</label>
                            <input type="text" name="title" id="edit_title" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--bg-primary); background: var(--bg-primary); border-radius: 8px; font-weight: 500; font-family: inherit; transition: all 0.2s;">
                        </div>

                        <!-- Respondents Section -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                            <div class="form-group">
                                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Respondent Group</label>
                                <select name="respondents" id="edit_respondents" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--bg-primary); background: var(--bg-primary); border-radius: 8px; font-family: inherit;">
                                    <option value="">Select an option</option>
                                    <option value="Facility">Facility</option>
                                    <option value="Faculty">Faculty</option>
                                    <option value="Administrative">Administrative</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <!-- Hidden category input just to keep form structure valid if JS hides it -->
                            <div class="form-group">
                                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Category</label>
                                <input type="text" name="category" id="edit_category" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--bg-primary); background: var(--bg-primary); border-radius: 8px; font-family: inherit;">
                            </div>
                        </div>

                        <!-- Dynamic Respondent Fields -->
                        <div class="form-group" id="edit_respondent_detail_group" style="display:none; margin-bottom: 24px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Specifics</label>
                            <select name="respondent_detail" id="edit_respondent_detail" style="width: 100%; padding: 12px 16px; border: 2px solid var(--bg-primary); background: var(--bg-primary); border-radius: 8px; font-family: inherit;">
                                <option value="">Select an option</option>
                                <option value="Single Person">Single Person</option>
                                <option value="Multiple People">Multiple People</option>
                            </select>
                        </div>
                        <div class="form-group" id="edit_respondent_count_group" style="display: none; margin-bottom: 24px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Count</label>
                            <input type="number" name="respondent_count" id="edit_respondent_count" min="1" style="width: 100%; padding: 12px 16px; border: 2px solid var(--bg-primary); background: var(--bg-primary); border-radius: 8px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">Description</label>
                            <textarea name="description" id="edit_description" required style="width: 100%; padding: 16px; border: 2px solid var(--bg-primary); background: var(--bg-primary); border-radius: 8px; min-height: 120px; font-family: inherit; line-height: 1.6; resize: vertical;"></textarea>
                        </div>

                        <!-- Admin Reply Box (Read Only) -->
                        <div id="admin_reply_section" style="margin-bottom: 24px; display: none;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--primary-color); font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                Admin Response
                            </label>
                            <div id="view_admin_reply" style="background: var(--primary-lightest); border: 1px solid var(--border-color); padding: 16px; border-radius: 8px; color: var(--primary-color); line-height: 1.6;">
                                <!-- content populated by js -->
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 12px; color: var(--text-primary); font-size: 0.95rem;">Attachments</label>

                            <!-- Existing -->
                            <div id="edit_existing_attachments" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 12px; margin-bottom: 16px;"></div>
                            <div id="deleted_attachments_container"></div>

                            <!-- Add New -->
                            <label for="new_attachments_btn" style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px; color: var(--primary-color); font-weight: 500; font-size: 0.9rem; padding: 8px 12px; background: var(--primary-lightest); border-radius: 6px; transition: background 0.2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                </svg>
                                Upload New Files
                            </label>
                            <input type="file" id="new_attachments_btn" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx" style="display: none;" onchange="this.previousElementSibling.innerHTML = this.files.length + ' file(s) selected'">
                        </div>

                        <div style="text-align: right; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                            <button type="submit" id="btn_update_complaint" class="hero-cta" style="border: none; cursor: pointer; box-shadow: var(--shadow-colored); background: var(--bg-gradient);">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div id="imageModal" class="modal">
            <div class="modal-content" style="max-width: 800px; text-align: center;">
                <span class="close" onclick="closeModal('imageModal')">&times;</span>
                <img id="img01" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">
            </div>
        </div>

        <script>
            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            function openImageModal(src) {
                const modal = document.getElementById('imageModal');
                const modalImg = document.getElementById('img01');
                modal.style.display = "block";
                modalImg.src = src;
            }

            // Using the same JS logic as before, just ensuring paths are correct
            function editComplaint(id) {
                fetch('actions/get_complaint.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('edit_complaint_id').value = data.id;
                        document.getElementById('edit_title').value = data.title;
                        document.getElementById('edit_category').value = data.category;
                        document.getElementById('edit_description').value = data.description;
                        document.getElementById('edit_respondents').value = data.respondents;

                        // New Ticket View Fields
                        document.getElementById('view_id_display').innerText = '#' + data.id;
                        document.getElementById('view_date_display').innerText = new Date(data.created_at).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });
                        document.getElementById('view_category_display').innerText = data.category;

                        // Status Badge
                        const statusClass = data.status.toLowerCase().replace(' ', '-');
                        document.getElementById('view_status_badge').innerHTML = `<span class="status-badge status-${statusClass}">${data.status}</span>`;

                        // Admin Reply
                        const adminSection = document.getElementById('admin_reply_section');
                        if (data.admin_reply && data.admin_reply.trim() !== "") {
                            adminSection.style.display = 'block';
                            document.getElementById('view_admin_reply').innerText = data.admin_reply;
                        } else {
                            adminSection.style.display = 'none';
                        }

                        // Reset UI elements
                        updateEditRespondentUI(); // Apply initial state based on value

                        // Handle Details
                        const detailSelect = document.getElementById('edit_respondent_detail');
                        const countInput = document.getElementById('edit_respondent_count');

                        // Simple logic to attempt restore - in a real scenario we'd need these fields from DB
                        // For now we map best effort based on the "respondents" string or if we had extra columns

                        if (data.respondent_count) countInput.value = data.respondent_count;

                        // Attachments
                        const attachmentsContainer = document.getElementById('edit_existing_attachments');
                        const deletedContainer = document.getElementById('deleted_attachments_container');
                        attachmentsContainer.innerHTML = '';
                        deletedContainer.innerHTML = '';

                        if (data.attachments) {
                            const files = data.attachments.split(',');
                            if (files.length > 0 && files[0] !== "") {
                                files.forEach((file, index) => {
                                    const div = document.createElement('div');
                                    div.style.position = 'relative';
                                    div.style.display = 'inline-block';

                                    const ext = file.split('.').pop().toLowerCase();
                                    let contentElement;

                                    if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                                        contentElement = document.createElement('img');
                                        contentElement.src = '../' + file;
                                        contentElement.style = 'width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;';
                                        contentElement.onclick = () => openImageModal('../' + file);
                                    } else {
                                        contentElement = document.createElement('a');
                                        contentElement.href = '../' + file;
                                        contentElement.target = '_blank';
                                        contentElement.style = 'display:inline-block; width: 60px; height: 60px; background:#f0f0f0; border-radius:4px; border:1px solid #ddd; text-align:center; line-height:60px; font-size:24px; text-decoration:none;';
                                        contentElement.innerHTML = '📄';
                                    }

                                    const delBtn = document.createElement('span');
                                    delBtn.innerHTML = '&times;';
                                    delBtn.style = 'position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 18px; height: 18px; text-align: center; line-height: 16px; font-size: 14px; cursor: pointer;';
                                    delBtn.onclick = () => markAttachmentForDeletion(file, div);

                                    div.appendChild(contentElement);
                                    div.appendChild(delBtn);
                                    attachmentsContainer.appendChild(div);
                                });
                            }
                        }

                        updateEditRespondentUI(); // Run again to ensure correct visibility
                        document.getElementById('editComplaintModal').style.display = 'block';
                    })
                    .catch(error => console.error('Error:', error));
            }

            function updateEditRespondentUI() {
                const mainSelect = document.getElementById('edit_respondents');
                const detailGroup = document.getElementById('edit_respondent_detail_group');
                const countGroup = document.getElementById('edit_respondent_count_group');
                const detailSelect = document.getElementById('edit_respondent_detail');

                if (mainSelect.value === 'Others') {
                    detailGroup.style.display = 'block';
                } else {
                    detailGroup.style.display = 'none';
                }

                if (mainSelect.value === 'Others' && detailSelect.value === 'Multiple People') {
                    countGroup.style.display = 'block';
                } else {
                    countGroup.style.display = 'none';
                }
            }

            document.getElementById('edit_respondents').addEventListener('change', updateEditRespondentUI);
            document.getElementById('edit_respondent_detail').addEventListener('change', updateEditRespondentUI);

            function markAttachmentForDeletion(filename, element) {
                element.remove();
                const container = document.getElementById('deleted_attachments_container');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'deleted_attachments[]';
                input.value = filename;
                container.appendChild(input);
            }

            window.onclick = function(event) {
                const modal = document.getElementById('editComplaintModal');
                const imageModal = document.getElementById('imageModal');
                if (event.target == modal) modal.style.display = 'none';
                if (event.target == imageModal) imageModal.style.display = 'none';
            }
        </script>
    </div>
</body>

</html>