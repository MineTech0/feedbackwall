<script>
  import { Link, page } from '@inertiajs/svelte';
  import Layout from '../../Layouts/Layout.svelte';

  let t = $derived($page.props.t);
</script>

<svelte:head>
  <title>{t.admin.dashboard}</title>
</svelte:head>

<Layout>
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">{t.admin.dashboard}</h1>
        <p class="text-gray-600">{t.admin.welcome}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Link href="/admin/boards" class="block bg-white p-6 rounded shadow hover:shadow-lg transition">
            <h2 class="text-xl font-bold mb-2 text-blue-600">{t.admin.boards.title}</h2>
            <p class="text-gray-600">{t.admin.boards.description}</p>
        </Link>

        <Link href="/admin/moderation-logs" class="block bg-white p-6 rounded shadow hover:shadow-lg transition">
            <h2 class="text-xl font-bold mb-2 text-purple-600">{t.admin.logs.title}</h2>
            <p class="text-gray-600">{t.admin.logs.description}</p>
        </Link>
    </div>
</Layout>
