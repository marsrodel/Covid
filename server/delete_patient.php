<?php
// Handles Delete Patient requests.
// Deletes a row from patient inside a transaction.
// A database trigger (defined separately) will delete related covid_cases rows.

require_once __DIR__ . '/db.php';

// Allow both POST (preferred, from AJAX) and GET (fallback redirect)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST' || $method === 'GET') {
    $patient_id = 0;

    if ($method === 'POST') {
        $patient_id = isset($_POST['patient_id']) && $_POST['patient_id'] !== '' ? (int)$_POST['patient_id'] : 0;
    } else {
        $patient_id = isset($_GET['patient_id']) && $_GET['patient_id'] !== '' ? (int)$_GET['patient_id'] : 0;
    }

    if ($patient_id <= 0) {
        header('Location: ../views/patients.php?delete_error=invalid');
        exit;
    }

    mysqli_begin_transaction($connect);

    try {
        $stmt = mysqli_prepare(
            $connect,
            'DELETE FROM patient WHERE patient_id = ?'
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare delete');
        }

        mysqli_stmt_bind_param($stmt, 'i', $patient_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute delete');
        }

        mysqli_commit($connect);
        mysqli_stmt_close($stmt);

        if ($method === 'POST') {
            http_response_code(204);
            exit;
        }

        header('Location: ../views/patients.php?deleted=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);

        if ($method === 'POST') {
            http_response_code(500);
            exit;
        }

        header('Location: ../views/patients.php?delete_error=tx');
        exit;
    }
}

