import { createRouter, createWebHistory } from 'vue-router'
import Channels from '@/views/Channels.vue'
import Movies from '@/views/Movies.vue'
import Series from '@/views/Series.vue'
import Player from '@/views/Player.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/canais'
    },
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: { public: true, hideNavbar: true }
    },
    {
      path: '/register',
      name: 'register',
      component: Register,
      meta: { public: true, hideNavbar: true }
    },
    {
      path: '/canais',
      name: 'canais',
      component: Channels,
      meta: { requiresAuth: true }
    },
    {
      path: '/filmes',
      name: 'filmes',
      component: Movies,
      meta: { requiresAuth: true }
    },
    {
      path: '/series',
      name: 'series',
      component: Series,
      meta: { requiresAuth: true }
    },
    {
      path: '/player/:id',
      name: 'player',
      component: Player,
      meta: { hideNavbar: true, requiresAuth: true }
    }
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (!auth.isAuthenticated && to.meta.requiresAuth) {
    return { name: 'login' }
  }
  if (auth.isAuthenticated && to.meta.public) {
    return { name: 'canais' }
  }
  return true
})

export default router
