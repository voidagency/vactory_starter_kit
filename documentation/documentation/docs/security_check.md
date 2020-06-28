[**CMSmap**](https://github.com/Dionach/CMSmap)  
======
CMS scanner that automates the process of detecting security flaws of the most popular CMSs.  
- **Installation/Configuration** 
  1. Clone the gitHub [CMSmap](https://github.com/Dionach/CMSmap) repository.  
  2. Follow the instructions to install requirement.   
  3. Install [exploitdb](https://www.exploit-db.com/) for mac you can use brew `brew install exploitdb`. 
  4. Configure the **edbtype** and **edbpath** settings by adding the following to **cmsmap.conf**  
     :warning: Change `2019-01-12`  by your installed folder.
  
  
        [exploitdb]
        edbtype = GIT
        edbpath = /usr/local/Cellar/exploitdb/2019-01-12/share/exploitdb/ 
       
  - **Usage:**  
    - `cd path/CMSmap`   
    - `chmod +x cmsmap.py`    
    - `./cmsmap.py -f D  http://vactory8.lapreprod.com -H 'Authorization: Basic hash_generate_by_base64_of_htaccess'`  
     To check all available arguments refer to [usage](https://github.com/Dionach/CMSmap/blob/master/README.md#usage) section of CMSmap.  
     On MacOs you may have this error `xargs: illegal option -- -` apply the following patch if it needs.
     
     ```
     diff --git a/cmsmap/lib/exploitdbsearch.py b/cmsmap/lib/exploitdbsearch.py
              index 565619b..9a0b01b 100644
              --- a/cmsmap/lib/exploitdbsearch.py
              +++ b/cmsmap/lib/exploitdbsearch.py
              @@ -57,7 +57,7 @@ class ExploitDBSearch:
                               if not initializer.NoExploitdb:
                                   if plugin not in self.exclude:
                                       p = subprocess.Popen("grep -ilRE " + self.pluginPath + plugin + "[\&=/] " + self.edbpath + 
              -                            "exploits/php/ | xargs -r grep -ilR " + self.cmstype + 
              +                            "exploits/php/ | xargs -I F grep -ilR " + self.cmstype +
                                           " | cut -d \".\" -f1 | rev | cut -d \"/\" -f1 | rev | sort -u", stdout=subprocess.PIPE, shell=True, universal_newlines=True)
                                       output, error = p.communicate()
                                       ExploitIds = output.splitlines()
              @@ -81,8 +81,8 @@ class ExploitDBSearch:
                           if not initializer.NoExploitdb:
                               if theme not in self.exclude:
                                   p = subprocess.Popen("grep -ilR " + theme + " " + self.edbpath + 
              -                        "exploits/php/webapps/ | xargs -r grep -ilR " + self.cmstype + 
              -                        "| xargs -r grep -ilE 'theme|template' | cut -d \".\" -f1 | rev | cut -d \"/\" -f1 | rev | sort -u" , 
              +                        "exploits/php/webapps/ | xargs -I F grep -ilR " + self.cmstype +
              +                        "| xargs -I F grep -ilE 'theme|template' | cut -d \".\" -f1 | rev | cut -d \"/\" -f1 | rev | sort -u" ,
                                       stdout=subprocess.PIPE, shell=True, universal_newlines=True)
                                   output , error = p.communicate()
                                   ExploitIds = output.splitlines()
     ```
        
       
- **Vulnerability list.**  
[M] Website Not in HTTPS: http://vactory8.lapreprod.com  
[I] Server: Apache  
[L] X-Generator: Drupal 8 (https://www.drupal.org)  
[L] X-Frame-Options: Not Enforced  
[I] Strict-Transport-Security: Not Enforced  
[I] X-Content-Security-Policy: Not Enforced  
[L] Robots.txt Found: http://vactory8.lapreprod.com/robots.txt  
[I] CMS Detection: Drupal  
[I] Drupal Theme: vactory  
[-] Drupal Default Files:  
[I] http://vactory8.lapreprod.com/core/COPYRIGHT.txt  
[I] http://vactory8.lapreprod.com/core/UPDATE.txt  
[I] http://vactory8.lapreprod.com/core/tests/Drupal/Tests/Component/Diff/Engine/fixtures/file2.txt  
[I] http://vactory8.lapreprod.com/core/tests/Drupal/Tests/Component/Diff/Engine/fixtures/file1.txt  
[I] http://vactory8.lapreprod.com/core/tests/Drupal/Tests/Component/FileCache/Fixtures/llama-42.txt  
[I] http://vactory8.lapreprod.com/core/tests/Drupal/Tests/Component/FileCache/Fixtures/llama-23.txt  
[I] http://vactory8.lapreprod.com/core/INSTALL.txt  
[I] http://vactory8.lapreprod.com/core/INSTALL.sqlite.txt  
[I] http://vactory8.lapreprod.com/core/INSTALL.pgsql.txt  
[I] http://vactory8.lapreprod.com/core/scripts/transliteration_data.php.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Core/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Transliteration/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Transliteration/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Transliteration/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Datetime/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Datetime/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Datetime/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Bridge/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Bridge/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Bridge/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/EventDispatcher/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/EventDispatcher/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/EventDispatcher/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Serialization/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Serialization/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Serialization/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Graph/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Graph/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Graph/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Discovery/TESTING.txt    
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Discovery/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Discovery/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Plugin/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Plugin/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Plugin/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Render/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Render/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Render/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Annotation/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Annotation/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Annotation/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Diff/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Diff/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Diff/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/FileSystem/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/FileSystem/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/FileSystem/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Assertion/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Assertion/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Assertion/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/ClassFinder/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/ClassFinder/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/ClassFinder/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/FileCache/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/FileCache/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/FileCache/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Gettext/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Gettext/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Gettext/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/ProxyBuilder/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/ProxyBuilder/README.txt   
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/ProxyBuilder/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/PhpStorage/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/PhpStorage/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/PhpStorage/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Uuid/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Uuid/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Uuid/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/DependencyInjection/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/DependencyInjection/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/DependencyInjection/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/HttpFoundation/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/HttpFoundation/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/HttpFoundation/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Utility/TESTING.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Utility/README.txt  
[I] http://vactory8.lapreprod.com/core/lib/Drupal/Component/Utility/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/lib/README.txt  
[I] http://vactory8.lapreprod.com/core/CHANGELOG.txt  
[I] http://vactory8.lapreprod.com/core/MAINTAINERS.txt  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/veggie-pasta-bake-umami.html   
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/watercress-soup-umami.html 
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/crema-catalana-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/chili-sauce-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/victoria-sponge-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/thai-green-curry-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/mediterranean-quiche-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/chocolate-brownie-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/recipe_instructions/pizza-umami.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/skip-the-spirits-with-delicious-mocktails.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/give-your-oatmeal-the-ultimate-makeover.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/the-real-deal-for-supermarket-savvy-shopping.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/lets-hear-it-for-carrots.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/baking-mishaps-our-troubleshooting-tips.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/the-umami-guide-to-our-favourite-mushrooms.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/article_body/give-it-a-go-and-grow-your-own-herbs.html  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/modules/demo_umami_content/default_content/LICENCE.txt  
[I] http://vactory8.lapreprod.com/core/profiles/demo_umami/themes/umami/README.txt  
[I] http://vactory8.lapreprod.com/core/LICENSE.txt  
[I] http://vactory8.lapreprod.com/core/modules/migrate_drupal_ui/tests/src/Functional/d7/files/sites/default/private/Babylon5.txt  
[I] http://vactory8.lapreprod.com/core/modules/migrate_drupal_ui/tests/src/Functional/d7/files/sites/default/files/ds9.txt  
[I] http://vactory8.lapreprod.com/core/modules/migrate_drupal_ui/tests/src/Functional/d6/files/core/modules/simpletest/files/html-1.txt  
[I] http://vactory8.lapreprod.com/core/modules/color/preview.html  
[I] http://vactory8.lapreprod.com/core/modules/color/tests/modules/color_test/themes/color_test_theme/color/preview.html  
[I] http://vactory8.lapreprod.com/core/modules/search/tests/UnicodeTest.txt  
[I] http://vactory8.lapreprod.com/core/modules/system/tests/fixtures/HtaccessTest/access_test.sql  
[I] http://vactory8.lapreprod.com/core/modules/system/tests/fixtures/HtaccessTest/access_test.php-info.txt  
[I] http://vactory8.lapreprod.com/core/modules/system/tests/fixtures/IgnoreDirectories/frontend_framework/b.txt  
[I] http://vactory8.lapreprod.com/core/modules/system/tests/fixtures/IgnoreDirectories/a.txt   
[I] http://vactory8.lapreprod.com/core/modules/system/tests/modules/plugin_test/src/Plugin/plugin_test/fruit/README.txt  
[I] http://vactory8.lapreprod.com/core/modules/system/tests/themes/test_theme_nyan_cat_engine/theme_test.template_test.nyan-cat.html  
[I] http://vactory8.lapreprod.com/core/modules/filter/tests/filter.url-input.txt  
[I] http://vactory8.lapreprod.com/core/modules/filter/tests/filter.url-output.txt  
[I] http://vactory8.lapreprod.com/core/modules/media/tests/fixtures/oembed/photo_flickr.html  
[I] http://vactory8.lapreprod.com/core/modules/media/tests/fixtures/oembed/video_collegehumor.html   
[I] http://vactory8.lapreprod.com/core/modules/media/tests/fixtures/oembed/video_vimeo.html  
[I] http://vactory8.lapreprod.com/core/modules/simpletest/tests/fixtures/select_2nd_selected.html  
[I] http://vactory8.lapreprod.com/core/modules/simpletest/tests/fixtures/select_none_selected.html  
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/javascript-1.txt  
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/sql-1.txt  
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/html-1.txt    
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/sql-2.sql      
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/html-2.html   
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/README.txt    
[I] http://vactory8.lapreprod.com/core/modules/simpletest/files/php-1.txt    
[I] http://vactory8.lapreprod.com/core/assets/vendor/ckeditor/plugins/a11yhelp/dialogs/lang/_translationstatus.txt  
[I] http://vactory8.lapreprod.com/core/assets/vendor/ckeditor/plugins/specialchar/dialogs/lang/_translationstatus.txt  
[I] http://vactory8.lapreprod.com/core/assets/vendor/jquery.ui/AUTHORS.txt  
[I] http://vactory8.lapreprod.com/core/INSTALL.mysql.txt  
[I] http://vactory8.lapreprod.com/core/themes/stable/README.txt  
[I] http://vactory8.lapreprod.com/core/themes/bartik/color/preview.html  
[I] http://vactory8.lapreprod.com/core/themes/bartik/README.txt  
[I] http://vactory8.lapreprod.com/core/themes/classy/README.txt  
[I] http://vactory8.lapreprod.com/core/themes/seven/README.txt  
[I] http://vactory8.lapreprod.com/core/themes/stark/README.txt  
[I] http://vactory8.lapreprod.com/sites/README.txt  
[I] http://vactory8.lapreprod.com/README.txt  
[I] http://vactory8.lapreprod.com/robots.txt  
[I] http://vactory8.lapreprod.com/profiles/README.txt  
[I] http://vactory8.lapreprod.com/modules/README.txt  
[I] http://vactory8.lapreprod.com/themes/README.txt  
[-] Drupal Modules ...   
[I] content  
[I] vactory  


[**drupwn**](https://github.com/immunIT/drupwn)  
======
This tool used for enumerate User, Node, Default files, Module, Theme.

- **Installation/Configuration** 
 Follow the instruction in documentation.
 
- **Usage:**  
`drupwn enum http://vactory8.lapreprod.com --users --nodes  --modules --dfiles --themes  --version=8  --bauth=htaccess_hash  --log`

- **Vulnerability list.**   
  Tested only on users so far.   
  ============ Users ============   
  [+] ***** (id=1)  
  [+] ***** (id=21)   
  [+] ***** (id=26)  
  _Solution:_   
  . Remove `View user information`   permission to anonymous users.
    
   ============ Default files ============   
   [+] /web.config (200)  
   [+] /README.txt (200)  
   [+] /LICENSE.txt (200)  
   [+] /update.php (403)  
   [+] /install.php (200)  
   _Solution:_   
   . Denied access to `install.php`  in htaccess.
   
         RedirectMatch 403     "/(install|update).php"
         
   
[**droopescan**](https://github.com/droope/droopescan)  
======
A plugin-based scanner that aids security researchers in identifying issues with several CMSs, mainly Drupal & Silverstripe.

- **Installation/Configuration** 
  - Follow the instruction in documentation.
  - if you use htaccess:
    create `.netrc` file in your root folder and set the credential like the following:
     
     
        machine vactory8.lapreprod.com
          login htaccess_login
          password htaccess_password
          

- **Usage:**  
  `droopescan scan drupal -u vactory8.lapreprod.com`

- **Vulnerability list.**   
 
   :warning: Known drupal folders have returned 404 Not Found. If a module does not have a LICENSE.txt file it will not be detected.
   
   [+] No plugins found.  
  
   [+] Themes found:  
       amptheme http://vactory8.lapreprod.com/themes/amptheme/  
       http://vactory8.lapreprod.com/themes/amptheme/LICENSE.txt
   
   [+] Possible version(s): 8.6.0-rc1  
     
   [+] No interesting urls found.  
            
          
        
        

 
 




