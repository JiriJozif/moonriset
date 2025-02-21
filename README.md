![Moonriset](https://raw.githubusercontent.com/JiriJozif/moonriset/main/moonriset.png)

# PHP library for calculating Moon rise, set and transit

Algorithm source: Oliver Montenbruck and Thomas Pfleger: Astronomy on the Personal Computer, Springer-Verlag 1994

## Installation

This library is available for use with [Composer](https://packagist.org/packages/jiri.jozif/moonriset) — add it to your project by running:

```bash
$ composer require jiri.jozif/moonriset
```

## Usage

Create an instance of the `Moonriset` class with up to three optional parameters:
- **$latitude** (float, optional): The latitude of the location (default: `ini_get('date.default_latitude')`).
- **$longitude** (float, optional): The longitude of the location (default: `ini_get('date.default_longitude')`).
- **$timezone** (string, optional): The timezone for the location (default: `date_default_timezone_get()`).
The calculation is automatically performed for today

You can then use the following methods:
-   `setDate($year, $month, $day)`: Sets the date for which the calculation will be performed. 
    - **$year** (integer): The year (e.g., 2025).
    - **$month** (integer): The month (e.g., 1 for January, 12 for December).
    - **$day** (integer): The day of the month (e.g., 15).

The following properties of the `Moonriset` class provide the calculated times in different formats:
-   `rise['timestamp']`: UNIX timestamp the moon rises or `true` is Moon continuously above horizon or `false` if Moon continuously below horizon
-   `rise['hh_mm']`: Time the moon rises as string in hh:mm format or "**:**" Moon continuously above horizon or "--:--" Moon continuously below horizon
-   `rise['hhmm']`: Time the moon rises as string in hhmm format or "****" Moon continuously above horizon or "----" Moon continuously below horizon
-   `set['timestamp']`: UNIX timestamp the moon sets or `true` is Moon continuously above horizon or `false` if Moon continuously below horizon
-   `set['hh_mm']`: Time the moon sets as string in hh:mm format or "**:**" Moon continuously above horizon, or "--:--" Moon continuously below horizon
-   `set['hhmm']`: Time the moon sets as string in hhmm format or "****" Moon continuously above horizon or "----" Moon continuously below horizon
-   `transit['timestamp']`: UNIX timestamp the moon transit or `null` if transit does not occur
-   `transit['hh_mm']`: Time the moon transit as string in hh:mm format
-   `transit['hhmm']`: Time the moon transit as string in hhmm format
-   `rise2['timestamp']`: UNIX timestamp the second moon rises (an exceptional phenomenon near the Arctic Circle)
-   `rise2['hh_mm']`: Time the second moon rises as string in hh:mm format (an exceptional phenomenon near the Arctic Circle)
-   `rise2['hhmm']`: Time the second moon rises as string in hhmm format (an exceptional phenomenon near the Arctic Circle)
-   `set2['timestamp']`: UNIX timestamp the second moon sets (an exceptional phenomenon near the Arctic Circle) 
-   `set2['hh_mm']`: Time the second moon sets as string in hh:mm format (an exceptional phenomenon near the Arctic Circle)
-   `set2['hhmm']`: Time the second moon sets as string in hhmm format (an exceptional phenomenon near the Arctic Circle)

### Example

```php
<?php

use JiriJozif\Moonriset\Moonriset;

$mrs = new Moonriset(51.48, 0.0, "Europe/London"); //Royal Observatory, Greenwich
echo "Moon rises today at {$mrs->rise["hh_mm"]} and sets at {$mrs->set["hh_mm"]}";
```
or
```php
<?php

use JiriJozif\Moonriset\Moonriset;

$mrs = new Moonriset(51.48, 0.0, "Europe/London"); //Royal Observatory, Greenwich
$mrs->setDate(2025, 12, 31);
echo "Moon rises last day of year 2025 at {$mrs->rise["hh_mm"]} and sets at {$mrs->set["hh_mm"]}";
```

## Help

If you have any questions, feel free to contact me at `jiri.jozif@gmail.com`.
