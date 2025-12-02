// Front-end logic for the dashboard charts.
// It reads JSON data from data-* attributes on the canvas
// elements and draws charts using Chart.js.

document.addEventListener('DOMContentLoaded', function () {
  const yearlyCanvas = document.getElementById('yearlyCasesChart');
  const yearFilter = document.getElementById('yearFilter');
  const severityCanvas = document.getElementById('severityChart');
  const severityFilter = document.getElementById('severityResultFilter');
  const growthBtn = document.getElementById('seeGrowthRateBtn');
  const growthModal = document.getElementById('growthModal');
  const growthModalClose = document.getElementById('growthModalClose');
  const moreCards = document.querySelectorAll('.more-card');
  const analyticsSections = document.querySelectorAll('.analytics-section');

  // -------------------
  // Yearly positive cases
  // -------------------
  if (yearlyCanvas && yearlyCanvas.dataset.yearly) {
    let yearlyData = [];
    let monthlyData = {};
    try {
      yearlyData = JSON.parse(yearlyCanvas.dataset.yearly);
    } catch (e) {
      console.error('Invalid yearly data', e);
    }

  // -------------------
  // Growth Rate modal
  // -------------------
  function openGrowthModal() {
    if (growthModal) {
      growthModal.classList.add('show');
    }
  }

  function closeGrowthModal() {
    if (growthModal) {
      growthModal.classList.remove('show');
    }
  }

  if (growthBtn && growthModal) {
    growthBtn.addEventListener('click', function (e) {
      e.preventDefault();
      openGrowthModal();
    });
  }

  if (growthModalClose && growthModal) {
    growthModalClose.addEventListener('click', function () {
      closeGrowthModal();
    });
  }

  if (growthModal) {
    const backdrop = growthModal.querySelector('.growth-modal-backdrop');
    if (backdrop) {
      backdrop.addEventListener('click', closeGrowthModal);
    }
  }

  // More Analytics cards scroll behavior
  moreCards.forEach(card => {
    card.addEventListener('click', function () {
      const target = this.getAttribute('data-target');
      if (!target) return;
      const el = document.querySelector(target);
      if (!el) return;

      const isCurrentlyVisible = el.classList.contains('show');

      // if already visible, hide all (toggle off)
      if (isCurrentlyVisible) {
        analyticsSections.forEach(sec => sec.classList.remove('show'));
        return;
      }

      // otherwise hide all, then show the selected one and scroll
      analyticsSections.forEach(sec => sec.classList.remove('show'));
      el.classList.add('show');
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

    if (yearlyCanvas.dataset.monthly) {
      try {
        monthlyData = JSON.parse(yearlyCanvas.dataset.monthly);
      } catch (e) {
        console.error('Invalid monthly data', e);
      }
    }

    const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function getCasesSeries(selectedYear) {
      if (!selectedYear || selectedYear === 'All') {
        return {
          labels: yearlyData.map(item => item.year),
          values: yearlyData.map(item => item.total),
        };
      }

      const yearKey = String(selectedYear);
      const monthsForYear = monthlyData[yearKey] || [];
      const totalsByMonth = {};

      monthsForYear.forEach(item => {
        totalsByMonth[item.month] = item.total;
      });

      const values = monthLabels.map((_, idx) => {
        const m = idx + 1;
        return totalsByMonth[m] || 0;
      });

      return {
        labels: monthLabels,
        values: values,
      };
    }

    const initialYearSeries = getCasesSeries('All');

    const yearlyChart = new Chart(yearlyCanvas, {
      type: 'line',
      data: {
        labels: initialYearSeries.labels,
        datasets: [{
          label: 'Positive cases',
          data: initialYearSeries.values,
          borderColor: '#0f766e',
          backgroundColor: 'rgba(16, 185, 129, 0.10)',
          tension: 0.35,
          fill: false,
          pointRadius: 4,
          pointBackgroundColor: '#0f766e',
          pointHoverRadius: 5
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { color: '#e5e7eb' }
          },
          y: {
            beginAtZero: false,
            grid: { color: '#e5e7eb' }
          }
        }
      }
    });

    if (yearFilter) {
      yearFilter.addEventListener('change', function () {
        const series = getCasesSeries(this.value || 'All');
        yearlyChart.data.labels = series.labels;
        yearlyChart.data.datasets[0].data = series.values;
        yearlyChart.update();
      });
    }
  }

  if (severityCanvas && severityCanvas.dataset.severity) {
    let severityData = {};
    try {
      severityData = JSON.parse(severityCanvas.dataset.severity);
    } catch (e) {
      console.error('Invalid severity data', e);
    }

    function getSeries(resultKey) {
      const key = resultKey && severityData[resultKey] ? resultKey : 'All';
      const arr = severityData[key] || [];
      return {
        labels: arr.map(item => item.severity),
        values: arr.map(item => item.total),
      };
    }

    const initial = getSeries('All');

    const severityChart = new Chart(severityCanvas, {
      type: 'doughnut',
      data: {
        labels: initial.labels,
        datasets: [{
          data: initial.values,
          // Stronger, non-pastel colors for better contrast
          backgroundColor: [
            '#1d4ed8', // Mild - blue
            '#0f766e', // Moderate - teal
            '#f59e0b', // Severe - amber
            '#b91c1c', // Critical / Unknown - deep red
          ],
          borderColor: '#ffffff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });

    if (severityFilter) {
      severityFilter.addEventListener('change', function () {
        const series = getSeries(this.value || 'All');
        severityChart.data.labels = series.labels;
        severityChart.data.datasets[0].data = series.values;
        severityChart.update();
      });
    }
  }
});

