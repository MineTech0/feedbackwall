<script>
  import { page, useForm, router } from '@inertiajs/svelte';
  import Layout from '../../Layouts/Layout.svelte';

  let { board, feedback, votedIds, sort } = $props();
  let t = $derived($page.props.t);

  let form = useForm({
    content: '',
  });

  function submit() {
    $form.post(`/b/${board.slug}/feedback`, {
      onSuccess: () => $form.reset(),
      preserveScroll: true,
    });
  }

  function toggleVote(item) {
    router.post(`/feedback/${item.id}/vote`, {}, {
      preserveScroll: true,
      preserveState: true,
    });
  }
  
  function hasVoted(id) {
      return votedIds.includes(id);
  }

  function handleSort(newSort) {
      router.get(`/b/${board.slug}`, { sort: newSort }, {
          preserveState: true,
          preserveScroll: true,
      });
  }
</script>

<svelte:head>
  <title>{board.name}</title>
</svelte:head>

<Layout>
  <div class="max-w-3xl mx-auto">
    <div class="mb-6 md:mb-8">
      <h1 class="text-2xl md:text-3xl font-bold mb-2 break-words">{board.name}</h1>
      <p class="text-gray-700 text-sm md:text-base">{board.description || ''}</p>
    </div>

    <!-- Feedback Form -->
    <div class="bg-white rounded shadow p-4 md:p-6 mb-6 md:mb-8">
      <h2 class="text-lg font-semibold mb-4">{t.feedback.submit}</h2>
      <form onsubmit={(e) => { e.preventDefault(); submit(); }}>
        <textarea
          bind:value={$form.content}
          class="w-full border border-gray-300 rounded p-3 h-32 focus:ring-blue-500 focus:border-blue-500 text-base"
          placeholder={t.feedback.placeholder}
          required
        ></textarea>
        
        {#if $form.errors.content}
            <div class="text-red-500 text-sm mt-1">{$form.errors.content}</div>
        {/if}
        
        <div class="mt-4 flex justify-end">
          <button 
            type="submit" 
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 w-full md:w-auto"
            disabled={$form.processing}
          >
            {t.feedback.submit}
          </button>
        </div>
        
        {#if $page.props.flash?.success}
            <div class="mt-2 text-green-600">{$page.props.flash.success}</div>
        {/if}
        {#if $page.props.flash?.error}
            <div class="mt-2 text-red-600">{$page.props.flash.error}</div>
        {/if}
      </form>
    </div>

    <!-- Filter/Sort -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-2">
      <h2 class="text-xl font-bold">{t.feedback.title} ({feedback.total})</h2>
      <div class="flex space-x-2 text-sm">
        <button 
            class:font-bold={sort === 'top'}
            onclick={() => handleSort('top')}
            class="hover:underline px-2 py-1"
        >
            {t.feedback.sort.top}
        </button>
        <span class="py-1">|</span>
        <button 
            class:font-bold={sort === 'newest'}
            onclick={() => handleSort('newest')}
            class="hover:underline px-2 py-1"
        >
            {t.feedback.sort.newest}
        </button>
      </div>
    </div>

    <!-- List -->
    <div class="space-y-4">
      {#each feedback.data as item (item.id)}
        <div class="bg-white rounded shadow p-4 flex gap-3 md:gap-4">
          <div class="flex flex-col items-center min-w-[3rem]">
            <button 
                onclick={() => toggleVote(item)}
                class="flex flex-col items-center p-2 rounded transition-colors touch-manipulation"
                class:text-blue-600={hasVoted(item.id)}
                class:bg-blue-50={hasVoted(item.id)}
                class:text-gray-500={!hasVoted(item.id)}
                class:hover:bg-gray-100={!hasVoted(item.id)}
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
              <span class="font-bold">{item.votes_count}</span>
            </button>
          </div>
          <div class="flex-1 min-w-0">
             <p class="text-gray-800 whitespace-pre-wrap break-words">{item.content}</p>
             <p class="text-xs text-gray-400 mt-2">{new Date(item.created_at).toLocaleString($page.props.locale)}</p>
          </div>
        </div>
      {/each}
      
      {#if feedback.data.length === 0}
         <p class="text-center text-gray-500 py-8">{t.feedback.empty}</p>
      {/if}
    </div>
  </div>
</Layout>
