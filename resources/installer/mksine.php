<?php

/**
 * MKSine quick installer.
 *
 * Upload this one file to the site's web root (public_html on shared hosting) and open
 * https://your-domain/mksine.php. It downloads the latest MKSine release from GitHub, checks
 * its SHA-256 checksum, extracts it in the right place and opens the MKSine installer.
 *
 * No code is asked for: the browser that clicks "Install" owns the run (a random secret in
 * a cookie, its hash in the state file). Another browser can take over only after the run
 * has been idle for MKSI_TAKEOVER_SECONDS.
 *
 * - Shared hosting: the application goes to ../mksine, the public files to the web root.
 * - Server: when the web root is a folder named "public" with nothing else beside it, the
 *   application goes to its parent folder.
 * - Hosts that block downloads: upload the release zip next to this file instead.
 *
 * Only PHP 7.0 syntax is used, so an old PHP version shows a readable message instead of a
 * parse error. MKSine itself needs the PHP version listed in release.json.
 */

const MKSI_BUILD = 'dev';

const MKSI_DEFAULT_MANIFEST_URL = 'https://github.com/MiranSalehi/mksine/releases/latest/download/release.json';

const MKSI_APP_DIRECTORY = 'mksine';

const MKSI_TOKEN_FILE = 'SETUP-TOKEN.txt';

const MKSI_TAKEOVER_SECONDS = 600;

const MKSI_STATE_FILE = '.mksine-installer.json';

const MKSI_LOCK_FILE = '.mksine-installer.lock';

const MKSI_ZIP_FILE = '.mksine-download.zip';

const MKSI_HANDOFF_FILE = 'storage/mksine/installer-handoff.json';

const MKSI_HANDOFF_URL = 'install/handoff';

const MKSI_INSTALL_URL = 'install';

/** Releases older than 1.23.0 have no /install/handoff route; their users enter the code again. */
const MKSI_HANDOFF_CLASS = 'vendor/miran/mksine/src/Core/Install/Web/InstallerHandoff.php';

/** Requirements used when release.json cannot be read (a zip uploaded by hand). */
const MKSI_FALLBACK_PHP = '8.2.0';

