<?php
/**
 * PHP library for calculate Moon rise, transit and set
 * Algorithm source: Oliver Montenbruck and Thomas Pfleger: Astronomy on the Personal Computer, Springer-Verlag 1994
 *
 * @author  Jiri Jozif <jiri.jozif@gmail.com>
 * @license MIT
 * @version 1.0.0
 */
declare(strict_types = 1);

namespace JiriJozif\Moonriset;

class Moonriset
{
    /**
     * The number of radians in one arc second
     * 206264.8 arc seconds = 1 radian
     *
     * @var float
     */
    private const float ARC = 206264.8;

    /**
     * sine of obliquity ecliptic (23° 26")
     *
     * @var float
     */
    private const float SIN_EPS = 0.3978;

    /**
     * cosine of obliquity ecliptic (23° 26")
     *
     * @var float
     */
    private const float COS_EPS = 0.9175;

    /**
     * precision, large number is larger precision
     * the number of positions of the Moon in the sky throughout the day,
     * between which one looks for rise, transit and set
     *
     * @var int
     */
    private const int PREC = 24;

    /**
     * latitude of the observer
     *
     * @var float
     */
    public float $latitude;

    /**
     * longitude of the observer
     *
     * @var float
     */
    public float $longitude;

    /**
     * timezone text of the observer
     *
     * @var string
     */
    public string $timezone;

    /**
     * Array contains time of rise in difference format time
     *
     * - `timestamp`: Unix timestamp of rise (e.g., 1739557860, true is Moon continuously above horizon, false if Moon continuously below horizon)
     * - `hhmm`: Short time string of rise (e.g., "1931", or "****" Moon continuously above horizon, or "----" Moon  continuously below horizon)
     * - `hh_mm`: Time string of rise with colon separator (e.g., "19:31", or "**:**" Moon continuously above horizon, or "--:--" Moon  continuously below horizon)
     *
     * @var array{timestamp: bool|int, hhmm: string, 'hh_mm': string}
     */
    public array $rise;

    /**
     * Array contains time of transit in difference format time
     *
     * - `timestamp`: Unix timestamp of transit (e.g., 1739492340, null if transit does not occur)
     * - `hhmm`: Short time string of transit  (e.g., "0119")
     * - `hh_mm`: Time string of transit with colon separator (e.g., "01:19")
     *
     * @var array{timestamp: bool|int, hhmm: string, 'hh_mm': string}
     */
    public array $transit;

    /**
     * Array contains time of set in difference format time
     *
     * - `timestamp`: Unix timestamp of set (e.g., 1739516520, true is Moon continuously above horizon, false if Moon continuously below horizon)
     * - `hhmm`: Short time string of set  (e.g., "0802", or "****" Moon continuously above horizon, or "----" Moon  continuously below horizon)
     * - `hh_mm`: Time string of set with colon separator (e.g., "08:02", or "**:**" Moon continuously above horizon, or "--:--" Moon  continuously below horizon)
     *
     * @var array{timestamp: bool|int, hhmm: string, 'hh_mm': string}
     */
    public array $set;

    /**
     * Array contains time of second rise in difference format time (An exceptional case in the polar regions)
     *
     * - `timestamp`: Unix timestamp of rise (e.g., 1739557860, true is Moon continuously above horizon, false if Moon continuously below horizon)
     * - `hhmm`: Short time string of rise (e.g., "1931")
     * - `hh_mm`: Time string of rise with colon separator (e.g., "19:31")
     *
     * @var array{timestamp: bool|int, hhmm: string, 'hh_mm': string}
     */
    public array $rise2;

    /**
     * Array contains time of second set in difference format time (An exceptional case in the polar regions)
     *
     * - `timestamp`: Unix timestamp of set (e.g., 1739516520, true is Moon continuously above horizon, false if Moon continuously below horizon)
     * - `hhmm`: Short time string of set  (e.g., "0802")
     * - `hh_mm`: Time string of set with colon separator (e.g., "08:02")
     *
     * @var array{timestamp: bool|int, hhmm: string, 'hh_mm': string}
     */
    public array $set2;

