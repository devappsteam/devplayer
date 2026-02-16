import { createRouter, createWebHistory } from 'vue-router'
import Channels from '@/views/Channels.vue'
import Movies from '@/views/Movies.vue'
import Series from '@/views/Series.vue'
import Player from '@/views/Player.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/canais'
    },
    {
      path: '/canais',
      name: 'canais',
      component: Channels
    },
    {
      path: '/filmes',
      name: 'filmes',
      component: Movies
    },
    {
      path: '/series',
      name: 'series',
      component: Series
    },
    {
      path: '/player/:id',
      name: 'player',
      component: Player,
      meta: { hideNavbar: true }
    }
  ],
})

export default router
