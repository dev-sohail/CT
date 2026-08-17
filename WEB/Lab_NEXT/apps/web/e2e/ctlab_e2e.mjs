#!/usr/bin/env node
/*
 * CTLabs end-to-end smoke test.
 *
 * Drives the statically-exported web app + the live API via Playwright Chromium.
 * Each check performs a full page navigation against the running site.
 *
 * Usage:
 *   sudo -H env PATH="/home/dev-so/.local/bin:$PATH" HOME=/home/dev-so \
 *     node apps/web/e2e/ctlab_e2e.mjs
 *
 * Requires: playwright-core installed (npm i -D playwright-core), Chrome
 * downloaded (npx playwright-core install chromium), and both the API
 * (http://api.lab.ct.local) and site (http://lab.ct.local) reachable.
 */

import playwright from 'playwright-core';
const { chromium } = playwright;

const SITE = process.env.CTLAB_SITE ?? 'http://lab.ct.local';
const API = process.env.CTLAB_API ?? 'http://api.lab.ct.local';
const OWNER_EMAIL = process.env.CTLAB_OWNER_EMAIL ?? 'owner@ctlab.local';
const OWNER_PASSWORD = process.env.CTLAB_OWNER_PASSWORD ?? 'admin123';

const CHROME_PATH =
    process.env.CTLAB_CHROME ??
    '/home/dev-so/.cache/ms-playwright/chromium-1208/chrome-linux64/chrome';

let passed = 0;
let failed = 0;
let token = null;
const results = [];

function check(name, ok, detail = '') {
    if (ok) {
        passed++;
        results.push(`  PASS  ${name}`);
    } else {
        failed++;
        results.push(`  FAIL  ${name} ${detail}`);
    }
}

async function apiJson(path, options = {}) {
    const headers = { Accept: 'application/json' };
    if (token) headers.Authorization = `Bearer ${token}`;
    if (options.body) headers['Content-Type'] = 'application/json';
    const res = await fetch(`${API}${path}`, { ...options, headers });
    const body = await res.json().catch(() => null);
    return { status: res.status, body };
}

async function loginAndInjectToken(page) {
    const r = await apiJson('/api/v1/login', {
        method: 'POST',
        body: JSON.stringify({ email: OWNER_EMAIL, password: OWNER_PASSWORD }),
    });
    if (r.status !== 200 || !r.body?.meta?.token) {
        throw new Error(`Login as owner failed (${r.status})`);
    }
    token = r.body.meta.token;
    await page.goto(`${SITE}/wiki`, { waitUntil: 'networkidle' });
    await page.evaluate((t) => window.localStorage.setItem('ctlab_token', t), token);
    await page.reload({ waitUntil: 'networkidle' });
}