    /**
     * interval in seconds between position Moon in the sky throughout the day
     *  = 24 * 3600 / PREC
     *
     * @var float
     */
    private float $tdiff;

    /**
     * sine of latitude
     * calculated only once to speed up the calculation
     *
     * @var float
     */
    private float $_sinLatitude;

    /**
     * cosine of latitude
     * calculated only once to speed up the calculation
     *
     * @var float
     */
    private float $_cosLatitude;

    /**
     * auxiliary data for calculations
     * size array depends on the constant PREC
     * each item containing an associative array of calculated values.
     *
     * @var array<int, array{
     *      timestamp: int,
     *      LST: float,
     *      RA: float,
     *      HA: float,
     *      sAlt: float
     * }>
     */
    private $_data = [];

    /**
     * Constructor for the class, initializes with optional parameters
     *
     * @param float|null $latitude Latitude of the observer in degree (optional, default is null)
     * @param float|null $longitude Longitude of the observer in degree (optional, default is null)
     * @param string|null $timezone Timezone of the observer (optional, default is null)
     */
    public function __construct(?float $latitude = null, ?float $longitude = null, ?string $timezone = null)
    {
        $this->latitude = (is_null($latitude) ? floatVal(ini_get('date.default_latitude')) : $latitude);
        $this->longitude = (is_null($longitude) ? floatVal(ini_get('date.default_longitude')) : $longitude);
        $this->timezone = (is_null($timezone) ? date_default_timezone_get() : $timezone);

        $now = time();
        $this->setDate(intval(date("Y", $now)), intval(date("n", $now)), intval(date("j", $now)));
    }

    /**
     * sets the day for which we want to perform the calculation
     * does not perform validation of the calendar date,
     * only in the case of the distant past or future it rejects the calculation for accuracy and returns false
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return bool `true` if the calculation has been performed, `false` otherwise
     */
    public function setDate(int $year, int $month, int $day): bool
    {
        if ($year < 1583 || $year > 2500) {
            return false;
        }

        $old_timezone = date_default_timezone_get();
        date_default_timezone_set($this->timezone);

        // calculation day's table, begin+end time
        $t = $tb = mktime(0, 0, 0, $month, $day, $year);
        $te = mktime(24, 0, 0, $month, $day, $year);
        $this->tdiff = ($te - $tb) / self::PREC;
        $this->_sinLatitude = $this->dsin($this->latitude);
        $this->_cosLatitude = $this->dcos($this->latitude);

        $i = 0;
        while ($i <= self::PREC) {
            $this->_data[$i]["timestamp"] = intval($t);
            $jd = $this->getJulianDate(intval($t));
            // Local Sidereal Time
            $LST = $this->getLST($jd);
            $this->_data[$i]["LST"] = $LST;
            $coordinate = $this->miniMoon(($jd - 2451545.0) / 36525.0);
            $this->_data[$i]["RA"] = $coordinate["RA"];
            // calculate Hour angle
            $HA = $LST - $coordinate["RA"];
            if ($HA < -12) $HA += 24;
            if ($HA > 12) $HA -= 24;
            $this->_data[$i]["HA"] = $HA;
            // sine Altitude
            $this->_data[$i]["sAlt"] = $this->_sinLatitude * $this->dsin($coordinate["Dec"]) + $this->_cosLatitude * $this->dcos($coordinate["Dec"]) * $this->dcos(15.0 * $this->_data[$i]["HA"]);
            $t += $this->tdiff;
            $i++;
        }
        // Moon transit
        list(
            $this->transit["timestamp"], $this->transit["hhmm"], $this->transit["hh_mm"]
        ) = $this->getTransit();

        // Moon's rise and set
        list(
            $this->rise["timestamp"], $this->rise["hhmm"], $this->rise["hh_mm"],
            $this->set["timestamp"], $this->set["hhmm"], $this->set["hh_mm"],
            $this->rise2["timestamp"], $this->rise2["hhmm"], $this->rise2["hh_mm"],
            $this->set2["timestamp"], $this->set2["hhmm"], $this->set2["hh_mm"]
        ) = $this->getRiSet($this->dsin(0.125)); // Paralax of Moon (57') - refraction (34') - apparent radius of Moon (15.5')

        date_default_timezone_set($old_timezone);
        return true;
    }

