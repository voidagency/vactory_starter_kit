import { chromium, type FullConfig } from '@playwright/test';

/**
 * Connexion par lien à usage unique (drush uli).
 *
 * Le lien arrive par E2E_LOGIN_URL (run.sh le lit dans `stack uli` et ne
 * l'écrit nulle part). La connexion se fait ici, dans un navigateur sans trace
 * ni vidéo, pour que le lien n'apparaisse dans aucun fichier produit. La
 * session obtenue passe au test par la mémoire du processus (process.env, hérité
 * par les workers), jamais par un fichier.
 */
export default async function globalSetup(config: FullConfig) {
  const loginUrl = process.env.E2E_LOGIN_URL;
  const baseURL = config.projects[0].use.baseURL as string;
  if (!loginUrl) {
    throw new Error('E2E_LOGIN_URL manquant : lancer par ./run.sh <instance> (voir README.md).');
  }
  delete process.env.E2E_LOGIN_URL;

  const browser = await chromium.launch();
  const context = await browser.newContext({ ignoreHTTPSErrors: true, baseURL });
  const page = await context.newPage();
  const errors: { path: string; message: string; stack: string }[] = [];
  page.on('pageerror', (e) => {
    errors.push({ path: new URL(page.url()).pathname, message: e.message, stack: (e.stack || '').split('\n').slice(0, 4).join('\n') });
  });

  await page.goto(loginUrl);
  await page.waitForLoadState('networkidle');
  // Le lien consommé, vérifier qu'on est bien connecté : la page des contenus
  // de l'administration répond sans demander de connexion.
  const res = await page.goto('/en/admin/content');
  if (!res || res.status() !== 200 || /\/user\/login/.test(page.url())) {
    await browser.close();
    throw new Error(`Connexion par lien à usage unique refusée (statut ${res?.status()}). Lien déjà utilisé ou expiré ?`);
  }

  process.env.E2E_STORAGE_STATE = JSON.stringify(await context.storageState());
  // Erreurs JS vues pendant la connexion (page d'atterrissage du lien) :
  // le test les relit et les juge avec les mêmes règles que le reste du parcours.
  process.env.E2E_LOGIN_PAGE_ERRORS = JSON.stringify(errors);
  await browser.close();
}