async function newPage() {
    const browser = await chromium.launch({
        executablePath: CHROME_PATH,
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    return { browser, page: await browser.newPage() };
}

const now = Date.now();
const wsName = `E2E WS ${now}`;
const nbName = `E2E NB ${now}`;
const secName = `E2E SEC ${now}`;
const pageTitle = `E2E Page ${now}`;

async function main() {
    const { browser, page } = await newPage();
    try {
        // ---- Auth ----
        results.push('--- Auth ---');
        await loginAndInjectToken(page);
        check('owner login returns token', !!token);

        const me = await apiJson('/api/v1/me');
        check('GET /me returns owner', me.status === 200 && me.body.data.email === OWNER_EMAIL);

        // ---- Workspace CRUD ----
        results.push('--- Workspaces ---');
        const ws = await apiJson('/api/v1/workspaces', {
            method: 'POST',
            body: JSON.stringify({ name: wsName, description: 'e2e' }),
        });
        check('POST workspace 201', ws.status === 201);
        const wsId = ws.body?.data?.id;
        check('workspace id present', typeof wsId === 'number');

        const wsList = await apiJson('/api/v1/workspaces');
        check('workspace index lists it', Array.isArray(wsList.body?.data) && wsList.body.data.some((w) => w.id === wsId));

        const wsUpd = await apiJson(`/api/v1/workspaces/${wsId}`, {
            method: 'PUT',
            body: JSON.stringify({ name: `${wsName} UPD` }),
        });
        check('PUT workspace 200', wsUpd.status === 200);

        // ---- Notebook / Section / Page hierarchy ----
        results.push('--- Wiki hierarchy ---');
        const nb = await apiJson(`/api/v1/workspaces/${wsId}/notebooks`, {
            method: 'POST',
            body: JSON.stringify({ name: nbName }),
        });
        check('POST notebook 201', nb.status === 201);
        const nbId = nb.body?.data?.id;

        const sec = await apiJson(`/api/v1/notebooks/${nbId}/sections`, {
            method: 'POST',
            body: JSON.stringify({ name: secName }),
        });
        check('POST section 201', sec.status === 201);
        const secId = sec.body?.data?.id;

        const pg = await apiJson(`/api/v1/sections/${secId}/pages`, {
            method: 'POST',
            body: JSON.stringify({ title: pageTitle, content: 'Hello from E2E', tags: ['e2e'] }),
        });
        check('POST page 201', pg.status === 201);
        const pageId = pg.body?.data?.id;

        // ---- Blocks sync ----
        const blocks = await apiJson(`/api/v1/pages/${pageId}/blocks/sync`, {
            method: 'POST',
            body: JSON.stringify({
                blocks: [
                    { type: 'heading', content: `# ${pageTitle}`, sort_order: 0 },
                    { type: 'text', content: 'Body text', sort_order: 1 },
                ],
            }),
        });
        check('blocks sync 200 + 2 blocks', blocks.status === 200 && blocks.body?.data?.length === 2);

        // ---- Favorite ----
        const fav = await apiJson(`/api/v1/pages/${pageId}/favorite`, { method: 'POST' });
        check('toggle favorite 200', fav.status === 200 && fav.body?.data?.is_favorite === true);

        // ---- Versions ----
        const ver = await apiJson(`/api/v1/pages/${pageId}/versions`, {
            method: 'POST',
            body: JSON.stringify({ note: 'e2e snapshot' }),
        });
        check('version snapshot 201', ver.status === 201);
        const verList = await apiJson(`/api/v1/pages/${pageId}/versions`);
        check('version index lists snapshot', Array.isArray(verList.body?.data) && verList.body.data.length >= 1);

        // ---- Search ----
        const search = await apiJson(`/api/v1/pages?q=${encodeURIComponent(pageTitle.split(' ').pop())}`);
        check('search finds page by title', search.status === 200 && search.body.data.some((p) => p.id === pageId));

        const searchBody = await apiJson(`/api/v1/pages?q=${encodeURIComponent('Body text')}`);
        check('search finds page by block content', searchBody.status === 200 && searchBody.body.data.some((p) => p.id === pageId));

        // ---- Template CRUD ----
        const tpl = await apiJson('/api/v1/templates', {
            method: 'POST',
            body: JSON.stringify({
                name: `E2E TPL ${now}`,
                description: 'e2e template',
                icon: '🧩',
                blocks: [
                    { type: 'heading', content: 'Agenda', meta: { level: 2 } },
                    { type: 'text', content: '', meta: {} },
                ],
            }),
        });
        check('template create 201', tpl.status === 201);
        const tplId = tpl.body?.data?.id;
        const tplUpd = await apiJson(`/api/v1/templates/${tplId}`, {
            method: 'PUT',
            body: JSON.stringify({ name: `E2E TPL ${now} v2`, blocks: [{ type: 'text', content: 'Replacement', meta: {} }] }),
        });
        check('template update replaces blocks', tplUpd.status === 200 && tplUpd.body?.data?.blocks?.length === 1);
        const tplDel = await apiJson(`/api/v1/templates/${tplId}`, { method: 'DELETE' });
        check('template delete', tplDel.status === 200 && tplDel.body?.data?.deleted === true);

        // ---- Ownership isolation ----
        const other = await apiJson('/api/v1/register', {
            method: 'POST',
            body: JSON.stringify({
                name: 'E2E Other',
                email: `e2e-other-${now}@ctlab.local`,
                password: 'OtherPass!123456',
                password_confirmation: 'OtherPass!123456',
            }),
        });
        check('register second user', other.status === 201);
        const otherToken = other.body?.meta?.token;
        const stolen = await fetch(`${API}/api/v1/workspaces/${wsId}`, {
            headers: { Authorization: `Bearer ${otherToken}` },
        });
        check('other user cannot read workspace', stolen.status === 403);

        // ---- Phase 3 kernels: settings, audit, search, tags, notifications, documents ----
        results.push('--- Phase 3 kernels ---');

        const setTheme = await apiJson('/api/v1/settings/theme', {
            method: 'PUT',
            body: JSON.stringify({ value: 'dark' }),
        });
        check('setting upsert', setTheme.status === 200 && setTheme.body?.data?.value === 'dark');

        const getTheme = await apiJson('/api/v1/settings/theme');
        check('setting read', getTheme.status === 200 && getTheme.body?.data?.value === 'dark');

        const unified = await apiJson(`/api/v1/search?q=${encodeURIComponent('E2E Page')}`);
        check(
            'unified search finds page',
            unified.status === 200 && unified.body.data.some((r) => r.title === pageTitle),
        );

        const audit = await apiJson('/api/v1/audit-log');
        check(
            'audit log lists login + page events',
            audit.status === 200 && audit.body.data.length >= 1,
        );

        const tag = await apiJson('/api/v1/tags', {
            method: 'POST',
            body: JSON.stringify({ name: 'e2e-tag' }),
        });
        check('tag create', tag.status === 201 && tag.body?.data?.slug === 'e2e-tag');

        const attach = await apiJson('/api/v1/tags/attach', {
            method: 'POST',
            body: JSON.stringify({
                taggable_type: 'App\\Domains\\SecondBrainPersonalWiki\\Models\\Page',
                taggable_id: pageId,
                names: ['e2e-tag'],
            }),
        });
        check('tag attach to page', attach.status === 200 && attach.body.data.length === 1);

        const tagList = await apiJson('/api/v1/tags');
        check(
            'tag list contains attached tag',
            tagList.status === 200 && tagList.body.data.some((t) => t.slug === 'e2e-tag' && t.count >= 1),
        );

        const pref = await apiJson('/api/v1/notification-preferences/reminder', {
            method: 'PUT',
            body: JSON.stringify({ enabled: true, channels: ['database'] }),
        });
        check('notification preference upsert', pref.status === 200 && pref.body?.data?.enabled === true);

        const notifications = await apiJson('/api/v1/notifications');
        check('notifications list empty', notifications.status === 200 && Array.isArray(notifications.body.data));

        const openapi = await apiJson('/api/v1/openapi.json');
        check('openapi spec served', openapi.status === 200 && openapi.body?.openapi === '3.0.3');


        // ---- Soft delete / restore / force ----
        await apiJson(`/api/v1/pages/${pageId}`, { method: 'DELETE' });
        const trash = await apiJson('/api/v1/pages/trash');
        check('trash contains page', trash.status === 200 && trash.body.data.some((p) => p.id === pageId));
        await apiJson(`/api/v1/pages/${pageId}/restore`, { method: 'POST' });
        const restored = await apiJson(`/api/v1/pages/${pageId}`);
        check('page restored', restored.status === 200);
        await apiJson(`/api/v1/pages/${pageId}/force`, { method: 'DELETE' });
        const gone = await apiJson(`/api/v1/pages/${pageId}`);
        check('force delete 404', gone.status === 404);

        // ---- UI smoke: dashboard renders with wiki link ----
        results.push('--- UI ---');
        await page.goto(`${SITE}/`, { waitUntil: 'networkidle' });
        const bodyText = await page.evaluate(() => document.body.innerText);
        check('home page renders', bodyText.length > 0);

        const dashText = await page.evaluate(() => document.body.innerText);
        check('dashboard shows stats cards', /Workspaces|Recent Pages|Quick Links/.test(dashText));

        await page.goto(`${SITE}/planner`, { waitUntil: 'networkidle' });
        const plannerText = await page.evaluate(() => document.body.innerText);
        check('planner renders buckets', /Overdue|Today|This Week/i.test(plannerText));

        await page.goto(`${SITE}/wiki/search?q=${encodeURIComponent(pageTitle)}`, { waitUntil: 'networkidle' });
        const searchText = await page.evaluate(() => document.body.innerText);
        check('wiki search page renders results', searchText.includes('results for') && searchText.includes(pageTitle));

        // ---- Design system gallery + theme toggle ----
        await page.goto(`${SITE}/design`, { waitUntil: 'networkidle' });
        const designText = await page.evaluate(() => document.body.innerText);
        check('design gallery renders components', designText.includes('Design System') && designText.includes('Buttons'));

        const before = await page.evaluate(() => document.documentElement.getAttribute('data-ctlab-theme'));
        await page.getByRole('button', { name: /Light|Dark/ }).first().click();
        const after = await page.evaluate(() => document.documentElement.getAttribute('data-ctlab-theme'));
        check('theme toggle flips attribute', before !== after && (after === 'dark' || after === 'light'));

        // ---- Cleanup ----
        const wsDel = await apiJson(`/api/v1/workspaces/${wsId}`, { method: 'DELETE' });
        check('cleanup workspace', wsDel.status === 204);
    } finally {
        await browser.close();
    }

    console.log(results.join('\n'));
    console.log(`\n${passed} passed, ${failed} failed`);
    process.exit(failed > 0 ? 1 : 0);
}

main().catch((err) => {
    console.error(err.message);
    console.log(`\n${passed} passed, ${failed} failed`);
    process.exit(1);
});
