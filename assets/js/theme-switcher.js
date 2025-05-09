document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("themeToggle")
    const html = document.documentElement

    // Check for saved theme preference
    const darkMode =
        localStorage.getItem("darkMode") === "true" ||
        (!localStorage.getItem("darkMode") &&
            window.matchMedia("(prefers-color-scheme: dark)").matches)

    // Apply initial theme
    if (darkMode) {
        html.classList.add("dark")
        localStorage.setItem("darkMode", "true")
    }

    // Toggle theme
    themeToggle.addEventListener("click", function () {
        html.classList.toggle("dark")
        const isDark = html.classList.contains("dark")
        localStorage.setItem("darkMode", isDark)

        // Set cookie for PHP detection
        document.cookie = `darkMode=${isDark}; path=/; max-age=${
            60 * 60 * 24 * 30
        }`
    })
})
