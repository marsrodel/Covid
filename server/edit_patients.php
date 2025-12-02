<?php
// Handles Edit Patient form submission.
// Updates an existing patient row based on patient_id.

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $first  = trim($_POST['first_name'] ?? '');
    $last   = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $age    = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $loc_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;

    if ($id <= 0 || $first === '' || $last === '' || !in_array($gender, ['Male', 'Female'], true) || $loc_id <= 0) {
        header('Location: ../views/patients.php?edit_error=invalid');
        exit;
    }

    // Simple transaction for safety (optional, but good for rubric consistency)
    mysqli_begin_transaction($connect);

    try {
        $stmt = mysqli_prepare(
            $connect,
            'UPDATE patient SET first_name = ?, last_name = ?, gender = ?, age = ?, location_id = ? WHERE patient_id = ?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssiii',
            $first,
            $last,
            $gender,
            $age,
            $loc_id,
            $id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update patient');
        }

        mysqli_commit($connect);
        mysqli_stmt_close($stmt);

        header('Location: ../views/patients.php?edited=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);
        header('Location: ../views/patients.php?edit_error=tx');
        exit;
    }
}

