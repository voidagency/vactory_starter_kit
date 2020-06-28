<?php

/**
 * @file
 * Main documentation file.
 */

use Symfony\Component\Yaml\Yaml;

define('DRUPAL_DIR', dirname(__FILE__, 3));
$autoloader = require_once DRUPAL_DIR . '/autoload.php';

function getModules($folder = 'vactory') {
  $path = DRUPAL_DIR . '/modules/' . $folder . '/';

  $RegexIterator = new RegexIterator(
    new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($path)
    ),
    '/^.+\.md/i',
    RecursiveRegexIterator::GET_MATCH
  );

  $files = [];
  $names = [];

  foreach ($RegexIterator as $key => $file) {
    $real_file = $file[0];
    $folder = dirname($real_file);
    $split_folder = explode('/', $folder);
    $module_name = end($split_folder);
    $yaml_file = $folder . '/' . $module_name . '.info.yml';

    $file_content = @file_get_contents($yaml_file);

    if (!$file_content) {
      continue;
    }

    $parsed = Yaml::parse($file_content);

    array_push($files, [
      'name'         => $parsed['name'],
      'machine_name' => $module_name,
      'path'         => '../..' . substr($real_file, strrpos($real_file, '/modules/')),
    ]);

    $names[$key] = $parsed['name'];
  }

  array_multisort($names, SORT_ASC, $files);


  return $files;
}

$vactory_modules = getModules('vactory');
$custom_modules = getModules('custom');

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentation - By VOID.</title>
</head>
<body>
<div id="catalog"/>

<!-- Use the development version to get helpful errors in your console -->
<!-- <script src="https://unpkg.com/catalog@3/dist/catalog-standalone.development.js"></script> -->

<!-- Use the minified production version to get optimal performance -->
<script src="https://unpkg.com/catalog@3/dist/catalog-standalone.min.js"></script>

<script>
  Catalog.render({
    title: 'Documentation',
    logoSrc: '/themes/vactory/logo.png',
    pages: [
      {
        path: '/',
        title: 'Introduction',
        src: 'docs/intro.md'
      },
      {
        title: 'Pré-requis',
        pages: [
          {
            path: '/pre-requis/drupal-console',
            title: 'Drupal Console',
            content: Catalog.pageLoader('docs/pre-requis/drupal-console.md')
          },
          {
            path: '/pre-requis/drush',
            title: 'Drush',
            content: Catalog.pageLoader('docs/pre-requis/drush.md')
          },
          {
            path: '/pre-requis/git',
            title: 'Git',
            content: Catalog.pageLoader('docs/pre-requis/git.md')
          },
          {
            path: '/pre-requis/nodejs',
            title: 'Node.js',
            content: Catalog.pageLoader('docs/pre-requis/nodejs.md')
          },
          {
            path: '/pre-requis/gulpjs',
            title: 'Gulp.js',
            content: Catalog.pageLoader('docs/pre-requis/gulpjs.md')
          },
          {
            path: '/pre-requis/php-codesniffer',
            title: 'PHP CodeSniffer',
            content: Catalog.pageLoader('docs/pre-requis/php-codesniffer.md')
          },
          {
            path: '/pre-requis/phpstorm',
            title: 'PhpStorm IDE',
            content: Catalog.pageLoader('docs/pre-requis/phpstorm.md')
          }
        ]
      },
      {
        path: '/installation',
        title: 'Installation',
        src: 'docs/installation/installation.md'
      },
      {
        title: 'Theming',
        pages: [
          {
            path: '/theming/guidelines',
            title: 'Guidelines Générale',
            content: Catalog.pageLoader('docs/theming/guidelines.md')
          },
          {
            path: '/theming/scss-guidelines',
            title: 'Guidelines SCSS',
            content: Catalog.pageLoader('docs/theming/scss-guidelines.md')
          }
        ]
      },
      {
        title: 'Développement des modules',
        pages: [
          {
            path: '/modules-developpement/nomenclature',
            title: 'Nomenclature',
            content: Catalog.pageLoader('docs/modules-developpement/nomenclature.md')
          },
          {
            path: '/modules-developpement/content-type',
            title: 'Types de contenu',
            content: Catalog.pageLoader('docs/modules-developpement/content-type.md')
          },
          {
            path: '/modules-developpement/taxonomies',
            title: 'Taxonomies',
            content: Catalog.pageLoader('docs/modules-developpement/taxonomies.md')
          },
          {
            path: '/modules-developpement/securtiy',
            title: 'Sécurité',
            content: Catalog.pageLoader('docs/modules-developpement/securtiy.md')
          },
          {
            path: '/modules-developpement/content-moderation',
            title: 'Content Moderation',
            content: Catalog.pageLoader('docs/modules-developpement/content-moderation.md')
          }
        ]
      },
      {
        title: 'Testing',
        pages: [
          {
            path: '/testing/commits',
            title: 'Git hooks',
            content: Catalog.pageLoader('docs/testing/commits.md')
          }
        ]
      },
      {
        path: '/cron',
        title: 'Cron',
        src: 'docs/cron_docs.md'
      },
      {
        path: '/tadaa',
        title: 'Tadaa!',
        src: 'docs/switch-env.md'
      },
      {
        path: '/capistrano',
        title: 'Capistrano',
        src: 'docs/capistrano.md'
      },
      {
        path: '/redirect',
        title: 'Redirections (SEO)',
        src: 'docs/redirect.md'
      },
      {
        path: '/gestion-blocks',
        title: 'Géstion des blocks',
        src: 'docs/gestion-blocks.md'
      },
      {
        path: '/stage-file-proxy',
        title: 'Stage File Proxy',
        src: 'docs/stage_file_proxy.md'
      },
      {
        path: '/memcache',
        title: 'Installation de Memcache',
        src: 'docs/memcache.md'
      },
      {
        title: 'Modules Vactory',
        pages: [
          <?php foreach ($vactory_modules as $file) : ?>
          {
            path: '/modules/vactory/<?php print $file['machine_name']; ?>',
            title: '<?php print $file['name']; ?>',
            content: Catalog.pageLoader('<?php print $file['path']; ?>')
          },
          <?php endforeach; ?>
        ]
      },
      <?php if (count($custom_modules) > 0) : ?>
      {
        title: 'Modules Custom',
        pages: [
          <?php foreach ($custom_modules as $file) : ?>
          {
            path: '/modules/custom/<?php print $file['machine_name']; ?>',
            title: '<?php print $file['name']; ?>',
            content: Catalog.pageLoader('<?php print $file['path']; ?>')
          },
          <?php endforeach; ?>
        ]
      }
      <?php endif; ?>
    ]
  }, document.getElementById('catalog'));
</script>
</body>
</html>
