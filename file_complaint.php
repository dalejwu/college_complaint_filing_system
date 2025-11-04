<?php 
include('db.php'); 
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
        // If user chose Others for respondents, use the detail choice when provided
        if ($respondents === 'Others' && !empty($respondent_detail)) {
            $respondents = $respondent_detail;
        }
        
        // category now free-text
        
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
                        
                        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
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

            // Backward-compatible insert depending on available columns
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
    <title>File Complaint</title>
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        .confirmation-details {
            margin: 20px 0;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-light);
        }
        .detail-item {
            margin: 12px 0;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-item strong {
            color: var(--text-primary);
            display: inline-block;
            min-width: 100px;
            font-weight: 600;
            font-size: 14px;
        }
        .detail-item span {
            color: var(--text-secondary);
            flex: 1;
            line-height: 1.5;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid var(--bg-secondary);
        }
        .btn-cancel {
            padding: 12px 24px;
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .btn-cancel::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .btn-cancel:hover::before {
            left: 100%;
        }
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        .btn-confirm {
            padding: 12px 24px;
            background: var(--bg-gradient);
            color: var(--text-on-primary);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .btn-confirm::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .btn-confirm:hover::before {
            left: 100%;
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="form-container">
        <h2>File a Complaint</h2>
        
        <a href="Student_Login/dashboard.php" class="return-btn" style="display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: linear-gradient(135deg, #6c757d, #5a6268); color: white; text-decoration: none; border-radius: 12px; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">← Return to Dashboard</a>
        
        <?php if (isset($error_msg)): ?>
            <div class='message error'><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_msg)): ?>
            <div class='message success'><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        
        <form id="complaintForm" action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required placeholder="Enter complaint title">
            </div>

            <div class="form-group">
                <label for="what_category">What Category?</label>
                <input type="text" id="what_category" name="what_category" required placeholder="Enter the category">
            </div>

            <div class="form-group">
                <label for="respondents">Who are the respondents?</label>
                <select id="respondents" name="respondents" required>
                    <option value="">Select an option</option>
                    <option value="Facility">Facility</option>
                    <option value="Faculty">Faculty</option>
                    <option value="Administrative">Administrative</option>
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
                <input type="number" id="respondent_count" name="respondent_count" min="1" placeholder="Enter a number or leave blank">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required placeholder="Please provide detailed description of your complaint"></textarea>
            </div>

            <div class="form-group">
                <label for="attachments">Attach Images (Optional)</label>
                <input type="file" id="attachments" name="attachments[]" multiple accept="image/jpeg,image/png,image/gif,image/jpg" />
                <small style="color: var(--text-muted); display: block; margin-top: 5px;">You can select multiple images. Maximum 5MB per file. Accepted formats: JPEG, PNG, GIF</small>
            </div>

            <button type="submit" name="submit" onclick="return confirmSubmission()" id="submitBtn">Submit Complaint</button>
        </form>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="confirmationModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close" onclick="closeConfirmationModal()">&times;</span>
        <h3 style="color: var(--primary-color); font-size: 24px; font-weight: 700; margin-bottom: 20px;">Confirm Complaint Submission</h3>
        <div class="confirmation-details">
            <p><strong>Please review your complaint details:</strong></p>
            <div class="detail-item">
                <strong>Title:</strong> <span id="confirm-title"></span>
            </div>
            <div class="detail-item">
                <strong>Category:</strong> <span id="confirm-category"></span>
            </div>
            <div class="detail-item">
                <strong>Description:</strong> <span id="confirm-description"></span>
            </div>
            <div class="detail-item">
                <strong>Respondents:</strong> <span id="confirm-respondents"></span>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeConfirmationModal()">Cancel</button>
            <button type="button" class="btn-confirm" onclick="submitComplaint()">Submit Complaint</button>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Complaint';
    }
});
function confirmSubmission() {
    const title = document.getElementById('title').value;
    const category = document.getElementById('what_category').value.trim();
    const description = document.getElementById('description').value.trim();
    const respondentsSel = document.getElementById('respondents').value;
    const respondentDetailEl = document.getElementById('respondent_detail');
    const respondents = respondentsSel === 'Others' && respondentDetailEl ? respondentDetailEl.value : respondentsSel;
    
    if (!title || !category || !description || !respondents) {
        alert('Please fill in all required fields before submitting.');
        return false;
    }
    
    // Populate the confirmation modal with form data
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-category').textContent = category;
    document.getElementById('confirm-description').textContent = description.length > 100 ? description.substring(0, 100) + '...' : description;
    document.getElementById('confirm-respondents').textContent = respondents;
    
    // Show the custom modal
    document.getElementById('confirmationModal').style.display = 'block';
    
    // Prevent form submission for now
    return false;
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
}

function submitComplaint() {
    // Close the modal
    closeConfirmationModal();
    
    // Submit the form (ensure native submit path)
    const form = document.getElementById('complaintForm');
    // add a hidden marker in case any server logic checks for it
    let marker = document.getElementById('confirmed_via_modal');
    if (!marker) {
        marker = document.createElement('input');
        marker.type = 'hidden';
        marker.name = 'confirmed_via_modal';
        marker.id = 'confirmed_via_modal';
        marker.value = '1';
        form.appendChild(marker);
    }
    if (typeof form.requestSubmit === 'function') {
        const submitBtn = document.getElementById('submitBtn');
        form.requestSubmit(submitBtn || undefined);
    } else {
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        const errorMessage = document.querySelector('.message.error');
        if (errorMessage) {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Complaint';
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.style.borderColor = '#dc3545';
                this.style.backgroundColor = '#fff5f5';
            } else {
                this.style.borderColor = '#E9ECEF';
                this.style.backgroundColor = '#FFFFFF';
            }
        });
        
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f8fff8';
            } else {
                this.style.borderColor = '#E9ECEF';
                this.style.backgroundColor = '#FFFFFF';
            }
        });
    });
});

<?php if (isset($form_reset) && $form_reset): ?>
document.querySelector('form').reset();
<?php endif; ?>

function resetButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Complaint';
    }
}

function toggleCustomCategory() {
    // removed category select
}

function updateRespondentConditionalUI() {
    const respondentsSel = document.getElementById('respondents');
    const detailGroup = document.getElementById('respondent_detail_group');
    const detailSel = document.getElementById('respondent_detail');
    const countGroup = document.getElementById('respondent_count_group');
    const countInput = document.getElementById('respondent_count');

    // Show detail selector only when Others chosen
    if (respondentsSel.value === 'Others') {
        detailGroup.style.display = 'block';
    } else {
        detailGroup.style.display = 'none';
        detailSel.value = '';
    }
    // Show count when Multiple People selected directly or via detail
    const effective = respondentsSel.value === 'Others' ? detailSel.value : respondentsSel.value;
    if (effective === 'Multiple People') {
        countGroup.style.display = 'block';
    } else {
        countGroup.style.display = 'none';
        countInput.value = '';
    }
}

document.getElementById('respondents').addEventListener('change', updateRespondentConditionalUI);
document.getElementById('respondent_detail').addEventListener('change', updateRespondentConditionalUI);
// Initialize on load
updateRespondentConditionalUI();

window.addEventListener('load', function() {
    resetButton();
});

setTimeout(resetButton, 100);
setTimeout(resetButton, 500);
setTimeout(resetButton, 1000);

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('confirmationModal');
    if (event.target === modal) {
        closeConfirmationModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeConfirmationModal();
    }
});
</script>
</body>
</html>
