<?php
// Handles Edit Case form submission.
// Updates an existing row in covid_cases inside a transaction.

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id   = isset($_POST['case_id']) && $_POST['case_id'] !== '' ? (int)$_POST['case_id'] : 0;
    $test_date = $_POST['test_date'] ?? '';
    $patient_id = isset($_POST['patient_id']) && $_POST['patient_id'] !== '' ? (int)$_POST['patient_id'] : 0;
    $result    = $_POST['result'] ?? '';
    $severity  = $_POST['severity'] ?? '';
    $vaccine_id = isset($_POST['vaccine_id']) && $_POST['vaccine_id'] !== '' ? (int)$_POST['vaccine_id'] : 0;
    $lab_id     = isset($_POST['lab_id']) && $_POST['lab_id'] !== '' ? (int)$_POST['lab_id'] : 0;

    $allowed_results  = ['Positive', 'Negative'];
    $allowed_severity = ['Mild', 'Moderate', 'Severe', 'Critical'];

    if ($case_id <= 0 || $test_date === '' || $patient_id <= 0 || !in_array($result, $allowed_results, true) || !in_array($severity, $allowed_severity, true) || $vaccine_id <= 0 || $lab_id <= 0) {
        header('Location: ../views/cases.php?edit_error=invalid');
        exit;
    }

    mysqli_begin_transaction($connect);

    try {
        $stmt = mysqli_prepare(
            $connect,
            'UPDATE covid_cases
             SET test_date = ?,
                 patient_id = ?,
                 result = ?,
                 severity = ?,
                 vaccine_id = ?,
                 lab_id = ?
             WHERE case_id = ?'
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare update');
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sissiii',
            $test_date,
            $patient_id,
            $result,
            $severity,
            $vaccine_id,
            $lab_id,
            $case_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute update');
        }

        mysqli_commit($connect);
        mysqli_stmt_close($stmt);

        header('Location: ../views/cases.php?edited=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);
        header('Location: ../views/cases.php?edit_error=tx');
        exit;
    }
}

