<?php
declare(strict_types=1);

const MRK_REPO = 'mrkioumarsi/MRKLinkhub';

function mrk_version(): string
{
    $file = ROOT . '/VERSION';
    if (!is_file($file)) {
        return '0';
    }
    $version = trim((string) file_get_contents($file));
    return $version !== '' ? $version : '0';
}

function mrk_norm_ver(string $tag): string
{
    $tag = trim($tag);
    if (str_starts_with(strtolower($tag), 'v')) {
        $tag = substr($tag, 1);
    }
    return $tag;
}

function mrk_https(string $url): string
{
    $headers = [
        'User-Agent: MRKLinkhub',
        'Accept: application/vnd.github+json',
    ];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if (defined('CURLPROTO_HTTPS')) {
            $opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            $opts[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
            throw new RuntimeException('Could not download the release');
        }
        return $body;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'timeout' => 90,
            'follow_location' => 1,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
        $code = (int) $match[1];
    }
    if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
        throw new RuntimeException('Could not download the release');
    }
    return $body;
}

function mrk_release(): array
{
    $raw = mrk_https('https://api.github.com/repos/' . MRK_REPO . '/releases/latest');
    $data = json_decode($raw, true);
    $zip = is_array($data) ? (string) ($data['zipball_url'] ?? '') : '';
    if ($zip === '' || !str_starts_with($zip, 'https://api.github.com/') || empty($data['tag_name'])) {
        throw new RuntimeException('GitHub did not return a release');
    }
    return ['tag' => mrk_norm_ver((string) $data['tag_name']), 'zip' => $zip];
}

function check_update(): array
{
    $release = mrk_release();
    $current = mrk_version();
    return [
        'ok' => 1,
        'current' => $current,
        'latest' => $release['tag'],
        'update' => version_compare($release['tag'], $current, '>'),
        'updated' => false,
    ];
}

function mrk_work_dir(): string
{
    foreach ([dirname(ROOT) . '/.mrk-update', ROOT . '/.mrk-update'] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            continue;
        }
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        return $dir;
    }
    throw new RuntimeException('Could not write the update');
}

function mrk_skip(string $rel): bool
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || $rel === 'inc/config.php') {
        return true;
    }
    foreach (['uploads', '.git', '.mrk-sessions', '.mrk-update'] as $dir) {
        if ($rel === $dir || str_starts_with($rel, $dir . '/')) {
            return true;
        }
    }
    return false;
}

function mrk_safe(string $name): ?string
{
    $name = str_replace('\\', '/', $name);
    if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name)) {
        return null;
    }
    $parts = [];
    foreach (explode('/', $name) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            return null;
        }
        $parts[] = $part;
    }
    return $parts ? implode('/', $parts) : null;
}

function mrk_rm(string $dir): void
{
    if (!is_dir($dir)) {
        @unlink($dir);
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            mrk_rm($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function mrk_status(string $current, string $latest, bool $updated): array
{
    return [
        'ok' => 1,
        'current' => $updated ? mrk_version() : $current,
        'latest' => $latest,
        'update' => $updated ? false : version_compare($latest, $current, '>'),
        'updated' => $updated,
    ];
}

function mrk_unpack(string $zipPath, string $unpack): void
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException('Could not open the release archive');
    }
    $root = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $raw = (string) $zip->getNameIndex($i);
        $safe = mrk_safe($raw);
        if ($safe === null) {
            $zip->close();
            throw new RuntimeException('The release archive is not safe');
        }
        $parts = explode('/', $safe);
        $top = array_shift($parts);
        if ($root === null) {
            $root = $top;
        }
        if ($top !== $root) {
            $zip->close();
            throw new RuntimeException('The release archive is not safe');
        }
        $rel = implode('/', $parts);
        if ($rel === '' || mrk_skip($rel)) {
            continue;
        }
        $dest = $unpack . '/' . $rel;
        if (str_ends_with($raw, '/')) {
            if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
                $zip->close();
                throw new RuntimeException('Could not write the update');
            }
            continue;
        }
        $parent = dirname($dest);
        if (!is_dir($parent) && !mkdir($parent, 0755, true)) {
            $zip->close();
            throw new RuntimeException('Could not write the update');
        }
        $data = $zip->getFromIndex($i);
        if (!is_string($data) || file_put_contents($dest, $data, LOCK_EX) === false) {
            $zip->close();
            throw new RuntimeException('Could not write the update');
        }
    }
    $zip->close();
}

function apply_update(): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Zip support is missing on this server');
    }
    @set_time_limit(180);
    $release = mrk_release();
    $current = mrk_version();
    if (!version_compare($release['tag'], $current, '>')) {
        return mrk_status($current, $release['tag'], false);
    }

    $work = mrk_work_dir();
    $zipPath = $work . '/release.zip';
    $unpack = $work . '/src';
    try {
        mrk_rm($unpack);
        if (!mkdir($unpack, 0700, true) && !is_dir($unpack)) {
            throw new RuntimeException('Could not write the update');
        }
        $bytes = mrk_https($release['zip']);
        if (!str_starts_with($bytes, "PK") || file_put_contents($zipPath, $bytes, LOCK_EX) === false) {
            throw new RuntimeException('Could not download the release');
        }
        mrk_unpack($zipPath, $unpack);

        $copy = function (string $from, string $rel) use (&$copy): void {
            if (mrk_skip($rel) || ($rel === '.htaccess' && is_file(ROOT . '/.htaccess'))) {
                return;
            }
            $dest = ROOT . '/' . $rel;
            if (is_dir($from)) {
                if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
                    throw new RuntimeException('Could not write the update');
                }
                foreach (scandir($from) ?: [] as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    $copy($from . '/' . $item, $rel === '' ? $item : $rel . '/' . $item);
                }
                return;
            }
            $parent = dirname($dest);
            if (!is_dir($parent) && !mkdir($parent, 0755, true)) {
                throw new RuntimeException('Could not write the update');
            }
            if (!copy($from, $dest)) {
                throw new RuntimeException('Could not write the update');
            }
        };
        foreach (scandir($unpack) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $copy($unpack . '/' . $item, $item);
        }
        return mrk_status($current, $release['tag'], true);
    } finally {
        mrk_rm($work);
    }
}
