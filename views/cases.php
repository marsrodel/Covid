<?php
require_once __DIR__ . '/../server/cases_queries.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COVID Analytics - Cases</title>
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/cases.css">
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
        <a href="cases.php" class="nav-link nav-link-active">Cases</a>
        <a href="patients.php" class="nav-link">Patients</a>
      </nav>
    </div>
  </header>

  <main class="container main">
    <section class="section hero hero-cases">
      <div class="hero-text">
        <p class="hero-badge">Browse • COVID-19 Cases</p>
        <h1 class="hero-title">Explore case results and severity trends</h1>
        <p class="hero-subtitle">Paginated records from the covid_cases table.</p>
        <p class="hero-subtitle">Total Cases: <?php echo number_format($cases_total_global); ?></p>
        <div class="hero-actions">
          <a href="index.php" class="hero-btn hero-btn-outline">Back to Dashboard</a>
          <button type="button" class="hero-btn hero-btn-primary" id="openAddCaseBtn">Add New Case</button>
        </div>
      </div>
    </section>

    <!-- Delete Case Confirmation Modal -->
    <section class="case-confirm-modal" id="deleteCaseModal" aria-hidden="true">
      <div class="case-confirm-backdrop" id="deleteCaseBackdrop"></div>
      <div class="case-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteCaseTitle">
        <header class="case-confirm-header">
          <h2 class="case-confirm-title" id="deleteCaseTitle">Delete case?</h2>
          <button type="button" class="case-confirm-close" id="deleteCaseClose" aria-label="Close">×</button>
        </header>
        <div class="case-confirm-body">
          <p>Are you sure you want to delete this case? This action cannot be undone.</p>
        </div>
        <footer class="case-confirm-footer">
          <button type="button" class="hero-btn hero-btn-ghost" id="deleteCaseCancel">Cancel</button>
          <button type="button" class="hero-btn hero-btn-primary" id="deleteCaseConfirm">Delete case</button>
        </footer>
      </div>
    </section>

    <!-- Add Case Modal -->
    <section class="case-modal" id="addCaseModal" aria-hidden="true">
      <div class="case-modal-backdrop" id="addCaseBackdrop"></div>
      <div class="case-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addCaseTitle">
        <header class="case-modal-header">
          <div>
            <h2 class="case-modal-title" id="addCaseTitle">Add New Case</h2>
            <p class="case-modal-subtitle">Create a new COVID-19 case with lab and vaccine details.</p>
          </div>
          <button type="button" class="case-modal-close" id="addCaseClose" aria-label="Close">
            ×
          </button>
        </header>
        <form class="case-modal-body" method="post" action="../server/add_cases.php" id="caseForm">
          <input type="hidden" name="case_id" id="acCaseId" value="">
          <div id="caseFormError" class="case-form-error" aria-live="polite"></div>
          <div class="case-modal-grid">
            <div class="case-field-group">
              <label for="acTestDate" class="cases-filter-label">Test date</label>
              <input type="date" id="acTestDate" name="test_date" class="patients-input" required>
            </div>
            <div class="case-field-group">
              <label for="acPatientId" class="cases-filter-label">Patient ID</label>
              <input type="number" id="acPatientId" name="patient_id" class="patients-input" min="1" required>
            </div>
            <div class="case-field-group">
              <label for="acResult" class="cases-filter-label">Result</label>
              <select id="acResult" name="result" class="patients-select" required>
                <option value="">Select result</option>
                <option value="Positive">Positive</option>
                <option value="Negative">Negative</option>
              </select>
            </div>
            <div class="case-field-group">
              <label for="acSeverity" class="cases-filter-label">Severity</label>
              <select id="acSeverity" name="severity" class="patients-select" required>
                <option value="">Select severity</option>
                <option value="Mild">Mild</option>
                <option value="Moderate">Moderate</option>
                <option value="Severe">Severe</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
            <div class="case-field-group">
              <label for="acVaccine" class="cases-filter-label">Vaccine</label>
              <select id="acVaccine" name="vaccine_id" class="patients-select" required>
                <option value="">Select vaccine</option>
<?php if ($vaccine_modal_options && mysqli_num_rows($vaccine_modal_options) > 0): ?>
<?php while ($vm = mysqli_fetch_assoc($vaccine_modal_options)): ?>
                <option value="<?php echo htmlspecialchars($vm['vaccine_id']); ?>"><?php echo htmlspecialchars($vm['vaccine_name']); ?></option>
<?php endwhile; ?>
<?php endif; ?>
              </select>
            </div>
            <div class="case-field-group">
              <label for="acLab" class="cases-filter-label">Lab</label>
              <select id="acLab" name="lab_id" class="patients-select" required>
                <option value="">Select lab</option>
