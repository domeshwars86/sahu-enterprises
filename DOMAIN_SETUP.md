# Domain setup

No custom domain, DNS zone or registrar connection is configured in this workspace. The application reads `SAHU_ENTERPRISES_DOMAIN` in `config/sahu.php`; keep it unset until the business supplies a domain it controls.

When a verified domain and authorized DNS access are available:

1. Point the apex and `www` records to the selected host using the host’s exact instructions.
2. Choose one canonical hostname and redirect the other to HTTPS on it.
3. Configure `APP_URL` and `SAHU_ENTERPRISES_DOMAIN` with the canonical HTTPS origin.
4. Issue and automatically renew a valid TLS certificate before enabling public traffic.
5. Set the correct session cookie domain only if the deployment topology requires it; keep secure cookies enabled under HTTPS.
6. Publish SPF, DKIM and DMARC records only from the chosen mail provider’s verified instructions.
7. Verify DNS, certificate renewal, canonical metadata, sitemap and transactional mail after propagation.

Do not register a domain or change DNS without account ownership and access. No domain purchase or DNS change was performed.
