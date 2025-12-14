<?php
// Handles Add Patient form submission.
// Uses a stored procedure (sp_add_patient) wrapped in a transaction.
//
// Stored procedure definition (run once in MySQL, e.g. via phpMyAdmin):
//
// DELIMITER $$
//
// CREATE PROCEDURE sp_add_patient (
//   IN p_first_name  VARCHAR(50),
//   IN p_last_name   VARCHAR(50),
//   IN p_gender      ENUM('Male','Female'),
//   IN p_age         INT,
//   IN p_location_id INT
// )
// BEGIN
//   INSERT INTO patient (first_name, last_name, gender, age, location_id)
//   VALUES (p_first_name, p_last_name, p_gender, p_age, p_location_id);
// END$$
//
// DELIMITER ;

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first  = trim($_POST['first_name'] ?? '');
    $last   = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $age    = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $loc_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;

    $is_ajax = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

    // Name validation: letters and spaces only, no numbers or special characters
    $name_pattern = '/^[A-Za-z\s]+$/';

    if ($first === '' || $last === '' || !in_array($gender, ['Male', 'Female'], true) || $loc_id <= 0) {
        if ($is_ajax) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Please fill in all required fields with valid values.',
            ]);
            exit;
        }

        header('Location: ../views/patients.php?add_error=invalid');
        exit;
    }

    if (!preg_match($name_pattern, $first) || !preg_match($name_pattern, $last)) {
        if ($is_ajax) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'First and last name must contain letters and spaces only (no numbers or special characters).',
            ]);
            exit;
        }

        header('Location: ../views/patients.php?add_error=name');
        exit;
    }

    mysqli_begin_transaction($connect);

    try {
        // Call stored procedure sp_add_patient
        $stmt = mysqli_prepare(
            $connect,
            'CALL sp_add_patient(?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssii',
            $first,
            $last,
            $gender,
            $age,
            $loc_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute sp_add_patient');
        }

        mysqli_commit($connect);
        mysqli_stmt_close($stmt);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: ../views/patients.php?added=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);
        if ($is_ajax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Something went wrong while saving the patient. Please try again.',
            ]);
            exit;
        }

        header('Location: ../views/patients.php?add_error=tx');
        exit;
    }
}