<?php if ($lab_modal_options && mysqli_num_rows($lab_modal_options) > 0): ?>
<?php while ($lm = mysqli_fetch_assoc($lab_modal_options)): ?>
                <option value="<?php echo htmlspecialchars($lm['lab_id']); ?>"><?php echo htmlspecialchars($lm['lab_name']); ?></option>
<?php endwhile; ?>
<?php endif; ?>
              </select>
            </div>
          </div>
          <footer class="case-modal-footer">
            <button type="button" class="hero-btn hero-btn-ghost" id="addCaseCancel">Cancel</button>
            <button type="submit" class="hero-btn hero-btn-primary">Save Case</button>
          </footer>
        </form>
      </div>
    </section>

    <section class="section">
      <h2 class="section-title">Cases</h2>
      <p class="section-caption">Paginated results from covid_cases (50 per page)</p>

      <form method="get" class="cases-filters">
        <div class="cases-filter-group">
          <label for="patientIdFilter" class="cases-filter-label">Patient ID</label>
          <input
            type="number"
            id="patientIdFilter"
            name="patient_id"
            class="patients-input"
            min="1"
            placeholder="e.g. 2348"
            value="<?php echo htmlspecialchars($patientid_filter); ?>"
          >
        </div>

        <div class="cases-filter-group">
          <label for="resultFilter" class="cases-filter-label">Result</label>
          <select id="resultFilter" name="result" class="cases-select">
            <?php
              $result_val = $result_filter;
            ?>
            <option value="All" <?php echo $result_val === 'All' ? 'selected' : ''; ?>>All</option>
            <option value="Positive" <?php echo $result_val === 'Positive' ? 'selected' : ''; ?>>Positive</option>
            <option value="Negative" <?php echo $result_val === 'Negative' ? 'selected' : ''; ?>>Negative</option>
          </select>
        </div>

        <div class="cases-filter-group">
          <label for="severityFilter" class="cases-filter-label">Severity</label>
          <select id="severityFilter" name="severity" class="cases-select">
            <?php
              $sev_val = $severity_filter;
              $sev_options = ['All', 'Mild', 'Moderate', 'Severe', 'Critical'];
            ?>
            <?php foreach ($sev_options as $opt): ?>
              <option value="<?php echo $opt; ?>" <?php echo $sev_val === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="cases-filter-group">
          <label for="vaccineFilter" class="cases-filter-label">Vaccine</label>
          <select id="vaccineFilter" name="vaccine" class="cases-select">
            <option value="All" <?php echo $vaccine_filter === 'All' ? 'selected' : ''; ?>>All</option>
<?php if ($vaccine_options && mysqli_num_rows($vaccine_options) > 0): ?>
<?php while ($v = mysqli_fetch_assoc($vaccine_options)): ?>
            <option value="<?php echo htmlspecialchars($v['vaccine_id']); ?>" <?php echo (string)$v['vaccine_id'] === (string)$vaccine_filter ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['vaccine_name']); ?>
            </option>
<?php endwhile; ?>
<?php endif; ?>
          </select>
        </div>

        <div class="cases-filter-group">
          <label for="yearFilter" class="cases-filter-label">Year</label>
          <select id="yearFilter" name="year" class="cases-select">
            <option value="All" <?php echo $year_filter === 'All' ? 'selected' : ''; ?>>All</option>
<?php foreach ($year_options as $yr): ?>
            <option value="<?php echo $yr; ?>" <?php echo (string)$yr === (string)$year_filter ? 'selected' : ''; ?>>
              <?php echo $yr; ?>
            </option>
<?php endforeach; ?>
          </select>
        </div>

        <div class="cases-filter-group">
          <label for="labFilter" class="cases-filter-label">Lab</label>
          <select id="labFilter" name="lab" class="cases-select">
            <option value="All" <?php echo $lab_filter === 'All' ? 'selected' : ''; ?>>All</option>
<?php if ($lab_options && mysqli_num_rows($lab_options) > 0): ?>
<?php while ($lab = mysqli_fetch_assoc($lab_options)): ?>
            <option value="<?php echo htmlspecialchars($lab['lab_id']); ?>" <?php echo (string)$lab['lab_id'] === (string)$lab_filter ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($lab['lab_name']); ?>
            </option>
<?php endwhile; ?>
<?php endif; ?>
          </select>
        </div>
      </form>

      <div class="card data-card cases-card">
        <div class="growth-table-scroll">
          <table class="growth-table cases-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Patient ID</th>
                <th>Result</th>
                <th>Severity</th>
                <th>Vaccine</th>
                <th>Lab</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
