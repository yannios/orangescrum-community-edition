# NEXT AGENT HANDOFF - Croatian (hrv) translation - UPDATED
Repo: E:\CODING\orangescrum-community-edition  (CakePHP 5, THE ONE DOCKER RUNS)

## CRITICAL CONTEXT
- TWO copies on disk:
  - E:\CODING\orangescrum-community-edition  <-- TARGET REPO (Docker runs this)
  - E:\CODING\orangescrumptions              <-- old CakePHP 2 copy. DO NOT TOUCH.
- File-writing tools (create_new_file / edit_existing_file / single_find_and_replace)
  default to the WRONG repo (orangescrumptions). => WRITE FILES VIA TERMINAL (Set-Content).
  read_file / terminal work fine on community-edition with ABSOLUTE paths.

## DOCKER
- app: orangescrum-orangescrum-app-1 | db: orangescrum-orangescrum-postgres-1
- The app container has its OWN copy of code (NOT bind-mounted).
  After editing on host: docker cp <hostfile> orangescrum-orangescrum-app-1:/var/www/html/<dest>
- PSQL: docker exec orangescrum-orangescrum-postgres-1 psql -U orangescrum -d orangescrum -c "SELECT ..."
- Docker Desktop may need ~1-2 min after boot; retry loop if 'pipe not found'.

## *** THE BREAKTHROUGH (this was the blocker) ***
CakePHP MessagesFileLoader uses INTL Locale::parseLocale(), which CANONICALIZES 3-letter codes:
    spa->es  deu->de  fra->fr  por->pt  ara->ar  hrv->hr  eng->en
So a folder named after short_code (spa, hrv...) IS NEVER FOUND. The folder must be named
after the CANONICAL language code. That is why only 'ara'+'ar' existed (Arabic special-cased).
=> For Croatian we need a folder named 'hr' (NOT 'hrv'), containing default.po.

Sign convention used by the app:
  - resources/locales/<SHORT_CODE>/  -> used by LanguagesTable::getAvaliableLanguages() dropdown
        (checks file_exists resources/locales/<short_code>/default.po)  => needs 'hrv' folder
  - resources/locales/<CANONICAL>/   -> actually loaded by I18n at runtime            => needs 'hr' folder
  => KEEP BOTH folders: 'hrv' (dropdown visibility) AND 'hr' (real translation).

SECOND root cause: I18n CACHES translations. After adding the folder you MUST clear cache:
    Cache::clear('_cake_core_'); Cache::clear('default');
  (otherwise previously-cached empty catalog is reused).

VERIFIED WORKING:
    I18n::setLocale('hrv'); echo __('Login');   => "Prijava"
    __('Project') => "Projekt"   |   __('Save') => "Spremi"

## CURRENT STATE (done)
1. resources/locales/hrv/default.po  (host + container)  - 113 msgids (basic UI terms).
2. resources/locales/hr/default.po   (host + container)  - COPY of hrv (the one actually loaded).
3. DB row inserted & verified: languages.id=11, language=Croatian, short_code=hrv.
4. Languages cache cleared: tmp/cache/languages/orangescrum_languages_available_languages
5. NOTE: language cache (available_languages) may need clearing again after changes.

## REMAINING WORK (priority order)
A) UNTRANSLATED SIDEBAR/MENU (reported by user on /users/profile):
   "Company Settings" and "Project Settings" stayed English.
   Reason: they are __() calls in templates/element/sidebar_new.php but MISSING from our .po.
   Fix: append these msgids to BOTH hrv and hr default.po. Strings to add (from sidebar_new.php):
     Project Settings, Company Settings, Search Settings, Import & Export, Task Type, Label,
     My Company, Project Status, Project Type, Status Workflow, Roles & Permissions,
     Due Date Change Reason, Profile Settings, My Profile, Change Password, Notifications,
     My Default View, Left Menus, About, Developer API, Outlook
   Suggested HR: Projektne postavke, Postavke tvrtke, Pretrazi postavke, Uvoz i izvoz,
     Vrsta zadatka, Oznaka, Moja tvrtka, Status projekta, Vrsta projekta, Statusni tijek rada,
     Uloge i dozvole, Razlog promjene roka, Postavke profila, Moj profil, Promjena lozinke,
     Obavijesti, Moj zadani prikaz, Lijevi izbornici, O nama, Developer API, Outlook
   After editing: docker cp both folders into container, then clear cache.
