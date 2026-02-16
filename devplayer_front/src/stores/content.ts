import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface ContentItem {
  id: number;
  uuid?: string; // UUID para referência
  title: string;
  image: string;
  type: 'channel' | 'movie' | 'series';
  url?: string; // Stream URL
  description?: string;
  rating?: number;
}

interface ContentCategory {
  id: string;
  title: string;
  items: ContentItem[];
  hasMore?: boolean;
  currentPage?: number;
  totalPages?: number;
}

const API_BASE_URL = 'http://localhost:8000/api/v1';
const RECENT_STORAGE_KEY = 'devplayer.recentlyWatched';
const USER_ID = 1;

export const useContentStore = defineStore('content', () => {
  const featured = ref<ContentItem | null>(null);

  const categories = ref<ContentCategory[]>([]);
  const recent = ref<ContentItem | null>(null);
  const favorites = ref<ContentItem[]>([]);
  const allApiCategories = ref<{ id: number; name: string }[]>([]);
  const loadedCategoriesCount = ref(0);
  const categoriesPerPage = ref(5);

  const loading = ref(false);
  const loadingMore = ref(false);
  const error = ref<string | null>(null);

  const formatImageUrl = (url: string | null) => {
    return url || 'https://placehold.co/300x169/222222/FFFFFF?text=No+Image';
  };

  const mapChannelToItem = (channel: any, type: ContentItem['type']) => ({
    id: channel.id,
    title: channel.name,
    image: formatImageUrl(channel.logo_url),
    type,
    url: channel.stream_url,
    description: channel.name,
  });

  const loadRecent = (): ContentItem | null => {
    try {
      const stored = localStorage.getItem(RECENT_STORAGE_KEY);
      if (!stored) return null;
      return JSON.parse(stored) as ContentItem;
    } catch {
      return null;
    }
  };

  const saveRecent = (item: ContentItem | null) => {
    if (item) {
      localStorage.setItem(RECENT_STORAGE_KEY, JSON.stringify(item));
    } else {
      localStorage.removeItem(RECENT_STORAGE_KEY);
    }
  };

  const loadFavoritesFromAPI = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/favorites/user/${USER_ID}`);
      if (!response.ok) return [];
      const result = await response.json();
      return (result.data || []).map((fav: any) => ({
        id: fav.id,
        title: fav.name,
        image: formatImageUrl(fav.logo_url),
        type: fav.type as ContentItem['type'],
        url: fav.stream_url,
        description: fav.name,
      }));
    } catch (err) {
      console.error('Error loading favorites:', err);
      return [];
    }
  };

  const isFavorite = (itemId: number): boolean => {
    return favorites.value.some(fav => fav.id === itemId);
  };

  const toggleFavorite = async (item: ContentItem) => {
    try {
      // Validação: garantir que o item tem ID
      if (!item || !item.id) {
        console.error('❌ Invalid item for toggleFavorite - missing id:', item);
        return;
      }

      // Mapear o tipo do ContentItem para stream_type da API
      const streamTypeMap: Record<ContentItem['type'], string> = {
        'channel': 'live',
        'movie': 'vod',
        'series': 'series'
      };

      const payload = {
        user_id: USER_ID,
        channel_id: item.id,
        stream_type: streamTypeMap[item.type] || 'live',
      };

      const response = await fetch(`${API_BASE_URL}/favorites/toggle`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const responseData = await response.json();

      if (!response.ok) {
        console.error('Failed to toggle favorite:', responseData);
        alert(`Erro: ${responseData.message}`);
        return;
      }

      // Recarregar favoritos da API
      favorites.value = await loadFavoritesFromAPI();

      // Atualizar categoria de favoritos
      const favCategoryIndex = categories.value.findIndex((category) => category.id === 'favorites');
      if (favCategoryIndex >= 0) {
        // Se não há favoritos, remover categoria
        if (favorites.value.length === 0) {
          categories.value.splice(favCategoryIndex, 1);
        } else {
          // Atualizar items da categoria existente
          const category = categories.value[favCategoryIndex];
          if (category) {
            category.items = [...favorites.value];
          }
        }
      } else if (favorites.value.length > 0) {
        // Adicionar categoria de favoritos após "Continuar Assistindo"
        const recentIndex = categories.value.findIndex(cat => cat.id === 'recent');
        const insertIndex = recentIndex >= 0 ? recentIndex + 1 : 0;
        categories.value.splice(insertIndex, 0, {
          id: 'favorites',
          title: 'Minha Lista',
          items: [...favorites.value],
        });
      }
    } catch (err) {
      console.error('Error toggling favorite:', err);
    }
  };

  const addRecent = (item: ContentItem) => {
    console.log('addRecent called with item:', JSON.stringify(item, null, 2));
    // Manter apenas o último item assistido
    recent.value = item;
    saveRecent(item);

    // Atualizar featured com o último assistido
    featured.value = item;
    console.log('featured.value set to:', JSON.stringify(featured.value, null, 2));

    const existingIndex = categories.value.findIndex((category) => category.id === 'recent');
    if (existingIndex >= 0) {
      const category = categories.value[existingIndex];
      if (category) {
        category.items = [item];
      }
    } else {
      categories.value.unshift({
        id: 'recent',
        title: 'Continuar Assistindo',
        items: [item],
      });
    }
  };

  const fetchCategoryChannels = async (categoryId: number, page = 1, perPage = 15) => {
    const response = await fetch(`${API_BASE_URL}/channels/category/${categoryId}?page=${page}&per_page=${perPage}`);
    if (!response.ok) return { data: [], meta: null };
    const result = await response.json();
    return {
      data: (result.data || []) as any[],
      meta: result.meta || null,
    };
  };

  const fetchCategoryByUuid = async (categoryUuid: string, page = 1, perPage = 15) => {
    const response = await fetch(`${API_BASE_URL}/channels?category_uuid=${categoryUuid}&page=${page}&per_page=${perPage}`);
    if (!response.ok) return { data: [], meta: null };
    const result = await response.json();
    return {
      data: (result.data || []) as any[],
      meta: result.meta || null,
    };
  };

  const loadMoreItems = async (categoryId: string) => {
    const categoryIndex = categories.value.findIndex((cat) => cat.id === categoryId);
    if (categoryIndex === -1) return;

    const category = categories.value[categoryIndex];
    if (!category || !category.hasMore || !category.currentPage) return;

    const nextPage = category.currentPage + 1;

    // Verificar se é UUID (categoria de tipo channel) ou ID numérico (categoria de tipo movie/series)
    const isUuid = categoryId.includes('-');

    let result;
    if (isUuid) {
      result = await fetchCategoryByUuid(categoryId, nextPage, 15);
    } else {
      const apiCategoryId = parseInt(categoryId.replace('category-', ''));
      if (isNaN(apiCategoryId)) return;
      result = await fetchCategoryChannels(apiCategoryId, nextPage, 15);
    }

    const newItems = result.data.map((c: any) => mapChannelToItem(c, 'channel'));

    const updatedCategory = categories.value[categoryIndex];
    if (updatedCategory) {
      updatedCategory.items.push(...newItems);
      updatedCategory.currentPage = nextPage;
      updatedCategory.hasMore = result.meta && nextPage < result.meta.last_page;
    }
  };

  const fetchContent = async () => {
    loading.value = true;
    error.value = null;
    recent.value = loadRecent();
    favorites.value = await loadFavoritesFromAPI();

    try {
      const nextCategories: ContentCategory[] = [];

      // Definir o item recente como featured (sem criar categoria)
      if (recent.value) {
        featured.value = recent.value;
        console.log('Featured set to recent:', JSON.stringify(featured.value, null, 2));
      }

      if (favorites.value.length > 0) {
        nextCategories.push({
          id: 'favorites',
          title: 'Minha Lista',
          items: favorites.value,
        });
      }

      const [liveRes, moviesRes, seriesRes, categoryRes] = await Promise.all([
        fetch(`${API_BASE_URL}/channels/live`),
        fetch(`${API_BASE_URL}/channels/movies`),
        fetch(`${API_BASE_URL}/channels/series`),
        fetch(`${API_BASE_URL}/categories`),
      ]);

      if (liveRes.ok) {
        const data = await liveRes.json();
        const items = (data.data || []).slice(0, 15).map((c: any) => mapChannelToItem(c, 'channel'));
        if (items.length > 0) {
          nextCategories.push({ id: 'live', title: 'Canais ao Vivo', items });
          // Se não há recent, usar o primeiro canal ao vivo como featured
          if (!featured.value && !recent.value) {
            featured.value = items[0];
            console.log('Featured set to first live channel:', JSON.stringify(featured.value, null, 2));
          }
        }
      }

      if (moviesRes.ok) {
        const data = await moviesRes.json();
        const items = (data.data || []).slice(0, 15).map((c: any) => mapChannelToItem(c, 'movie'));
        if (items.length > 0) {
          nextCategories.push({ id: 'movies', title: 'Filmes', items });
        }
      }

      if (seriesRes.ok) {
        const data = await seriesRes.json();
        const items = (data.data || []).slice(0, 15).map((c: any) => mapChannelToItem(c, 'series'));
        if (items.length > 0) {
          nextCategories.push({ id: 'series', title: 'Series', items });
        }
      }

      if (categoryRes.ok) {
        const data = await categoryRes.json();

        // Buscar todas as páginas de categorias
        let allCategoriesData = (data.data || []) as { id: number; name: string }[];
        if (data.meta && data.meta.last_page > 1) {
          const totalPages = data.meta.last_page;
          const remainingPages = [];
          for (let page = 2; page <= totalPages; page++) {
            remainingPages.push(fetch(`${API_BASE_URL}/categories?page=${page}`));
          }
          const remainingResponses = await Promise.all(remainingPages);
          for (const res of remainingResponses) {
            if (res.ok) {
              const pageData = await res.json();
              allCategoriesData = [...allCategoriesData, ...(pageData.data || [])];
            }
          }
        }

        allApiCategories.value = allCategoriesData;
        const categoriesToLoad = allApiCategories.value.slice(0, categoriesPerPage.value);

        for (const category of categoriesToLoad) {
          const result = await fetchCategoryChannels(category.id, 1, 15);
          const items = result.data.map((c: any) => mapChannelToItem(c, 'channel'));
          if (items.length > 0) {
            nextCategories.push({
              id: `category-${category.id}`,
              title: category.name,
              items,
              currentPage: 1,
              totalPages: result.meta?.last_page || 1,
              hasMore: result.meta && 1 < result.meta.last_page,
            });
          }
        }
        loadedCategoriesCount.value = categoriesToLoad.length;
      }

      categories.value = nextCategories;
    } catch (err: any) {
      console.error('Failed to fetch content:', err);
      error.value = 'Erro ao carregar conteudo. Verifique a API.';
    } finally {
      loading.value = false;
    }
  };

  const loadMoreCategories = async () => {
    if (loadingMore.value || loadedCategoriesCount.value >= allApiCategories.value.length) return;

    loadingMore.value = true;
    try {
      const nextBatch = allApiCategories.value.slice(
        loadedCategoriesCount.value,
        loadedCategoriesCount.value + categoriesPerPage.value
      );

      for (const category of nextBatch) {
        const result = await fetchCategoryChannels(category.id, 1, 15);
        const items = result.data.map((c: any) => mapChannelToItem(c, 'channel'));
        if (items.length > 0) {
          categories.value.push({
            id: `category-${category.id}`,
            title: category.name,
            items,
            currentPage: 1,
            totalPages: result.meta?.last_page || 1,
            hasMore: result.meta && 1 < result.meta.last_page,
          });
        }
      }
      loadedCategoriesCount.value += nextBatch.length;
    } finally {
      loadingMore.value = false;
    }
  };

  const hasMoreCategories = () => {
    return loadedCategoriesCount.value < allApiCategories.value.length;
  };

  const initializeFavorites = async () => {
    favorites.value = await loadFavoritesFromAPI();
  };

  return {
    featured,
    categories,
    recent,
    favorites,
    loading,
    loadingMore,
    error,
    fetchContent,
    addRecent,
    toggleFavorite,
    isFavorite,
    loadMoreItems,
    loadMoreCategories,
    hasMoreCategories,
    initializeFavorites
  };
});
