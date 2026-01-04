<script>
  import { page, Link } from '@inertiajs/svelte';
  import Layout from '../../Layouts/Layout.svelte';

  let { boards } = $props();
  let t = $derived($page.props.t);
</script>

<svelte:head>
  <title>{t.board.list_title}</title>
</svelte:head>

<Layout>
  <h1 class="text-2xl font-bold mb-6">{t.board.list_title}</h1>

  {#if boards.length === 0}
    <p class="text-gray-500">{t.board.empty}</p>
  {:else}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
      {#each boards as board (board.id)}
        <Link href={`/b/${board.slug}`} class="block bg-white rounded shadow p-5 md:p-6 hover:shadow-lg transition">
          <h2 class="text-lg md:text-xl font-bold mb-2 break-words">{board.name}</h2>
          <p class="text-gray-600 line-clamp-3 text-sm md:text-base">{board.description || ''}</p>
        </Link>
      {/each}
    </div>
  {/if}
</Layout>