    // PRIVATE //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Calculate Moon transit
     *
     * @return array{
     *      0: bool|int,
     *      1: string,
     *      2: string
     * }
     */
    private function getTransit(): array
    {
        // flag for found transit
        $fTran = false;
        // time of transit or false if none
        $tTran = false;

        for ($i = 1; $i < self::PREC && !$fTran; $i += 2) {
            // transit only above horizon
            if ($this->_data[$i-1]["sAlt"] > 0 && $this->_data[$i]["sAlt"] > 0 && $this->_data[$i+1]["sAlt"] > 0) {
                if (($this->_data[$i-1]["HA"] < 0 && $this->_data[$i]["HA"] >= 0) || ($this->_data[$i]["HA"] <= 0 && $this->_data[$i+1]["HA"] > 0)) {
                    list($nz, $z1, $z2, $xe, $ye) = $this->quad($this->_data[$i-1]["HA"], $this->_data[$i]["HA"], $this->_data[$i+1]["HA"]);
                    $tTran = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                    $fTran = true;
                }
            }
        }

        $transit = [];
        if ($fTran) {
            list($transit["timestamp"], $transit["hhmm"], $transit["hh_mm"]) = $this->formatTime($tTran);
        }
        else {
            $transit["timestamp"] = false;
            $transit["hhmm"] = "    ";
            $transit["hh_mm"] = "     ";
        }

        return [
            $transit["timestamp"], $transit["hhmm"], $transit["hh_mm"]
        ];
    }

    /**
     * Calculate Moon rise and set
     *
     * @param float $sAlt sine altitude
     * @return array{
     *      0: bool|int,
     *      1: string,
     *      2: string,
     *      3: bool|int,
     *      4: string,
     *      5: string,
     *      6: bool|int,
     *      7: string,
     *      8: string,
     *      9: bool|int,
     *     10: string,
     *     11: string
     * }
     */
    private function getRiSet(float $sAlt): array
    {
        // flag position is above horizon
        $fAbove = boolval($this->_data[0]["sAlt"] > $sAlt);
        // flag for found rise and set
        $fRise = $fSet = false;
        // timestamp rise and set
        $tRise = $tSet = 0;
        // flag for found second rise or set
        $fRise2 = $fSet2 = false;
        // timestamp rise and set
        $tRise2 = $tSet2 = 0;

        for ($i = 1; $i < self::PREC; $i += 2) {
            list($nz, $z1, $z2, $xe, $ye) = $this->quad(
                $this->_data[$i-1]["sAlt"] - $sAlt,
                $this->_data[$i]["sAlt"] - $sAlt,
                $this->_data[$i+1]["sAlt"] - $sAlt
            );
            if ($nz == 1) {
                if ($this->_data[$i-1]["sAlt"] < $sAlt) {
                    if ($fRise === true) {
                        $tRise2 = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                        $fRise2 = true;
                    }
                    else {
                        $tRise = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                        $fRise = true;
                    }
                }
                else {
                    if ($fSet === true) {
                        $tSet2 = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                        $fSet2 = true;
                    }
                    else {
                        $tSet = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                        $fSet = true;
                    }
                }
            }
            elseif ($nz == 2) {
                if ($ye < 0.0) {
                    $tRise = $this->_data[$i]["timestamp"] + $this->tdiff * $z2;
                    $tSet = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                }
                else {
                    $tRise = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
                    $tSet = $this->_data[$i]["timestamp"] + $this->tdiff * $z2;
                }
                $fRise = $fSet = true;
            }
        }

        // output first rise and set
        $rise = $set = [];
        if ($fRise === true || $fSet === true ) {
            if ($fRise === true) {
                list($rise["timestamp"], $rise["hhmm"], $rise["hh_mm"]) = $this->formatTime($tRise);
            }
            else {
                $rise["timestamp"] = false;
                $rise["hhmm"] = "    ";
                $rise["hh_mm"] = "     ";
            }
            if ($fSet === true) {
                list($set["timestamp"], $set["hhmm"], $set["hh_mm"]) = $this->formatTime($tSet);
            }
            else {
                $set["timestamp"] = true;
                $set["hhmm"] = "    ";
                $set["hh_mm"] = "     ";
            }
        }
        else {
            if ($fAbove === true) { // Moon continuously above horizon
                $rise["timestamp"] = $set["timestamp"] = true;
                $rise["hhmm"] = $set["hhmm"] = "****";
                $rise["hh_mm"] = $set["hh_mm"] = "**:**";
            }
            else { // Moon continuously below horizon
                $rise["timestamp"] = $set["timestamp"] = false;
                $rise["hhmm"] = $set["hhmm"] = "----";
                $rise["hh_mm"] = $set["hh_mm"] = "--:--";
            }
        }
        // output second rise or set
        $rise2 = $set2 = [];
        if ($fRise2) {
            list($rise2["timestamp"], $rise2["hhmm"], $rise2["hh_mm"]) = $this->formatTime($tRise2);
        }
        else {
            $rise2["timestamp"] = false;
            $rise2["hhmm"] = "    ";
            $rise2["hh_mm"] = "     ";
        }
        if ($fSet2) {
            list($set2["timestamp"], $set2["hhmm"], $set2["hh_mm"]) = $this->formatTime($tSet2);
        }
        else {
            $set2["timestamp"] = false;
            $set2["hhmm"] = "    ";
            $set2["hh_mm"] = "     ";
        }

        return [
            $rise["timestamp"], $rise["hhmm"], $rise["hh_mm"],
            $set["timestamp"], $set["hhmm"], $set["hh_mm"],
            $rise2["timestamp"], $rise2["hhmm"], $rise2["hh_mm"],
            $set2["timestamp"], $set2["hhmm"], $set2["hh_mm"]
        ];
    }

