/**
 * Admin Theme - Interactive Behaviors
 */
document.addEventListener('DOMContentLoaded', () => {

  /* ==========================================
     1. Interactive Button Click Animation
     ========================================== */
  const buttons = document.querySelectorAll('.btn, .action-btn, .menu-toggle');
  
  buttons.forEach(btn => {
    btn.addEventListener('mousedown', function () {
      this.classList.add('btn-clicked');
    });
    
    ['mouseup', 'mouseleave'].forEach(evt => {
      btn.addEventListener(evt, function () {
        this.classList.remove('btn-clicked');
      });
    });
  });

  /* ==========================================
     2. Mobile Sidebar Drawer Logic
     ========================================== */
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const menuBtn = document.getElementById('menuToggle');

  function setSidebar(open) {
    if (!sidebar || !backdrop || !menuBtn) return;
    sidebar.classList.toggle('open', open);
    backdrop.classList.toggle('show', open);
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (menuBtn) {
    menuBtn.addEventListener('click', () => {
      const isOpen = sidebar.classList.contains('open');
      setSidebar(!isOpen);
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', () => setSidebar(false));
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setSidebar(false);
  });

  /* ==========================================
     3. Smooth Navigation & Scroll Spy
     ========================================== */
  const menuLinks = document.querySelectorAll('.menu-link');
  const sections = document.querySelectorAll('.scroll-section');
  let clickedId = null;

  function setActiveLink(id) {
    menuLinks.forEach(link => {
      const isTarget = link.getAttribute('href') === '#' + id;
      link.classList.toggle('active', isTarget);
    });
  }

  menuLinks.forEach(link => {
    link.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId && targetId.startsWith('#')) {
        e.preventDefault();
        const targetSection = document.querySelector(targetId);
        if (targetSection) {
          clickedId = targetId.slice(1);
          setActiveLink(clickedId);
          targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        setSidebar(false);
      }
    });
  });

  ['wheel', 'touchmove', 'keydown'].forEach(evt => {
    window.addEventListener(evt, () => { clickedId = null; }, { passive: true });
  });

  if (sections.length > 0) {
    window.addEventListener('scroll', () => {
      if (clickedId) return;

      let current = sections[0].id;
      sections.forEach(section => {
        if (window.scrollY >= section.offsetTop - 140) {
          current = section.id;
        }
      });

      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) {
        current = sections[sections.length - 1].id;
      }

      setActiveLink(current);
    });
  }

  /* ==========================================
     4. Dynamic User Search & Date Filter
     ========================================== */
  const searchInput = document.getElementById('userSearch');
  const dateFrom = document.getElementById('dateFrom');
  const dateTo = document.getElementById('dateTo');
  const dateClear = document.getElementById('dateClear');

  function applyUserFilters() {
    if (!searchInput) return;

    const q = searchInput.value.trim().toLowerCase();
    const from = dateFrom ? dateFrom.value : '';
    const to = dateTo ? dateTo.value : '';

    document.querySelectorAll('.user-table').forEach(table => {
      const rows = table.querySelectorAll('tbody tr');
      let visibleCount = 0;

      rows.forEach(row => {
        const rowDate = row.dataset.date || '';
        const rowSearch = row.dataset.search || '';

        let match = rowSearch.includes(q);
        if (match && from) match = rowDate !== '' && rowDate >= from;
        if (match && to) match = rowDate !== '' && rowDate <= to;

        row.hidden = !match;
        if (match) visibleCount++;
      });

      const noResults = table.parentElement.querySelector('.no-results');
      if (noResults) noResults.classList.toggle('d-none', visibleCount !== 0);
      table.hidden = visibleCount === 0;

      const badge = document.querySelector(`[data-count="${table.dataset.role}"]`);
      if (badge) badge.textContent = visibleCount;
    });
  }

  if (searchInput) searchInput.addEventListener('input', applyUserFilters);
  if (dateFrom) dateFrom.addEventListener('change', applyUserFilters);
  if (dateTo) dateTo.addEventListener('change', applyUserFilters);
  if (dateClear) {
    dateClear.addEventListener('click', () => {
      if (dateFrom) dateFrom.value = '';
      if (dateTo) dateTo.value = '';
      applyUserFilters();
    });
  }

  /* ==========================================
     5. Posts Role Filter
     ========================================== */
  document.querySelectorAll('[data-post-filter]').forEach(btn => {
    btn.addEventListener('click', function () {
      const filter = this.dataset.postFilter;
      document.querySelectorAll('[data-post-filter]').forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      document.querySelectorAll('#postsTable tbody tr[data-role]').forEach(row => {
        row.hidden = filter !== 'all' && row.dataset.role !== filter;
      });
    });
  });

  /* ==========================================
     6. Password Visibility Toggle
     ========================================== */
  const pwInput = document.getElementById('password') || document.getElementById('new_password');
  const pwToggle = document.getElementById('pwToggle');

  if (pwInput && pwToggle) {
    pwToggle.addEventListener('click', () => {
      const isPassword = pwInput.type === 'password';
      pwInput.type = isPassword ? 'text' : 'password';
      pwToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      pwToggle.setAttribute('aria-pressed', String(isPassword));
      
      const icon = pwToggle.querySelector('i');
      if (icon) {
        icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
      }
    });
  }

  /* ==========================================
     7. Modal Data Populators
     ========================================== */
  const deleteModal = document.getElementById('deleteModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', (ev) => {
      const btn = ev.relatedTarget;
      document.getElementById('deleteUserId').value = btn.dataset.id;
      document.getElementById('deleteUserName').textContent = btn.dataset.name;
    });
  }

  const deleteItemModal = document.getElementById('deleteItemModal');
  if (deleteItemModal) {
    deleteItemModal.addEventListener('show.bs.modal', (ev) => {
      const btn = ev.relatedTarget;
      document.getElementById('deleteItemForm').action = btn.dataset.action;
      document.getElementById('deleteItemId').value = btn.dataset.id;
      document.getElementById('deleteItemTitle').textContent = btn.dataset.title;
      document.getElementById('deleteItemLabel').textContent = btn.dataset.label;
    });
  }

});