const MKSI_FALLBACK_EXTENSIONS = ['ctype', 'curl', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip'];

/** Placeholder pages that hosts put in a new web root; renamed so index.php is served. */
const MKSI_PLACEHOLDERS = ['index.html', 'index.htm', 'default.html', 'default.htm'];

/**
 * @return array<string, array<string, string>>
 */
function mksi_strings()
{
    return [
        'en' => [
            'title' => 'MKSine quick installer',
            'switch_language' => 'فارسی',
            'wrong_root_heading' => 'Upload this file to the site\'s root folder',
            'wrong_root' => 'This file is in :dir, but the site\'s root folder is :root. Move it there (usually public_html) and open it again.',
            'app_dir_failed' => 'Could not create the folder :path. The folder above the site\'s root must be writable: create the folder in your file manager, or ask your host for help.',
            'in_progress_heading' => 'Installation in progress',
            'in_progress' => 'MKSine is being installed from another browser. Keep using that browser.',
            'in_progress_takeover' => 'That browser has not continued for a while. You can continue the installation here.',
            'in_progress_wait' => 'If that browser was closed, you can continue here after :minutes minutes without activity.',
            'take_over' => 'Continue here',
            'check_heading' => 'Ready to install MKSine :version',
            'check_heading_unknown' => 'Ready to install MKSine',
            'check_lead' => 'This installer downloads MKSine from GitHub, checks it and puts the files in place. Then the MKSine setup opens for the database and the admin account.',
            'where_heading' => 'Where the files go',
            'app_folder' => 'Application',
            'public_folder' => 'Public files (site root)',
            'layout_split' => 'Shared hosting: the application is kept outside the site root.',
            'layout_single' => 'Server: the site root is the application\'s public folder.',
            'source_heading' => 'Release file',
            'source_remote' => ':name (:size), downloaded from GitHub',
            'source_local' => ':name (:size), uploaded by you',
            'source_unverified' => 'Its checksum cannot be checked against the latest release; use only a file downloaded from mksine.com or GitHub.',
            'requirements_heading' => 'Server requirements',
            'req_php' => 'PHP :version or newer',
            'req_extension' => 'PHP extension :name',
            'req_download' => 'Downloads from the internet (curl or allow_url_fopen)',
            'req_writable' => 'Writable folder :path',
            'req_disk' => 'Free disk space (:size needed)',
            'ok' => 'OK',
            'missing' => 'Missing',
            'recommended' => 'Recommended',
            'php_hint' => 'Change the PHP version in your host panel (for example cPanel → Select PHP Version), then check again.',
            'requirements_fail' => 'Fix the items marked "Missing", then check again. Your host\'s support can enable PHP extensions.',
            'placeholders' => 'These default pages in the site root will be renamed to *.mksine-old: :files',
            'app_not_empty' => 'The folder :path already contains files. Empty or remove it, then check again.',
            'public_has_index' => 'The site root already has an index.php, so another site may be installed here. Remove it if you want to install MKSine here.',
            'manifest_failed' => 'Could not read the latest release from GitHub: :error',
            'manual_hint' => 'If your host blocks downloads, download :name from mksine.com yourself, upload it next to this file and check again.',
            'no_layout_file' => 'The release has no file for this kind of hosting.',
            'check_again' => 'Check again',
            'install' => 'Download and install',
            'run_heading' => 'Installing MKSine :version',
            'run_lead' => 'Keep this page open; each step continues on its own.',
            'phase_download' => 'Downloading',
            'phase_verify' => 'Checking the file',
            'phase_prepare' => 'Preparing',
            'phase_extract' => 'Extracting files',
            'phase_finalize' => 'Finishing',
            'phase_done' => 'Opening the MKSine setup…',
            'files_count' => ':done of :total files',
            'checksum_failed' => 'The downloaded file is damaged (checksum mismatch). It was deleted; try again.',
            'download_failed' => 'Download failed: :error',
            'write_failed' => 'Could not write :path. Check the folder permissions.',
            'zip_failed' => 'The zip file could not be opened (:error). Start over to download it again.',
            'not_a_release' => 'This zip is not an MKSine release for this kind of hosting. Start over and use :name.',
            'unsafe_entry' => 'The zip contains an unsafe path: :name',
            'conflicts' => 'These files already exist and would be overwritten. Remove them and try again: :files',
            'retry' => 'Try again',
            'start_over' => 'Start over',
            'network' => 'The connection to the server was interrupted.',
            'session_lost' => 'This installation is now running in another browser. Reload the page.',
            'busy' => 'Another browser tab is running this installer.',
            'fallback' => 'If the setup does not open, go to :url.',
            'delete_self' => 'Delete :file from the site root.',
            'already_installed' => 'MKSine is already installed here. Delete :file from the site root.',
        ],
        'fa' => [
            'title' => 'نصب سریع MKSine',
            'switch_language' => 'English',
            'wrong_root_heading' => 'این فایل را در پوشهٔ اصلی سایت آپلود کنید',
            'wrong_root' => 'این فایل در :dir است، ولی پوشهٔ اصلی سایت :root است. آن را به همان پوشه (معمولاً public_html) منتقل کنید و دوباره باز کنید.',
            'app_dir_failed' => 'پوشهٔ :path ساخته نشد. پوشهٔ بالای ریشهٔ سایت باید قابل نوشتن باشد. این پوشه را خودتان در File Manager بسازید یا از پشتیبانی هاست کمک بگیرید.',
            'in_progress_heading' => 'نصب در حال انجام است',
            'in_progress' => 'MKSine در مرورگر دیگری در حال نصب است. نصب را در همان مرورگر ادامه دهید.',
            'in_progress_takeover' => 'آن مرورگر مدتی است کاری انجام نداده. می‌توانید نصب را اینجا ادامه دهید.',
            'in_progress_wait' => 'اگر آن مرورگر بسته شده، بعد از :minutes دقیقه بدون فعالیت می‌توانید نصب را اینجا ادامه دهید.',
            'take_over' => 'ادامه در اینجا',
            'check_heading' => 'آمادهٔ نصب MKSine نسخهٔ :version',
            'check_heading_unknown' => 'آمادهٔ نصب MKSine',
            'check_lead' => 'این نصب‌کننده MKSine را از GitHub دانلود و بررسی می‌کند و فایل‌ها را سر جایشان می‌گذارد. بعد، نصب‌کنندهٔ MKSine برای تنظیم دیتابیس و حساب مدیر باز می‌شود.',
            'where_heading' => 'محل فایل‌ها',
            'app_folder' => 'برنامه',
            'public_folder' => 'فایل‌های عمومی (ریشهٔ سایت)',
            'layout_split' => 'هاست اشتراکی: برنامه بیرون از ریشهٔ سایت نگه داشته می‌شود.',
            'layout_single' => 'سرور: ریشهٔ سایت همان پوشهٔ public برنامه است.',
            'source_heading' => 'فایل نسخه',
            'source_remote' => ':name (:size)، دانلود از GitHub',
            'source_local' => ':name (:size)، آپلودشده توسط شما',
            'source_unverified' => 'چک‌سام این فایل با آخرین نسخه قابل بررسی نیست؛ فقط از فایلی استفاده کنید که از mksine.com یا GitHub دانلود کرده‌اید.',
            'requirements_heading' => 'پیش‌نیازهای سرور',
            'req_php' => 'PHP نسخهٔ :version یا جدیدتر',
            'req_extension' => 'افزونهٔ :name در PHP',
            'req_download' => 'دانلود از اینترنت (curl یا allow_url_fopen)',
            'req_writable' => 'پوشهٔ قابل نوشتن :path',
            'req_disk' => 'فضای خالی دیسک (:size لازم است)',
            'ok' => 'دارد',
            'missing' => 'ندارد',
            'recommended' => 'پیشنهادی',
            'php_hint' => 'نسخهٔ PHP را از پنل هاست عوض کنید (مثلاً cPanel ← Select PHP Version) و دوباره بررسی کنید.',
            'requirements_fail' => 'مواردی را که «ندارد» خورده‌اند درست کنید و دوباره بررسی کنید. پشتیبانی هاست می‌تواند افزونه‌های PHP را فعال کند.',
            'placeholders' => 'این صفحه‌های پیش‌فرض در ریشهٔ سایت به ‎*.mksine-old تغییر نام می‌دهند: :files',
            'app_not_empty' => 'پوشهٔ :path از قبل فایل دارد. آن را خالی یا حذف کنید و دوباره بررسی کنید.',
            'public_has_index' => 'ریشهٔ سایت از قبل فایل index.php دارد و شاید سایت دیگری اینجا نصب باشد. اگر می‌خواهید MKSine اینجا نصب شود، آن را حذف کنید.',
            'manifest_failed' => 'اطلاعات آخرین نسخه از GitHub خوانده نشد: :error',
            'manual_hint' => 'اگر هاست شما اجازهٔ دانلود نمی‌دهد، فایل :name را خودتان از mksine.com دانلود کنید، کنار همین فایل آپلود کنید و دوباره بررسی کنید.',
            'no_layout_file' => 'این نسخه فایلی برای این نوع هاست ندارد.',
            'check_again' => 'بررسی دوباره',
            'install' => 'دانلود و نصب',
            'run_heading' => 'در حال نصب MKSine نسخهٔ :version',
            'run_lead' => 'این صفحه را باز نگه دارید؛ مراحل خودکار ادامه پیدا می‌کنند.',
            'phase_download' => 'در حال دانلود',
            'phase_verify' => 'بررسی فایل',
            'phase_prepare' => 'آماده‌سازی',
            'phase_extract' => 'استخراج فایل‌ها',
            'phase_finalize' => 'پایان کار',
            'phase_done' => 'در حال باز کردن نصب‌کنندهٔ MKSine…',
            'files_count' => ':done از :total فایل',
            'checksum_failed' => 'فایل دانلودشده سالم نیست (چک‌سام نمی‌خواند). حذف شد؛ دوباره تلاش کنید.',
            'download_failed' => 'دانلود انجام نشد: :error',
            'write_failed' => 'نوشتن :path ممکن نشد. دسترسی پوشه‌ها را بررسی کنید.',
            'zip_failed' => 'فایل zip باز نشد (:error). «شروع دوباره» را بزنید تا دوباره دانلود شود.',
            'not_a_release' => 'این zip نسخهٔ MKSine برای این نوع هاست نیست. «شروع دوباره» را بزنید و از :name استفاده کنید.',
            'unsafe_entry' => 'فایل zip مسیر ناامنی دارد: :name',
            'conflicts' => 'این فایل‌ها از قبل وجود دارند و بازنویسی می‌شوند. آن‌ها را حذف کنید و دوباره تلاش کنید: :files',
            'retry' => 'تلاش دوباره',
            'start_over' => 'شروع دوباره',
            'network' => 'ارتباط با سرور قطع شد.',
            'session_lost' => 'این نصب حالا در مرورگر دیگری ادامه پیدا کرده است. صفحه را دوباره باز کنید.',
            'busy' => 'این نصب‌کننده در زبانهٔ دیگری از مرورگر در حال اجراست.',
            'fallback' => 'اگر نصب‌کننده باز نشد، به :url بروید.',
            'delete_self' => 'فایل :file را از ریشهٔ سایت حذف کنید.',
            'already_installed' => 'MKSine از قبل اینجا نصب شده است. فایل :file را از ریشهٔ سایت حذف کنید.',
        ],
    ];
}

/* ------------------------------------------------------------------------------------
 | Helpers
 * ------------------------------------------------------------------------------------ */

function mksi_t($key, $replace = [])
{
    $strings = mksi_strings();
    $lang = mksi_lang();
    $text = isset($strings[$lang][$key]) ? $strings[$lang][$key] : (isset($strings['en'][$key]) ? $strings['en'][$key] : $key);

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

function mksi_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mksi_lang()
{
    static $lang = null;

    if ($lang !== null) {
        return $lang;
    }

    if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa', 'en'], true)) {
        $lang = $_GET['lang'];
    } elseif (isset($_COOKIE['mksi_lang']) && in_array($_COOKIE['mksi_lang'], ['fa', 'en'], true)) {
        $lang = $_COOKIE['mksi_lang'];
    } else {
        $accept = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtolower((string) $_SERVER['HTTP_ACCEPT_LANGUAGE']) : '';
        $lang = strpos($accept, 'fa') === 0 ? 'fa' : 'en';
    }

    return $lang;
}

function mksi_format_size($bytes)
{
    return number_format($bytes / 1048576, 1) . ' MB';
}

function mksi_is_https()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

function mksi_set_cookie($name, $value, $maxAge, $sameSite)
{
    header('Set-Cookie: ' . $name . '=' . rawurlencode($value) . '; Path=/; Max-Age=' . (int) $maxAge
        . '; HttpOnly; SameSite=' . $sameSite . (mksi_is_https() ? '; Secure' : ''), false);
}

function mksi_join($directory, $relative)
{
    return rtrim($directory, '/\\') . '/' . ltrim($relative, '/');
}

function mksi_read_json($path)
{
    if (! is_file($path)) {
        return null;
    }

    $data = json_decode((string) @file_get_contents($path), true);

    return is_array($data) ? $data : null;
}

function mksi_write_json($path, $data)
{
    return @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function mksi_script_name()
{
    return basename(__FILE__);
}

/* ------------------------------------------------------------------------------------
 | Where the files go
 * ------------------------------------------------------------------------------------ */

/**
 * Files this installer itself writes; they do not make a folder "not empty".
 */
function mksi_own_files()
{
    return [MKSI_TOKEN_FILE, MKSI_STATE_FILE, MKSI_LOCK_FILE, MKSI_ZIP_FILE];
}

function mksi_dir_is_free($directory, $ignore = [])
{
    if (! is_dir($directory)) {
        return true;
    }

    $entries = @scandir($directory);

    if ($entries === false) {
        return false;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $ignore, true) || in_array($entry, mksi_own_files(), true)) {
            continue;
        }

        return false;
    }

    return true;
}

