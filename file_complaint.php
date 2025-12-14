<?php
include('includes/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['student_id'])) {
        $error_msg = "You must be logged in to file a complaint.";
    } else {
        $student_id = $_SESSION['student_id'];
        $title = trim($_POST['title']);
        $category = trim($_POST['what_category'] ?? '');
        $description = trim($_POST['description']);
        $respondents = trim($_POST['respondents'] ?? '');
        $respondent_detail = trim($_POST['respondent_detail'] ?? '');
        $respondent_count = isset($_POST['respondent_count']) && $_POST['respondent_count'] !== '' ? intval($_POST['respondent_count']) : NULL;

        if ($respondents === 'Others' && !empty($respondent_detail)) {
            $respondents = $respondent_detail;
        }

        if (empty($title) || empty($category) || empty($description) || empty($respondents)) {
            $error_msg = "Please fill in all required fields.";
        } else {
            $uploaded_files = array();
            $upload_dir = 'uploads/';

            if (!empty($_FILES['attachments']['name'][0])) {
                foreach ($_FILES['attachments']['name'] as $key => $file_name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['attachments']['tmp_name'][$key];
                        $file_size = $_FILES['attachments']['size'][$key];
                        $file_type = $_FILES['attachments']['type'][$key];

                        $allowed_types = array(
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/jpg',
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        );
                        if (in_array($file_type, $allowed_types)) {
                            if ($file_size <= 5000000) {
                                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                                $new_filename = uniqid() . '_' . time() . '_' . $key . '.' . $file_ext;
                                $upload_path = $upload_dir . $new_filename;

                                if (move_uploaded_file($file_tmp, $upload_path)) {
                                    $uploaded_files[] = $upload_path;
                                }
                            }
                        }
                    }
                }
            }

            $attachments = !empty($uploaded_files) ? implode(',', $uploaded_files) : null;

            // Check columns
            $hasRespondentsCol = $conn->query("SHOW COLUMNS FROM complaints LIKE 'respondents'");
            $hasRespondentCountCol = $conn->query("SHOW COLUMNS FROM complaints LIKE 'respondent_count'");
            $hasRespondents = $hasRespondentsCol && $hasRespondentsCol->num_rows > 0;
            $hasRespondentCount = $hasRespondentCountCol && $hasRespondentCountCol->num_rows > 0;

            if ($hasRespondents && $hasRespondentCount) {
                $stmt = $conn->prepare("INSERT INTO complaints (student_id, title, category, description, respondents, respondent_count, attachments) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssis", $student_id, $title, $category, $description, $respondents, $respondent_count, $attachments);
            } elseif ($hasRespondents) {
                $stmt = $conn->prepare("INSERT INTO complaints (student_id, title, category, description, respondents, attachments) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $student_id, $title, $category, $description, $respondents, $attachments);
            } else {
                $stmt = $conn->prepare("INSERT INTO complaints (student_id, title, category, description, attachments) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $student_id, $title, $category, $description, $attachments);
            }

            if ($stmt->execute()) {
                $success_msg = "Complaint filed successfully!";
                $form_reset = true;
            } else {
                $error_msg = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Complaint - Student Dashboard</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #f3f4f6;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #374151;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
            /* Important for padding */
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .submit-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        /* Modal Styles Reuse */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 0;
            border: none;
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: modalSlideIn 0.3s ease-out;
        }

        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            margin: 15px 20px 0 0;
        }

        .close:hover {
            color: #000;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Hero Header with Nav -->
        <!-- Admin-Style Header -->
        <div class="dashboard-header">
            <div>
                <h2>File a Complaint</h2>
                <p class="login-subtitle">Submit a new concern to the administration.</p>
            </div>

            <div class="admin-actions">
                <a href="Student/dashboard.php" class="admin-btn">Dashboard</a>
                <a href="Student/profile.php" class="admin-btn">Profile</a>
                <a href="Student/actions/logout.php" class="admin-btn danger">Logout</a>
            </div>
        </div>

        <div class="form-card">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.8rem; color: #111827;">File a New Complaint</h2>
            <p style="color: #6b7280; margin-bottom: 30px; line-height: 1.6;">Please fill out the form below with specific details. Your complaint will be reviewed by the administration.</p>

            <?php if (isset($error_msg)): ?>
                <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #fecaca;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success_msg)): ?>
                <div style="background: #d1fae5; color: #047857; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #a7f3d0;">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <form id="complaintForm" action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required placeholder="Brief summary of the issue">
                </div>

                <div class="form-group">
                    <label for="what_category">Category</label>
                    <input type="text" id="what_category" name="what_category" required placeholder="e.g., Facilities, Grading, Harassment">
                </div>

                <div class="form-group">
                    <label for="respondents">Who are the respondents?</label>
                    <select id="respondents" name="respondents" required>
                        <option value="">Select an option</option>
                        <option value="Facility">Facility (e.g., Maintenance, Security)</option>
                        <option value="Faculty">Faculty (e.g., Professors, Instructors)</option>
                        <option value="Administrative">Administrative Office</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <div class="form-group" id="respondent_detail_group" style="display:none;">
                    <label for="respondent_detail">Please specify:</label>
                    <select id="respondent_detail" name="respondent_detail">
                        <option value="">Select an option</option>
                        <option value="Single Person">Single Person</option>
                        <option value="Multiple People">Multiple People</option>
                    </select>
                </div>

                <div class="form-group" id="respondent_count_group" style="display: none;">
                    <label for="respondent_count">How many respondents? (optional)</label>
                    <input type="number" id="respondent_count" name="respondent_count" min="1" placeholder="Enter a number">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required placeholder="Provide full details of your complaint..." style="min-height: 150px; resize: vertical;"></textarea>
                </div>

                <div class="form-group">
                    <label>Attachments (Optional)</label>
                    <div style="border: 2px dashed #d1d5db; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer; background: #f9fafb;" onclick="document.getElementById('attachments').click()">
                        <span style="color: #6b7280; display: block; margin-bottom: 10px;">Click to upload files</span>
                        <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx" style="display: none;" onchange="updateFileName(this)" />
                        <small style="color: #9ca3af;">Allowed: Images, PDF, Word Docs (Max 5MB)</small>
                    </div>
                    <div id="file-name-display" style="margin-top: 10px; color: #4b5563; font-size: 0.9em;"></div>
                </div>

                <button type="submit" name="submit" onclick="return confirmSubmission()" id="submitBtn" class="submit-btn">Submit Complaint</button>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; color: #111827;">Confirm Submission</h3>
            </div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 15px;">
                    <strong style="display: block; color: #6b7280; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px;">Title</strong>
                    <span id="confirm-title" style="color: #111827; font-weight: 500;"></span>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong style="display: block; color: #6b7280; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px;">Category</strong>
                    <span id="confirm-category" style="color: #111827; font-weight: 500;"></span>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong style="display: block; color: #6b7280; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px;">Description</strong>
                    <span id="confirm-description" style="color: #374151; line-height: 1.5;"></span>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" onclick="closeConfirmationModal()" style="flex: 1; padding: 12px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 8px; font-weight: 600; cursor: pointer;">Edit</button>
                    <button type="button" onclick="submitComplaint()" style="flex: 1; padding: 12px; border: none; background: #4f46e5; color: white; border-radius: 8px; font-weight: 600; cursor: pointer;">Confirm & Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const display = document.getElementById('file-name-display');
            if (input.files.length > 0) {
                display.textContent = input.files.length + ' file(s) selected';
            } else {
                display.textContent = '';
            }
        }

        function confirmSubmission() {
            const title = document.getElementById('title').value;
            const category = document.getElementById('what_category').value;
            const description = document.getElementById('description').value;
            const respondents = document.getElementById('respondents').value;

            if (!title || !category || !description || !respondents) {
                return true; // Let browser validation handle it
            }

            document.getElementById('confirm-title').textContent = title;
            document.getElementById('confirm-category').textContent = category;
            document.getElementById('confirm-description').textContent = description.substring(0, 150) + (description.length > 150 ? '...' : '');

            document.getElementById('confirmationModal').style.display = 'block';
            return false;
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        function submitComplaint() {
            closeConfirmationModal();
            const form = document.getElementById('complaintForm');
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.removeAttribute('onclick'); // Prevent duplicate checks

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitBtn);
            } else {
                form.submit();
            }
        }

        function updateRespondentConditionalUI() {
            const respondentsSel = document.getElementById('respondents');
            const detailGroup = document.getElementById('respondent_detail_group');
            const countGroup = document.getElementById('respondent_count_group');

            if (respondentsSel.value === 'Others') {
                detailGroup.style.display = 'block';
            } else {
                detailGroup.style.display = 'none';
            }

            const detailSel = document.getElementById('respondent_detail');
            const effective = respondentsSel.value === 'Others' ? detailSel.value : respondentsSel.value;

            if (effective === 'Multiple People') {
                countGroup.style.display = 'block';
            } else {
                countGroup.style.display = 'none';
            }
        }

        document.getElementById('respondents').addEventListener('change', updateRespondentConditionalUI);
        document.getElementById('respondent_detail').addEventListener('change', updateRespondentConditionalUI);
        updateRespondentConditionalUI();

        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target == modal) {
                closeConfirmationModal();
            }
        }
    </script>
</body>

</html>