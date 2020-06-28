Memcached et une extention PHP fournit une interface procédurale maniable ainsi qu'orientée objet à Memcache,
 un démon fortement efficace dans la gestion du cache, qui est principalement destiné à faire baisser la charge des bases de données dans les applications web dynamiques.

## Environnement
Après l'activation de l'extention memcached sur l'envirenement et avant de touché ou faire quoi ce soi sur la partie Drupal,
 faut faire un petit test pour vérifier la connexion memcache [Port - Adresse].
- Code de test : 
````code

if (class_exists('Memcache')) {
    $server = 'SETME'; # Memcached server hostname
    $memcache = new Memcache;
    $isMemcacheAvailable = @$memcache->connect($server);
    if ($isMemcacheAvailable) {
        $aData = $memcache->get('data');
        echo '<pre>';
        if ($aData) {
            echo '<h2>Data from Cache:</h2>';
            print_r($aData);
        } else {
            $aData = array(
                'me' => 'you',
                'us' => 'them',
            );
            echo '<h2>Fresh Data:</h2>';
            print_r($aData);
            $memcache->set('data', $aData, 0, 300);
        }
        $aData = $memcache->get('data');
        if ($aData) {
            echo '<h3>Memcache seem to be working fine!</h3>';
        } else {
            echo '<h3>Memcache DOES NOT seem to be working!</h3>';
        }
        echo '</pre>';
    }
}
if (!$isMemcacheAvailable) {
    echo 'Memcache not available';
}

````

## Drupal
Pour la partie drupal faut installé deux(2) Modules de la contribution 
    - Memcache : https://www.drupal.org/project/memcache
    - Memcache Storage : https://www.drupal.org/project/memcache_storage
Après l'instalattion des deux modules, reste juste a r'ajouter la configuration suivante au niveau du **setting.php**

```code
// Memcache
$settings['memcache']['servers'] = ['127.0.0.1:11211' => 'default'];
$settings['memcache']['bins'] = ['default' => 'default'];
$settings['memcache']['key_prefix'] = '';

// Set’s default cache storage as Memcache and excludes database connection for cache
$settings['cache']['default'] = 'cache.backend.memcache_storage';
$settings['cache']['bins']['render'] = 'cache.backend.memcache';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.memcache';
// Enables to display total hits and misses
$settings['memcache_storage']['debug'] = TRUE;
```
et Voilaa.
