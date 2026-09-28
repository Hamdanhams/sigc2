<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use ZipArchive;

class MapConversionService
{
    protected int $minZoom = 10;
    protected int $maxZoom = 19;

    protected function gdalEnv(): array
{
    if (PHP_OS_FAMILY === 'Windows') {
        $osgeoRoot = 'C:\Users\muhnu\AppData\Local\Programs\OSGeo4W';

        return [
            'GDAL_DATA' => $osgeoRoot . '\apps\gdal\share\gdal',
            'PROJ_LIB' => $osgeoRoot . '\share\proj',
            'PROJ_DATA' => $osgeoRoot . '\share\proj',
            'GDAL_PAM_ENABLED' => 'NO',
        ];
    }

    // Linux/production: biarkan GDAL pakai path bawaan sistem
    return [
        'GDAL_PAM_ENABLED' => 'NO',
    ];
}

    public function convert(mixed $peta): void
    {
        $peta->update(['status' => 'processing']);

        try {
            $inputPath = storage_path('app/private/' . $peta->file_geopdf);
            $baseDir = storage_path('app/public/mbtiles');

            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            $tiffPath = $baseDir . '/' . $peta->id . '.tif';
            $tilesDir = $baseDir . '/' . $peta->id . '_tiles';
            $zipPath = $baseDir . '/' . $peta->id . '.zip';

            $env = $this->gdalEnv();

            // Step 1: GeoPDF -> GeoTIFF
            $process1 = new Process(['gdal_translate', '-of', 'GTiff', $inputPath, $tiffPath]);
            $process1->setTimeout(600);
            $process1->setEnv($env);
            $process1->run();

            if (!$process1->isSuccessful()) {
                throw new ProcessFailedException($process1);
            }

            // Step 2: Ambil info bounding box (untuk center peta) dari gdalinfo
            $infoProcess = new Process(['gdalinfo', '-json', $tiffPath]);
            $infoProcess->setEnv($env);
            $infoProcess->run();

            $centerLat = null;
            $centerLon = null;

            if ($infoProcess->isSuccessful()) {
                $info = json_decode($infoProcess->getOutput(), true);
                $extent = $info['wgs84Extent']['coordinates'][0] ?? null;

                if ($extent) {
                    $lons = array_column($extent, 0);
                    $lats = array_column($extent, 1);
                    $centerLon = (min($lons) + max($lons)) / 2;
                    $centerLat = (min($lats) + max($lats)) / 2;
                }
            }

            // Step 3: GeoTIFF -> folder tile (multi zoom level, skema WebMercatorQuad/XYZ)
            if (is_dir($tilesDir)) {
                $this->deleteDirectory($tilesDir);
            }

            $process2 = new Process([
                'gdal',
                'raster',
                'tile',
                '-i',
                $tiffPath,
                '-o',
                $tilesDir,
                '-f',
                'PNG',
                '--min-zoom',
                $this->minZoom,
                '--max-zoom',
                $this->maxZoom,
                '--webviewer',
                'none',
                '--resampling',
                'cubic',
            ]);
            $process2->setTimeout(1800);
            $process2->setEnv($env);
            $process2->run();

            if (!$process2->isSuccessful()) {
                throw new ProcessFailedException($process2);
            }

            // Step 4: kompres folder tile jadi .zip
            $this->zipDirectory($tilesDir, $zipPath);

            $peta->update([
                'status' => 'selesai',
                'file_mbtiles' => 'mbtiles/' . $peta->id . '.zip',
                'center_lat' => $centerLat,
                'center_lon' => $centerLon,
                'min_zoom' => $this->minZoom,
                'max_zoom' => $this->maxZoom,
                'pesan_error' => null,
            ]);

            @unlink($tiffPath);
            $this->deleteDirectory($tilesDir);
        } catch (\Exception $e) {
            $peta->update([
                'status' => 'gagal',
                'pesan_error' => $e->getMessage(),
            ]);
        }
    }

    protected function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath); // paksa forward slash
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
