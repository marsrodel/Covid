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

    // Basic validation
    if ($first === '' || $last === '' || !in_array($gender, ['Male', 'Female'], true) || $loc_id <= 0) {
        header('Location: ../views/patients.php?add_error=invalid');
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

        header('Location: ../views/patients.php?added=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);
        header('Location: ../views/patients.php?add_error=tx');
        exit;
    }
}

