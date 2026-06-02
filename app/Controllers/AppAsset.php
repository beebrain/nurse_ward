<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves whitelisted static files from public/ through CI when the web server
 * does not map /public/* correctly (e.g. Caddy subpath rewrites non-.php to index.php).
 */
class AppAsset extends BaseController
{
    private const ALLOWED_JS = [
        'census_entry.js',
    ];

    private const ALLOWED_CSS = [
        'app.css',
        'census-layout.css',
    ];

    public function javascript(string $name): ResponseInterface
    {
        return $this->serveFile('js', $name, self::ALLOWED_JS, 'application/javascript');
    }

    public function stylesheet(string $name): ResponseInterface
    {
        return $this->serveFile('css', $name, self::ALLOWED_CSS, 'text/css');
    }

    /**
     * @param list<string> $allowed
     */
    private function serveFile(string $dir, string $name, array $allowed, string $contentType): ResponseInterface
    {
        if (! in_array($name, $allowed, true)) {
            return $this->response->setStatusCode(404);
        }

        $path = FCPATH . $dir . DIRECTORY_SEPARATOR . $name;
        if (! is_file($path) || ! is_readable($path)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setContentType($contentType, 'UTF-8')
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($path));
    }
}
