import React, { useState, useRef, useEffect } from 'react';

/**
 * Reusable ProfileBadge Component (React)
 * 
 * Accessible dropdown menu for FarmMarket Buyer and Farmer dashboards.
 * 
 * @param {Object} props
 * @param {string} props.name - User's full name (e.g. "Juan Dela Cruz")
 * @param {string} props.role - User's role ('Buyer' | 'Farmer')
 * @param {string} [props.email] - Optional user email
 * @param {string} [props.profileUrl='/profile'] - Link to profile page
 * @param {string} [props.settingsUrl='/profile#settings'] - Link to settings page
 * @param {string} [props.logoutUrl='/logout'] - Link/action for logout
 * @param {Function} [props.onLogout] - Optional logout handler callback
 * @param {string} [props.className] - Optional extra class names
 */
export default function ProfileBadge({
  name = 'User',
  role = 'Buyer',
  email = '',
  profileUrl = '/profile',
  settingsUrl = '/profile#settings',
  logoutUrl = '/logout',
  onLogout,
  className = ''
}) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef(null);
  const buttonRef = useRef(null);
  const menuRef = useRef(null);

  // Compute initials: e.g. "Juan Dela Cruz" -> "JC", "Buyer" -> "B"
  const initials = (() => {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return role.charAt(0).toUpperCase();
    const first = parts[0].charAt(0).toUpperCase();
    const last = parts.length > 1 ? parts[parts.length - 1].charAt(0).toUpperCase() : '';
    return first + last;
  })();

  // Close when clicking outside
  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Keyboard navigation & accessibility
  const handleButtonKeyDown = (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      setIsOpen((prev) => !prev);
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      setIsOpen(true);
      setTimeout(() => {
        const first = menuRef.current?.querySelector('[role="menuitem"]');
        first?.focus();
      }, 50);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setIsOpen(true);
      setTimeout(() => {
        const items = menuRef.current?.querySelectorAll('[role="menuitem"]');
        if (items && items.length > 0) items[items.length - 1].focus();
      }, 50);
    } else if (e.key === 'Escape') {
      setIsOpen(false);
    }
  };

  const handleMenuKeyDown = (e) => {
    const items = Array.from(menuRef.current?.querySelectorAll('[role="menuitem"]') || []);
    const currentIndex = items.indexOf(document.activeElement);

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
      items[nextIndex]?.focus();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
      items[prevIndex]?.focus();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      setIsOpen(false);
      buttonRef.current?.focus();
    } else if (e.key === 'Tab') {
      setTimeout(() => {
        if (!containerRef.current?.contains(document.activeElement)) {
          setIsOpen(false);
        }
      }, 10);
    }
  };

  return (
    <div
      ref={containerRef}
      className={`user-profile-menu ${className}`}
      data-user-menu
      style={{ position: 'relative', display: 'inline-block', zIndex: 100 }}
    >
      <button
        ref={buttonRef}
        type="button"
        className="profile profile-btn"
        id="user-profile-btn"
        aria-haspopup="menu"
        aria-expanded={isOpen}
        aria-controls="user-profile-menu"
        aria-label={`User profile options for ${name} (${role})`}
        onClick={() => setIsOpen((prev) => !prev)}
        onKeyDown={handleButtonKeyDown}
      >
        <span className="avatar" aria-hidden="true">{initials}</span>
        <span className="profile-role">{role}</span>
        <i
          className="fa-solid fa-chevron-down profile-chevron"
          aria-hidden="true"
          style={{ transform: isOpen ? 'rotate(180deg)' : 'none' }}
        />
      </button>

      {isOpen && (
        <div
          ref={menuRef}
          id="user-profile-menu"
          className="profile-dropdown open"
          role="menu"
          aria-labelledby="user-profile-btn"
          onKeyDown={handleMenuKeyDown}
        >
          <div className="profile-dropdown-header">
            <div className="profile-dropdown-user">
              <span className="profile-dropdown-avatar" aria-hidden="true">{initials}</span>
              <div className="profile-dropdown-meta">
                <strong className="profile-dropdown-name">{name}</strong>
                <span className="profile-dropdown-role">
                  {role}{email ? ` • ${email}` : ''}
                </span>
              </div>
            </div>
          </div>

          <ul className="profile-dropdown-list" role="none">
            <li role="none">
              <a
                href={profileUrl}
                className="profile-dropdown-item"
                role="menuitem"
                tabIndex={0}
                onClick={() => setIsOpen(false)}
              >
                <i className="fa-solid fa-user" aria-hidden="true" />
                <span>My Profile</span>
              </a>
            </li>

            <li role="none">
              <a
                href={settingsUrl}
                className="profile-dropdown-item"
                role="menuitem"
                tabIndex={0}
                onClick={() => setIsOpen(false)}
              >
                <i className="fa-solid fa-gear" aria-hidden="true" />
                <span>Account Settings</span>
              </a>
            </li>

            <li role="none" className="dropdown-divider-item">
              <div className="profile-dropdown-divider" role="separator" />
            </li>

            <li role="none">
              {onLogout ? (
                <button
                  type="button"
                  className="profile-dropdown-item item-danger"
                  role="menuitem"
                  tabIndex={0}
                  onClick={(e) => {
                    setIsOpen(false);
                    onLogout(e);
                  }}
                >
                  <i className="fa-solid fa-right-from-bracket" aria-hidden="true" />
                  <span>Logout</span>
                </button>
              ) : (
                <a
                  href={logoutUrl}
                  className="profile-dropdown-item item-danger"
                  role="menuitem"
                  tabIndex={0}
                  onClick={() => setIsOpen(false)}
                >
                  <i className="fa-solid fa-right-from-bracket" aria-hidden="true" />
                  <span>Logout</span>
                </a>
              )}
            </li>
          </ul>
        </div>
      )}
    </div>
  );
}

