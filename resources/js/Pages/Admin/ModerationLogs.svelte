<script>
  import { Link, page } from '@inertiajs/svelte';
  import Layout from '../../Layouts/Layout.svelte';

  let { logs } = $props();
  let t = $derived($page.props.t);

  function formatReason(reason) {
      if (!reason) return '-';
      try {
          const parsed = JSON.parse(reason);
          if (typeof parsed === 'object' && parsed !== null) {
              return parsed;
          }
          return reason;
      } catch (e) {
          return reason;
      }
  }
</script>

<svelte:head>
  <title>{t.admin.logs.title}</title>
</svelte:head>

<Layout>
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">{t.admin.logs.title}</h1>
    <Link href="/admin" class="text-gray-600 hover:underline">{t.admin.logs.back}</Link>
  </div>

  <div class="bg-white rounded shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.logs.moderator}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.logs.action}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.logs.feedback_content}</th>
               <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.logs.reason}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{t.admin.logs.date}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            {#each logs.data as log (log.id)}
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {log.user?.name || t.admin.logs.system || 'System'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="capitalize px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                        {log.action}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {log.feedback?.content || 'Deleted Feedback'}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {#if log.reason}
                        {@const formattedReason = formatReason(log.reason)}
                        {#if typeof formattedReason === 'object'}
                            <div class="space-y-1 text-xs">
                                {#if formattedReason.badword !== undefined}
                                    <div class:text-red-600={formattedReason.badword} class:text-green-600={!formattedReason.badword}>
                                        <span class="font-medium">Badword:</span> {formattedReason.badword ? 'Yes' : 'No'}
                                    </div>
                                {/if}
                                {#if formattedReason.toxicity_score !== undefined}
                                    <div>
                                        <span class="font-medium">Score:</span> 
                                        <span class:text-red-600={formattedReason.toxicity_score > 0.8} class:text-yellow-600={formattedReason.toxicity_score > 0.5 && formattedReason.toxicity_score <= 0.8}>
                                            {Number(formattedReason.toxicity_score).toFixed(2)}
                                        </span>
                                    </div>
                                {/if}
                                {#if formattedReason.model_label}
                                    <div><span class="font-medium">Label:</span> {formattedReason.model_label}</div>
                                {/if}
                                <!-- Fallback for other keys -->
                                {#each Object.entries(formattedReason) as [key, value]}
                                    {#if !['badword', 'toxicity_score', 'model_label'].includes(key)}
                                        <div><span class="font-medium">{key}:</span> {JSON.stringify(value)}</div>
                                    {/if}
                                {/each}
                            </div>
                        {:else}
                            {formattedReason}
                        {/if}
                    {:else}
                        -
                    {/if}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(log.created_at).toLocaleString($page.props.locale)}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
    </div>
    
    {#if logs.data.length === 0}
        <div class="p-6 text-center text-gray-500">{t.admin.logs.empty}</div>
    {/if}
  </div>
</Layout>
