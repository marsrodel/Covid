// Page-specific JavaScript for cases.php
// Enhances pagination and filters so navigating between pages and changing filters
// does not reload the whole page.

document.addEventListener('DOMContentLoaded', function () {
  const casesCard = document.querySelector('.cases-card');
  const filtersForm = document.querySelector('.cases-filters');
  const openAddCaseBtn = document.getElementById('openAddCaseBtn');
  const addCaseModal = document.getElementById('addCaseModal');
  const addCaseBackdrop = document.getElementById('addCaseBackdrop');
  const addCaseClose = document.getElementById('addCaseClose');
  const addCaseCancel = document.getElementById('addCaseCancel');
  const caseForm = document.getElementById('caseForm');
  const acCaseId = document.getElementById('acCaseId');
  const acTestDate = document.getElementById('acTestDate');
  const acPatientId = document.getElementById('acPatientId');
  const acResult = document.getElementById('acResult');
  const acSeverity = document.getElementById('acSeverity');
  const acVaccine = document.getElementById('acVaccine');
  const acLab = document.getElementById('acLab');

  // Delete confirmation modal elements
  const deleteCaseModal = document.getElementById('deleteCaseModal');
  const deleteCaseBackdrop = document.getElementById('deleteCaseBackdrop');
  const deleteCaseClose = document.getElementById('deleteCaseClose');
  const deleteCaseCancel = document.getElementById('deleteCaseCancel');
  const deleteCaseConfirm = document.getElementById('deleteCaseConfirm');
  let pendingDeleteCaseId = null;

  if (!casesCard) {
    return;
  }

  function openAddCaseModal() {
    if (!addCaseModal) return;
    if (caseForm) {
      caseForm.reset();
      caseForm.action = '../server/add_cases.php';
    }
    addCaseModal.classList.add('show');
    addCaseModal.setAttribute('aria-hidden', 'false');
  }

  function closeAddCaseModal() {
    if (!addCaseModal) return;
    addCaseModal.classList.remove('show');
    addCaseModal.setAttribute('aria-hidden', 'true');
  }

  if (openAddCaseBtn) {
    openAddCaseBtn.addEventListener('click', openAddCaseModal);
  }

  if (addCaseBackdrop) {
    addCaseBackdrop.addEventListener('click', closeAddCaseModal);
  }

  if (addCaseClose) {
    addCaseClose.addEventListener('click', closeAddCaseModal);
  }

  if (addCaseCancel) {
    addCaseCancel.addEventListener('click', function (e) {
      e.preventDefault();
      closeAddCaseModal();
    });
  }

  function openDeleteCaseModal(caseId) {
    if (!deleteCaseModal) return;
    pendingDeleteCaseId = caseId;
    deleteCaseModal.classList.add('show');
    deleteCaseModal.setAttribute('aria-hidden', 'false');
  }

  function closeDeleteCaseModal() {
    if (!deleteCaseModal) return;
    deleteCaseModal.classList.remove('show');
    deleteCaseModal.setAttribute('aria-hidden', 'true');
    pendingDeleteCaseId = null;
  }

  if (filtersForm) {
    filtersForm.addEventListener('submit', function (e) {
      e.preventDefault();
    });
  }

  // Submit Add Case form via AJAX so the page does not fully reload
  if (caseForm) {
    caseForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const actionUrl = caseForm.action;
      const formData = new FormData(caseForm);

      fetch(actionUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          const current = new URL(window.location.href);
          const currentPage = parseInt(current.searchParams.get('page') || '1', 10);
          const url = buildCasesUrl(currentPage);
          closeAddCaseModal();
          loadCases(url);
        })
        .catch(function () {
          // Fallback: submit normally if AJAX fails
          caseForm.submit();
        });
    });
  }

  // Edit Case handler (event delegation)
  document.addEventListener('click', function (event) {
    const editBtn = event.target.closest('.patients-action-edit');
    if (!editBtn || !addCaseModal || !caseForm) return;

    const caseId = editBtn.getAttribute('data-case-id') || '';
    const testDate = editBtn.getAttribute('data-test-date') || '';
    const patientId = editBtn.getAttribute('data-patient-id') || '';
    const result = editBtn.getAttribute('data-result') || '';
    const severity = editBtn.getAttribute('data-severity') || '';
    const vaccineId = editBtn.getAttribute('data-vaccine-id') || '';
    const labId = editBtn.getAttribute('data-lab-id') || '';

    caseForm.action = '../server/edit_case.php';

    if (acCaseId) acCaseId.value = caseId;
    if (acTestDate) acTestDate.value = testDate;
    if (acPatientId) acPatientId.value = patientId;
    if (acResult) acResult.value = result;
    if (acSeverity) acSeverity.value = severity;
    if (acVaccine) acVaccine.value = vaccineId;
    if (acLab) acLab.value = labId;

    addCaseModal.classList.add('show');
    addCaseModal.setAttribute('aria-hidden', 'false');
  });

  // Delete Case handler (event delegation)
  document.addEventListener('click', function (event) {
    const deleteBtn = event.target.closest('.patients-action-delete');
    if (!deleteBtn) return;

    const caseId = deleteBtn.getAttribute('data-case-id');
    if (!caseId) return;
    openDeleteCaseModal(caseId);
  });

  if (deleteCaseBackdrop) {
    deleteCaseBackdrop.addEventListener('click', closeDeleteCaseModal);
  }

  if (deleteCaseClose) {
    deleteCaseClose.addEventListener('click', closeDeleteCaseModal);
  }

  if (deleteCaseCancel) {
    deleteCaseCancel.addEventListener('click', function (e) {
      e.preventDefault();
      closeDeleteCaseModal();
    });
  }

  if (deleteCaseConfirm) {
    deleteCaseConfirm.addEventListener('click', function () {
      if (!pendingDeleteCaseId) {
        closeDeleteCaseModal();
        return;
      }

      const formData = new FormData();
      formData.append('case_id', pendingDeleteCaseId);

      fetch('../server/delete_cases.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      })
        .then(function (response) {
          if (!response.ok && response.status !== 204) {
            throw new Error('Network response was not ok');
          }
          const current = new URL(window.location.href);
          const currentPage = parseInt(current.searchParams.get('page') || '1', 10);
          const url = buildCasesUrl(currentPage);
          closeDeleteCaseModal();
          loadCases(url);
        })
        .catch(function () {
          // Fallback: navigate normally if AJAX fails
          window.location.href = '../server/delete_cases.php?case_id=' + encodeURIComponent(pendingDeleteCaseId);
        });
    });
  }

  function buildCasesUrl(page) {
    const url = new URL(window.location.href);
    if (!page || page < 1) page = 1;
    url.searchParams.set('page', page);

    // Always fetch the latest filter elements so this continues working
    // even after the filters DOM is replaced via AJAX.
    const resultSelect = document.getElementById('resultFilter');
    const severitySelect = document.getElementById('severityFilter');
    const vaccineSelect = document.getElementById('vaccineFilter');
    const yearSelect = document.getElementById('yearFilter');
    const labSelect = document.getElementById('labFilter');
    const patientIdInput = document.getElementById('patientIdFilter');

    const resultVal = resultSelect ? resultSelect.value : 'All';
    const sevVal = severitySelect ? severitySelect.value : 'All';
    const vacVal = vaccineSelect ? vaccineSelect.value : 'All';
    const yearVal = yearSelect ? yearSelect.value : 'All';
    const labVal = labSelect ? labSelect.value : 'All';
    const pidVal = patientIdInput ? patientIdInput.value.trim() : '';

    if (resultVal && resultVal !== 'All') url.searchParams.set('result', resultVal);
    else url.searchParams.delete('result');

    if (sevVal && sevVal !== 'All') url.searchParams.set('severity', sevVal);
    else url.searchParams.delete('severity');

    if (vacVal && vacVal !== 'All') url.searchParams.set('vaccine', vacVal);
    else url.searchParams.delete('vaccine');

    if (yearVal && yearVal !== 'All') url.searchParams.set('year', yearVal);
    else url.searchParams.delete('year');

    if (labVal && labVal !== 'All') url.searchParams.set('lab', labVal);
    else url.searchParams.delete('lab');

    if (pidVal) url.searchParams.set('patient_id', pidVal);
    else url.searchParams.delete('patient_id');

    return url.toString();
  }

  function loadCases(url) {
    fetch(url, { credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(function (html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newCard = doc.querySelector('.cases-card');
        if (!newCard) {
          // If structure is not found, fall back to full navigation
          window.location.href = url;
          return;
        }

        casesCard.innerHTML = newCard.innerHTML;

        if (history.pushState) {
          history.pushState({}, '', new URL(url, window.location.href).toString());
        }
      })
      .catch(function () {
        window.location.href = url;
      });
  }

  function debounce(fn, delay) {
    let timer = null;
    return function () {
      const args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(null, args);
      }, delay);
    };
  }

  const triggerFilterUpdate = debounce(function () {
    const url = buildCasesUrl(1);
    loadCases(url);
  }, 250);

  // Use event delegation so new select elements added via AJAX
  // automatically keep the live filtering behavior.
  if (filtersForm) {
    filtersForm.addEventListener('change', function (event) {
      const target = event.target;
      if (target && target.classList && target.classList.contains('cases-select')) {
        triggerFilterUpdate();
      }
    });

    // Live update when typing Patient ID
    filtersForm.addEventListener('input', function (event) {
      const target = event.target;
      if (target && target.id === 'patientIdFilter') {
        triggerFilterUpdate();
      }
    });
  }

  // Intercept pagination link clicks
  document.addEventListener('click', function (event) {
    const link = event.target.closest('.cases-pagination a.cases-page-link');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href || href === '#' || href.startsWith('javascript:')) return;

    event.preventDefault();
    const targetUrl = new URL(href, window.location.href);
    const page = parseInt(targetUrl.searchParams.get('page') || '1', 10);
    const url = buildCasesUrl(page);
    loadCases(url);
  });
});

