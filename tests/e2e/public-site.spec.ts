import { expect, test } from '@playwright/test';

test('homepage presents the vehicle finance experience', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('heading', { name: /your next big move/i })).toBeVisible();
    const loanCta = page.locator('.hero-actions a').first();
    await expect(loanCta).toHaveAttribute('href', '/loans/car-loan');
    await expect(loanCta).toContainText(/explore car finance/i);
    await page.locator('.hero-loan-option').nth(1).click();
    await expect(loanCta).toHaveAttribute('href', '/loans/two-wheeler-loan');
    await expect(loanCta).toContainText(/explore two-wheeler finance/i);
    await expect(page.getByRole('heading', { name: /recent updates, with source context/i })).toBeVisible();
    await expect(page.getByText(/not a lender/i).last()).toBeVisible();
});

test('calculator updates an estimate and comparison opens', async ({ page }) => {
    await page.goto('/emi-calculator');

    await expect(page.getByRole('heading', { name: /see the shape of/i })).toBeVisible();
    await expect(page.getByText(/estimated monthly emi/i)).toBeVisible();
    await page.getByLabel('Tenure unit').selectOption('years');
    await expect(page.getByText('5 years')).toBeVisible();
    await page.getByLabel('Vehicle price').fill('2000000');
    await page.getByLabel('Down payment').fill('500000');
    await expect(page.getByText('75.0%')).toBeVisible();
    await page.getByRole('button', { name: 'Reset' }).click();
    await expect(page.getByLabel('Tenure unit')).toHaveValue('months');
    await expect(page.getByLabel('Vehicle price')).toHaveValue('1200000');
    await page.getByText('View 60-month amortization schedule').click();
    await expect(page.getByRole('table').getByText('Month', { exact: true })).toBeVisible();
    await page.goto('/compare-loans');
    await expect(page.getByRole('heading', { name: /compare the numbers yourself/i })).toBeVisible();
    await expect(page.getByText(/no lender or bank offers are represented/i)).toBeVisible();
});

test('enquiry validates, persists and confirms with a reference number', async ({ page }) => {
    await page.goto('/apply');
    await page.getByRole('button', { name: /continue/i }).click();
    await expect(page.getByRole('heading', { name: /tell us a little/i })).toBeVisible();
    await page.getByRole('button', { name: /continue/i }).click();
    await expect(page.getByPlaceholder('e.g. ₹8,00,000')).toBeFocused();
    await page.getByPlaceholder('e.g. ₹8,00,000').fill('850000');
    await page.getByPlaceholder('Your city').fill('Pune');
    await page.getByLabel('Vehicle condition').selectOption('new');
    await page.getByLabel('Preferred tenure').selectOption('3–5 years');
    await page.getByRole('button', { name: /continue/i }).click();
    await page.getByPlaceholder('Your name').fill('Test Customer');
    await page.getByPlaceholder('10-digit mobile number').fill('9876543210');
    await page.getByLabel('Best time to call').selectOption('Morning');
    await page.getByRole('button', { name: /continue/i }).click();
    await page.getByLabel(/i consent to sahu enterprises/i).check();
    await page.getByRole('button', { name: /send enquiry/i }).click();
    await expect(page.getByRole('heading', { name: /thank you for contacting sahu enterprises/i })).toBeVisible();
    await expect(page.locator('.reference-number strong')).toHaveText(/^SAH-\d{8}-[A-Z0-9]{8}$/);
});

test('contact form persists a message and confirms its reference', async ({ page }) => {
    await page.goto('/contact');
    await page.getByLabel('Your name').fill('Browser Test Customer');
    await page.getByLabel('Mobile number').fill('9876543210');
    await page.getByLabel('What can we help with?').selectOption('callback');
    await page.getByLabel('Your message').fill('Please call to discuss vehicle finance guidance.');
    await page.getByRole('checkbox').check();
    await page.getByRole('button', { name: /send message/i }).click();
    await expect(page.getByRole('heading', { name: /thank you for reaching out/i })).toBeVisible();
    await expect(page.locator('.reference-number strong')).toHaveText(/^SAH-C-\d{8}-[A-Z0-9]{8}$/);
});

