<?php
// Server-side queries for cases.php
// Provides paginated covid_cases with optional filters:
// - result (All, Positive, Negative)
// - severity (All, Mild, Moderate, Severe, Critical)
// - vaccine (All or specific vaccine_id)
// - year (All or specific year of test_date)
// - lab (All or specific lab_id)

require_once __DIR__ . '/db.php';

$per_page = 50;

// Global total cases (for hero), independent of filters
$cases_total_global = 0;
$global_cases_sql = "SELECT COUNT(*) AS total_all FROM covid_cases";
$global_cases_result = mysqli_query($connect, $global_cases_sql);

if ($global_cases_result && mysqli_num_rows($global_cases_result) > 0) {
    $global_row = mysqli_fetch_assoc($global_cases_result);
    $cases_total_global = (int)$global_row['total_all'];
}

// Read filters from query string
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$result_filter    = isset($_GET['result']) ? $_GET['result'] : 'All';
$severity_filter  = isset($_GET['severity']) ? $_GET['severity'] : 'All';
$vaccine_filter   = isset($_GET['vaccine']) ? $_GET['vaccine'] : 'All';
$year_filter      = isset($_GET['year']) ? $_GET['year'] : 'All';
$lab_filter       = isset($_GET['lab']) ? $_GET['lab'] : 'All';
$patientid_filter = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : '';

// Build WHERE conditions
$conditions = '1=1';

if ($result_filter === 'Positive' || $result_filter === 'Negative') {
    $safe = mysqli_real_escape_string($connect, $result_filter);
    $conditions .= " AND c.result = '$safe'";
}

if (in_array($severity_filter, ['Mild', 'Moderate', 'Severe', 'Critical'], true)) {
    $safe = mysqli_real_escape_string($connect, $severity_filter);
    $conditions .= " AND c.severity = '$safe'";
}

if ($vaccine_filter !== 'All' && ctype_digit((string)$vaccine_filter)) {
    $v_id = (int)$vaccine_filter;
    $conditions .= ' AND c.vaccine_id = ' . $v_id;
}

if ($year_filter !== 'All' && ctype_digit((string)$year_filter)) {
    $y = (int)$year_filter;
    $conditions .= ' AND YEAR(c.test_date) = ' . $y;
}

if ($lab_filter !== 'All' && ctype_digit((string)$lab_filter)) {
    $l_id = (int)$lab_filter;
    $conditions .= ' AND c.lab_id = ' . $l_id;
}

if ($patientid_filter !== '' && ctype_digit($patientid_filter)) {
    $pid = (int)$patientid_filter;
    if ($pid > 0) {
        $conditions .= ' AND c.patient_id = ' . $pid;
    }
}

// Count total cases for pagination
$count_sql = "SELECT COUNT(*) AS total
              FROM covid_cases c
              LEFT JOIN vaccine v ON c.vaccine_id = v.vaccine_id
              LEFT JOIN testing_lab t ON c.lab_id = t.lab_id
              WHERE $conditions";

$count_result = mysqli_query($connect, $count_sql);
$total_cases = 0;

if ($count_result && mysqli_num_rows($count_result) > 0) {
    $row = mysqli_fetch_assoc($count_result);
    $total_cases = (int)$row['total'];
}

$total_pages = $total_cases > 0 ? (int)ceil($total_cases / $per_page) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;
if ($offset < 0) {
    $offset = 0;
}

// Main cases query
$cases = [];

$data_sql = "SELECT 
    c.case_id,
    c.test_date,
    c.patient_id,
    c.result,
    c.severity,
    c.vaccine_id,
    c.lab_id,
    v.vaccine_name,
    t.lab_name
FROM covid_cases c
LEFT JOIN vaccine v ON c.vaccine_id = v.vaccine_id
LEFT JOIN testing_lab t ON c.lab_id = t.lab_id
WHERE $conditions
ORDER BY c.test_date DESC, c.case_id DESC
LIMIT $per_page OFFSET $offset";

$data_result = mysqli_query($connect, $data_sql);

if ($data_result) {
    while ($row = mysqli_fetch_assoc($data_result)) {
        $cases[] = [
            'case_id'      => isset($row['case_id']) ? (int)$row['case_id'] : null,
            'test_date'    => $row['test_date'],
            'patient_id'   => isset($row['patient_id']) ? (int)$row['patient_id'] : null,
            'result'       => $row['result'],
            'severity'     => $row['severity'],
            'vaccine_id'   => isset($row['vaccine_id']) ? (int)$row['vaccine_id'] : null,
            'lab_id'       => isset($row['lab_id']) ? (int)$row['lab_id'] : null,
            'vaccine_name' => $row['vaccine_name'],
            'lab_name'     => $row['lab_name'],
        ];
    }
}

// Expose pagination variables
$cases_page       = $page;
$cases_per_page   = $per_page;
$cases_total      = $total_cases;
$cases_totalpages = $total_pages;

// Load dropdown options
$vaccine_options = mysqli_query(
    $connect,
    "SELECT vaccine_id, vaccine_name FROM vaccine ORDER BY vaccine_id ASC"
);

$lab_options = mysqli_query(
    $connect,
    "SELECT lab_id, lab_name FROM testing_lab ORDER BY lab_id ASC"
);

// Separate result sets for the Add Case modal
$vaccine_modal_options = mysqli_query(
    $connect,
    "SELECT vaccine_id, vaccine_name FROM vaccine ORDER BY vaccine_id ASC"
);

$lab_modal_options = mysqli_query(
    $connect,
    "SELECT lab_id, lab_name FROM testing_lab ORDER BY lab_id ASC"
);

// Distinct years from covid_cases for the Year filter
$year_options = [];
$years_result = mysqli_query(
    $connect,
    "SELECT DISTINCT YEAR(test_date) AS yr FROM covid_cases ORDER BY yr ASC"
);

if ($years_result) {
    while ($row = mysqli_fetch_assoc($years_result)) {
        $year_options[] = (int)$row['yr'];
    }
}

