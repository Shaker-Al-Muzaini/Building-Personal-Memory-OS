import { ref, watch, onMounted } from 'vue';

const isDark = ref(false);

const applyTheme = (dark) => {
    if (dark) {
        document.documentElement.classList.add('dark-mode');
        document.documentElement.classList.remove('light-mode');
    } else {
        document.documentElement.classList.add('light-mode');
        document.documentElement.classList.remove('dark-mode');
    }
    localStorage.setItem('theme', dark ? 'dark' : 'light');
};

export function useTheme() {
    onMounted(() => {
        const saved = localStorage.getItem('theme');
        isDark.value = saved === 'dark'; // default = light
        applyTheme(isDark.value);
    });

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        applyTheme(isDark.value);
    };

    return { isDark, toggleTheme };
}
