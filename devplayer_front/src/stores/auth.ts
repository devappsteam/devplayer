import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

const API_BASE_URL = 'http://localhost:8000/api/v1';
const TOKEN_KEY = 'devplayer.auth.token';
const USER_KEY = 'devplayer.auth.user';

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null);
  const user = ref<any | null>(null);

  const isAuthenticated = computed(() => !!token.value);

  const loadFromStorage = () => {
    const storedToken = localStorage.getItem(TOKEN_KEY);
    const storedUser = localStorage.getItem(USER_KEY);

    token.value = storedToken || null;
    user.value = storedUser ? JSON.parse(storedUser) : null;
  };

  const saveToStorage = () => {
    if (token.value) {
      localStorage.setItem(TOKEN_KEY, token.value);
    } else {
      localStorage.removeItem(TOKEN_KEY);
    }

    if (user.value) {
      localStorage.setItem(USER_KEY, JSON.stringify(user.value));
    } else {
      localStorage.removeItem(USER_KEY);
    }
  };

  const authHeaders = () => {
    return token.value ? { Authorization: `Bearer ${token.value}` } : {};
  };

  const login = async (email: string, password: string) => {
    const response = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();
    if (!response.ok || !data?.data?.token) {
      throw new Error(data?.message || 'Falha no login');
    }

    token.value = data.data.token;
    user.value = data.data.user;
    saveToStorage();
  };

  const register = async (name: string, email: string, password: string) => {
    const response = await fetch(`${API_BASE_URL}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, password })
    });

    const data = await response.json();
    if (!response.ok || !data?.data?.token) {
      throw new Error(data?.message || 'Falha no cadastro');
    }

    token.value = data.data.token;
    user.value = data.data.user;
    saveToStorage();
  };

  const fetchMe = async () => {
    if (!token.value) return;

    const response = await fetch(`${API_BASE_URL}/auth/me`, {
      headers: { ...authHeaders() }
    });

    if (response.ok) {
      const data = await response.json();
      user.value = data.data;
      saveToStorage();
    }
  };

  const logout = async () => {
    if (token.value) {
      await fetch(`${API_BASE_URL}/auth/logout`, {
        method: 'POST',
        headers: { ...authHeaders() }
      });
    }

    token.value = null;
    user.value = null;
    saveToStorage();
  };

  return {
    token,
    user,
    isAuthenticated,
    authHeaders,
    loadFromStorage,
    login,
    register,
    fetchMe,
    logout,
  };
});
