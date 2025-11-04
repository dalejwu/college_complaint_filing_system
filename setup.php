<?php
include('db.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;}.warning{color:orange;}.info{color:#666;margin:10px 0;}";
echo "ul{list-style-type:none;padding-left:0;}li{margin:5px 0;padding:5px;background:#f5f5f5;}</style></head><body>";
echo "<h1>Database Setup & Migration</h1>";

$errors = [];
$success = [];
$warnings = [];

// Helper function to execute SQL query safely
function executeQuery($conn, $query, $ignoreErrors = []) {
    $query = trim($query);
    if (empty($query)) {
        return true;
    }
    
    if ($conn->query($query)) {
        return true;
    } else {
        $error = $conn->error;
        foreach ($ignoreErrors as $ignore) {
            if (strpos($error, $ignore) !== false) {
                return true; // Ignore this error
            }
        }
        return $error;
    }
}

// Step 1: Import base SQL file
$sqlFile = __DIR__ . '/college_complaint_system.sql';
if (file_exists($sqlFile)) {
    echo "<h2>Step 1: Importing Base Database Structure</h2>";
    
    $sql = file_get_contents($sqlFile);
    
    // Remove MySQL-specific comments and commands
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);
    $sql = preg_replace('/SET SQL_MODE.*?;/i', '', $sql);
    $sql = preg_replace('/START TRANSACTION.*?;/i', '', $sql);
    $sql = preg_replace('/COMMIT.*?;/i', '', $sql);
    $sql = preg_replace('/SET time_zone.*?;/i', '', $sql);
    $sql = preg_replace('/SET @OLD.*?;/i', '', $sql);
    $sql = preg_replace('/SET NAMES.*?;/i', '', $sql);
    $sql = preg_replace('/\/\*!.*?\*\//s', '', $sql);
    
    // Better query splitting - handle multi-line queries
    $queries = [];
    $currentQuery = '';
    
    // Split by semicolon but handle it better
    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || preg_match('/^--/', $line)) {
            continue;
        }
        $currentQuery .= $line . " ";
        if (substr(rtrim($line), -1) === ';') {
            $queries[] = trim($currentQuery);
            $currentQuery = '';
        }
    }
    
    // Also try splitting by semicolon as fallback
    if (empty($queries)) {
        $queries = array_filter(array_map('trim', explode(';', $sql)));
    }
    
    $executed = 0;
    $createTableQueries = [];
    $alterTableQueries = [];
    $insertQueries = [];
    
    // Categorize queries
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || strlen($query) < 10) {
            continue;
        }
        
        $upperQuery = strtoupper($query);
        if (strpos($upperQuery, 'CREATE TABLE') === 0) {
            $createTableQueries[] = $query;
        } elseif (strpos($upperQuery, 'ALTER TABLE') === 0) {
            $alterTableQueries[] = $query;
        } elseif (strpos($upperQuery, 'INSERT INTO') === 0) {
            $insertQueries[] = $query;
        } else {
            $createTableQueries[] = $query; // Assume it's a table creation
        }
    }
    
    // Execute CREATE TABLE queries first
    foreach ($createTableQueries as $query) {
        $result = executeQuery($conn, $query, ['already exists', 'Duplicate', 'Unknown table']);
        if ($result === true) {
            $executed++;
        } elseif (is_string($result)) {
            $errors[] = "CREATE TABLE error: " . $result . " (Query: " . substr($query, 0, 80) . "...)";
        }
    }
    
    // Execute ALTER TABLE queries (indexes, auto_increment, constraints)
    foreach ($alterTableQueries as $query) {
        $result = executeQuery($conn, $query, ['already exists', 'Duplicate', 'Unknown table', 'Duplicate key']);
        if ($result === true) {
            $executed++;
        } elseif (is_string($result)) {
            // Some ALTER errors might be okay (like if constraint already exists)
            if (strpos($result, 'Duplicate foreign key') === false) {
                $warnings[] = "ALTER TABLE: " . $result;
            }
        }
    }
    
    // Execute INSERT queries last
    foreach ($insertQueries as $query) {
        $result = executeQuery($conn, $query, ['Duplicate', 'Duplicate entry']);
        if ($result === true) {
            $executed++;
        } elseif (is_string($result)) {
            // Insert errors are usually okay if data already exists
            if (strpos($result, 'Duplicate') === false) {
                $warnings[] = "INSERT: " . $result;
            }
        }
    }
    
    $success[] = "Imported base SQL structure ($executed queries executed)";
} else {
    $errors[] = "SQL file not found: $sqlFile";
}

// Step 2: Run migrations - Add missing columns and tables
echo "<h2>Step 2: Running Database Migrations</h2>";

// Check if tables exist
$checkComplaints = $conn->query("SHOW TABLES LIKE 'complaints'");
$complaintsExists = $checkComplaints && $checkComplaints->num_rows > 0;

$checkStudents = $conn->query("SHOW TABLES LIKE 'students'");
$studentsExists = $checkStudents && $checkStudents->num_rows > 0;

// 2.1: Update complaints.status ENUM to include Approved and Denied
if ($complaintsExists) {
    try {
        $res = $conn->query("SHOW COLUMNS FROM complaints LIKE 'status'");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $type = $row['Type'];
            if (strpos($type, "'Approved'") === false || strpos($type, "'Denied'") === false) {
                $result = executeQuery($conn, "ALTER TABLE `complaints` MODIFY `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT 'Pending'");
                if ($result === true) {
                    $success[] = "Updated complaints.status ENUM to include Approved and Denied";
                } else {
                    $warnings[] = "Could not update status ENUM: " . $result;
                }
            }
        }
    } catch (Exception $e) {
        $warnings[] = "Could not update status ENUM: " . $e->getMessage();
    }
}