test('news filters educational fixtures by search text', async ({ page }) => {
    await page.goto('/news/dsa');

    await page.getByLabel('Search updates').fill('Direct Selling Agent');
    await expect(page.getByRole('heading', { name: /understanding the role of a direct selling agent/i })).toBeVisible();
    await expect(page.getByText(/original educational content/i)).toBeVisible();
});

test('mobile navigation opens and exposes vehicle finance', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');

    await page.getByRole('button', { name: 'Toggle menu' }).click();
    await expect(page.locator('.mobile-nav').getByRole('link', { name: 'Vehicle finance' })).toBeVisible();
    await page.locator('.mobile-nav .loan-nav > summary').click();
    await expect(page.locator('.mobile-nav').getByRole('link', { name: /new car loans/i })).toBeVisible();
});

test('robots and sitemap expose public routes', async ({ page }) => {
    const robots = await page.request.get('/robots.txt');
    expect(robots.status()).toBe(200);
    expect(await robots.text()).toContain('/sitemap.xml');

    const sitemap = await page.request.get('/sitemap.xml');
    expect(sitemap.status()).toBe(200);
    expect(await sitemap.text()).toContain('/vehicle-finance');
});

test('education links and balance transfer estimates stay user-triggered', async ({ page }) => {
    await page.goto('/videos');
    await expect(page.getByRole('heading', { name: /borrow with more confidence/i })).toBeVisible();
    await expect(page.locator('iframe')).toHaveCount(0);
    await page.getByRole('button', { name: /load video: responsible borrowing/i }).click();
    await expect(page.locator('iframe')).toHaveAttribute('src', /youtube-nocookie\.com\/embed/);
    await page.goto('/financial-resources');
    await expect(page.getByRole('link', { name: /visit source/i }).first()).toHaveAttribute('rel', 'noopener noreferrer');
    await page.goto('/calculators/balance-transfer');
    await expect(page.getByRole('heading', { name: /would a transfer change your total/i })).toBeVisible();
    await expect(page.getByText(/estimated difference after fees/i)).toBeVisible();
    await page.getByLabel('New illustrative annual rate').fill('9');
    await expect(page.getByText(/estimated difference after fees/i)).toBeVisible();
});

test('the required public routes render a page heading', async ({ page }) => {
    const routes = [
        '/', '/loans', '/loans/car-loan', '/loans/two-wheeler-loan', '/loans/used-vehicle-loan',
        '/loans/commercial-vehicle-loan', '/loans/vehicle-refinance', '/loans/personal-loan',
        '/loans/business-loan', '/loans/home-loan', '/loans/loan-against-property', '/loans/balance-transfer',
        '/vehicle-finance', '/emi-calculator', '/compare-loans', '/apply', '/news', '/news/dsa',
        '/news/banking', '/news/vehicle-finance', '/news/sample-story', '/resources',
        '/resources/vehicle-loan-emi', '/resources/dsa-guide', '/resources/vehicle-finance-guide',
        '/resources/used-car-finance', '/resources/loan-tenure-guide', '/resources/vehicle-finance-documents',
        '/resources/credit-score-guide', '/resources/loan-processing-fees', '/resources/vehicle-refinancing-guide',
        '/resources/borrower-rights-guide', '/about', '/contact', '/faq', '/privacy-policy', '/terms',
        '/disclaimer', '/grievance-redressal',
    ];

    for (const route of routes) {
        const response = await page.goto(route);
        expect(response?.status(), route).toBe(200);
        await expect(page.locator('main h1').first(), route).toBeVisible();
    }
});

test('homepage, enquiry and news avoid horizontal overflow at key widths', async ({ page }) => {
    for (const width of [320, 375, 430, 768, 1024, 1280, 1440, 1920]) {
        await page.setViewportSize({ width, height: 900 });

        for (const route of ['/', '/apply', '/news']) {
            await page.goto(route);
            const dimensions = await page.evaluate(() => ({
                viewport: document.documentElement.clientWidth,
                content: document.documentElement.scrollWidth,
            }));
            expect(dimensions.content, `${route} at ${width}px`).toBeLessThanOrEqual(dimensions.viewport);
        }
    }
});
