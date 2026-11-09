<template>
  <div
    ref="containerRef"
    class="user-profile-menu"
    :class="customClass"
    data-user-menu
  >
    <button
      ref="buttonRef"
      type="button"
      class="profile profile-btn"
      :id="`${id}-btn`"
      aria-haspopup="menu"
      :aria-expanded="isOpen"
      :aria-controls="`${id}-menu`"
      :aria-label="`User profile options for ${name} (${role})`"
      @click="toggleDropdown"
      @keydown="handleButtonKeyDown"
    >
      <span class="avatar" aria-hidden="true">{{ initials }}</span>
      <span class="profile-role">{{ role }}</span>
      <i
        class="fa-solid fa-chevron-down profile-chevron"
        aria-hidden="true"
        :style="{ transform: isOpen ? 'rotate(180deg)' : 'none' }"
      ></i>
    </button>

    <div
      v-show="isOpen"
      ref="menuRef"
      :id="`${id}-menu`"
      class="profile-dropdown"
      :class="{ open: isOpen }"
      role="menu"
      :aria-labelledby="`${id}-btn`"
      @keydown="handleMenuKeyDown"
    >
      <div class="profile-dropdown-header">
        <div class="profile-dropdown-user">
          <span class="profile-dropdown-avatar" aria-hidden="true">{{ initials }}</span>
          <div class="profile-dropdown-meta">
            <strong class="profile-dropdown-name">{{ name }}</strong>
            <span class="profile-dropdown-role">
              {{ role }}<template v-if="email"> • {{ email }}</template>
            </span>
          </div>
        </div>
      </div>

      <ul class="profile-dropdown-list" role="none">
        <li role="none">
          <a
            :href="profileUrl"
            class="profile-dropdown-item"
            role="menuitem"
            tabindex="0"
            @click="closeDropdown"
          >
            <i class="fa-solid fa-user" aria-hidden="true"></i>
            <span>My Profile</span>
          </a>
        </li>

        <li role="none">
          <a
            :href="settingsUrl"
            class="profile-dropdown-item"
            role="menuitem"
            tabindex="0"
            @click="closeDropdown"
          >
            <i class="fa-solid fa-gear" aria-hidden="true"></i>
            <span>Account Settings</span>
          </a>
        </li>

        <li role="none" class="dropdown-divider-item">
          <div class="profile-dropdown-divider" role="separator"></div>
        </li>

        <li role="none">
          <a
            :href="logoutUrl"
            class="profile-dropdown-item item-danger"
            role="menuitem"
            tabindex="0"
            @click="handleLogout"
          >
            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
  name: {
    type: String,
    default: 'User'
  },
  role: {
    type: String,
    default: 'Buyer'
  },
  email: {
    type: String,
    default: ''
  },
  profileUrl: {
    type: String,
    default: '/profile'
  },
  settingsUrl: {
    type: String,
    default: '/profile#settings'
  },
  logoutUrl: {
    type: String,
    default: '/logout'
  },
  customClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['logout', 'toggle']);

const isOpen = ref(false);
const containerRef = ref(null);
const buttonRef = ref(null);
const menuRef = ref(null);
const id = `user-profile-${Math.random().toString(36).substring(2, 8)}`;

const initials = computed(() => {
  const parts = props.name.trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return props.role.charAt(0).toUpperCase();
  const first = parts[0].charAt(0).toUpperCase();
  const last = parts.length > 1 ? parts[parts.length - 1].charAt(0).toUpperCase() : '';
  return first + last;
});

function toggleDropdown() {
  isOpen.value = !isOpen.value;
  emit('toggle', isOpen.value);
}

function closeDropdown() {
  isOpen.value = false;
  emit('toggle', false);
}

function handleLogout(e) {
  closeDropdown();
  emit('logout', e);
}

function handleClickOutside(event) {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    closeDropdown();
  }
}

function handleButtonKeyDown(e) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    toggleDropdown();
  } else if (e.key === 'ArrowDown') {
    e.preventDefault();
    isOpen.value = true;
    nextTick(() => {
      const first = menuRef.value?.querySelector('[role="menuitem"]');
      first?.focus();
    });
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    isOpen.value = true;
    nextTick(() => {
      const items = menuRef.value?.querySelectorAll('[role="menuitem"]');
      if (items && items.length) items[items.length - 1].focus();
    });
  } else if (e.key === 'Escape') {
    closeDropdown();
  }
}

function handleMenuKeyDown(e) {
  const items = Array.from(menuRef.value?.querySelectorAll('[role="menuitem"]') || []);
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
    closeDropdown();
    buttonRef.value?.focus();
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
});
</script>

