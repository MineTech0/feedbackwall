<script>
  import { Link, router, page } from '@inertiajs/svelte';
  import Layout from '../../../Layouts/Layout.svelte';

  let { boards } = $props();
  let t = $derived($page.props.t);

  function archive(id) {
    if (confirm(t.admin.boards.archive_confirm)) {
        router.delete(`/admin/boards/${id}`);
    }
  }
</script>

<svelte:head>
  <title>{t.admin.boards.title}</title>
</svelte:head>

<Layout>
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl font-bold">{t.admin.boards.title}</h1>
    <Link href="/admin/boards/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full sm:w-auto text-center">
      {t.admin.boards.new}
    </Link>
  </div>

  <div class="bg-white rounded shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[600px]">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.boards.name}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.boards.slug}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.boards.status}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.boards.actions}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            {#each boards as board (board.id)}
              <tr>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">{board.name}</div>
                    <div class="text-sm text-gray-500 line-clamp-1">{board.description || ''}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {board.slug}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {#if board.archived_at}
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{t.admin.boards.archived}</span>
                    {:else if board.is_public}
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{t.admin.boards.public}</span>
                    {:else}
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{t.admin.boards.private}</span>
                    {/if}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <Link href={`/admin/boards/${board.id}/feedback`} class="text-indigo-600 hover:text-indigo-900 block sm:inline mb-1 sm:mb-0">Feedback</Link>
                    <Link href={`/admin/boards/${board.id}/edit`} class="text-blue-600 hover:text-blue-900 block sm:inline mb-1 sm:mb-0">Edit</Link>
                    {#if !board.archived_at}
                        <button onclick={() => archive(board.id)} class="text-red-600 hover:text-red-900 block sm:inline">Archive</button>
                    {/if}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
    </div>
  </div>
</Layout>
