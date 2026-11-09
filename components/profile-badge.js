/**
 * FarmMarket - Interactive Profile Badge & Dropdown Behavior
 * Provides full keyboard accessibility (WAI-ARIA menu pattern),
 * mouse click handling, outside-click closing, and focus management.
 */
(function () {
  'use strict';

  function initProfileMenus() {
    const menus = document.querySelectorAll('[data-user-menu]');

    menus.forEach((menu) => {
      if (menu.dataset.initialized === 'true') return;
      menu.dataset.initialized = 'true';

      const button = menu.querySelector('.profile-btn, [data-profile-trigger]');
      const dropdown = menu.querySelector('.profile-dropdown, [data-profile-dropdown]');

      if (!button || !dropdown) return;

      const getMenuItems = () => {
        return Array.from(dropdown.querySelectorAll('[role="menuitem"], a, button')).filter(
          (el) => !el.disabled && el.offsetParent !== null
        );
      };

      const parentHeader = menu.closest('.page-header, .header');

      const openDropdown = (focusFirst = false, focusLast = false) => {
        dropdown.hidden = false;
        dropdown.classList.add('open');
        button.setAttribute('aria-expanded', 'true');
        menu.classList.add('has-open-menu');
        if (parentHeader) parentHeader.classList.add('has-open-menu');

        if (focusFirst) {
          const items = getMenuItems();
          if (items.length > 0) items[0].focus();
        } else if (focusLast) {
          const items = getMenuItems();
          if (items.length > 0) items[items.length - 1].focus();
        }
      };

      const closeDropdown = (restoreFocus = false) => {
        dropdown.classList.remove('open');
        dropdown.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        menu.classList.remove('has-open-menu');
        if (parentHeader) parentHeader.classList.remove('has-open-menu');

        if (restoreFocus) {
          button.focus();
        }
      };

      const toggleDropdown = () => {
        const isOpen = button.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
          closeDropdown();
        } else {
          openDropdown();
        }
      };

      // Click to toggle
      button.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown();
      });

      // Keyboard handling on trigger button
      button.addEventListener('keydown', (e) => {
        switch (e.key) {
          case 'Enter':
          case ' ':
            e.preventDefault();
            toggleDropdown();
            break;
          case 'ArrowDown':
            e.preventDefault();
            openDropdown(true);
            break;
          case 'ArrowUp':
            e.preventDefault();
            openDropdown(false, true);
            break;
          case 'Escape':
            closeDropdown(true);
            break;
        }
      });

      // Keyboard navigation inside dropdown menu
      dropdown.addEventListener('keydown', (e) => {
        const items = getMenuItems();
        const currentIndex = items.indexOf(document.activeElement);

        switch (e.key) {
          case 'ArrowDown': {
            e.preventDefault();
            const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
            if (items[nextIndex]) items[nextIndex].focus();
            break;
          }
          case 'ArrowUp': {
            e.preventDefault();
            const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
            if (items[prevIndex]) items[prevIndex].focus();
            break;
          }
          case 'Home':
            e.preventDefault();
            if (items.length > 0) items[0].focus();
            break;
          case 'End':
            e.preventDefault();
            if (items.length > 0) items[items.length - 1].focus();
            break;
          case 'Escape':
            e.preventDefault();
            closeDropdown(true);
            break;
          case 'Tab':
            // If tabbing away, close the menu smoothly
            setTimeout(() => {
              if (!menu.contains(document.activeElement)) {
                closeDropdown(false);
              }
            }, 10);
            break;
        }
      });

      // Close menu when clicking outside
      document.addEventListener('click', (e) => {
        if (!menu.contains(e.target)) {
          closeDropdown();
        }
      });

      // Close menu if Escape is pressed anywhere
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
          closeDropdown(true);
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProfileMenus);
  } else {
    initProfileMenus();
  }

  // Export helper globally if needed
  window.initProfileMenus = initProfileMenus;
})();