    /**
     * Low precision formulae for planetary position, Flandern & Pulkkinen
     * Returns RA and Dec of Moon to 5 arc min (RA) and 1 arc min (Dec) for a few centuries either side of J2000.0
     * Predicts rise and set times to within minutes for about 500 years
     *
     * @param float $T Julian Century
     * @return array{
     *      RA: float,
     *      Dec: float
     * }
     */
    private function miniMoon(float $T): array
    {
        $l0 = $this->frac(0.606433 + 1336.855225 * $T);
        $l = 2*M_PI * $this->frac(0.374897 + 1325.552410 * $T);
        $ls = 2*M_PI * $this->frac(0.993133 + 99.997361 * $T);
        $d = 2*M_PI * $this->frac(0.827361 + 1236.853086 * $T);
        $f = 2*M_PI * $this->frac(0.259086 + 1342.227825 * $T);

        // perturbation
        $dl =  22640 * sin($l);
        $dl += -4586 * sin($l - 2*$d);
        $dl += +2370 * sin(2*$d);
        $dl +=  +769 * sin(2*$l);
        $dl +=  -668 * sin($ls);
        $dl +=  -412 * sin(2*$f);
        $dl +=  -212 * sin(2*$l - 2*$d);
        $dl +=  -206 * sin($l + $ls - 2*$d);
        $dl +=  +192 * sin($l + 2*$d);
        $dl +=  -165 * sin($ls - 2*$d);
        $dl +=  -125 * sin($d);
        $dl +=  -110 * sin($l + $ls);
        $dl +=  +148 * sin($l - $ls);
        $dl +=   -55 * sin(2*$f - 2*$d);

        $s = $f + ($dl + 412 * sin(2*$f) + 541*sin($ls)) / self::ARC;
        $h = $f - 2*$d;

        $n =   -526 * sin($h);
        $n +=   +44 * sin($l + $h);
        $n +=   -31 * sin(-$l + $h);
        $n +=   -23 * sin($ls + $h);
        $n +=   +11 * sin(-$ls + $h);
        $n +=   -25 * sin(-2*$l + $f);
        $n +=   +21 * sin(-$l + $f);

        $l_moon = 2 * M_PI * $this->frac($l0 + $dl / 1296000);
        $b_moon = (18520.0 * sin($s) + $n) / self::ARC;

        // convert to equatorial coords using a fixed ecliptic
        $cb = cos($b_moon);
        $x = $cb * cos($l_moon);
        $v = $cb * sin($l_moon);
        $w = sin($b_moon);
        $y = self::COS_EPS * $v - self::SIN_EPS * $w;
        $z = self::SIN_EPS * $v + self::COS_EPS * $w;
        $rho = sqrt(1.0 - $z * $z);
        $de = (180 / M_PI) * atan($z / $rho);
        $RA = (24 / M_PI) * atan($y / ($x + $rho));
        if ($RA < 0) $RA += 24.0;

        return ["RA" => $RA, "Dec" => $de];
    }