/**
 * @return array{layout: string, app: string, public: string}
 */
function mksi_target_for($layout, $docroot)
{
    $docroot = rtrim($docroot, '/\\');

    return $layout === 'single'
        ? ['layout' => 'single', 'app' => dirname($docroot), 'public' => $docroot]
        : ['layout' => 'split', 'app' => mksi_join(dirname($docroot), MKSI_APP_DIRECTORY), 'public' => $docroot];
}

/**
 * The layout chosen on the first visit is kept in the state file; later visits must not
 * change it once files start to appear next to the web root.
 *
 * @return array{layout: string, app: string, public: string}
 */
function mksi_target($docroot)
{
    $docroot = rtrim($docroot, '/\\');
    $parent = dirname($docroot);

    foreach ([$parent, mksi_join($parent, MKSI_APP_DIRECTORY)] as $candidate) {
        $state = mksi_read_json(mksi_join($candidate, MKSI_STATE_FILE));

        if (is_array($state) && isset($state['layout']) && in_array($state['layout'], ['split', 'single'], true)) {
            return mksi_target_for($state['layout'], $docroot);
        }
    }

    $single = basename($docroot) === 'public' && mksi_dir_is_free($parent, [basename($docroot)]);

    return mksi_target_for($single ? 'single' : 'split', $docroot);
}

function mksi_layout_suffix($layout)
{
    return $layout === 'single' ? 'server' : 'shared-hosting';
}

/**
 * The zip entry name of the application root and the public folder for a layout.
 *
 * @return array{app: string, public: string}
 */
function mksi_zip_prefixes($layout)
{
    return $layout === 'single'
        ? ['app' => '', 'public' => 'public/']
        : ['app' => MKSI_APP_DIRECTORY . '/', 'public' => 'public_html/'];
}

function mksi_is_safe_entry($name)
{
    if (! is_string($name) || $name === '' || strpos($name, "\0") !== false || strpos($name, '\\') !== false) {
        return false;
    }

    if ($name[0] === '/' || preg_match('#^[A-Za-z]:#', $name) === 1) {
        return false;
    }

    foreach (explode('/', $name) as $segment) {
        if ($segment === '..') {
            return false;
        }
    }

    return true;
}

/**
 * Absolute path for a zip entry, or null when the entry is not part of this layout.
 */
function mksi_map_entry($name, $target)
{
    $prefixes = mksi_zip_prefixes($target['layout']);

    if ($target['layout'] === 'single') {
        return mksi_join($target['app'], $name);
    }

    if (strpos($name, $prefixes['public']) === 0) {
        return mksi_join($target['public'], substr($name, strlen($prefixes['public'])));
    }

    if (strpos($name, $prefixes['app']) === 0) {
        return mksi_join($target['app'], substr($name, strlen($prefixes['app'])));
    }

    return null;
}

/**
 * Entries written last: index.php switches the site over to MKSine, .htaccess is merged.
 */
function mksi_deferred_entries($layout)
{
    $prefixes = mksi_zip_prefixes($layout);

    return [$prefixes['public'] . 'index.php', $prefixes['public'] . '.htaccess'];
}

/**
 * The new .htaccess, keeping the host's own rules (cPanel puts the PHP version there).
 */
function mksi_merge_htaccess($new, $existing)
{
    $existing = (string) $existing;

    if (trim($existing) === '' || strpos($existing, trim($new)) !== false) {
        return $new;
    }

    return rtrim($new) . "\n\n# --- Kept from the previous .htaccess by the MKSine installer ---\n" . ltrim($existing);
}

function mksi_patch_public_path($environment, $publicDirectoryName)
{
    $line = 'MKSINE_PUBLIC_PATH=../' . $publicDirectoryName;

    if (preg_match('/^MKSINE_PUBLIC_PATH=.*$/m', $environment) === 1) {
        return (string) preg_replace('/^MKSINE_PUBLIC_PATH=.*$/m', $line, $environment);
    }

    return rtrim($environment) . "\n" . $line . "\n";
}

/**
 * Turn ".../releases/latest/download/release.json" into the versioned download URL, so a
 * release published during the download cannot swap the file under the checksum.
 */
function mksi_file_url($manifestUrl, $version, $fileName)
{
    $marker = '/releases/latest/download/';
    $position = strpos($manifestUrl, $marker);

    if ($position !== false) {
        return substr($manifestUrl, 0, $position) . '/releases/download/v' . rawurlencode($version) . '/' . rawurlencode($fileName);
    }

    return substr($manifestUrl, 0, (int) strrpos($manifestUrl, '/') + 1) . rawurlencode($fileName);
}

function mksi_manifest_url()
{
    $override = getenv('MKSINE_INSTALLER_MANIFEST_URL');

    return is_string($override) && $override !== '' ? $override : MKSI_DEFAULT_MANIFEST_URL;
}

/* ------------------------------------------------------------------------------------
 | Who runs the install: the browser that started it
 * ------------------------------------------------------------------------------------ */

function mksi_is_owner($state)
{
    return is_array($state) && isset($state['owner_hash'], $_COOKIE['mksi_owner']) && is_string($_COOKIE['mksi_owner'])
        && hash_equals((string) $state['owner_hash'], hash('sha256', $_COOKIE['mksi_owner']));
}

/**
 * Make this browser the owner: a random secret in a cookie, only its hash on disk.
 *
 * @param  array<string, mixed>  $state
 */
function mksi_claim(&$state)
{
    $secret = bin2hex(random_bytes(32));
    $state['owner_hash'] = hash('sha256', $secret);
    $_COOKIE['mksi_owner'] = $secret;
    mksi_set_cookie('mksi_owner', $secret, 86400, 'Strict');
}

function mksi_idle_seconds($target)
{
    clearstatcache(true, mksi_state_path($target));
    $modified = @filemtime(mksi_state_path($target));

    return $modified === false ? PHP_INT_MAX : time() - $modified;
}

/* ------------------------------------------------------------------------------------
 | HTTP
 * ------------------------------------------------------------------------------------ */

function mksi_can_download()
{
    return function_exists('curl_init') || filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN);
}

/**
 * @return array{status: int, body: string, error: string|null}
 */
