// Page-specific JavaScript for patients.php
// Enhances pagination so navigating between pages does not reload the whole page.

document.addEventListener('DOMContentLoaded', function () {
  const patientsCard = document.querySelector('.patients-card');
  const filterForm = document.querySelector('.patients-filters');
  const searchInput = document.getElementById('searchName');
  const genderSelect = document.getElementById('genderFilter');
  const ageInput = document.getElementById('ageFilter');
  const openAddBtn = document.getElementById('openAddPatientBtn');
  const addModal = document.getElementById('addPatientModal');
  const addBackdrop = document.getElementById('addPatientBackdrop');
  const addClose = document.getElementById('addPatientClose');
  const addCancel = document.getElementById('addPatientCancel');

  if (!patientsCard) {
    return;
  }

  function openAddModal() {
    if (!addModal) return;
    addModal.classList.add('show');
    addModal.setAttribute('aria-hidden', 'false');
  }

  function closeAddModal() {
    if (!addModal) return;
    addModal.classList.remove('show');
    addModal.setAttribute('aria-hidden', 'true');
  }

  if (openAddBtn) {
    openAddBtn.addEventListener('click', openAddModal);
  }

  if (addBackdrop) {
    addBackdrop.addEventListener('click', closeAddModal);
  }

  if (addClose) {
    addClose.addEventListener('click', closeAddModal);
  }

  if (addCancel) {
    addCancel.addEventListener('click', function (e) {
      e.preventDefault();
      closeAddModal();
    });
  }

  // Prevent form submit (we use live filtering instead)
  if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
      e.preventDefault();
    });
  }

  function buildPatientsUrl(page) {
    const url = new URL(window.location.href);
    if (!page || page < 1) page = 1;
    url.searchParams.set('page', page);

    const nameVal = searchInput ? searchInput.value.trim() : '';
    const genderVal = genderSelect ? genderSelect.value : 'All';
    const ageVal = ageInput ? ageInput.value.trim() : '';

    if (nameVal) {
      url.searchParams.set('name', nameVal);
    } else {
      url.searchParams.delete('name');
    }

    if (genderVal && genderVal !== 'All') {
      url.searchParams.set('gender', genderVal);
    } else {
      url.searchParams.delete('gender');
    }

    if (ageVal) {
      url.searchParams.set('age', ageVal);
    } else {
      url.searchParams.delete('age');
    }

    return url.toString();
  }

  function loadPatients(url) {
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
        const newCard = doc.querySelector('.patients-card');
        if (!newCard) {
          return;
        }

        patientsCard.innerHTML = newCard.innerHTML;

        // Update the URL in the address bar so refresh / back work as expected
        if (window.history && window.history.pushState) {
          const newUrl = new URL(url, window.location.href);
          window.history.pushState({}, '', newUrl.toString());
        }
      })
      .catch(function (err) {
        console.error('Failed to load patients page:', err);
        // Fallback: if something goes wrong, navigate normally
        window.location.href = url;
      });
  }

  // Debounce helper for live search
  function debounce(fn, delay) {
    let timer;
    return function () {
      const args = arguments;
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(null, args), delay);
    };
  }

  const triggerFilterUpdate = debounce(function () {
    const url = buildPatientsUrl(1); // always reset to page 1 on filter change
    loadPatients(url);
  }, 350);

  // Intercept clicks on pagination links inside the patients card
  document.addEventListener('click', function (event) {
    const link = event.target.closest('.patients-pagination a.patients-page-link');
    if (!link) {
      return;
    }

    // Let disabled style (spans) and non-anchor elements behave normally
    const href = link.getAttribute('href');
    if (!href || href === '#' || href.startsWith('javascript:')) {
      return;
    }

    event.preventDefault();

    // Keep current filters but change page based on link href
    const targetUrl = new URL(href, window.location.href);
    const page = parseInt(targetUrl.searchParams.get('page') || '1', 10);
    const url = buildPatientsUrl(page);
    loadPatients(url);
  });

  // Live filtering: name input and gender select
  if (searchInput) {
    searchInput.addEventListener('input', triggerFilterUpdate);
  }

  if (genderSelect) {
    genderSelect.addEventListener('change', triggerFilterUpdate);
  }

  if (ageInput) {
    ageInput.addEventListener('input', triggerFilterUpdate);
  }
});


