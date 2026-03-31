<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApprovalFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return;
        }

        $user = auth()->user();

        // Allow Super Admin to access everything
        if ($user->inGroup('superadmin')) {
            return;
        }

        $path = $request->getUri()->getPath();
        $allowedPaths = ['auth/pending', 'auth/deactivated', 'logout'];

        if (in_array($path, $allowedPaths)) {
            return;
        }

        if ($user->approval_status === 'pending') {
            return redirect()->to('/auth/pending');
        }

        if ($user->approval_status === 'deactivated') {
            return redirect()->to('/auth/deactivated');
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