function mksi_http_get($url, $timeout = 20)
{
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'MKSine-Installer/' . MKSI_BUILD,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = $body === false ? curl_error($handle) : null;
        curl_close($handle);

        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'error' => $error];
    }

    $context = stream_context_create(['http' => [
        'follow_location' => 1,
        'max_redirects' => 5,
        'timeout' => $timeout,
        'user_agent' => 'MKSine-Installer/' . MKSI_BUILD,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $context);
    $status = mksi_last_status(isset($http_response_header) ? $http_response_header : []);
    $error = $body === false ? (error_get_last()['message'] ?? 'request failed') : null;

    return ['status' => $status, 'body' => is_string($body) ? $body : '', 'error' => $error];
}

function mksi_last_status($headers)
{
    $status = 0;

    foreach ((array) $headers as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $header, $match) === 1) {
            $status = (int) $match[1];
        }
    }

    return $status;
}

/**
 * @return array{manifest: array<string, mixed>|null, error: string|null}
 */
function mksi_fetch_manifest()
{
    $response = mksi_http_get(mksi_manifest_url());

    if ($response['error'] !== null) {
        return ['manifest' => null, 'error' => $response['error']];
    }

    if ($response['status'] !== 200) {
        return ['manifest' => null, 'error' => 'HTTP ' . $response['status']];
    }

    $manifest = json_decode($response['body'], true);

    if (! is_array($manifest) || ! isset($manifest['version'], $manifest['files']) || ! is_array($manifest['files'])) {
        return ['manifest' => null, 'error' => 'release.json is not valid'];
    }

    return ['manifest' => $manifest, 'error' => null];
}

/**
 * @return array<string, mixed>|null
 */
function mksi_manifest_file($manifest, $layout)
{
    foreach ((array) $manifest['files'] as $file) {
        if (is_array($file) && isset($file['layout'], $file['name'], $file['size'], $file['sha256']) && $file['layout'] === $layout) {
            return $file;
        }
    }

    return null;
}

/**
 * Download the next part of a file until the deadline. Resumes from the bytes already on
 * disk with an HTTP Range request.
 *
 * @return string|null An error message, or null when the download went on (or finished).
 */
function mksi_download_chunk($url, $path, $expectedSize, $deadline)
{
    clearstatcache(true, $path);
    $offset = is_file($path) ? (int) filesize($path) : 0;

    if ($offset > $expectedSize) {
        @unlink($path);
        $offset = 0;
    }

    if ($offset === $expectedSize) {
        return null;
    }

    $file = @fopen($path, 'ab');

    if ($file === false) {
        return mksi_t('write_failed', ['path' => $path]);
    }

    $seconds = max(3, (int) floor($deadline - microtime(true)));
    $error = null;

    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        $response = ['checked' => false, 'status' => 0];
        curl_setopt_array($handle, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => min(15, $seconds),
            CURLOPT_TIMEOUT => $seconds,
            CURLOPT_USERAGENT => 'MKSine-Installer/' . MKSI_BUILD,
            CURLOPT_WRITEFUNCTION => function ($handle, $data) use ($file, $offset, &$response) {
                if (! $response['checked']) {
                    $response['checked'] = true;
                    $response['status'] = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

                    if ($response['status'] === 200 && $offset > 0) {
                        ftruncate($file, 0);
                    } elseif ($response['status'] !== 200 && $response['status'] !== 206) {
                        return 0;
                    }
                }

                return fwrite($file, $data) === false ? 0 : strlen($data);
            },
        ]);

        if ($offset > 0) {
            curl_setopt($handle, CURLOPT_RANGE, $offset . '-');
        }

        curl_exec($handle);
        $errorNumber = curl_errno($handle);
        $status = $response['status'] !== 0 ? $response['status'] : (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($status !== 0 && $status !== 200 && $status !== 206) {
            $error = 'HTTP ' . $status;
        } elseif ($errorNumber !== 0 && $errorNumber !== 28) {
            // 28 is the time limit of this request; the next request carries on.
            $error = curl_error($handle);
        }

        curl_close($handle);
    } else {
        $context = stream_context_create(['http' => [
            'header' => $offset > 0 ? 'Range: bytes=' . $offset . "-\r\n" : '',
            'follow_location' => 1,
            'max_redirects' => 5,
            'timeout' => $seconds,
            'user_agent' => 'MKSine-Installer/' . MKSI_BUILD,
            'ignore_errors' => true,
        ]]);
        $stream = @fopen($url, 'rb', false, $context);

        if ($stream === false) {
            $error = error_get_last()['message'] ?? 'request failed';
        } else {
            $status = mksi_last_status(isset($http_response_header) ? $http_response_header : []);

            if ($status === 200 && $offset > 0) {
                ftruncate($file, 0);
            }

            if ($status !== 200 && $status !== 206) {
                $error = 'HTTP ' . $status;
            } else {
                stream_set_timeout($stream, $seconds);

                while (! feof($stream) && microtime(true) < $deadline) {
                    $buffer = fread($stream, 262144);

                    if ($buffer === false || ($buffer === '' && stream_get_meta_data($stream)['timed_out'])) {
                        break;
                    }

                    if (fwrite($file, $buffer) === false) {
                        $error = mksi_t('write_failed', ['path' => $path]);

                        break;
                    }
                }
            }

            fclose($stream);
        }
    }

    fclose($file);
    clearstatcache(true, $path);

    return $error === null ? null : mksi_t('download_failed', ['error' => $error]);
}

/* ------------------------------------------------------------------------------------
 | Checks before the download
 * ------------------------------------------------------------------------------------ */

/**
 * A release zip the user uploaded by hand, next to this file or in the application folder.
 *
 * @return array{path: string, version: string}|null
 */
function mksi_find_local_zip($target)
{
    $suffix = mksi_layout_suffix($target['layout']);
    $best = null;

    foreach ([$target['public'], $target['app']] as $directory) {
        foreach ((array) glob(mksi_join($directory, 'mksine-*-' . $suffix . '.zip')) as $path) {
            if (preg_match('/^mksine-(.+)-' . preg_quote($suffix, '/') . '\.zip$/', basename($path), $match) !== 1) {
                continue;
            }

            if ($best === null || version_compare($match[1], $best['version'], '>')) {
                $best = ['path' => $path, 'version' => $match[1]];
            }
        }
    }

    return $best;
}

/**
 * @return array{source: array<string, mixed>|null, manifest_error: string|null, checks: list<array<string, mixed>>, errors: list<string>, notices: list<string>, passes: bool}
 */
