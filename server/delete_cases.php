<?php
// Handles Delete Case requests.
// Deletes a row from covid_cases inside a transaction.

require_once __DIR__ . '/db.php';

// Allow both POST (preferred, from AJAX) and GET (fallback redirect)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST' || $method === 'GET') {
    $case_id = 0;

    if ($method === 'POST') {
        $case_id = isset($_POST['case_id']) && $_POST['case_id'] !== '' ? (int)$_POST['case_id'] : 0;
    } else {
        $case_id = isset($_GET['case_id']) && $_GET['case_id'] !== '' ? (int)$_GET['case_id'] : 0;
    }

    if ($case_id <= 0) {
        header('Location: ../views/cases.php?delete_error=invalid');
        exit;
    }

    mysqli_begin_transaction($connect);

    try {
        $stmt = mysqli_prepare(
            $connect,
            'DELETE FROM covid_cases WHERE case_id = ?'
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare delete');
        }

        mysqli_stmt_bind_param($stmt, 'i', $case_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute delete');
        }

        mysqli_commit($connect);
        mysqli_stmt_close($stmt);

        // For AJAX callers, just return 204 No Content
        if ($method === 'POST') {
            http_response_code(204);
            exit;
        }

        header('Location: ../views/cases.php?deleted=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($connect);

        if ($method === 'POST') {
            http_response_code(500);
            exit;
        }

        header('Location: ../views/cases.php?delete_error=tx');
        exit;
    }
}

