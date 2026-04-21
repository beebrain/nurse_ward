<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves whitelisted static files from public/ through CI when the web server
 * does not map /public/js/* correctly (e.g. some Caddy + subpath setups).
 */
class AppAsset extends BaseController
{
    private const ALLOWED_JS = [
        'census_entry.js',
    ];

    public function javascript(string $name): ResponseInterface
    {
        if (! in_array($name, self::ALLOWED_JS, true)) {
            return $this->response->setStatusCode(404);
        }

        $path = FCPATH . 'js' . DIRECTORY_SEPARATOR . $name;
        if (! is_file($path) || ! is_readable($path)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setContentType('application/javascript', 'UTF-8')
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($path));
    }
}
