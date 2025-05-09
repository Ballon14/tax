document.addEventListener("DOMContentLoaded", function () {
    const exportBtn = document.getElementById("exportBtn")
    const exportForm = document.getElementById("exportForm")

    exportBtn.addEventListener("click", function () {
        // Show loading state
        exportBtn.innerHTML =
            '<i class="fas fa-spinner fa-spin mr-2"></i>Menyiapkan CSV...'
        exportBtn.classList.add("opacity-75")
        exportBtn.disabled = true

        // Submit form
        exportForm.submit()

        // Reset button after 3 seconds (in case the download fails)
        setTimeout(() => {
            exportBtn.innerHTML =
                '<i class="fas fa-file-csv mr-2"></i>Export to CSV'
            exportBtn.classList.remove("opacity-75")
            exportBtn.disabled = false
        }, 3000)
    })
})
