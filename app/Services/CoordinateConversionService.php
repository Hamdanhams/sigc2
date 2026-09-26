<?php

namespace App\Services;

class CoordinateConversionService
{
    protected float $a = 6378137.0; // WGS84 semi-major axis
    protected float $f = 1 / 298.257223563; // flattening

    /**
     * Convert UTM (Easting, Northing) ke Latitude/Longitude (WGS84).
     * Default: Zone 51 Selatan (sesuai area kerja Kolaka/Pomalaa).
     */
    public function utmToLatLon(float $easting, float $northing, int $zone = 51, bool $southern = true): array
    {
        $a = $this->a;
        $f = $this->f;
        $e = sqrt($f * (2 - $f));
        $e1sq = ($e * $e) / (1 - $e * $e);
        $k0 = 0.9996;

        $x = $easting - 500000.0;
        $y = $southern ? $northing - 10000000.0 : $northing;

        $zoneCM = deg2rad(6 * $zone - 183);

        $M = $y / $k0;
        $mu = $M / ($a * (1 - pow($e, 2) / 4 - 3 * pow($e, 4) / 64 - 5 * pow($e, 6) / 256));

        $e1 = (1 - sqrt(1 - $e * $e)) / (1 + sqrt(1 - $e * $e));

        $phi1 = $mu
            + (3 * $e1 / 2 - 27 * pow($e1, 3) / 32) * sin(2 * $mu)
            + (21 * pow($e1, 2) / 16 - 55 * pow($e1, 4) / 32) * sin(4 * $mu)
            + (151 * pow($e1, 3) / 96) * sin(6 * $mu)
            + (1097 * pow($e1, 4) / 512) * sin(8 * $mu);

        $C1 = $e1sq * pow(cos($phi1), 2);
        $T1 = pow(tan($phi1), 2);
        $N1 = $a / sqrt(1 - $e * $e * pow(sin($phi1), 2));
        $R1 = $a * (1 - $e * $e) / pow(1 - $e * $e * pow(sin($phi1), 2), 1.5);
        $D = $x / ($N1 * $k0);

        $lat = $phi1 - ($N1 * tan($phi1) / $R1) * (
            pow($D, 2) / 2
            - (5 + 3 * $T1 + 10 * $C1 - 4 * pow($C1, 2) - 9 * $e1sq) * pow($D, 4) / 24
            + (61 + 90 * $T1 + 298 * $C1 + 45 * pow($T1, 2) - 252 * $e1sq - 3 * pow($C1, 2)) * pow($D, 6) / 720
        );

        $lon = $zoneCM + (
            $D
            - (1 + 2 * $T1 + $C1) * pow($D, 3) / 6
            + (5 - 2 * $C1 + 28 * $T1 - 3 * pow($C1, 2) + 8 * $e1sq + 24 * pow($T1, 2)) * pow($D, 5) / 120
        ) / cos($phi1);

        return [
            'lat' => rad2deg($lat),
            'lon' => rad2deg($lon),
        ];
    }
}
