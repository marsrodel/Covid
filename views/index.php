<?php
// Main dashboard page.
// All SQL for this page is stored in server/dashboard_queries.php
// so that this file focuses on layout and display only.

require_once __DIR__ . '/../server/dashboard_queries.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COVID Analytics - Dashboard</title>
  <link rel="stylesheet" href="../css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
  <header class="header">
    <div class="container header-inner">
      <div class="brand">
        <div class="brand-mark">CA</div>
        <div class="brand-text">
          <span class="brand-title">COVID Analytics</span>
          <span class="brand-subtitle">IT 108 · COVID-19 Dataset</span>
        </div>
      </div>
      <nav class="nav">
        <a href="index.php" class="nav-link nav-link-active">Dashboard</a>
        <a href="cases.php" class="nav-link">Cases</a>
        <a href="patients.php" class="nav-link">Patients</a>
      </nav>
    </div>
  </header>

  <main class="container main">
    <section class="section hero">
      <div class="hero-text">
        <p class="hero-badge">Health • COVID-19 Analytics</p>
        <h1 class="hero-title">Track COVID-19 cases, severity, and vaccinations</h1>
        <p class="hero-subtitle">Analytical views based on records from the MySQL database.</p>
        <div class="hero-actions">
          <a href="cases.php" class="hero-btn hero-btn-primary">View Cases</a>
          <a href="patients.php" class="hero-btn hero-btn-primary">Explore Patients</a>
          <button type="button" class="hero-btn hero-btn-ghost" id="seeGrowthRateBtn">See Growth Rate</button>
        </div>
      </div>
    </section>

    <section class="section">
      <h2 class="section-title">Key numbers</h2>
      <div class="kpi-grid">
        <div class="card kpi">
          <div class="kpi-main">
            <p class="kpi-label">Total Results</p>
            <p class="kpi-value kpi-value-total"><?php echo number_format($total_cases); ?></p>
          </div>
          <span class="kpi-pill kpi-pill-all">All Time</span>
        </div>
        <div class="card kpi">
          <div class="kpi-main">
            <p class="kpi-label">Positive</p>
            <p class="kpi-value kpi-value-danger"><?php echo number_format($positivity_pct, 2); ?>%</p>
          </div>
          <span class="kpi-pill kpi-pill-positive">Positivity</span>
        </div>
        <div class="card kpi">
          <div class="kpi-main">
            <p class="kpi-label">Severe/Critical</p>
            <p class="kpi-value kpi-value-warning"><?php echo number_format($sevcrit_pct, 2); ?>%</p>
          </div>
          <span class="kpi-pill kpi-pill-severity">Severity</span>
        </div>
        <div class="card kpi">
          <div class="kpi-main">
            <p class="kpi-label">Vaccinated</p>
            <p class="kpi-value kpi-value-success"><?php echo number_format($vac_pct, 2); ?>%</p>
          </div>
          <span class="kpi-pill kpi-pill-success">Coverage</span>
        </div>
      </div>
    </section>

    <section class="section charts">
      <div class="card chart-card">
        <div class="chart-header">
          <h2 class="section-title">Cases Over Time (Positive)</h2>
          <div class="chart-filter-row">
            <label for="yearFilter" class="chart-filter-label">Year:</label>
            <select id="yearFilter" class="chart-filter-select">
              <option value="All">All</option>
<?php foreach ($yearly_cases as $yc) : ?>
              <option value="<?php echo htmlspecialchars($yc['year']); ?>"><?php echo htmlspecialchars($yc['year']); ?></option>
<?php endforeach; ?>
            </select>
          </div>
        </div>
        <canvas
          id="yearlyCasesChart"
          data-yearly='<?php echo json_encode($yearly_cases); ?>'
          data-monthly='<?php echo json_encode($monthly_cases); ?>'
        ></canvas>
      </div>

      <div class="card chart-card">
        <div class="chart-header">
          <h2 class="section-title">Severity Distribution</h2>
          <div class="chart-filter-row">
            <label for="severityResultFilter" class="chart-filter-label">Status:</label>
            <select id="severityResultFilter" class="chart-filter-select">
              <option value="All">All</option>
              <option value="Positive">Positive</option>
              <option value="Negative">Negative</option>
            </select>
          </div>
        </div>
        <canvas
          id="severityChart"
          data-severity='<?php echo json_encode($severity_counts); ?>'
        ></canvas>
      </div>
    </section>

    <section class="section more-analytics">
      <h2 class="section-title">More Analytics</h2>
      <p class="section-caption">Quick access to supporting datasets</p>
      <div class="more-grid">
        <button type="button" class="more-card" data-target="#labsSection">
          <div class="more-card-body">
            <span class="more-card-title">Testing Labs</span>
            <span class="more-card-subtitle">Laboratory capacity and details</span>
          </div>
        </button>
        <button type="button" class="more-card" data-target="#vaccinesSection">
          <div class="more-card-body">
            <span class="more-card-title">Vaccines</span>
            <span class="more-card-subtitle">Vaccine inventory and usage</span>
          </div>
        </button>
        <button type="button" class="more-card" data-target="#locationsSection">
          <div class="more-card-body">
            <span class="more-card-title">Covered Locations</span>
            <span class="more-card-subtitle">Cities and regions in this dataset</span>
          </div>
        </button>
      </div>
    </section>