// 2.2: Add respondents column to complaints if missing
if ($complaintsExists) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM complaints LIKE 'respondents'");
        if ($check->num_rows == 0) {
            $result = executeQuery($conn, "ALTER TABLE `complaints` ADD COLUMN `respondents` ENUM('Single Person','Multiple People') DEFAULT NULL AFTER `description`");
            if ($result === true) {
                $success[] = "Added respondents column to complaints table";
            } else {
                $warnings[] = "Could not add respondents column: " . $result;
            }
        }
    } catch (Exception $e) {
        $warnings[] = "Could not add respondents column: " . $e->getMessage();
    }
}

// 2.3: Add respondent_count column to complaints if missing
if ($complaintsExists) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM complaints LIKE 'respondent_count'");
        if ($check->num_rows == 0) {
            $result = executeQuery($conn, "ALTER TABLE `complaints` ADD COLUMN `respondent_count` INT NULL DEFAULT NULL AFTER `respondents`");
            if ($result === true) {
                $success[] = "Added respondent_count column to complaints table";
            } else {
                $warnings[] = "Could not add respondent_count column: " . $result;
            }
        }
    } catch (Exception $e) {
        $warnings[] = "Could not add respondent_count column: " . $e->getMessage();
    }
}

// 2.4: Add approved column to students if missing
if ($studentsExists) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM students LIKE 'approved'");
        if ($check->num_rows == 0) {
            $result = executeQuery($conn, "ALTER TABLE `students` ADD COLUMN `approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`");
            if ($result === true) {
                $success[] = "Added approved column to students table";
            } else {
                $warnings[] = "Could not add approved column: " . $result;
            }
        }
    } catch (Exception $e) {
        $warnings[] = "Could not add approved column: " . $e->getMessage();
    }
}

// 2.5: Create categories table if it doesn't exist
try {
    $check = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($check->num_rows == 0) {
        $result = executeQuery($conn, "CREATE TABLE `categories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        if ($result === true) {
            // Seed default categories
            executeQuery($conn, "INSERT IGNORE INTO `categories` (`name`) VALUES ('Facility'), ('Faculty'), ('Administrative')");
            $success[] = "Created categories table with default categories";
        } else {
            $warnings[] = "Could not create categories table: " . $result;
        }
    }
} catch (Exception $e) {
    $warnings[] = "Could not create categories table: " . $e->getMessage();
}

// 2.6: Create complaint_updates table if it doesn't exist
if ($complaintsExists) {
    try {
        $check = $conn->query("SHOW TABLES LIKE 'complaint_updates'");
        if ($check->num_rows == 0) {
            // First, try to drop the foreign key if it exists (in case of previous failed attempts)
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            
            $result = executeQuery($conn, "CREATE TABLE `complaint_updates` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `complaint_id` INT(11) NOT NULL,
                `admin_id` INT(11) NOT NULL,
                `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT NULL,
                `message` TEXT NOT NULL,
                `attachments` TEXT DEFAULT NULL COMMENT 'Comma-separated uploaded file paths',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_complaint_id` (`complaint_id`),
                CONSTRAINT `updates_complaint_fk` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            
            if ($result === true) {
                $success[] = "Created complaint_updates table";
            } else {
                $warnings[] = "Could not create complaint_updates table: " . $result;
            }
        }
    } catch (Exception $e) {
        $warnings[] = "Could not create complaint_updates table: " . $e->getMessage();
    }
}

// Step 3: Verify all tables exist
echo "<h2>Step 3: Verifying Database Structure</h2>";
$requiredTables = ['admins', 'students', 'complaints', 'activity_logs', 'categories', 'complaint_updates'];
$existingTables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $existingTables[] = $row[0];
    }
}

$missingTables = array_diff($requiredTables, $existingTables);
if (empty($missingTables)) {
    $success[] = "All required tables exist: " . implode(', ', $existingTables);
} else {
    $errors[] = "Missing tables: " . implode(', ', $missingTables);
}

// Display results
echo "<div style='margin-top:30px;'>";

if (!empty($success)) {
    echo "<h3 class='success'>✓ Success Messages:</h3><ul>";
    foreach ($success as $msg) {
        echo "<li class='success'>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

if (!empty($warnings)) {
    echo "<h3 class='warning'>⚠ Warnings:</h3><ul>";
    foreach ($warnings as $msg) {
        echo "<li class='warning'>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 class='error'>✗ Errors:</h3><ul>";
    foreach ($errors as $msg) {
        echo "<li class='error'>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Final status
if (empty($errors)) {
    echo "<div style='margin-top:30px;padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;'>";
    echo "<h2 class='success'>✓ Database Setup Complete!</h2>";
    echo "<p>The database has been successfully set up with all required tables and columns.</p>";
    echo "</div>";
} else {
    echo "<div style='margin-top:30px;padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;'>";
    echo "<h2 class='error'>✗ Setup Completed with Errors</h2>";
    echo "<p>Please review the errors above. You may need to manually fix some issues.</p>";
    echo "</div>";
}

echo "<p style='margin-top:30px;'>";
echo "<a href='admin/login.php' style='padding:12px 24px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin-right:10px;display:inline-block;'>Go to Admin Login</a>";
echo "<a href='Student Login/index.php' style='padding:12px 24px;background:#28a745;color:white;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Student Login</a>";
echo "</p>";

echo "</body></html>";
?>
