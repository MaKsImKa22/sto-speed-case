# Case: Elementor page → light HTML/CSS (PageSpeed 65 → 100)

> Learning/demo project. The car service is made up, photos are generated placeholders. The page text is in Ukrainian.

The same design and text as an Elementor page, but without the page builder and heavy photos.

- Case page: https://maksimka22.github.io/sto-speed-case/
- [Before (Elementor)](https://maksimka22.github.io/sto-speed-case/before/) · [After](https://maksimka22.github.io/sto-speed-case/after/)

## Results (Google PageSpeed Insights, 25.10.2026)

| Metric | Before, mobile | After, mobile | Before, desktop | After, desktop |
|---|---|---|---|---|
| Performance | 65 | **100** | 81 | **100** |
| Largest Contentful Paint | 14.9 s | **1.1 s** | 3.2 s | **0.3 s** |
| First Contentful Paint | 3.6 s | **0.8 s** | 0.8 s | **0.2 s** |
| Speed Index | 4.9 s | **0.8 s** | 1.1 s | **0.3 s** |

First load in Chrome (no cache): 49 requests and 2.7 MB → 5 requests and 188 KB. JavaScript files: 16 → 0.

Reports: [before](https://pagespeed.web.dev/analysis/https-maksimka22-github-io-sto-speed-case-before/l5n3f2osvt) · [after](https://pagespeed.web.dev/analysis/https-maksimka22-github-io-sto-speed-case-after/itbujezisg)

## What I changed
- 4000×2667 photos of 4 MB each → 800/1600 px of 30-100 KB, with `srcset`
- The hero photo moved from a CSS background to `<img>` with `fetchpriority="high"` and `preload` (LCP 14.9 → 1.1 s)
- jQuery, Swiper and Elementor scripts (16 JS files) → no JavaScript: accordion on `<details>`, gallery on CSS scroll-snap
- 19 CSS files and Google Fonts → one inline CSS and system fonts
- Embedded Google Map → address block with a link to Google Maps
- `loading="lazy"` and fixed sizes for images below the first screen
- Schema.org `AutoRepair` markup for local SEO

## Fair conditions
The "before" page was built in real Elementor 4.3.5 the way site owners usually do it. Both pages are hosted on GitHub Pages and measured the same way. Both are `noindex`, so the SEO score is lowered equally.

Built with Claude Code. Stack: WordPress, Elementor, HTML, CSS, PHP, Python, PageSpeed Insights.
