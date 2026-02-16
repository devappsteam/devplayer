import { ref } from 'vue';

const isSearchOpen = ref(false);

export function useSearch() {
  const openSearch = () => {
    isSearchOpen.value = true;
  };

  const closeSearch = () => {
    isSearchOpen.value = false;
  };

  return {
    isSearchOpen,
    openSearch,
    closeSearch
  };
}
