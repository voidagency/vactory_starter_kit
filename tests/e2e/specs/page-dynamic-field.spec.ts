import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { test, expect, request, type Page, type Locator, type APIRequestContext } from '@playwright/test';

/**
 * Parcours contributeur : créer puis modifier une page (vactory_page) avec un
 * paragraphe « Template » (vactory_component) dont le Dynamic Field est rempli
 * par la popin, puis vérifier ce que voient la base, la page Drupal et le front
 * (JSON:API du nœud, lu comme le fait vactory_next).
 *
 * Template utilisé : « DWidget Links » (vactory_dynamic_field_default, widget
 * 16) : une image (médiathèque), un texte alternatif, une description et un
 * lien (url_extended, autocomplete linkit).
 */

// ---------------------------------------------------------------------------
// Erreurs JavaScript.
//
// Toute erreur non attrapée d'une page (événement `pageerror`) fait échouer le
// test, sauf celles de la liste ci-dessous. Une erreur tolérée n'est jamais
// muette : elle est inscrite dans les annotations du rapport et dans la sortie.
// ---------------------------------------------------------------------------
type KnownError = { id: string; why: string; message: RegExp; path: RegExp };
const KNOWN_JS_ERRORS: KnownError[] = [
  {
    // Redmine #71533 : vactory_dynamic_field/assets/js/app.js (behavior
    // allowChildDialogInteraction) lit $.ui.dialog.prototype sur une page où
    // jQuery UI Dialog n'est pas chargé. Vue sur la page d'atterrissage du lien
    // de connexion (/espace-prive/profile/<uid>) ; le ticket la relève aussi sur
    // des formulaires d'administration. Sans effet sur le parcours.
    id: '#71533',
    why: "app.js de vactory_dynamic_field : $.ui.dialog absent (lecture de 'prototype')",
    message: /^Cannot read properties of undefined \(reading 'prototype'\)$/,
    path: /./,
  },
  {
    // Redmine #71569 : vactory_dynamic_field/assets/js/drag_and_drop.js fait
    // new Sortable(#sortable-components) à chaque ajaxStop, même popin fermée
    // (élément absent) : Sortable lève « Sortable: `el` must be an HTMLElement,
    // not [object Null] » (chaîne levée : Playwright n'en garde que la fin).
    // Vue sur le formulaire de nœud après la fermeture de la popin, sans effet
    // sur le parcours (template, enregistrement et modification aboutissent).
    // Tolérée seulement sur le formulaire de nœud.
    id: '#71569',
    why: 'drag_and_drop.js de vactory_dynamic_field : Sortable sur un élément absent',
    message: /`el` must be an HTMLElement, not \[object Null\]$/,
    path: /^\/(?:[a-z]{2}\/)?node\/(?:add\/[a-z_]+|\d+\/edit)$/,
  },
];

type JsError = { path: string; message: string; stack?: string };

function judgeErrors(errors: JsError[]): { known: (JsError & { id: string })[]; unknown: JsError[] } {
  const known: (JsError & { id: string })[] = [];
  const unknown: JsError[] = [];
  for (const e of errors) {
    const k = KNOWN_JS_ERRORS.find((k) => k.message.test(e.message) && k.path.test(e.path));
    if (k) known.push({ ...e, id: k.id });
    else unknown.push(e);
  }
  return { known, unknown };
}

