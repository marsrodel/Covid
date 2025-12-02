<?php
// All SQL queries used by the patients page (patients.php).
// This keeps patients.php clean and easy to read.

require_once __DIR__ . '/db.php';

$per_page = 50;

// Read filters from query string
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$search_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$gender      = isset($_GET['gender']) ? $_GET['gender'] : 'All';

// Build WHERE conditions
$conditions = '1=1';

if ($search_name !== '') {
    $safe_name = mysqli_real_escape_string($connect, $search_name);
    $conditions .= " AND (CONCAT(p.first_name, ' ', p.last_name) LIKE '%$safe_name%')";
}

if ($gender === 'Male' || $gender === 'Female') {
    $safe_gender = mysqli_real_escape_string($connect, $gender);
    $conditions .= " AND p.gender = '$safe_gender'";
}

// Count total patients for pagination
$count_sql = "SELECT COUNT(*) AS total
              FROM patient p
              LEFT JOIN location l ON p.location_id = l.location_id
              WHERE $conditions";

$count_result = mysqli_query($connect, $count_sql);
$total_patients = 0;

if ($count_result && mysqli_num_rows($count_result) > 0) {
    $row = mysqli_fetch_assoc($count_result);
    $total_patients = (int)$row['total'];
}

$total_pages = $total_patients > 0 ? (int)ceil($total_patients / $per_page) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;
if ($offset < 0) {
    $offset = 0;
}

// Main patients query (with location info)
$patients = [];

$data_sql = "SELECT 
    p.patient_id,
    p.first_name,
    p.last_name,
    p.gender,
    p.age,
    l.city,
    l.region,
    l.country
FROM patient p
LEFT JOIN location l ON p.location_id = l.location_id
WHERE $conditions
ORDER BY p.patient_id
LIMIT $per_page OFFSET $offset";

$data_result = mysqli_query($connect, $data_sql);

if ($data_result) {
    while ($row = mysqli_fetch_assoc($data_result)) {
        $patients[] = [
            'patient_id' => (int)$row['patient_id'],
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
            'gender'     => $row['gender'],
            'age'        => isset($row['age']) ? (int)$row['age'] : null,
            'city'       => $row['city'],
            'region'     => $row['region'],
            'country'    => $row['country'],
        ];
    }
}

// Expose pagination variables for the view
$patients_page       = $page;
$patients_per_page   = $per_page;
$patients_total      = $total_patients;
$patients_totalpages = $total_pages;

