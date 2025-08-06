// =====================================================
// PERFORMANCE OPTIMIZATIONS - Lazy Loading & More
// =====================================================

;(() => {
    // ===========================================
    // LAZY LOADING IMAGES
    // ===========================================

    function initLazyLoading() {
        if ("IntersectionObserver" in window) {
            const imageObserver = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const img = entry.target

                            // Create a new image to test loading
                            const newImg = new Image()
                            newImg.onload = () => {
                                img.src = img.dataset.src
                                img.classList.remove("lazy-image")
                                img.classList.add("image-loaded")
                            }

                            newImg.onerror = () => {
                                img.classList.remove("lazy-image")
                                img.classList.add("image-error")
                                img.innerHTML = '<div class="image-error">Image failed to load</div>'
                            }

                            newImg.src = img.dataset.src
                            observer.unobserve(img)
                        }
                    })
                },
                {
                    rootMargin: "50px 0px",
                    threshold: 0.01,
                },
            )

            // Observe all lazy images
            document.querySelectorAll("img[data-src]").forEach((img) => {
                imageObserver.observe(img)
            })
        } else {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll("img[data-src]").forEach((img) => {
                img.src = img.dataset.src
                img.classList.remove("lazy-image")
                img.classList.add("image-loaded")
            })
        }
    }

    // ===========================================
    // RESPONSIVE IMAGE LOADING
    // ===========================================

    function optimizeImages() {
        // Get device pixel ratio for high-DPI displays
        const pixelRatio = window.devicePixelRatio || 1
        const isHighDPI = pixelRatio > 1

        // Get connection information if available
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection
        const isSlowConnection = connection && (connection.effectiveType === "slow-2g" || connection.effectiveType === "2g")

        // Adjust image quality based on connection and device
        document.querySelectorAll("img[data-src]").forEach((img) => {
            let src = img.dataset.src

            // Use lower quality images on slow connections
            if (isSlowConnection) {
                src = src.replace(".webp", "-low.webp").replace(".jpg", "-low.jpg")
            }

            // Use higher resolution images on high-DPI displays (if not slow connection)
            if (isHighDPI && !isSlowConnection) {
                src = src.replace(".webp", "@2x.webp").replace(".jpg", "@2x.jpg")
            }

            img.dataset.src = src
        })
    }

    // ===========================================
    // CRITICAL RESOURCE LOADING
    // ===========================================

    function loadCriticalResources() {
        // Preload critical images
        const criticalImages = document.querySelectorAll('img[loading="eager"]')
        criticalImages.forEach((img) => {
            if (img.dataset.src) {
                const link = document.createElement("link")
                link.rel = "preload"
                link.as = "image"
                link.href = img.dataset.src
                document.head.appendChild(link)
            }
        })
    }

    // ===========================================
    // FONT LOADING OPTIMIZATION
    // ===========================================

    function optimizeFontLoading() {
        if ("fonts" in document) {
            // Load critical fonts first
            const criticalFonts = [
                new FontFace("Lora", "url(/fonts/lora-v20-latin-regular.woff2)", {
                    weight: "400",
                    style: "normal",
                    display: "swap",
                }),
                new FontFace("Source Sans 3", "url(/fonts/source-sans-3-v8-latin-regular.woff2)", {
                    weight: "400",
                    style: "normal",
                    display: "swap",
                }),
            ]

            criticalFonts.forEach((font) => {
                font
                    .load()
                    .then((loadedFont) => {
                        document.fonts.add(loadedFont)
                    })
                    .catch((error) => {
                        console.warn("Font loading failed:", error)
                    })
            })

            // Remove font-loading class when fonts are ready
            document.fonts.ready.then(() => {
                document.body.classList.remove("font-loading")
            })
        }
    }

    // ===========================================
    // ANIMATION PERFORMANCE
    // ===========================================

    function initAnimationObserver() {
        if ("IntersectionObserver" in window) {
            const animationObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("in-view")
                            // Unobserve after animation to improve performance
                            animationObserver.unobserve(entry.target)
                        }
                    })
                },
                {
                    threshold: 0.1,
                    rootMargin: "0px 0px -50px 0px",
                },
            )

            document.querySelectorAll(".observe-lazy").forEach((el) => {
                animationObserver.observe(el)
            })
        } else {
            // Fallback: just add the class immediately
            document.querySelectorAll(".observe-lazy").forEach((el) => {
                el.classList.add("in-view")
            })
        }
    }

    // ===========================================
    // PERFORMANCE MONITORING
    // ===========================================

    function monitorPerformance() {
        if ("PerformanceObserver" in window) {
            // Monitor Largest Contentful Paint
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries()
                const lastEntry = entries[entries.length - 1]
                console.log("LCP:", lastEntry.startTime)
            })
            lcpObserver.observe({ entryTypes: ["largest-contentful-paint"] })

            // Monitor First Input Delay
            const fidObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries()
                entries.forEach((entry) => {
                    console.log("FID:", entry.processingStart - entry.startTime)
                })
            })
            fidObserver.observe({ entryTypes: ["first-input"] })

            // Monitor Cumulative Layout Shift
            const clsObserver = new PerformanceObserver((list) => {
                let clsValue = 0
                const entries = list.getEntries()
                entries.forEach((entry) => {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value
                    }
                })
                console.log("CLS:", clsValue)
            })
            clsObserver.observe({ entryTypes: ["layout-shift"] })
        }
    }

    // ===========================================
    // SERVICE WORKER REGISTRATION
    // ===========================================

    function registerServiceWorker() {
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker
                    .register("/sw.js")
                    .then((registration) => {
                        console.log("SW registered: ", registration)
                    })
                    .catch((registrationError) => {
                        console.log("SW registration failed: ", registrationError)
                    })
            })
        }
    }

    // ===========================================
    // INITIALIZATION
    // ===========================================

    function init() {
        // Run immediately
        optimizeImages()
        loadCriticalResources()

        // Run when DOM is ready
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", () => {
                initLazyLoading()
                optimizeFontLoading()
                initAnimationObserver()
                monitorPerformance()
                registerServiceWorker()
            })
        } else {
            initLazyLoading()
            optimizeFontLoading()
            initAnimationObserver()
            monitorPerformance()
            registerServiceWorker()
        }
    }

    // Start the optimization
    init()
})()
