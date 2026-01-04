<script>
  import { useForm, Link, page } from '@inertiajs/svelte';
  import Layout from '../../../Layouts/Layout.svelte';

  let t = $derived($page.props.t);

  let form = useForm({
    name: '',
    description: '',
    is_public: true,
  });

  function submit() {
    $form.post('/admin/boards');
  }
</script>

<svelte:head>
  <title>{t.admin.boards.create}</title>
</svelte:head>

<Layout>
  <div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{t.admin.boards.create}</h1>
        <Link href="/admin/boards" class="text-gray-600 hover:underline">{t.admin.boards.back}</Link>
    </div>

    <div class="bg-white rounded shadow p-6">
      <form onsubmit={(e) => { e.preventDefault(); submit(); }}>
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
            {t.admin.boards.name}
          </label>
          <input
            bind:value={$form.name}
            id="name"
            type="text"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            required
          />
          {#if $form.errors.name}
            <p class="text-red-500 text-xs italic mt-1">{$form.errors.name}</p>
          {/if}
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
            {t.admin.boards.desc_label}
          </label>
          <textarea
            bind:value={$form.description}
            id="description"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-32"
          ></textarea>
           {#if $form.errors.description}
            <p class="text-red-500 text-xs italic mt-1">{$form.errors.description}</p>
          {/if}
        </div>

        <div class="mb-6">
          <label class="flex items-center">
            <input type="checkbox" bind:checked={$form.is_public} class="form-checkbox h-5 w-5 text-blue-600">
            <span class="ml-2 text-gray-700">{t.admin.boards.visible}</span>
          </label>
        </div>

        <div class="flex items-center justify-end">
          <button
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
            type="submit"
            disabled={$form.processing}
          >
            {t.admin.boards.save}
          </button>
        </div>
      </form>
    </div>
  </div>
</Layout>
