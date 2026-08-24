<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Setting;
use ZipArchive;

/**
 * Generates the per-router MikroTik hotspot HTML file set (login/error/logout/status
 * pages + css) from the templates in hotspot_router_files/hotspot, substituting the
 * router's identifier, the current portal URL, and the configured company name.
 */
class HotspotFileGeneratorService
{
    private string $templateDir;

    public function __construct()
    {
        $this->templateDir = base_path('hotspot_router_files/hotspot');
    }

    /**
     * Build a ZIP archive of the customized hotspot files for the given router.
     * Returns the absolute path to the generated (temporary) ZIP file.
     */
    public function generateZip(Router $router): string
    {
        $replacements = $this->replacements($router);

        $zipPath = storage_path('app/hotspot-files-' . $router->identifier . '-' . uniqid() . '.zip');

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->addDirectoryToZip($zip, $this->templateDir, '', $replacements);

        $zip->close();

        return $zipPath;
    }

    /**
     * Recursively add template files to the zip, applying placeholder substitution
     * to text-based files (html/css) and copying other files (e.g. images) as-is.
     */
    private function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix, array $replacements): void
    {
        foreach (scandir($sourceDir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $item;
            $zipEntryName = $zipPrefix === '' ? $item : $zipPrefix . '/' . $item;

            if (is_dir($sourcePath)) {
                $this->addDirectoryToZip($zip, $sourcePath, $zipEntryName, $replacements);
                continue;
            }

            $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            if (in_array($extension, ['html', 'css', 'js', 'txt'], true)) {
                $contents = file_get_contents($sourcePath);
                $contents = strtr($contents, $replacements);
                $zip->addFromString($zipEntryName, $contents);
            } else {
                $zip->addFile($sourcePath, $zipEntryName);
            }
        }
    }

    /**
     * Build the placeholder => value substitution map for a given router.
     */
    private function replacements(Router $router): array
    {
        $companyName = Setting::first()?->company_name ?: config('app.name', 'Hotspot');

        return [
            '{RouterIdentifier}' => $router->identifier,
            '{PortalUrl}' => route('portal.landing'),
            '{Company}' => $companyName,
        ];
    }
}
