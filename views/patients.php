<?php
require_once __DIR__ . '/../server/patient_queries.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COVID Analytics - Patients</title>
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/patient.css">
</head>
<body>
  <header class="header">
    <div class="container header-inner">
      <div class="brand">
        <div class="brand-mark">
          <img src="../assets/covid_icon.png" alt="COVID Analytics logo">
        </div>
        <div class="brand-text">
          <span class="brand-title">COVID Analytics</span>
          <span class="brand-subtitle">IT 108 · COVID-19 Dataset</span>
        </div>
      </div>
      <nav class="nav">
        <a href="index.php" class="nav-link">Dashboard</a>
        <a href="cases.php" class="nav-link">Cases</a>
        <a href="patients.php" class="nav-link nav-link-active">Patients</a>
      </nav>
    </div>
  </header>

  <main class="container main">
    <section class="section hero hero-patients">
      <div class="hero-text">
        <p class="hero-badge">Browse • Patients</p>
        <h1 class="hero-title">Demographics and locations of patients</h1>
        <p class="hero-subtitle">Browse patient demographics and locations with pagination.</p>
        <p class="hero-subtitle">Total Patients: <?php echo number_format($patients_total_global); ?></p>
        <div class="hero-actions">
          <a href="index.php" class="hero-btn hero-btn-outline">Back to Dashboard</a>
          <button type="button" class="hero-btn hero-btn-primary" id="openAddPatientBtn">Add Patient</button>
        </div>
      </div>
    </section>

    <!-- Add Patient Modal -->
    <section class="patient-modal" id="addPatientModal" aria-hidden="true">
      <div class="patient-modal-backdrop" id="addPatientBackdrop"></div>
      <div class="patient-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addPatientTitle">
        <header class="patient-modal-header">
          <div>
            <h2 class="patient-modal-title" id="addPatientTitle">Add Patient</h2>
            <p class="patient-modal-subtitle">Create a new patient linked to an existing location.</p>
          </div>
          <button type="button" class="patient-modal-close" id="addPatientClose" aria-label="Close">
            ×
          </button>
        </header>
        <form class="patient-modal-body" method="post" action="../server/add_patient_queries.php" id="patientForm">
          <input type="hidden" name="patient_id" id="apPatientId" value="">
          <div class="patient-modal-grid">
            <div class="patient-field-group">
              <label for="apFirstName" class="patients-filter-label">First name</label>
              <input type="text" id="apFirstName" name="first_name" class="patients-input" required>
            </div>
            <div class="patient-field-group">
              <label for="apLastName" class="patients-filter-label">Last name</label>
              <input type="text" id="apLastName" name="last_name" class="patients-input" required>
            </div>
            <div class="patient-field-group">
              <label for="apAge" class="patients-filter-label">Age</label>
              <input type="number" id="apAge" name="age" class="patients-input" min="0" required>
            </div>
            <div class="patient-field-group">
              <label for="apGender" class="patients-filter-label">Gender</label>
              <select id="apGender" name="gender" class="patients-select" required>
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="patient-field-group patient-field-wide">
              <label for="apLocation" class="patients-filter-label">Location ID</label>
              <select id="apLocation" name="location_id" class="patients-select" required>
                <option value="">Select Location ID</option>
<?php if ($all_locations && mysqli_num_rows($all_locations) > 0): ?>
<?php while ($loc = mysqli_fetch_assoc($all_locations)): ?>
<?php
  $loc_label = sprintf(
    'ID %d · %s, %s, %s',
    $loc['location_id'],
    $loc['city'],
    $loc['region'],
    $loc['country']
  );
?>
                <option value="<?php echo htmlspecialchars($loc['location_id']); ?>"><?php echo htmlspecialchars($loc_label); ?></option>
<?php endwhile; ?>
<?php endif; ?>
              </select>
            </div>
          </div>
          <footer class="patient-modal-footer">
            <button type="button" class="hero-btn hero-btn-ghost" id="addPatientCancel">Cancel</button>
            <button type="submit" class="hero-btn hero-btn-primary">Save Patient</button>
          </footer>
        </form>
      </div>
    </section>

    <section class="section">
      <h2 class="section-title">Patients</h2>
      <p class="section-caption">Basic info with location (50 per page)</p>

      <form method="get" class="patients-filters">
        <div class="patients-filter-group">
          <label for="searchName" class="patients-filter-label">Search name</label>
          <input
            type="text"
            id="searchName"
            name="name"
            class="patients-input"
            placeholder="e.g. Juan Dela Cruz"
            value="<?php echo htmlspecialchars($search_name); ?>"
          >
        </div>
        <div class="patients-filter-group">
          <label for="ageFilter" class="patients-filter-label">Age</label>
          <input
            type="number"
            id="ageFilter"
            name="age"
            class="patients-input"
            placeholder="e.g. 40"
            value="<?php echo isset($age_filter) && $age_filter !== null ? htmlspecialchars($age_filter) : ''; ?>"
            min="0"
          >
        </div>
        <div class="patients-filter-group">
          <label for="genderFilter" class="patients-filter-label">Gender</label>
          <select id="genderFilter" name="gender" class="patients-select">
            <option value="All" <?php echo $gender === 'All' ? 'selected' : ''; ?>>All</option>
            <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
          </select>
        </div>
      </form>

      <div class="card data-card patients-card">
        <div class="growth-table-scroll">
          <table class="growth-table patients-table">
            <thead>
              <tr>
                <th>Patient ID</th>
                <th>Patient</th>
                <th>Gender</th>
                <th>Age</th>
                <th>City</th>
                <th>Region</th>
                <th>Country</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