function mksi_preflight($target)
{
    $local = mksi_find_local_zip($target);
    $fetched = mksi_fetch_manifest();
    $manifest = $fetched['manifest'];
    $source = null;
    $errors = [];
    $notices = [];

    if ($local !== null) {
        $remote = $manifest !== null ? mksi_manifest_file($manifest, $target['layout']) : null;
        $verified = $remote !== null && (string) $manifest['version'] === $local['version'] && $remote['name'] === basename($local['path']);
        $source = [
            'type' => 'local',
            'path' => $local['path'],
            'name' => basename($local['path']),
            'version' => $local['version'],
            'size' => (int) filesize($local['path']),
            'sha256' => $verified ? (string) $remote['sha256'] : null,
        ];

        if (! $verified) {
            $notices[] = mksi_t('source_unverified');
        }
    } elseif ($manifest !== null) {
        $file = mksi_manifest_file($manifest, $target['layout']);

        if ($file === null) {
            $errors[] = mksi_t('no_layout_file');
        } else {
            $source = [
                'type' => 'remote',
                'url' => mksi_file_url(mksi_manifest_url(), (string) $manifest['version'], (string) $file['name']),
                'name' => (string) $file['name'],
                'version' => (string) $manifest['version'],
                'size' => (int) $file['size'],
                'sha256' => (string) $file['sha256'],
            ];
        }
    }

    $php = MKSI_FALLBACK_PHP;
    $extensions = MKSI_FALLBACK_EXTENSIONS;
    $recommended = [];

    if ($manifest !== null && isset($manifest['requirements']) && is_array($manifest['requirements'])) {
        $requirements = $manifest['requirements'];
        $php = isset($requirements['php']) ? ltrim((string) $requirements['php'], '>=~^ ') : $php;
        $extensions = isset($requirements['extensions']) ? (array) $requirements['extensions'] : $extensions;
        $recommended = isset($requirements['recommended_extensions']) ? (array) $requirements['recommended_extensions'] : [];
    }

    $checks = [];
    $checks[] = ['label' => mksi_t('req_php', ['version' => $php]), 'passed' => version_compare(PHP_VERSION, $php, '>='), 'current' => PHP_VERSION, 'hint' => mksi_t('php_hint')];

    foreach ($extensions as $extension) {
        $checks[] = ['label' => mksi_t('req_extension', ['name' => $extension]), 'passed' => extension_loaded((string) $extension)];
    }

    foreach ($recommended as $extension) {
        $checks[] = ['label' => mksi_t('req_extension', ['name' => $extension]), 'passed' => extension_loaded((string) $extension), 'optional' => true];
    }

    if ($local === null) {
        $checks[] = ['label' => mksi_t('req_download'), 'passed' => mksi_can_download()];
    }

    $checks[] = ['label' => mksi_t('req_writable', ['path' => $target['app']]), 'passed' => is_dir($target['app']) ? is_writable($target['app']) : is_writable(dirname($target['app']))];
    $checks[] = ['label' => mksi_t('req_writable', ['path' => $target['public']]), 'passed' => is_writable($target['public'])];

    if ($source !== null) {
        $free = function_exists('disk_free_space') ? @disk_free_space($target['app']) : false;
        $needed = $source['size'] * 4;

        if ($free !== false) {
            $checks[] = ['label' => mksi_t('req_disk', ['size' => mksi_format_size($needed)]), 'passed' => $free >= $needed, 'current' => mksi_format_size($free)];
        }
    }

    $appIgnore = $target['layout'] === 'single' ? [basename($target['public'])] : [];

    if (! mksi_dir_is_free($target['app'], $appIgnore)) {
        $errors[] = mksi_t('app_not_empty', ['path' => $target['app']]);
    }

    if (is_file(mksi_join($target['public'], 'index.php'))) {
        $errors[] = mksi_t('public_has_index');
    }

    $placeholders = mksi_placeholders($target['public']);

    if ($placeholders !== []) {
        $notices[] = mksi_t('placeholders', ['files' => implode(', ', $placeholders)]);
    }

    $passes = $source !== null && $errors === [];

    foreach ($checks as $check) {
        if (! $check['passed'] && empty($check['optional'])) {
            $passes = false;
        }
    }

    return [
        'source' => $source,
        'manifest_error' => $local === null ? $fetched['error'] : null,
        'checks' => $checks,
        'errors' => $errors,
        'notices' => $notices,
        'passes' => $passes,
    ];
}

function mksi_placeholders($public)
{
    $found = [];

    foreach (MKSI_PLACEHOLDERS as $name) {
        if (is_file(mksi_join($public, $name))) {
            $found[] = $name;
        }
    }

    return $found;
}

/* ------------------------------------------------------------------------------------
 | The install run: one bounded piece of work per request
 * ------------------------------------------------------------------------------------ */

function mksi_time_budget()
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(60);
    }

    $limit = (int) ini_get('max_execution_time');

    return $limit <= 0 ? 15 : max(5, min(15, $limit - 10));
}

function mksi_state_path($target)
{
    return mksi_join($target['app'], MKSI_STATE_FILE);
}

function mksi_zip_path($target)
{
    return mksi_join($target['app'], MKSI_ZIP_FILE);
}

/**
 * Advance the install until the deadline.
 *
 * @param  array<string, mixed>  $state
 * @return string|null An error message, or null.
 */
function mksi_advance(&$state, $target, $deadline)
{
    while (microtime(true) < $deadline) {
        switch ($state['phase']) {
            case 'download':
                $error = mksi_download_chunk($state['source']['url'], mksi_zip_path($target), (int) $state['source']['size'], $deadline);

                if ($error !== null) {
                    return $error;
                }

                clearstatcache(true, mksi_zip_path($target));

                if (is_file(mksi_zip_path($target)) && filesize(mksi_zip_path($target)) === (int) $state['source']['size']) {
                    $state['phase'] = 'verify';
                } else {
                    return null;
                }

                break;

            case 'verify':
                if ($state['source']['sha256'] !== null && ! hash_equals((string) $state['source']['sha256'], (string) hash_file('sha256', mksi_zip_path($target)))) {
                    @unlink(mksi_zip_path($target));
                    $state['phase'] = $state['source']['type'] === 'remote' ? 'download' : 'new';

                    return mksi_t('checksum_failed');
                }

                $state['phase'] = 'prepare';

                break;

            case 'prepare':
                $error = mksi_prepare($state, $target);

                if ($error !== null) {
                    return $error;
                }

                $state['phase'] = 'extract';

                break;

            case 'extract':
                $error = mksi_extract($state, $target, $deadline);

                if ($error !== null || $state['cursor'] < $state['total']) {
                    return $error;
                }

                $state['phase'] = 'finalize';

                break;

            case 'finalize':
                return mksi_finalize($state, $target);

            default:
                return null;
        }

        mksi_write_json(mksi_state_path($target), $state);
    }

    return null;
}

function mksi_open_zip($target, &$error)
{
    if (! class_exists('ZipArchive')) {
        $error = mksi_t('zip_failed', ['error' => 'ZipArchive']);

        return null;
    }

    $zip = new ZipArchive;
    $result = $zip->open(mksi_zip_path($target));

    if ($result !== true) {
        $error = mksi_t('zip_failed', ['error' => 'code ' . $result]);

        return null;
    }

    return $zip;
}

/**
 * Check every entry before anything is written: safe paths, the right layout, and no
 * existing file that would be overwritten. Then rename the host's placeholder pages.
 */
function mksi_prepare(&$state, $target)
{
    $error = null;
    $zip = mksi_open_zip($target, $error);

    if ($zip === null) {
        return $error;
    }

    $prefixes = mksi_zip_prefixes($target['layout']);
    $deferred = mksi_deferred_entries($target['layout']);
    $conflicts = [];
    $hasArtisan = false;

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = (string) $zip->getNameIndex($index);

        if (! mksi_is_safe_entry($name)) {
            $zip->close();

            return mksi_t('unsafe_entry', ['name' => $name]);
        }

        if ($name === $prefixes['app'] . 'artisan') {
            $hasArtisan = true;
        }

        $path = mksi_map_entry($name, $target);

        if ($path === null || substr($name, -1) === '/' || $name === $deferred[1]) {
            continue;
        }

        if (file_exists($path)) {
            $conflicts[] = $name;
        }
    }

    $total = $zip->numFiles;
    $zip->close();

    if (! $hasArtisan) {
        return mksi_t('not_a_release', ['name' => 'mksine-…-' . mksi_layout_suffix($target['layout']) . '.zip']);
    }

    if ($conflicts !== []) {
        return mksi_t('conflicts', ['files' => implode(', ', array_slice($conflicts, 0, 10)) . (count($conflicts) > 10 ? ' …' : '')]);
    }

    $state['renamed'] = [];

    foreach (mksi_placeholders($target['public']) as $placeholder) {
        $from = mksi_join($target['public'], $placeholder);

        if (@rename($from, $from . '.mksine-old')) {
            $state['renamed'][] = $placeholder;
        }
    }

    $state['cursor'] = 0;
    $state['total'] = $total;

    return null;
}