B) EXPAND .po: full catalog has ~4785 phrases (see resources/locales/ar/default.po as ref).
   Current ~113. Untranslated strings safely fall back to English. Grow incrementally.
   To see all English source strings: resources/locales/default.pot
C) FRONTEND JS (gettext.js): loads JSON via <link rel=gettext href=Languages/index/<code>.json>.
   Static catalogs in webroot/Languages/*.json (spa,por,deu,fra,dan,original). NO controller.
   => create webroot/Languages/hrv.json  (JSON: {"":"<header>","Login":"Prijava",...})
   gettext.js silently skips a missing file, so UI still works but JS strings stay English.
D) AppController.php (~line 1550) hardcoded switch for spa/por/deu/fra sets os_locale_short + LANG_PREFIX.
   For full hrv support add: case 'hrv': os_locale_short='hr'; LANG_PREFIX='_hrv'; break;
   (search: set('os_locale_short', ...)). Also DST/date locale if needed.
E) Sidebar menu LANGUAGE (separate system): tables:
     menu_languages (columns: id,string_name,en,spa,por,deu,fra) -- NO hrv column
     sidebar_menus / sidebar_submenus -> menu_language_id FK
   The left nav uses custom_left_menu_new.php reading menu_languages columns. To translate the
   LEFT NAV (not the settings sub-menu) you must ALTER TABLE menu_languages ADD COLUMN hrv and
   fill it, then update the code that picks the column by locale. Arabic uses 'ara'/'ar' special case.
   (This is bigger; confirm with user which menus they want.)

## AFTER ANY .po / template change, ALWAYS:
   docker cp resources/locales/hrv orangescrum-orangescrum-app-1:/var/www/html/resources/locales/hrv
   docker cp resources/locales/hr  orangescrum-orangescrum-app-1:/var/www/html/resources/locales/hr
   docker exec orangescrum-orangescrum-app-1 chown -R www-data:www-data resources/locales
   # clear caches:
   docker exec -w /var/www/html orangescrum-orangescrum-app-1 php -r "require 'vendor/autoload.php'; require 'config/bootstrap.php'; Cake\Cache\Cache::clear('_cake_core_'); Cake\Cache\Cache::clear('default');"
   # available languages cache file:
   docker exec orangescrum-orangescrum-app-1 rm -f tmp/cache/languages/orangescrum_languages_available_languages

## VERIFY (CLI)
   docker exec -w /var/www/html orangescrum-orangescrum-app-1 php -r "require 'vendor/autoload.php'; require 'config/bootstrap.php'; Cake\I18n\I18n::setLocale('hrv'); echo __('Login').'|'.__('Project').'|'.__('Save');"
   EXPECT: Prijava|Projekt|Spremi

## GIT STATUS
Already staged (index): NEXT_AGENT_HANDOFF.md, resources/locales/hrv/default.po
Untracked: resources/locales/hr/   (ADD and COMMIT this too!)
_build.log was unstaged (git rm --cached). DO NOT commit _build.log or tmp_*.php.
Suggested first commit message:
   "i18n(hr): add Croatian translation catalog (hr + hrv locale folders)"
IMPORTANT: commit BOTH resources/locales/hrv/ AND resources/locales/hr/ - the runtime needs 'hr'.

## QUICK START FOR NEXT AGENT
1) Ensure Docker up: docker ps  (retry loop if engine starting).
2) Do task (A): append missing sidebar msgids to both .po, cp into container, clear cache, re-verify.
3) Test in browser /users/profile -> switch language to Croatian -> check sidebar.
4) Then (B) grow catalog / (C) hrv.json / (D) AppController case / (E) left-nav DB.
