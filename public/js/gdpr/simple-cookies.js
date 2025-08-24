// Simple Cookie Notice for Read-Only News Site
document.addEventListener("DOMContentLoaded", () => {
    const cookieNotice = document.getElementById("cookie-notice")
    const acceptButton = document.getElementById("accept-cookies")

    // Check if user has already accepted cookies
    const cookiesAccepted = localStorage.getItem("cookies-accepted")

    // Show notice if not accepted
    if (!cookiesAccepted) {
        cookieNotice.style.display = "block"
    }

    // Handle accept button click
    acceptButton.addEventListener("click", () => {
        // Store acceptance in localStorage
        localStorage.setItem("cookies-accepted", "true")
        localStorage.setItem("cookies-accepted-date", new Date().toISOString())

        // Hide the notice
        cookieNotice.style.display = "none"

        console.log("[v0] Cookies accepted by user")
    })

    // Auto-hide notice after 30 seconds if no interaction
    setTimeout(() => {
        if (cookieNotice.style.display !== "none") {
            cookieNotice.style.opacity = "0.7"
        }
    }, 30000)
})

// Simple analytics consent check
function isAnalyticsAllowed() {
    return localStorage.getItem("cookies-accepted") === "true"
}

// Export for use in other scripts
window.GripGrinCookies = {
    isAccepted: () => localStorage.getItem("cookies-accepted") === "true",
    getAcceptanceDate: () => localStorage.getItem("cookies-accepted-date"),
}