<?php if (!empty($cases)): ?>
<?php foreach ($cases as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['test_date']); ?></td>
                <td><?php echo $c['patient_id'] !== null ? htmlspecialchars($c['patient_id']) : '—'; ?></td>
                <td><?php echo htmlspecialchars($c['result']); ?></td>
                <td><?php echo htmlspecialchars($c['severity']); ?></td>
                <td><?php echo $c['vaccine_name'] !== null ? htmlspecialchars($c['vaccine_name']) : '—'; ?></td>
                <td><?php echo $c['lab_name'] !== null ? htmlspecialchars($c['lab_name']) : '—'; ?></td>
                <td>
                  <div class="patients-actions">
                    <button
                      type="button"
                      class="patients-action-btn patients-action-edit"
                      data-case-id="<?php echo htmlspecialchars($c['case_id']); ?>"
                      data-test-date="<?php echo htmlspecialchars($c['test_date']); ?>"
                      data-patient-id="<?php echo htmlspecialchars($c['patient_id']); ?>"
                      data-result="<?php echo htmlspecialchars($c['result']); ?>"
                      data-severity="<?php echo htmlspecialchars($c['severity']); ?>"
                      data-vaccine-id="<?php echo htmlspecialchars($c['vaccine_id']); ?>"
                      data-lab-id="<?php echo htmlspecialchars($c['lab_id']); ?>"
                    >Edit</button>
                    <button
                      type="button"
                      class="patients-action-btn patients-action-delete"
                      data-case-id="<?php echo htmlspecialchars($c['case_id']); ?>"
                    >Delete</button>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
<?php else: ?>
              <tr>
                <td colspan="7">No cases found for the selected filters.</td>
              </tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($cases_totalpages > 1): ?>
        <div class="cases-pagination">
<?php
  function cases_build_query($page, $result_filter, $severity_filter, $vaccine_filter, $year_filter, $lab_filter, $patientid_filter) {
      $params = [
          'page' => $page,
      ];
      if ($result_filter !== 'All') {
          $params['result'] = $result_filter;
      }
      if ($severity_filter !== 'All') {
          $params['severity'] = $severity_filter;
      }
      if ($vaccine_filter !== 'All') {
          $params['vaccine'] = $vaccine_filter;
      }
      if ($year_filter !== 'All') {
          $params['year'] = $year_filter;
      }
      if ($lab_filter !== 'All') {
          $params['lab'] = $lab_filter;
      }
      if ($patientid_filter !== '') {
          $params['patient_id'] = $patientid_filter;
      }
      return '?' . http_build_query($params);
  }

  $current = $cases_page;
  $last    = $cases_totalpages;

  // Previous link
  if ($current > 1):
      $prev_link = cases_build_query($current - 1, $result_filter, $severity_filter, $vaccine_filter, $year_filter, $lab_filter, $patientid_filter);
?>
          <a href="<?php echo $prev_link; ?>" class="cases-page-link">Previous</a>
<?php else: ?>
          <span class="cases-page-link cases-page-disabled">Previous</span>
<?php endif; ?>

<?php
  $window = 4;
  $start = max(1, $current - $window);
  $end   = min($last, $current + $window);

  // Always show page 1
  $link = cases_build_query(1, $result_filter, $severity_filter, $vaccine_filter, $year_filter, $lab_filter, $patientid_filter);
  $is_current = ($current === 1);
?>
          <a href="<?php echo $link; ?>" class="cases-page-link<?php echo $is_current ? ' cases-page-current' : ''; ?>">1</a>
<?php
  if ($start > 2) {
?>
          <span class="cases-page-ellipsis">...</span>
<?php
  }

  for ($i = $start; $i <= $end; $i++):
      if ($i === 1 || $i === $last) continue;
      $link = cases_build_query($i, $result_filter, $severity_filter, $vaccine_filter, $year_filter, $lab_filter, $patientid_filter);
      $is_current = ($i === $current);
?>
          <a href="<?php echo $link; ?>" class="cases-page-link<?php echo $is_current ? ' cases-page-current' : ''; ?>"><?php echo $i; ?></a>
<?php endfor; ?>

<?php
  if ($last > 1) {
      if ($end < $last - 1) {
?>
          <span class="cases-page-ellipsis">...</span>
<?php
      }
      $link = cases_build_query($last, $result_filter, $severity_filter, $vaccine_filter, $year_filter, $lab_filter, $patientid_filter);
      $is_current = ($current === $last);
?>
          <a href="<?php echo $link; ?>" class="cases-page-link<?php echo $is_current ? ' cases-page-current' : ''; ?>"><?php echo $last; ?></a>
<?php } ?>

<?php
  if ($current < $last):
      $next_link = cases_build_query($current + 1, $result_filter, $severity_filter, $vaccine_filter, $year_filter, $lab_filter, $patientid_filter);
?>
          <a href="<?php echo $next_link; ?>" class="cases-page-link">Next</a>
<?php else: ?>
          <span class="cases-page-link cases-page-disabled">Next</span>
<?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="../js/cases.js"></script>
</body>
</html>

