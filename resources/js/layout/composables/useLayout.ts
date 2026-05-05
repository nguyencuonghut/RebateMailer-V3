import { computed, reactive } from 'vue';

const layoutState = reactive({
    sidebarOpen: true,
    theme: 'light' as 'light' | 'dark',
});

export function useLayout() {
    const isSidebarOpen = computed(() => layoutState.sidebarOpen);
    const isDarkMode = computed(() => layoutState.theme === 'dark');

    const applyTheme = (theme: 'light' | 'dark') => {
        if (typeof document === 'undefined') {
            return;
        }

        layoutState.theme = theme;
        document.documentElement.classList.toggle('app-dark', theme === 'dark');
        document.body.classList.toggle('app-dark', theme === 'dark');
        window.localStorage.setItem('rebate-mailer-theme', theme);
    };

    const initializeTheme = () => {
        if (typeof window === 'undefined') {
            return;
        }

        const savedTheme = window.localStorage.getItem('rebate-mailer-theme');

        if (savedTheme === 'light' || savedTheme === 'dark') {
            applyTheme(savedTheme);
            return;
        }

        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDarkMode ? 'dark' : 'light');
    };

    const initializeSidebar = () => {
        if (typeof window === 'undefined') {
            return;
        }

        layoutState.sidebarOpen = window.innerWidth >= 1024;
    };

    const toggleSidebar = () => {
        layoutState.sidebarOpen = !layoutState.sidebarOpen;
    };

    const closeSidebar = () => {
        layoutState.sidebarOpen = false;
    };

    const toggleTheme = () => {
        applyTheme(layoutState.theme === 'dark' ? 'light' : 'dark');
    };

    return {
        isSidebarOpen,
        isDarkMode,
        initializeSidebar,
        initializeTheme,
        toggleSidebar,
        closeSidebar,
        toggleTheme,
    };
}
