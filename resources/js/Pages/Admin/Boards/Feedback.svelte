<script>
  import { Link, router, useForm, page } from '@inertiajs/svelte';
  import Layout from '../../../Layouts/Layout.svelte';

  let { board, feedback } = $props();
  let t = $derived($page.props.t);
  
  // State for moderation modal/reason
  let selectedFeedback = $state(null);
  let actionType = $state(null);
  
  let reasonForm = useForm({
      action: '',
      reason: '',
  });

  function openAction(item, type) {
      if (type === 'publish' && confirm(t.admin.feedback.modal_title.replace(':action', t.admin.feedback.approve) + '?')) {
           router.post(`/admin/feedback/${item.id}/moderate`, { action: 'publish' }, { preserveScroll: true });
           return;
      }
      
      selectedFeedback = item;
      actionType = type;
      $reasonForm.action = type;
      $reasonForm.reason = '';
  }

  function submitReason() {
      $reasonForm.post(`/admin/feedback/${selectedFeedback.id}/moderate`, {
          onSuccess: () => {
              selectedFeedback = null;
              actionType = null;
          },
          preserveScroll: true,
      });
  }
</script>

<svelte:head>
  <title>{t.admin.feedback.title.replace(':name', board.name)}</title>
</svelte:head>

<Layout>
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-2">
    <h1 class="text-2xl font-bold break-words w-full">{t.admin.feedback.title.replace(':name', board.name)}</h1>
    <Link href="/admin/boards" class="text-gray-600 hover:underline whitespace-nowrap">{t.admin.boards.back}</Link>
  </div>

  <div class="bg-white rounded shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3 min-w-[200px]">{t.admin.feedback.content}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">{t.admin.feedback.votes}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.feedback.status}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">{t.admin.feedback.created}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.boards.actions}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            {#each feedback.data as item (item.id)}
              <tr>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 whitespace-pre-wrap break-words max-w-xs sm:max-w-md lg:max-w-xl max-h-64 overflow-y-auto custom-scrollbar">{item.content}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">
                    {item.votes_count}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {#if item.moderation_state === 'published'}
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{t.admin.feedback.published}</span>
                    {:else if item.moderation_state === 'rejected'}
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{t.admin.feedback.rejected}</span>
                    {:else}
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{t.admin.feedback.pending}</span>
                    {/if}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                    {new Date(item.created_at).toLocaleDateString($page.props.locale)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    {#if item.moderation_state !== 'published'}
                         <button onclick={() => openAction(item, 'publish')} class="text-green-600 hover:text-green-900">{t.admin.feedback.approve}</button>
                    {/if}
                    {#if item.moderation_state !== 'rejected'}
                        <button onclick={() => openAction(item, 'reject')} class="text-red-600 hover:text-red-900">{t.admin.feedback.reject}</button>
                    {/if}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
    </div>
  </div>
  
  {#if selectedFeedback}
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50 p-4">
        <div class="bg-white p-5 rounded shadow-lg max-w-md w-full">
            <h3 class="text-lg font-bold mb-4 capitalize">
                {t.admin.feedback.modal_title.replace(':action', actionType === 'publish' ? t.admin.feedback.approve : t.admin.feedback.reject)}
            </h3>
            <p class="mb-4 text-gray-600 italic break-words">"{selectedFeedback.content}"</p>
            
            <form onsubmit={(e) => { e.preventDefault(); submitReason(); }}>
                <label class="block mb-2 text-sm font-bold">{t.admin.feedback.reason_label}</label>
                <textarea bind:value={$reasonForm.reason} class="w-full border rounded p-2 mb-4" placeholder={t.admin.feedback.reason_placeholder}></textarea>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick={() => selectedFeedback = null} class="px-4 py-2 bg-gray-300 rounded">{t.admin.feedback.cancel}</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">{t.admin.feedback.confirm}</button>
                </div>
            </form>
        </div>
    </div>
  {/if}
</Layout>
