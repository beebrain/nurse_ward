<?php

declare(strict_types=1);

if (! function_exists('asset_url')) {
    /**
     * URL ไปยังไฟล์ใต้โฟลเดอร์ public/
     *
     * เมื่อเว็บเซิร์ฟเวอร์ชี้ document root ที่โฟลเดอร์โปรเจกต์ (ไม่ใช่ public/)
     * ให้ตั้งใน .env: app.publicAssetsPrefix = public
     * จะได้ URL เช่น https://host/nurse_ward/public/js/...
     *
     * ถ้า document root ชี้ที่ public/ อยู่แล้ว ให้เว้นค่าว่าง (ค่าเริ่มต้น)
     *
     * @param string $path path ภายใน public เช่น js/census_entry.js
     */
    function asset_url(string $path): string
    {
        $path = ltrim($path, '/');

        if (preg_match('#^(css|js)/([^/]+)$#', $path, $m)) {
            $viaIndexPhp = filter_var(env('app.assetsViaIndexPhp', '0'), FILTER_VALIDATE_BOOLEAN);

            return rtrim(base_url(), '/')
                . ($viaIndexPhp ? '/index.php' : '')
                . '/app-asset/' . $m[1] . '/' . $m[2];
        }

        $prefix = (string) env('app.publicAssetsPrefix', '');
        if ($prefix !== '') {
            $prefix = rtrim($prefix, '/') . '/';
        }

        return rtrim(base_url(), '/') . '/' . $prefix . $path;
    }
}
