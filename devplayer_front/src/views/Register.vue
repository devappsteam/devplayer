<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const name = ref('');
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref<string | null>(null);

const submit = async () => {
  loading.value = true;
  error.value = null;

  try {
    await auth.register(name.value, email.value, password.value);
    await auth.fetchMe();
    router.push('/canais');
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Falha no cadastro';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-neutral-950 px-4">
    <div class="w-full max-w-md bg-neutral-900 rounded-xl shadow-2xl p-8 space-y-6">
      <div class="space-y-2">
        <h1 class="text-3xl font-bold text-white">Criar conta</h1>
        <p class="text-sm text-neutral-400">Preencha os dados para criar seu acesso.</p>
      </div>

      <div v-if="error" class="bg-red-600/20 text-red-300 border border-red-600/40 rounded-md p-3 text-sm">
        {{ error }}
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-2">
          <label class="text-sm text-neutral-300">Nome</label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full bg-neutral-800 text-white rounded-md px-3 py-2 border border-neutral-700 focus:outline-none focus:border-red-500"
            placeholder="Seu nome"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm text-neutral-300">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full bg-neutral-800 text-white rounded-md px-3 py-2 border border-neutral-700 focus:outline-none focus:border-red-500"
            placeholder="voce@email.com"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm text-neutral-300">Senha</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full bg-neutral-800 text-white rounded-md px-3 py-2 border border-neutral-700 focus:outline-none focus:border-red-500"
            placeholder="******"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-md transition disabled:opacity-60"
        >
          {{ loading ? 'Criando...' : 'Criar conta' }}
        </button>
      </form>

      <div class="text-sm text-neutral-400">
        Ja tem conta?
        <router-link to="/login" class="text-red-400 hover:text-red-300">Entrar</router-link>
      </div>
    </div>
  </div>
</template>