function mksi_extract(&$state, $target, $deadline)
{
    $error = null;
    $zip = mksi_open_zip($target, $error);

    if ($zip === null) {
        return $error;
    }

    $deferred = mksi_deferred_entries($target['layout']);
    $savedAt = microtime(true);

    for ($index = (int) $state['cursor']; $index < $zip->numFiles; $index++) {
        if (microtime(true) >= $deadline) {
            break;
        }

        $name = (string) $zip->getNameIndex($index);
        $path = mksi_is_safe_entry($name) ? mksi_map_entry($name, $target) : null;

        if ($path !== null && ! in_array($name, $deferred, true)) {
            $error = mksi_extract_entry($zip, $name, $path);

            if ($error !== null) {
                break;
            }
        }

        $state['cursor'] = $index + 1;

        if (microtime(true) - $savedAt > 2) {
            mksi_write_json(mksi_state_path($target), $state);
            $savedAt = microtime(true);
        }
    }

    $zip->close();

    return $error;
}

function mksi_extract_entry($zip, $name, $path)
{
    if (substr($name, -1) === '/') {
        return is_dir($path) || @mkdir($path, 0755, true) ? null : mksi_t('write_failed', ['path' => $path]);
    }

    $directory = dirname($path);

    if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
        return mksi_t('write_failed', ['path' => $directory]);
    }

    $input = $zip->getStream($name);
    $output = @fopen($path, 'wb');

    if ($input === false || $output === false) {
        if (is_resource($input)) {
            fclose($input);
        }

        return mksi_t('write_failed', ['path' => $path]);
    }

    $copied = stream_copy_to_stream($input, $output);
    fclose($input);
    fclose($output);

    return $copied === false ? mksi_t('write_failed', ['path' => $path]) : null;
}

/**
 * Write index.php and the merged .htaccess, point MKSINE_PUBLIC_PATH at the real web root,
 * leave a one-time handoff code for /install and clean up, this file included.
 */
function mksi_finalize(&$state, $target)
{
    $error = null;
    $zip = mksi_open_zip($target, $error);

    if ($zip === null) {
        return $error;
    }

    $deferred = mksi_deferred_entries($target['layout']);
    $index = $zip->getFromName($deferred[0]);
    $htaccess = $zip->getFromName($deferred[1]);
    $zip->close();

    if ($htaccess !== false) {
        $path = mksi_join($target['public'], '.htaccess');
        $existing = is_file($path) ? (string) @file_get_contents($path) : '';

        if (@file_put_contents($path, mksi_merge_htaccess($htaccess, $existing)) === false) {
            return mksi_t('write_failed', ['path' => $path]);
        }
    }

    if ($target['layout'] === 'split') {
        $environment = mksi_join($target['app'], '.env.example');

        if (is_file($environment)) {
            @file_put_contents($environment, mksi_patch_public_path((string) file_get_contents($environment), basename($target['public'])));
        }
    }

    $handoff = null;

    if (is_file(mksi_join($target['app'], MKSI_HANDOFF_CLASS))) {
        $handoff = bin2hex(random_bytes(32));
        $handoffPath = mksi_join($target['app'], MKSI_HANDOFF_FILE);

        if (! is_dir(dirname($handoffPath))) {
            @mkdir(dirname($handoffPath), 0755, true);
        }

        if (! mksi_write_json($handoffPath, ['token_hash' => hash('sha256', $handoff), 'expires_at' => time() + 1800])) {
            $handoff = null;
        }
    }

    if ($index !== false && @file_put_contents(mksi_join($target['public'], 'index.php'), $index) === false) {
        return mksi_t('write_failed', ['path' => mksi_join($target['public'], 'index.php')]);
    }

    @unlink(mksi_zip_path($target));
    @unlink(mksi_join($target['app'], MKSI_LOCK_FILE));
    @unlink(mksi_state_path($target));
    $state['phase'] = 'done';
    $state['handoff'] = $handoff;
    $state['self_deleted'] = @unlink(__FILE__);

    return null;
}

/**
 * @return array{percent: int, label: string, detail: string}
 */
function mksi_progress($state, $target)
{
    $phase = (string) $state['phase'];
    $size = max(1, (int) $state['source']['size']);

    switch ($phase) {
        case 'download':
            clearstatcache(true, mksi_zip_path($target));
            $bytes = is_file(mksi_zip_path($target)) ? (int) filesize(mksi_zip_path($target)) : 0;

            return ['percent' => (int) floor(min(1, $bytes / $size) * 60), 'label' => mksi_t('phase_download'), 'detail' => mksi_format_size($bytes) . ' / ' . mksi_format_size($size)];

        case 'new':
            return ['percent' => 0, 'label' => mksi_t('phase_verify'), 'detail' => ''];

        case 'verify':
            return ['percent' => 61, 'label' => mksi_t('phase_verify'), 'detail' => ''];

        case 'prepare':
            return ['percent' => 62, 'label' => mksi_t('phase_prepare'), 'detail' => ''];

        case 'extract':
            $total = max(1, (int) $state['total']);

            return ['percent' => 63 + (int) floor(((int) $state['cursor'] / $total) * 35), 'label' => mksi_t('phase_extract'), 'detail' => mksi_t('files_count', ['done' => (int) $state['cursor'], 'total' => $total])];

        case 'finalize':
            return ['percent' => 99, 'label' => mksi_t('phase_finalize'), 'detail' => ''];

        default:
            return ['percent' => 100, 'label' => mksi_t('phase_done'), 'detail' => ''];
    }
}

/* ------------------------------------------------------------------------------------
 | Requests
 * ------------------------------------------------------------------------------------ */

function mksi_json($data)
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function mksi_redirect()
{
    header('Location: ' . mksi_script_name(), true, 303);
}

function mksi_handle_step($target)
{
    $lock = @fopen(mksi_join($target['app'], MKSI_LOCK_FILE), 'c');

    if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
        mksi_json(['busy' => true, 'message' => mksi_t('busy')]);

        return;
    }

    $state = mksi_read_json(mksi_state_path($target));

    if (! is_array($state) || ! isset($state['source']) || ! in_array($state['phase'], ['download', 'verify', 'prepare', 'extract', 'finalize'], true)) {
        flock($lock, LOCK_UN);
        fclose($lock);
        mksi_json(['reload' => true]);

        return;
    }

    $error = mksi_advance($state, $target, microtime(true) + mksi_time_budget());

    if ($state['phase'] === 'done') {
        flock($lock, LOCK_UN);
        fclose($lock);

        mksi_json([
            'done' => true,
            'percent' => 100,
            'label' => mksi_t('phase_done'),
            'detail' => $state['self_deleted'] ? '' : mksi_t('delete_self', ['file' => mksi_script_name()]),
            'handoff' => $state['handoff'],
            'handoff_url' => $state['handoff'] !== null ? MKSI_HANDOFF_URL : null,
            'install_url' => MKSI_INSTALL_URL,
            'locale' => mksi_lang(),
        ]);

        return;
    }

    $state['error'] = $error;

    if ($state['phase'] === 'new') {
        // An uploaded zip failed its checksum and was deleted: back to the check page.
        @unlink(mksi_state_path($target));
    } else {
        mksi_write_json(mksi_state_path($target), $state);
    }

    flock($lock, LOCK_UN);
    fclose($lock);

    $progress = mksi_progress($state, $target);
    mksi_json($progress + ['error' => $error]);
}