    /**
     * finds the parabola throuh the three points (-1,ym), (0,yz), (1, yp) and returns
     * the coordinates of the values of x where the parabola crosses zero (roots of the quadratic)
     * and the number of roots (0, 1 or 2) within the interval [-1, 1]
     *
     * @param float $ym value for point (-1,ym)
     * @param float $yz value for point (0,yz)
     * @param float $yp value for point (1,yp)
     * @return array{
     *      0:int,
     *      1:float,
     *      2:float,
     *      3:float,
     *      4:float
     * }
     */
    private function quad(float $ym, float $yz, float $yp): array
    {
        $z1 = $z2 = 0.0;
        $nz = 0;
        $a = 0.5 * ($ym + $yp) - $yz;
        $b = 0.5 * ($yp - $ym);
        $c = $yz;
        $xe = -$b / (2.0 * $a);
        $ye = ($a * $xe + $b) * $xe + $c;
        $dis = $b * $b - 4.0 * $a * $c;
        if ($dis > 0.0) {
            $dx = 0.5 * sqrt($dis) / abs($a);
            $z1 = $xe - $dx;
            $z2 = $xe + $dx;
            if (abs($z1) <= 1.0) $nz++;
            if (abs($z2) <= 1.0) $nz++;
            if ($z1 < -1.0) $z1 = $z2;
        }

        return [$nz, $z1, $z2, $xe, $ye];
    }

    /**
     * Calculate Julian date from timestamp
     *
     * @param int $t timestamp
     * @return float Julian date
     */
    private function getJulianDate(int $t):float
    {
        $jd = gregoriantojd(intval(gmdate("n", $t)), intval(gmdate("j", $t)), intval(gmdate("Y", $t))) - 0.5;
        $jd += floatVal(gmdate("H", $t)) / 24.0 + floatVal(gmdate("i", $t)) / 1440.0 + floatVal(gmdate("s", $t)) / 86400.0;

        return($jd);
    }

    /**
     * Calculate Local Sidereal Time (LST) in degree
     *
     * @param float $jd Julian date
     * @return float LST
     */
    private function getLST(float $jd): float
    {
        $mjd = $jd - 2451545.0;
        $lst = $this->range(280.46061837 + 360.98564736629 * $mjd);

        return ($lst + $this->longitude) / 15.0;
    }

    /**
     *  Return formatted time, three types
     *
     * @param float $t unix timestamp
     * @return array{
     *      0: int,
     *      1: string,
     *      2: string
     * }
     */
    private function formatTime(float $t): array
    {
        $t = (int)$t;
        // round to minute
        $t0 = 60 * intVal($t / 60.0 + 0.5);
        if (date("j", $t) === date("j", $t0)) { // this same day
            $t = $t0;
        }
        return [$t, date("Hi", $t), date("H:i", $t)];
    }

    /**
     * Calculate the decimal part of the number
     *
     * @param float $x number
     * @return float decimal part of the number
     */
    private function frac(float $x): float
    {
        return fmod($x, 1);
    }

    /**
     * Adjusts the angle to a range of 0-360
     *
     * @param float $x angle
     * @return float angle 0-360
     */
    private function range(float $x): float
    {
        return ($x - 360.0 * (Floor($x / 360.0)));
    }

    /**
     * Calculates the sine of the angle specified in degrees
     *
     * @param float $x angle
     * @return float sine
     */
    private function dsin(float $x): float
    {
        return sin(deg2rad($x));
    }

    /**
     * Calculates the cosine of the angle specified in degrees
     *
     * @param float $x angle
     * @return float cosine
     */
    private function dcos(float $x): float
    {
        return cos(deg2rad($x));
    }
}