// ---------------------------------------------------------------------------
// Base de données (optionnel) : run.sh donne E2E_DRUSH, une commande drush de
// l'instance (ex. « scripts/stack/stack drush e2e »).
// ---------------------------------------------------------------------------
function drush(args: string[]): string | null {
  const cmd = process.env.E2E_DRUSH;
  if (!cmd) return null;
  const [bin, ...pre] = cmd.split(' ').filter(Boolean);
  return execFileSync(bin, [...pre, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
}

function sqlq(sql: string): string | null {
  return drush(['sql:query', sql]);
}

// ---------------------------------------------------------------------------
// Popin Dynamic Field.
// ---------------------------------------------------------------------------
const TEMPLATE_TAB = 'Content';
const TEMPLATE_NAME = /DWidget Links$/;
const WIDGET_ID = 'vactory_default:16';
const LINK_TARGET_TITLE = 'Homepage';

/** La dernière popin jQuery UI ouverte (popin DF, puis médiathèque par-dessus). */
function topDialog(page: Page): Locator {
  return page.locator('.ui-dialog:visible').last();
}

/** Bouton de la barre de boutons de la popin (Drupal y recopie les submits). */
function dialogButton(dialog: Locator, name: string | RegExp): Locator {
  return dialog.locator('.ui-dialog-buttonpane').getByRole('button', { name });
}

async function waitAjax(page: Page) {
  await page.waitForLoadState('networkidle');
  await page.locator('.ajax-progress, .ajax-progress-throbber, .ajax-progress-fullscreen').first()
    .waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {});
}

async function fillLinkWithLinkit(page: Page, dialog: Locator, title: string) {
  const url = dialog.locator('input[name="components[0][link][url]"]');
  await url.fill('');
  await url.pressSequentially(title.slice(0, 6), { delay: 80 });
  // linkit (jQuery UI autocomplete) : la liste est ajoutée au body.
  const suggestion = page.locator('ul.linkit-ui-autocomplete:visible li, ul.ui-autocomplete:visible li')
    .filter({ hasText: title }).first();
  await expect(suggestion, `suggestion linkit « ${title} »`).toBeVisible();
  await suggestion.click();
  // linkit remplace le texte saisi par le chemin de l'entité choisie.
  await expect(url).toHaveValue(/^\/node\/\d+$/);
  return url.inputValue();
}

// ---------------------------------------------------------------------------
// Lecture comme le front (vactory_next) : routeur puis ressource individuelle
// JSON:API du nœud, avec les paragraphes inclus. Requête anonyme.
// ---------------------------------------------------------------------------
async function frontNodeJson(api: APIRequestContext, alias: string) {
  const router = await api.get(`/en/api/router?path=${encodeURIComponent(alias)}`);
  expect(router.status(), 'routeur /api/router').toBe(200);
  const r = await router.json();
  expect(r.jsonapi?.individual, 'jsonapi.individual du routeur').toBeTruthy();
  const params = new URLSearchParams({
    include: 'field_vactory_paragraphs',
    'fields[node--vactory_page]': 'title,field_vactory_paragraphs',
    'fields[paragraph--vactory_component]': 'drupal_internal__id,field_vactory_component,field_vactory_title',
  });
  const res = await api.get(`${r.jsonapi.individual}?${params}`);
  expect(res.status(), 'JSON:API du nœud').toBe(200);
  const body = await res.json();
  const para = (body.included || []).find((i: any) => i.type === 'paragraph--vactory_component');
  expect(para, 'paragraphe vactory_component inclus dans la réponse JSON:API').toBeTruthy();
  return { node: body.data, para, df: para.attributes.field_vactory_component, raw: JSON.stringify(para.attributes) };
}

// ---------------------------------------------------------------------------

const storageState = process.env.E2E_STORAGE_STATE ? JSON.parse(process.env.E2E_STORAGE_STATE) : undefined;
test.use({ storageState });

// Erreurs JS de tout le parcours (connexion comprise), jugées après le test,
// qu'il ait réussi ou non : une erreur qui casse le parcours est ainsi nommée.
const jsErrors: JsError[] = JSON.parse(process.env.E2E_LOGIN_PAGE_ERRORS || '[]');
const consoleErrors: string[] = [];

test.afterEach(async ({}, testInfo) => {
  const { known, unknown } = judgeErrors(jsErrors);
  for (const k of known) {
    testInfo.annotations.push({ type: `erreur JS connue ${k.id}`, description: `${k.path} : ${k.message}` });
    console.warn(`[erreur JS connue ${k.id}] ${k.path} : ${k.message}`);
  }
  for (const u of unknown) console.error(`[erreur JS] ${u.path} : ${u.message}\n    ${u.stack || ''}`);
  if (consoleErrors.length) {
    testInfo.attachments.push({ name: 'console-errors', contentType: 'text/plain', body: Buffer.from(consoleErrors.join('\n')) });
  }
  expect(unknown, 'erreurs JavaScript non attrapées pendant le parcours').toEqual([]);
});

test('contributeur : créer puis modifier une page avec un paragraphe Dynamic Field (popin)', async ({ page, baseURL }, testInfo) => {
  const run = `${Date.now()}`;
  const pageTitle = `E2E page DF ${run}`;
  const paraTitle = `E2E paragraphe ${run}`;
  const text1 = `E2E texte initial ${run}`;
  const text2 = `E2E texte modifié ${run}`;
  const linkTitle = `E2E lien ${run}`;
  const imageAlt = `E2E image ${run}`;

  page.on('pageerror', (e) => jsErrors.push({
    path: new URL(page.url()).pathname,
    message: e.message,
    stack: (e.stack || '').split('\n').slice(1, 4).map((l) => l.trim().replace(/\?[^:)]*/, '')).join(' | '),
  }));
  page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(`${new URL(page.url()).pathname} ${m.text().slice(0, 300)}`); });

  // Captures nommées des étapes clés (rapport HTML et dossier de sortie).
  let shot = 0;
  const capture = async (name: string) => {
    shot += 1;
    const file = testInfo.outputPath(`${String(shot).padStart(2, '0')}-${name}.png`);
    await page.screenshot({ path: file });
    await testInfo.attach(`${String(shot).padStart(2, '0')}-${name}`, { path: file, contentType: 'image/png' });
  };

  let nid = '';
  let alias = '';
  let linkPath = '';

  await test.step('Ajouter une page (Contenu > Ajouter du contenu > Page)', async () => {
    await page.goto('/en/admin/content');
    await page.getByRole('link', { name: /^\+?\s*Add content$/ }).first().click();
    // Plusieurs types de contenu : page de choix ; un seul : Drupal redirige.
    if (!/\/node\/add\/vactory_page/.test(page.url())) {
      // Le lien de la page de choix, pas celui (caché) de la barre d'administration.
      await page.getByRole('main').locator('a[href$="/node/add/vactory_page"]').first().click();
    }
    await expect(page).toHaveURL(/\/node\/add\/vactory_page$/);
    await expect(page.getByRole('heading', { name: /Create Page/ })).toBeVisible();
    await page.locator('[name="title[0][value]"]').fill(pageTitle);
    await page.getByLabel('Page description', { exact: true }).fill(`E2E description ${run}`);
  });

  await test.step('Paragraphe Template (vactory_component) présent', async () => {
    const paraTitleInput = page.locator('[name="field_vactory_paragraphs[0][subform][field_vactory_title][0][value]"]');
    if (!(await paraTitleInput.count())) {
      // Pas de paragraphe par défaut : l'ajouter par le bouton du champ.
      await page.locator('[name="field_vactory_paragraphs_vactory_component_add_more"]').click();
      await waitAjax(page);
    }
    await paraTitleInput.fill(paraTitle);
  });

  await test.step('Popin Dynamic Field : choisir le template « DWidget Links »', async () => {
    await page.getByRole('link', { name: 'Choose template' }).click();
    const dialog = topDialog(page);
    await expect(dialog).toBeVisible();
    await dialog.locator('.horizontal-tabs-list a', { hasText: TEMPLATE_TAB }).click();
    await dialog.locator('.form-item--template')
      .filter({ has: page.locator('.template-name', { hasText: TEMPLATE_NAME }) })
      .locator('label').click();
    await dialogButton(dialog, 'Next').click();
    await waitAjax(page);
    await expect(topDialog(page).locator('textarea[name="components[0][description]"]')).toBeVisible();
    await capture('popin-formulaire-template');
  });

  await test.step('Popin : image par la médiathèque (envoi d\'un fichier)', async () => {
    const df = topDialog(page);
    await df.getByRole('button', { name: 'Add media' }).click();
    await waitAjax(page);
    const ml = topDialog(page);
    await expect(ml).toContainText('Add or select media');
    const [chooser] = await Promise.all([
      page.waitForEvent('filechooser'),
      ml.getByText('Select files', { exact: true }).click(),
    ]);
    await chooser.setFiles(path.join(__dirname, '..', 'fixtures', 'e2e-image.png'));
    await waitAjax(page);
    const alt = ml.locator('input[name="media[0][fields][field_media_image][0][alt]"]');
    await expect(alt).toBeVisible();
    await alt.fill(imageAlt);
    await dialogButton(ml, /^Save( and insert)?$/).click();
    await waitAjax(page);
    // Selon la version de media_library : retour à la grille (« Insert selected »)
    // ou insertion directe.
    const insert = dialogButton(topDialog(page), 'Insert selected');
    if (await insert.isVisible().catch(() => false)) {
      await insert.click();
      await waitAjax(page);
    }
    await expect(page.locator('.ui-dialog:visible')).toHaveCount(1);
    await expect(topDialog(page).locator('.media-library-item, .js-media-library-item').first()).toBeVisible();
  });

  await test.step('Popin : texte et lien (autocomplete linkit), enregistrer le template', async () => {
    const df = topDialog(page);
    await df.locator('input[name="components[0][image_alt]"]').fill(imageAlt);
    await df.locator('textarea[name="components[0][description]"]').fill(text1);
    await df.locator('input[name="components[0][link][title]"]').fill(linkTitle);
    linkPath = await fillLinkWithLinkit(page, df, LINK_TARGET_TITLE);
    await capture('popin-remplie');
    await dialogButton(df, 'Save template').click();
    await waitAjax(page);
    await expect(page.locator('.ui-dialog:visible')).toHaveCount(0);
    // Le widget du formulaire affiche le template choisi et ses données.
    await expect(page.getByRole('link', { name: 'Edit template' })).toBeVisible();
    const data = await page.locator('input[data-dynamic-widget-value]').first().inputValue();
    expect(data).toContain(text1);
    expect(await page.locator('input[data-dynamic-widget-id]').first().inputValue()).toBe(WIDGET_ID);
  });

  await test.step('Enregistrer la page (publiée)', async () => {
    // Publication : moderation_state si la modération est active, sinon la case
    // « Published » (barre latérale de gin). L'une des deux doit exister.
    const state = page.locator('select[name="moderation_state[0][state]"]');
    const status = page.locator('input[name="status[value]"]');
    if (await state.count()) await state.selectOption({ label: 'Published' });
    else {
      await expect(status, 'case « Published » du formulaire de nœud').toHaveCount(1);
      await status.check();
    }
    await page.locator('#edit-submit, #edit-gin-sticky-actions input[value="Save"]').first().click();
    await expect(page.locator('[role="contentinfo"], .messages--status, [data-drupal-messages]').first())
      .toContainText(`Page ${pageTitle} has been created.`);
    await capture('page-creee');
    // nid : depuis la base (fiable quel que soit l'alias de la page d'arrivée).
    const fromDb = sqlq(`SELECT nid FROM node_field_data WHERE title = '${pageTitle}' AND type='vactory_page'`);
    if (fromDb !== null) nid = fromDb;
    else {
      const edit = await page.locator('a[href*="/node/"][href$="/edit"]').first().getAttribute('href');
      nid = edit!.match(/\/node\/(\d+)\/edit/)![1];
    }
    expect(nid, 'nid de la page créée').toMatch(/^\d+$/);
  });

  const checkAll = async (expected: string, absent: string | null, label: string) => {
    await test.step(`Base : nœud ${label}`, async () => {
      const row = sqlq(`SELECT status, title FROM node_field_data WHERE nid = ${nid}`);
      if (row === null) {
        testInfo.annotations.push({ type: 'base non vérifiée', description: 'E2E_DRUSH absent (lancer par run.sh)' });
        return;
      }
      expect(row).toBe(`1\t${pageTitle}`);
      const df = sqlq(
        `SELECT c.field_vactory_component_widget_id, HEX(c.field_vactory_component_widget_data) FROM node__field_vactory_paragraphs n `
        + `JOIN paragraph__field_vactory_component c ON c.entity_id = n.field_vactory_paragraphs_target_id `
        + `WHERE n.entity_id = ${nid}`,
      )!;
      // HEX : la sortie de mysql en mode batch échappe les « \\ » du JSON.
      const [widgetId, widgetHex] = df.split('\t');
      const widgetData = Buffer.from(widgetHex, 'hex').toString('utf8');
      expect(widgetId).toBe(WIDGET_ID);
      const item = JSON.parse(widgetData)['0'];
      expect(item.description).toBe(expected);
      expect(item.image_alt).toBe(imageAlt);
      expect(item.link.title).toBe(linkTitle);
      expect(item.link.url).toBe(linkPath);
      // Image : un média référencé par la médiathèque.
      expect(JSON.stringify(item.image)).toMatch(/"target_id":"\d+"/);
    });

    await test.step(`Page Drupal rendue ${label}`, async () => {
      const res = await page.goto(`/en/node/${nid}`);
      expect(res?.status()).toBe(200);
      alias = new URL(page.url()).pathname.replace(/^\/en/, '') || `/node/${nid}`;
      const widget = page.locator('.widget-links').first();
      await expect(widget).toContainText(expected);
      if (absent) await expect(widget).not.toContainText(absent);
      await widget.scrollIntoViewIfNeeded();
      await capture(`page-rendue-${label.replace(/ /g, '-')}`);
      const a = widget.locator('a').first();
      const href = await a.getAttribute('href');
      expect(href, 'lien du template').toBeTruthy();
      // L'image envoyée par la médiathèque est référencée par le template.
      const img = widget.locator('img').first();
      await expect(img).toHaveAttribute('alt', imageAlt);
      expect(`${await img.getAttribute('data-src')} ${await img.getAttribute('src')}`).toContain('e2e-image');
      // Le lien pointe vers la page choisie dans linkit.
      const target = await page.request.get(new URL(href!, baseURL).toString());
      expect(target.status()).toBe(200);
      expect(await target.text()).toContain(LINK_TARGET_TITLE);
    });

    await test.step(`JSON:API (lecture du front) ${label}`, async () => {
      const api = await request.newContext({ baseURL, ignoreHTTPSErrors: true });
      try {
        const { node, df, raw, para } = await frontNodeJson(api, alias);
        expect(node.attributes.title).toBe(pageTitle);
        expect(para.attributes.field_vactory_title).toBe(paraTitle);
        // Enhancer vactory_dynamic_field : widget_data est une chaîne JSON
        // (images résolues en URL, liens en chemin du front).
        expect(df?.widget_id).toBe(WIDGET_ID);
        const data = JSON.parse(df.widget_data);
        const item = data.components[0];
        expect(item.description).toBe(expected);
        if (absent) expect(raw).not.toContain(absent);
        expect(item.link.title).toBe(linkTitle);
        // Le chemin du lien donné au front (ex. « /en » pour la page d'accueil)
        // mène, par le routeur du front, au nœud choisi dans linkit.
        expect(item.link.url).toMatch(/^\/en(\/|$)/);
        const linkRoute = await api.get(`/en/api/router?path=${encodeURIComponent(item.link.url.replace(/^\/en/, '') || '/')}`);
        expect(linkRoute.status()).toBe(200);
        expect((await linkRoute.json()).entity?.id, 'nœud cible du lien (routeur du front)').toBe(linkPath.replace('/node/', ''));
        expect(item.image[0]._default).toMatch(/\/sites\/default\/files\/.*e2e-image.*\.png$/);
        expect(item.image[0].meta.alt).toBe(imageAlt);
        const img = await api.get(item.image[0]._default);
        expect(img.status(), 'image servie à l\'adresse donnée au front').toBe(200);
        expect(img.headers()['content-type']).toContain('image/png');
        testInfo.attachments.push({ name: `jsonapi-${label}`, contentType: 'application/json', body: Buffer.from(JSON.stringify(df, null, 2)) });
      } finally {
        await api.dispose();
      }
    });
  };

  await checkAll(text1, null, 'après création');

  await test.step('Modifier : rouvrir la popin, changer le texte, enregistrer', async () => {
    await page.goto(`/en/node/${nid}/edit`);
    // Paragraphes en mode « fermé » : ouvrir le paragraphe avant d'éditer.
    const editPara = page.locator('[name="field_vactory_paragraphs_0_edit"]');
    if (await editPara.count()) {
      await editPara.click();
      await waitAjax(page);
    }
    await page.getByRole('link', { name: 'Edit template' }).click();
    const df = topDialog(page);
    const desc = df.locator('textarea[name="components[0][description]"]');
    await expect(desc).toHaveValue(text1);
    // Les autres valeurs sont reprises dans la popin.
    await expect(df.locator('input[name="components[0][link][title]"]')).toHaveValue(linkTitle);
    await expect(df.locator('input[name="components[0][link][url]"]')).toHaveValue(linkPath);
    await expect(df.locator('input[name="components[0][image_alt]"]')).toHaveValue(imageAlt);
    await expect(df.locator('.media-library-item, .js-media-library-item').first()).toBeVisible();
    await desc.fill(text2);
    await capture('popin-modification');
    await dialogButton(df, 'Save template').click();
    await waitAjax(page);
    await expect(page.locator('.ui-dialog:visible')).toHaveCount(0);
    await page.locator('#edit-submit, #edit-gin-sticky-actions input[value="Save"]').first().click();
    await expect(page.locator('[role="contentinfo"], .messages--status, [data-drupal-messages]').first())
      .toContainText(`Page ${pageTitle} has been updated.`);
  });

  await checkAll(text2, text1, 'après modification');

});
