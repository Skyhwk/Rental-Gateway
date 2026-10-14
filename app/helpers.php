<?php

if (! function_exists('asset_versioned')) {
    /**
     * URL asset publik + ?v=filemtime agar browser muat ulang setelah file diganti.
     */
    function asset_versioned($path)
    {
        $path = ltrim($path, '/');
        $full = public_path($path);
        $version = file_exists($full) ? filemtime($full) : time();

        return asset($path).'?v='.$version;
    }
}
