<script>
  import { page, Link, router } from '@inertiajs/svelte';
  
  let { children } = $props();
  
  let locale = $derived($page.props.locale);
  let user = $derived($page.props.auth.user);
  let t = $derived($page.props.t);
  
  let isMenuOpen = $state(false);

  function switchLang(lang) {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
  }
  
  function toggleMenu() {
      isMenuOpen = !isMenuOpen;
  }
</script>

<div class="min-h-screen bg-gray-100 font-sans text-gray-900">
  <header class="bg-white shadow relative">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <Link href="/" class="text-xl font-bold text-blue-600 hover:text-blue-800 z-20">
        {t.common.app_name}
      </Link>
      
      <!-- Mobile Menu Button -->
      <button onclick={toggleMenu} class="md:hidden z-20 p-2 focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              {#if isMenuOpen}
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              {:else}
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              {/if}
          </svg>
      </button>

      <!-- Desktop Menu -->
      <div class="hidden md:flex items-center space-x-4">
        <div class="flex space-x-2 text-sm">
          <button 
            onclick={() => switchLang('fi')} 
            class:font-bold={locale === 'fi'}
            class="hover:underline"
          >
            FI
          </button>
          <span>|</span>
          <button 
            onclick={() => switchLang('en')} 
            class:font-bold={locale === 'en'}
            class="hover:underline"
          >
            EN
          </button>
        </div>

        {#if user}
          <Link href="/admin" class="text-sm font-medium hover:underline">{t.common.admin}</Link>
          <Link href="/logout" method="post" as="button" class="text-sm font-medium hover:underline">{t.common.logout}</Link>
        {/if}
      </div>
    </div>
    
    <!-- Mobile Menu Overlay -->
    {#if isMenuOpen}
        <div class="absolute top-0 left-0 w-full bg-white shadow-lg p-4 pt-16 md:hidden z-10 flex flex-col space-y-4">
            <div class="flex space-x-4 justify-center text-lg">
              <button 
                onclick={() => switchLang('fi')} 
                class:font-bold={locale === 'fi'}
                class="hover:underline"
              >
                FI
              </button>
              <span>|</span>
              <button 
                onclick={() => switchLang('en')} 
                class:font-bold={locale === 'en'}
                class="hover:underline"
              >
                EN
              </button>
            </div>

            {#if user}
              <Link href="/admin" class="text-lg font-medium text-center hover:underline block py-2" onclick={() => isMenuOpen = false}>{t.common.admin}</Link>
              <Link href="/logout" method="post" as="button" class="text-lg font-medium text-center hover:underline block py-2 w-full" onclick={() => isMenuOpen = false}>{t.common.logout}</Link>
            {/if}
        </div>
    {/if}
  </header>

  <main class="max-w-7xl mx-auto px-4 py-6">
    {@render children()}
  </main>
</div>
