import { ref, watch, onMounted } from 'vue';

const isDark = ref(false);

const applyTheme = (dark) => {
    if (dark) {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
    } else {
        document.documentElement.classList.add('light');
        document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('theme', dark ? 'dark' : 'light');
};

export function useTheme() {
    onMounted(() => {
        const saved = localStorage.getItem('theme');
        // Default to dark mode if no preference saved
        isDark.value = saved ? (saved === 'dark') : true; 
        applyTheme(isDark.value);
    });

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        applyTheme(isDark.value);
    };

    return { isDark, toggleTheme };
}
