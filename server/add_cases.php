<?php
// Handles Add Case form submission.
// Uses a stored procedure (sp_add_case) wrapped in a transaction.
//
// Stored procedure definition (run once in MySQL, e.g. via phpMyAdmin):
//
// DELIMITER $$
//
// CREATE PROCEDURE sp_add_case (
//   IN p_test_date  DATE,
//   IN p_patient_id INT,
//   IN p_result     ENUM('Positive','Negative'),
//   IN p_severity   VARCHAR(20),
//   IN p_vaccine_id INT,
//   IN p_lab_id     INT
// )
// BEGIN
//   INSERT INTO covid_cases (test_date, patient_id, result, severity, vaccine_id, lab_id)
//   VALUES (p_test_date, p_patient_id, p_result, p_severity, p_vaccine_id, p_lab_id);
// END$$
//
// DELIMITER ;

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_date  = $_POST['test_date'] ?? '';
    $patient_id = isset($_POST['patient_id']) && $_POST['patient_id'] !== '' ? (int)$_POST['patient_id'] : 0;
    $result     = $_POST['result'] ?? '';
    $severity   = $_POST['severity'] ?? '';
    $vaccine_id = isset($_POST['vaccine_id']) && $_POST['vaccine_id'] !== '' ? (int)$_POST['vaccine_id'] : 0;
    $lab_id     = isset($_POST['lab_id']) && $_POST['lab_id'] !== '' ? (int)$_POST['lab_id'] : 0;

    $allowed_results   = ['Positive', 'Negative'];
    $allowed_severity  = ['Mild', 'Moderate', 'Severe', 'Critical'];

    if ($test_date === '' || $patient_id <= 0 || !in_array($result, $allowed_results, true) || !in_array($severity, $allowed_severity, true) || $vaccine_id <= 0 || $lab_id <= 0) {
        header('Location: ../views/cases.php?add_error=invalid');
        exit;
    }

    mysqli_begin_transaction($connect);

    try {
        $stmt = mysqli_prepare(
            $connect,
            'CALL sp_add_case(?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare sp_add_case');
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sissii',
            $test_date,
            $patient_id,
            $result,
            $severity,
            $vaccine_id,
            $lab_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute sp_add_case');
        }

        mysqli_commit($connect);
        mysqli_stmt_close($stmt);

        header('Location: ../views/cases.php?added=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);
        header('Location: ../views/cases.php?add_error=tx');
        exit;
    }
}