function mksi_main()
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');

    if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa', 'en'], true)) {
        mksi_set_cookie('mksi_lang', $_GET['lang'], 86400 * 30, 'Lax');
        mksi_redirect();

        return;
    }

    $docroot = (string) realpath(__DIR__);
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;

    if ($documentRoot !== false && $documentRoot !== '' && rtrim($documentRoot, '/\\') !== rtrim($docroot, '/\\')) {
        mksi_page(mksi_t('wrong_root_heading'), '<div class="alert error">' . mksi_e(mksi_t('wrong_root', ['dir' => $docroot, 'root' => $documentRoot])) . '</div>');

        return;
    }

    $target = mksi_target($docroot);

    if (is_file(mksi_join($target['app'], 'storage/mksine/installed.json'))) {
        mksi_page(mksi_t('title'), '<div class="alert warn">' . mksi_e(mksi_t('already_installed', ['file' => mksi_script_name()])) . '</div>');

        return;
    }

    $statePath = mksi_state_path($target);
    $state = mksi_read_json($statePath);
    $running = is_array($state) && isset($state['source']);
    $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
    $isPost = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost && $action === 'start' && ! $running) {
        $preflight = mksi_preflight($target);

        if (! $preflight['passes']) {
            mksi_redirect();

            return;
        }

        if (! is_dir($target['app']) && ! @mkdir($target['app'], 0755, true)) {
            mksi_check_page($target, $preflight, mksi_t('app_dir_failed', ['path' => $target['app']]));

            return;
        }

        $source = $preflight['source'];

        if ($source['type'] === 'local') {
            if (! @rename($source['path'], mksi_zip_path($target)) && ! @copy($source['path'], mksi_zip_path($target))) {
                mksi_check_page($target, $preflight, mksi_t('write_failed', ['path' => mksi_zip_path($target)]));

                return;
            }

            unset($source['path']);
        }

        $state = ['layout' => $target['layout'], 'phase' => $source['type'] === 'local' ? 'verify' : 'download', 'source' => $source, 'cursor' => 0, 'total' => 0, 'error' => null];
        mksi_claim($state);
        mksi_write_json($statePath, $state);
        mksi_redirect();

        return;
    }

    if ($isPost && $action === 'takeover' && $running && ! mksi_is_owner($state) && mksi_idle_seconds($target) >= MKSI_TAKEOVER_SECONDS) {
        mksi_claim($state);
        mksi_write_json($statePath, $state);
        mksi_redirect();

        return;
    }

    if ($isPost && $action === 'reset' && $running && mksi_is_owner($state)) {
        @unlink(mksi_zip_path($target));
        @unlink($statePath);
        mksi_redirect();

        return;
    }

    if ($isPost && $action === 'step') {
        // The custom header keeps other sites from driving the install with a plain form post.
        if (! $running || ! mksi_is_owner($state) || ! isset($_SERVER['HTTP_X_MKSINE_INSTALLER'])) {
            mksi_json(['error' => mksi_t('session_lost'), 'reload' => true]);

            return;
        }

        mksi_handle_step($target);

        return;
    }

    if ($isPost) {
        mksi_redirect();

        return;
    }

    if (! $running) {
        mksi_check_page($target, mksi_preflight($target), null);

        return;
    }

    if (! mksi_is_owner($state)) {
        mksi_busy_page($target);

        return;
    }

    mksi_run_page($target, $state);
}

/* ------------------------------------------------------------------------------------
 | Pages
 * ------------------------------------------------------------------------------------ */

function mksi_page($heading, $body, $script = '')
{
    $rtl = mksi_lang() === 'fa';
    $other = $rtl ? 'en' : 'fa';

    echo '<!DOCTYPE html><html lang="' . mksi_lang() . '" dir="' . ($rtl ? 'rtl' : 'ltr') . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">'
        . '<title>' . mksi_e(mksi_t('title')) . '</title><style>'
        . ':root{--bg:#f5f6f8;--card:#fff;--text:#1f2430;--muted:#6b7280;--border:#e3e6eb;--primary:#4f46e5;--primary-text:#fff;--ok:#15803d;--warn:#b45309;--error:#b91c1c}'
        . '@media (prefers-color-scheme:dark){:root{--bg:#111318;--card:#1a1d24;--text:#e6e8ec;--muted:#9aa1ad;--border:#2a2e38;--primary:#818cf8;--primary-text:#0b0d12;--ok:#4ade80;--warn:#fbbf24;--error:#f87171}}'
        . '*{box-sizing:border-box}[hidden]{display:none!important}'
        . 'body{margin:0;background:var(--bg);color:var(--text);font:15px/1.6 ' . ($rtl ? 'Tahoma,"Vazirmatn","IRANYekan",sans-serif' : 'system-ui,-apple-system,"Segoe UI",sans-serif') . '}'
        . '.wrap{max-width:760px;margin:0 auto;padding:32px 16px 64px}'
        . 'header{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:20px}header h1{font-size:20px;margin:0}'
        . 'header a{color:var(--primary);text-decoration:none;font-size:14px}'
        . '.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:16px}'
        . 'h2{font-size:18px;margin:0 0 6px}h3{font-size:15px;margin:20px 0 8px}p.lead{color:var(--muted);margin:0 0 18px}'
        . 'label{display:block;font-weight:600;margin:14px 0 4px}.hint{color:var(--muted);font-size:13px;font-weight:400}'
        . 'input[type=text]{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font:inherit}'
        . 'input:focus{outline:2px solid var(--primary);outline-offset:1px}'
        . '.actions{display:flex;gap:10px;justify-content:flex-end;margin-top:22px;flex-wrap:wrap}'
        . '.btn{display:inline-block;padding:10px 18px;border-radius:8px;border:1px solid var(--primary);background:var(--primary);color:var(--primary-text);font:inherit;font-weight:600;text-decoration:none;cursor:pointer}'
        . '.btn.secondary{background:transparent;color:var(--primary)}.btn[disabled]{opacity:.5;cursor:not-allowed}'
        . 'table{width:100%;border-collapse:collapse}td{padding:7px 4px;border-bottom:1px solid var(--border);vertical-align:top;overflow-wrap:anywhere}'
        . 'td.status{text-align:end;white-space:nowrap;font-weight:600}td.status .mono{white-space:normal;font-weight:400}td:first-child{min-width:9em}'
        . '.ok{color:var(--ok)}.warn{color:var(--warn)}.error{color:var(--error)}'
        . '.alert{padding:12px 14px;border-radius:8px;border:1px solid currentColor;margin-bottom:14px;overflow-wrap:anywhere}'
        . 'code,.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;direction:ltr;unicode-bidi:embed;overflow-wrap:anywhere}'
        . '.bar{height:8px;background:var(--bg);border-radius:999px;overflow:hidden;margin:6px 0 10px;border:1px solid var(--border)}'
        . '.bar span{display:block;height:100%;width:0;background:var(--primary);transition:width .3s}'
        . '</style></head><body><div class="wrap"><header><h1>' . mksi_e(mksi_t('title')) . '</h1>'
        . '<a href="?lang=' . $other . '">' . mksi_e(mksi_t('switch_language')) . '</a></header>'
        . '<div class="card"><h2>' . mksi_e($heading) . '</h2>' . $body . '</div>'
        . '<p class="hint">' . mksi_e(mksi_script_name() . ' ' . MKSI_BUILD) . '</p>'
        . '</div>' . $script . '</body></html>';
}

