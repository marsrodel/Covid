<?php
// All SQL queries used by the main dashboard (index.php).
// This keeps index.php clean and easy to read.

require_once __DIR__ . '/db.php';

// ----------------------------------------------
// 1. Basic totals
// ----------------------------------------------

$totals_sql = "SELECT 
    COUNT(*) AS total_cases,
    SUM(CASE WHEN result = 'Positive' THEN 1 ELSE 0 END) AS positive_cases,
    SUM(CASE WHEN result = 'Negative' THEN 1 ELSE 0 END) AS negative_cases,
    SUM(CASE WHEN severity IN ('Severe', 'Critical') THEN 1 ELSE 0 END) AS severe_critical_cases,
    SUM(CASE WHEN vaccine_id IS NOT NULL THEN 1 ELSE 0 END) AS vaccinated_cases
FROM covid_cases";

$totals_result = mysqli_query($connect, $totals_sql);

$total_cases           = 0;
$positive_cases        = 0;
$negative_cases        = 0;
$severe_critical_cases = 0;
$vaccinated_cases      = 0;

if ($totals_result && mysqli_num_rows($totals_result) > 0) {
    $row = mysqli_fetch_assoc($totals_result);
    $total_cases           = (int)$row['total_cases'];
    $positive_cases        = (int)$row['positive_cases'];
    $negative_cases        = (int)$row['negative_cases'];
    $severe_critical_cases = (int)$row['severe_critical_cases'];
    $vaccinated_cases      = (int)$row['vaccinated_cases'];
}

$positivity_pct = $total_cases > 0 ? ($positive_cases / $total_cases) * 100 : 0;
$sevcrit_pct    = $total_cases > 0 ? ($severe_critical_cases / $total_cases) * 100 : 0;
$vac_pct        = $total_cases > 0 ? ($vaccinated_cases / $total_cases) * 100 : 0;

// ----------------------------------------------
// 2. Yearly positive cases (for line chart)
// ----------------------------------------------

$yearly_cases = [];

$yearly_sql = "SELECT 
    YEAR(test_date) AS yr,
    COUNT(*) AS total_positive
FROM covid_cases
WHERE result = 'Positive'
GROUP BY YEAR(test_date)
ORDER BY YEAR(test_date)";

$yearly_result = mysqli_query($connect, $yearly_sql);

if ($yearly_result) {
    while ($row = mysqli_fetch_assoc($yearly_result)) {
        $yearly_cases[] = [
            'year'  => (int)$row['yr'],
            'total' => (int)$row['total_positive']
        ];
    }
}

// Build yearly growth data: current year total, previous year total, and growth %.
$yearly_growth = [];
$prev_total = null;

foreach ($yearly_cases as $row) {
    $year   = $row['year'];
    $total  = $row['total'];
    $growth = null;

    if ($prev_total !== null && $prev_total > 0) {
        $growth = round((($total - $prev_total) / $prev_total) * 100, 2);
    }

    $yearly_growth[] = [
        'year'         => $year,
        'total'        => $total,
        'previous'     => $prev_total,
        'growth_pct'   => $growth,
    ];

    $prev_total = $total;
}

// ----------------------------------------------
// 2b. Monthly positive cases per year
//     Used when a specific year is selected in the chart filter
// ----------------------------------------------

$monthly_cases = [];

$monthly_sql = "SELECT 
    YEAR(test_date) AS yr,
    MONTH(test_date) AS mn,
    COUNT(*) AS total_positive
FROM covid_cases
WHERE result = 'Positive'
GROUP BY YEAR(test_date), MONTH(test_date)
ORDER BY YEAR(test_date), MONTH(test_date)";

$monthly_result = mysqli_query($connect, $monthly_sql);

if ($monthly_result) {
    while ($row = mysqli_fetch_assoc($monthly_result)) {
        $year  = (int)$row['yr'];
        $month = (int)$row['mn'];
        $total = (int)$row['total_positive'];

        if (!isset($monthly_cases[$year])) {
            $monthly_cases[$year] = [];
        }

        $monthly_cases[$year][] = [
            'month' => $month,
            'total' => $total,
        ];
    }
}

// Build monthly growth data across all years, ordered by year then month.
$monthly_growth = [];
$prev_month_total = null;

ksort($monthly_cases); // ensure years are in order

foreach ($monthly_cases as $year => $months) {
    // months are already in order due to SQL, but sort by month key to be safe
    $sorted_months = $months;
    usort($sorted_months, function ($a, $b) {
        return $a['month'] <=> $b['month'];
    });

    foreach ($sorted_months as $row) {
        $month_num = (int)$row['month'];
        $total     = (int)$row['total'];
        $label     = sprintf('%04d-%02d', $year, $month_num);

        $growth = null;
        if ($prev_month_total !== null && $prev_month_total > 0) {
            $growth = round((($total - $prev_month_total) / $prev_month_total) * 100, 2);
        }

        $monthly_growth[] = [
            'label'        => $label,
            'total'        => $total,
            'previous'     => $prev_month_total,
            'growth_pct'   => $growth,
        ];

        $prev_month_total = $total;
    }
}

// ----------------------------------------------
// 3. Supporting datasets (labs, vaccines, locations)
// ----------------------------------------------

$labs_result     = mysqli_query($connect, "SELECT lab_name, city, capacity_per_day FROM testing_lab ORDER BY lab_id ASC");
$vaccines_result = mysqli_query($connect, "SELECT vaccine_name, manufacturer, doses_required FROM vaccine ORDER BY vaccine_id ASC");
$locations_result = mysqli_query($connect, "SELECT location_id, city, region, country FROM location ORDER BY location_id ASC");

// ----------------------------------------------
// 4. Severity distribution by result
//    This will allow filtering: All / Positive / Negative
// ----------------------------------------------

$severity_counts = [
    'All'      => [],
    'Positive' => [],
    'Negative' => [],
];

$severity_totals_all = [];

$severity_sql = "SELECT 
    result,
    severity,
    COUNT(*) AS total
FROM covid_cases
GROUP BY result, severity
ORDER BY FIELD(severity, 'Mild', 'Moderate', 'Severe', 'Critical'),
         FIELD(result, 'Positive', 'Negative')";

$severity_result = mysqli_query($connect, $severity_sql);

if ($severity_result) {
    while ($row = mysqli_fetch_assoc($severity_result)) {
        $result_type = $row['result'];
        $severity    = $row['severity'];
        $total       = (int)$row['total'];

        if ($severity === null) {
            $severity = 'Unknown';
        }
        if ($result_type === null || $result_type === '') {
            $result_type = 'Unknown';
        }

        if (!isset($severity_counts[$result_type])) {
            $severity_counts[$result_type] = [];
        }

        $severity_counts[$result_type][] = [
            'severity' => $severity,
            'total'    => $total,
        ];

        if (!isset($severity_totals_all[$severity])) {
            $severity_totals_all[$severity] = 0;
        }
        $severity_totals_all[$severity] += $total;
    }
}

// Build the "All" bucket from totals per severity so that
// the order matches the Mild/Moderate/Severe/Critical sequence.
$severity_order = ['Mild', 'Moderate', 'Severe', 'Critical', 'Unknown'];

foreach ($severity_order as $sev) {
    if (isset($severity_totals_all[$sev])) {
        $severity_counts['All'][] = [
            'severity' => $sev,
            'total'    => $severity_totals_all[$sev],
        ];
    }
}