<?php // Testing labs table ?>
    <section class="section analytics-section" id="labsSection">
      <h2 class="section-title">Testing Labs</h2>
      <div class="card data-card">
        <div class="growth-table-scroll">
          <table class="growth-table">
            <thead>
              <tr>
                <th>Lab</th>
                <th>City</th>
                <th>Capacity/Day</th>
              </tr>
            </thead>
            <tbody>
<?php if ($labs_result && mysqli_num_rows($labs_result) > 0): ?>
<?php while ($lab = mysqli_fetch_assoc($labs_result)): ?>
              <tr>
                <td><?php echo htmlspecialchars($lab['lab_name']); ?></td>
                <td><?php echo htmlspecialchars($lab['city']); ?></td>
                <td><?php echo htmlspecialchars($lab['capacity_per_day']); ?></td>
              </tr>
<?php endwhile; ?>
<?php else: ?>
              <tr><td colspan="3">No lab records found.</td></tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

<?php // Vaccines table ?>
    <section class="section analytics-section" id="vaccinesSection">
      <h2 class="section-title">Vaccines</h2>
      <div class="card data-card">
        <div class="growth-table-scroll">
          <table class="growth-table">
            <thead>
              <tr>
                <th>Vaccine</th>
                <th>Manufacturer</th>
                <th>Doses Required</th>
              </tr>
            </thead>
            <tbody>
<?php if ($vaccines_result && mysqli_num_rows($vaccines_result) > 0): ?>
<?php while ($v = mysqli_fetch_assoc($vaccines_result)): ?>
              <tr>
                <td><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
                <td><?php echo htmlspecialchars($v['manufacturer']); ?></td>
                <td><?php echo htmlspecialchars($v['doses_required']); ?></td>
              </tr>
<?php endwhile; ?>
<?php else: ?>
              <tr><td colspan="3">No vaccine records found.</td></tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

<?php // Locations table ?>
    <section class="section analytics-section" id="locationsSection">
      <h2 class="section-title">Covered Locations</h2>
      <div class="card data-card">
        <div class="growth-table-scroll">
          <table class="growth-table">
            <thead>
              <tr>
                <th>City</th>
                <th>Region</th>
                <th>Country</th>
              </tr>
            </thead>
            <tbody>
<?php if ($locations_result && mysqli_num_rows($locations_result) > 0): ?>
<?php while ($loc = mysqli_fetch_assoc($locations_result)): ?>
              <tr>
                <td><?php echo htmlspecialchars($loc['city']); ?></td>
                <td><?php echo htmlspecialchars($loc['region']); ?></td>
                <td><?php echo htmlspecialchars($loc['country']); ?></td>
              </tr>
<?php endwhile; ?>
<?php else: ?>
              <tr><td colspan="3">No location records found.</td></tr>
<?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <!-- Growth Rate Modal -->
  <div id="growthModal" class="growth-modal" aria-hidden="true">
    <div class="growth-modal-backdrop"></div>
    <div class="growth-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="growthModalTitle">
      <div class="growth-modal-header">
        <h2 id="growthModalTitle" class="growth-modal-title">Growth Rate of Positive Cases</h2>
        <button type="button" class="growth-modal-close" id="growthModalClose" aria-label="Close growth rate">×</button>
      </div>
      <div class="growth-modal-body">
        <p class="growth-modal-subtitle">Year-over-year and month-to-month growth of positive COVID-19 cases.</p>

        <div class="growth-section">
          <h3 class="growth-section-title">Yearly Growth (Positive Cases)</h3>
          <div class="growth-table-scroll">
            <table class="growth-table">
              <thead>
                <tr>
                  <th>Year</th>
                  <th>Total Positive Cases</th>
                  <th>Previous Year</th>
                  <th>Growth Rate</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($yearly_growth as $row): ?>
<?php
  $year       = $row['year'];
  $total      = $row['total'];
  $prev       = $row['previous'];
  $growth_pct = $row['growth_pct'];
?>
                <tr>
                  <td><?php echo htmlspecialchars($year); ?></td>
                  <td><?php echo number_format($total); ?></td>
                  <td><?php echo $prev === null ? '—' : number_format($prev); ?></td>
                  <td><?php echo $growth_pct === null ? '—' : number_format($growth_pct, 2) . '%'; ?></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="growth-section">
          <h3 class="growth-section-title">Monthly Growth (Positive Cases)</h3>
          <div class="growth-table-scroll">
            <table class="growth-table">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Total Positive Cases</th>
                  <th>Previous Month</th>
                  <th>Growth Rate</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($monthly_growth as $row): ?>
<?php
  $month_label = $row['label'];
  $total       = $row['total'];
  $prev        = $row['previous'];
  $growth_pct  = $row['growth_pct'];
?>
                <tr>
                  <td><?php echo htmlspecialchars($month_label); ?></td>
                  <td><?php echo number_format($total); ?></td>
                  <td><?php echo $prev === null ? '—' : number_format($prev); ?></td>
                  <td><?php echo $growth_pct === null ? '—' : number_format($growth_pct, 2) . '%'; ?></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer">
    <div class="container footer-inner">
      <span>2025 · COVID Analytics · For academic use only</span>
    </div>
  </footer>

  <script src="../js/index.js"></script>
</body>
</html>