function mksi_busy_page($target)
{
    $canTakeOver = mksi_idle_seconds($target) >= MKSI_TAKEOVER_SECONDS;
    $body = '<p class="lead">' . mksi_e(mksi_t('in_progress')) . '</p>';

    if ($canTakeOver) {
        $body .= '<div class="alert warn">' . mksi_e(mksi_t('in_progress_takeover')) . '</div>'
            . '<form method="post" action="?action=takeover"><div class="actions"><button type="submit" class="btn">' . mksi_e(mksi_t('take_over')) . '</button></div></form>';
    } else {
        $body .= '<p class="hint">' . mksi_e(mksi_t('in_progress_wait', ['minutes' => (int) (MKSI_TAKEOVER_SECONDS / 60)])) . '</p>'
            . '<div class="actions"><a class="btn secondary" href="' . mksi_e(mksi_script_name()) . '">' . mksi_e(mksi_t('check_again')) . '</a></div>';
    }

    mksi_page(mksi_t('in_progress_heading'), $body);
}

function mksi_check_page($target, $preflight, $error)
{
    $source = $preflight['source'];
    $body = '<p class="lead">' . mksi_e(mksi_t('check_lead')) . '</p>';

    if ($error !== null) {
        $body .= '<div class="alert error">' . mksi_e($error) . '</div>';
    }

    foreach ($preflight['errors'] as $error) {
        $body .= '<div class="alert error">' . mksi_e($error) . '</div>';
    }

    if ($preflight['manifest_error'] !== null) {
        $body .= '<div class="alert error">' . mksi_e(mksi_t('manifest_failed', ['error' => $preflight['manifest_error']])) . '<br>'
            . mksi_e(mksi_t('manual_hint', ['name' => 'mksine-…-' . mksi_layout_suffix($target['layout']) . '.zip'])) . '</div>';
    }

    foreach ($preflight['notices'] as $notice) {
        $body .= '<div class="alert warn">' . mksi_e($notice) . '</div>';
    }

    $body .= '<h3>' . mksi_e(mksi_t('where_heading')) . '</h3><p class="hint">' . mksi_e(mksi_t('layout_' . $target['layout'])) . '</p><table>'
        . '<tr><td>' . mksi_e(mksi_t('app_folder')) . '</td><td class="status"><span class="mono">' . mksi_e($target['app']) . '</span></td></tr>'
        . '<tr><td>' . mksi_e(mksi_t('public_folder')) . '</td><td class="status"><span class="mono">' . mksi_e($target['public']) . '</span></td></tr></table>';

    if ($source !== null) {
        $body .= '<h3>' . mksi_e(mksi_t('source_heading')) . '</h3><p>'
            . mksi_e(mksi_t('source_' . $source['type'], ['name' => $source['name'], 'size' => mksi_format_size($source['size'])])) . '</p>';
    }

    $body .= '<h3>' . mksi_e(mksi_t('requirements_heading')) . '</h3><table>';
    $failedHints = [];

    foreach ($preflight['checks'] as $check) {
        $optional = ! empty($check['optional']);
        $class = $check['passed'] ? 'ok' : ($optional ? 'warn' : 'error');
        $status = $check['passed'] ? mksi_t('ok') : ($optional ? mksi_t('recommended') : mksi_t('missing'));
        $current = isset($check['current']) ? ' <span class="hint">(' . mksi_e($check['current']) . ')</span>' : '';
        $body .= '<tr><td>' . mksi_e($check['label']) . $current . '</td><td class="status ' . $class . '">' . mksi_e($status) . '</td></tr>';

        if (! $check['passed'] && ! $optional && isset($check['hint'])) {
            $failedHints[] = $check['hint'];
        }
    }

    $body .= '</table>';

    if (! $preflight['passes']) {
        $failedHints[] = mksi_t('requirements_fail');
    }

    foreach (array_unique($failedHints) as $hint) {
        $body .= '<p class="hint">' . mksi_e($hint) . '</p>';
    }

    $body .= '<form method="post" action="?action=start">'
        . '<div class="actions"><a class="btn secondary" href="' . mksi_e(mksi_script_name()) . '">' . mksi_e(mksi_t('check_again')) . '</a>'
        . '<button type="submit" class="btn"' . ($preflight['passes'] ? '' : ' disabled') . '>' . mksi_e(mksi_t('install')) . '</button></div></form>';

    $heading = $source !== null ? mksi_t('check_heading', ['version' => $source['version']]) : mksi_t('check_heading_unknown');

    mksi_page($heading, $body);
}

function mksi_run_page($target, $state)
{
    $progress = mksi_progress($state, $target);
    $body = '<p class="lead">' . mksi_e(mksi_t('run_lead')) . '</p>'
        . '<div><strong data-label>' . mksi_e($progress['label']) . '</strong> <span class="hint" data-detail>' . mksi_e($progress['detail']) . '</span></div>'
        . '<div class="bar" aria-hidden="true"><span data-bar style="width:' . (int) $progress['percent'] . '%"></span></div>'
        . '<div class="alert error" data-error hidden></div>'
        . '<p class="hint">' . mksi_e(mksi_t('fallback', ['url' => '/' . MKSI_INSTALL_URL])) . '</p>'
        . '<div class="actions" data-actions hidden>'
        . '<form method="post" action="?action=reset"><button type="submit" class="btn secondary">' . mksi_e(mksi_t('start_over')) . '</button></form>'
        . '<button type="button" class="btn" data-retry>' . mksi_e(mksi_t('retry')) . '</button></div>';

    $text = json_encode([
        'network' => mksi_t('network'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    $script = '<script>(function(){'
        . 'var text=' . $text . ',failures=0;'
        . 'var bar=document.querySelector("[data-bar]"),label=document.querySelector("[data-label]"),detail=document.querySelector("[data-detail]"),'
        . 'errorBox=document.querySelector("[data-error]"),actions=document.querySelector("[data-actions]");'
        . 'function show(d){if(d.percent!==undefined){bar.style.width=d.percent+"%";}if(d.label){label.textContent=d.label;}detail.textContent=d.detail||"";}'
        . 'function fail(message){errorBox.textContent=message;errorBox.hidden=false;actions.hidden=false;}'
        . 'function handoff(d){if(!d.handoff_url){location.href=d.install_url;return;}var f=document.createElement("form");f.method="post";f.action=d.handoff_url;'
        . '[["handoff",d.handoff],["locale",d.locale]].forEach(function(p){var i=document.createElement("input");i.type="hidden";i.name=p[0];i.value=p[1];f.appendChild(i);});'
        . 'document.body.appendChild(f);f.submit();}'
        . 'function step(){fetch("?action=step",{method:"POST",credentials:"same-origin",headers:{"Accept":"application/json","X-MKSine-Installer":"1"}})'
        . '.then(function(r){return r.json();}).then(function(d){failures=0;show(d);'
        . 'if(d.reload&&!d.error){location.reload();return;}'
        . 'if(d.error){fail(d.error);return;}'
        . 'if(d.done){handoff(d);return;}'
        . 'setTimeout(step,d.busy?2000:50);})'
        . '.catch(function(){failures++;if(failures<6){setTimeout(step,1500*failures);}else{fail(text.network);}});}'
        . 'document.querySelector("[data-retry]").addEventListener("click",function(){errorBox.hidden=true;actions.hidden=true;failures=0;step();});'
        . 'step();})();</script>';

    mksi_page(mksi_t('run_heading', ['version' => $state['source']['version']]), $body, $script);
}

if (! defined('MKSINE_INSTALLER_LIBRARY')) {
    mksi_main();
}
