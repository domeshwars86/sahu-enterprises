# News integration

## Connected source

The application ingests the official [Press Information Bureau (PIB) press release RSS feed](https://www.pib.gov.in/ViewRss.aspx?lang=1&reg=1). The importer accepts only HTTPS article links on `pib.gov.in` or `www.pib.gov.in`, keeps titles that match the finance categories, and deduplicates releases by their source URL.

PIB's [copyright policy](https://www.pib.gov.in/content/186_2_Copyright-Policy.aspx?lang=1&reg=5) permits reuse of material featured on the site with prominent source acknowledgement and accuracy, with third-party material excluded. To keep the display narrow, this site shows only the headline, category, publication date, source name and a direct source link. It does not copy release text or images.

The keyword classifier is intentionally conservative and is not an editorial review. It sorts matching headlines into general finance, banking, DSA, or vehicle-finance categories. The site identifies the original publisher and links readers to the original release.

## Operations

Run a one-time import with:

```powershell
php artisan news:ingest-pib
```

Laravel schedules the command hourly with overlap protection. In local development, run `php artisan schedule:work`; in production, configure the host scheduler to run `php artisan schedule:run` every minute. News lists are cached for five minutes and the importer clears those cached lists after a successful feed read.

Set `PIB_NEWS_ENABLED=false` to pause ingestion. Feed errors are reported to the Laravel log and return a failed command status; the website continues to show the most recently imported headlines. Configure production cache storage with a shared, durable backend if the app runs on multiple instances.

## Sources that are not enabled

RBI RSS is intentionally not enabled. The [RBI disclaimer](https://www.rbi.org.in/Scripts/Disclaimer.aspx) restricts caching and linking to internal RBI pages without specific permission. Obtain written permission before adding an RBI feed or source links.

No commercial news API is configured. Add one only after selecting a provider, confirming the intended commercial display and storage rights, and placing its credentials in server-side environment variables. Do not expose API keys to Vite or the browser.