<?php if (!empty($patients)): ?>
<?php foreach ($patients as $p): ?>
<?php
  $full_name = trim($p['first_name'] . ' ' . $p['last_name']);
?>
              <tr>
                <td><?php echo htmlspecialchars($p['patient_id']); ?></td>
                <td><?php echo htmlspecialchars($full_name); ?></td>
                <td><?php echo htmlspecialchars($p['gender']); ?></td>
                <td><?php echo $p['age'] !== null ? htmlspecialchars($p['age']) : '—'; ?></td>
                <td><?php echo htmlspecialchars($p['city']); ?></td>
                <td><?php echo htmlspecialchars($p['region']); ?></td>
                <td><?php echo htmlspecialchars($p['country']); ?></td>
                <td>
                  <div class="patients-actions">
                    <button
                      type="button"
                      class="patients-action-btn patients-action-edit"
                      data-patient-id="<?php echo htmlspecialchars($p['patient_id']); ?>"
                      data-first-name="<?php echo htmlspecialchars($p['first_name']); ?>"
                      data-last-name="<?php echo htmlspecialchars($p['last_name']); ?>"
                      data-gender="<?php echo htmlspecialchars($p['gender']); ?>"
                      data-age="<?php echo htmlspecialchars($p['age']); ?>"
                      data-location-id="<?php echo htmlspecialchars($p['location_id'] ?? ''); ?>"
                    >Edit</button>
                    <button type="button" class="patients-action-btn patients-action-delete">Delete</button>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
<?php else: ?>
              <tr>
                <td colspan="8">No patients found for the selected filters.</td>
              </tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($patients_totalpages > 1): ?>
        <div class="patients-pagination">
<?php
  // Helper to build query string while keeping filters
  function patients_build_query($page, $search_name, $gender, $age_filter) {
      $params = [
          'page'   => $page,
      ];
      if ($search_name !== '') {
          $params['name'] = $search_name;
      }
      if ($gender !== 'All') {
          $params['gender'] = $gender;
      }
      if ($age_filter !== null && $age_filter !== '') {
          $params['age'] = $age_filter;
      }
      return '?' . http_build_query($params);
  }

  $current = $patients_page;
  $last    = $patients_totalpages;
  $age_val = isset($age_filter) && $age_filter !== null ? $age_filter : '';

  // Previous link
  if ($current > 1):
      $prev_link = patients_build_query($current - 1, $search_name, $gender, $age_val);
?>
          <a href="<?php echo $prev_link; ?>" class="patients-page-link">Previous</a>
<?php else: ?>
          <span class="patients-page-link patients-page-disabled">Previous</span>
<?php endif; ?>

<?php
  // Dynamic window of page numbers around the current page
  $window = 4; // how many pages before/after current
  $start = max(1, $current - $window);
  $end   = min($last, $current + $window);

  // Always show page 1
  $link = patients_build_query(1, $search_name, $gender, $age_val);
  $is_current = ($current === 1);
?>
          <a href="<?php echo $link; ?>" class="patients-page-link<?php echo $is_current ? ' patients-page-current' : ''; ?>">1</a>
<?php
  // If our window doesn't start until after page 2, show leading ellipsis
  if ($start > 2) {
?>
          <span class="patients-page-ellipsis">...</span>
<?php
  }

  // Middle window
  for ($i = $start; $i <= $end; $i++):
      // Skip 1 and last here to avoid duplicates
      if ($i === 1 || $i === $last) continue;
      $link = patients_build_query($i, $search_name, $gender, $age_val);
      $is_current = ($i === $current);
?>
          <a href="<?php echo $link; ?>" class="patients-page-link<?php echo $is_current ? ' patients-page-current' : ''; ?>"><?php echo $i; ?></a>
<?php endfor; ?>

<?php
  // Always show last page
  if ($last > 1) {
      if ($end < $last - 1) {
?>
          <span class="patients-page-ellipsis">...</span>
<?php
      }
      $link = patients_build_query($last, $search_name, $gender, $age_val);
      $is_current = ($current === $last);
?>
          <a href="<?php echo $link; ?>" class="patients-page-link<?php echo $is_current ? ' patients-page-current' : ''; ?>"><?php echo $last; ?></a>
<?php } ?>

<?php
  // Next link
  if ($current < $last):
      $next_link = patients_build_query($current + 1, $search_name, $gender, $age_val);
?>
          <a href="<?php echo $next_link; ?>" class="patients-page-link">Next</a>
<?php else: ?>
          <span class="patients-page-link patients-page-disabled">Next</span>
<?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-inner">
      <span>2025 · COVID Analytics · For academic use only</span>
    </div>
  </footer>

  <script src="../js/patient.js"></script>
</body>
</html>

