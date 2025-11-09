<?php
require_once(__DIR__ . '/../include/functions.php');

function abbreviateNumber($num, $precision = 1) {
    if (!is_numeric($num)) {
        return $num;
    }

    $absNum = abs($num);
    if ($absNum < 1000) {
        return $num; // no abbreviation
    }

    $units = ['K', 'M', 'B', 'T']; // thousand, million, billion, trillion
    $power = floor((strlen((string)intval($absNum)) - 1) / 3);
    $power = min($power, count($units)); // prevent overflow

    $short = $num / pow(1000, $power);
    $formatted = number_format($short, $precision);

    // remove trailing ".0" if not needed
    $formatted = rtrim(rtrim($formatted, '0'), '.');

    return $formatted . $units[$power - 1];
}

/**
 * Generate a simple SVG pie chart.
 *
 * Usage:
 *   echo pie_chart_svg(20);                // binary chart: 20% vs 80%
 *   echo pie_chart_svg([20, 13, 5]);       // multi chart: 20%, 13%, 5%, others
 *
 * @param float|array $values 1 value (float) or 3 values (array of floats), in percent (0–100).
 * @param int $size Size of the SVG in pixels (width & height).
 * @return string SVG markup.
 */
function pie_chart_svg($values, int $size = 300): string
{
    // Colors
    $binaryColors = ['#3b82f6', '#ec4899']; // blue, pink
    $multiColors  = ['#eab308', '#3b82f6', '#22c55e', '#9ca3af']; // yellow, blue, green, gray
    $strokeColor  = '#111827';

    // Normalize input to an array of slice percentages.
    if (!is_array($values)) {
        // Single value: make it [value, 100 - value]
        $v = (float)$values;
        if ($v < 0) $v = 0;
        if ($v > 100) $v = 100;
        $slices = [$v, 100 - $v];
        $colors = $binaryColors;
    } else {
        // Expect 3 values: [a, b, c] and compute the "others" slice.
        if (count($values) !== 3) {
            throw new InvalidArgumentException('Array input must have exactly 3 values.');
        }

        $a = max(0.0, min(100.0, (float)$values[0]));
        $b = max(0.0, min(100.0, (float)$values[1]));
        $c = max(0.0, min(100.0, (float)$values[2]));

        $sum = $a + $b + $c;
        $others = max(0.0, 100.0 - $sum); // clamp at 0 if over 100

        $slices = [$a, $b, $c, $others];
        $colors = $multiColors;
    }

    $cx = $size / 2;
    $cy = $size / 2;
    $radius = ($size / 2) - 2; // small padding

    // Total for normalization (in case the sum != 100)
    $total = array_sum($slices);
    if ($total <= 0) {
        // Avoid division by zero: empty chart
        return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'" xmlns="http://www.w3.org/2000/svg"></svg>';
    }

    // Build SVG paths
    $svgParts = [];
    $svgParts[] = '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Pie chart">';
    $startAngle = -90.0; // start at 12 o'clock

    foreach ($slices as $i => $value) {
        if ($value <= 0) {
            continue; // skip empty slices
        }

        $angle = ($value / $total) * 360.0;
        $endAngle = $startAngle + $angle;

        $largeArcFlag = ($angle > 180.0) ? 1 : 0;

        // Convert degrees to radians
        $startRad = deg2rad($startAngle);
        $endRad   = deg2rad($endAngle);

        // Start point on the circle
        $x1 = $cx + $radius * cos($startRad);
        $y1 = $cy + $radius * sin($startRad);

        // End point on the circle
        $x2 = $cx + $radius * cos($endRad);
        $y2 = $cy + $radius * sin($endRad);

        // Path for the slice
        $d = sprintf(
            'M %f %f L %f %f A %f %f 0 %d 1 %f %f Z',
            $cx, $cy,
            $x1, $y1,
            $radius, $radius,
            $largeArcFlag,
            $x2, $y2
        );

        $color = $colors[$i] ?? '#cccccc';

        $svgParts[] = sprintf(
            '<path d="%s" fill="%s" stroke="%s" stroke-width="1" />',
            $d,
            $color,
            $strokeColor
        );

        $startAngle = $endAngle;
    }

    $svgParts[] = '</svg>';

    return implode("\n", $svgParts);
}

function createVideosHtml($json) {
    ob_start();
    foreach ($json->videos as $video) {
        ?>
        <div>
            <article>
                <img src="<?php echo $video->snippet->thumbnails->maxres->url ?>"/>
                <footer>
                    <div class="grid">
                        <div>
                            <svg width="24" height="24" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true" focusable="false">
                                <path
                                    d="M3 12C5.5 7 9 5 12 5C15 5 18.5 7 21 12
                                    M3 12C5.5 17 9 19 12 19C15 19 18.5 17 21 12"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <circle
                                    cx="12" cy="12" r="3.2"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    fill="none" />
                                <circle
                                    cx="12" cy="12" r="1.5"
                                    fill="currentColor" />
                            </svg>
                            <?php echo $video->statistics->viewCount ?>
                        </div>
                        <div>
                            <svg width="24" height="24" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true" focusable="false">
                            <!-- Bubble outline -->
                            <path
                                d="M4 5.5C4 4.7 4.7 4 5.5 4H18.5C19.3 4 20 4.7 20 5.5V14.5C20 15.3 19.3 16 18.5 16H9L5 20V16H5.5C4.7 16 4 15.3 4 14.5V5.5Z"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                fill="none"/>
                            <!-- Text lines -->
                            <line x1="7.5" y1="8.5" x2="16.5" y2="8.5"
                                    stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round"/>
                            <line x1="7.5" y1="11.5" x2="13.5" y2="11.5"
                                    stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round"/>
                            </svg>
                            <?php echo $video->statistics->commentCount ?>
                        </div>
                    </div>
                    <div class="grid" style="padding-bottom: 20px">
                        <div>
                            <svg width="24" height="24" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true" focusable="false">
                                <path
                                    d="M6 10V20H19C19.8 20 20.5 19.3 20.5 18.5V11C20.5 10.2 19.8 9.5 19 9.5H13.4L14.1 6.1C14.2 5.6 14.1 5 13.8 4.6C13.4 4.2 13 4 12.5 4H12L8.5 9.5H6Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="none"/>
                            </svg>
                            <?php echo $video->statistics->likeCount ?>
                        </div>
                        <div>
                            <svg width="24" height="24" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true" focusable="false">
                            <path
                                d="M6 14V4H19C19.8 4 20.5 4.7 20.5 5.5V13C20.5 13.8 19.8 14.5 19 14.5H13.4L14.1 17.9C14.2 18.4 14.1 19 13.8 19.4C13.4 19.8 13 20 12.5 20H12L8.5 14.5H6Z"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                fill="none"/>
                            </svg>
                            <?php echo $video->statistics->dislikeCount ?>
                        </div>
                    </div>
                    <?php echo $video->snippet->title ?>
                </footer>
            </article>
        </div>
        <?php        
    }
    return ob_get_clean();
}

$stmt = $mysqli->prepare('SELECT * FROM users');
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user) {
    if (!file_exists(__DIR__ . '/../data/mediakit_' . $user['id'] . '.json')) continue;
    $text = file_get_contents(__DIR__ . '/../data/mediakit_' . $user['id'] . '.json');
    $json = json_decode($text);

    $mediaKitFile = $json->id;

    if (preg_match('/^[A-Za-z0-9_-]+$/', $mediaKitFile) !== 1) {
        continue;
    }

    $htmlDoc = file_get_contents(__DIR__ . '/../template/mediakit.html');
    $htmlDoc = str_replace('[%channel_name%]', $json->snippet->title, $htmlDoc);
    $htmlDoc = str_replace('[%channel_desc%]', $json->snippet->description, $htmlDoc);
    $htmlDoc = str_replace('[%channel_subscribers%]', abbreviateNumber($json->statistics->subscriberCount), $htmlDoc);
    $htmlDoc = str_replace('[%channel_views%]', abbreviateNumber($json->statistics->viewCount), $htmlDoc);
    $htmlDoc = str_replace('[%channel_videos%]', abbreviateNumber($json->statistics->videoCount), $htmlDoc);

    if (isset($json->demoStat)) {
        $malePercent = 0;
        $ageSplit = [];
        foreach ($json->demoStat as $demo) {
            $ageSplit[$demo[0]] = 0;
        }
        foreach ($json->demoStat as $demo) {
            $ageSplit[$demo[0]] += $demo[2];
            if ($demo[1] == 'male') {
                $malePercent += $demo[2];
            }
        }

        arsort($ageSplit);
        $ageValues = array_values($ageSplit);

        $countryList = [];
        $total = 0;
        foreach ($json->countryStat as $country) {
            $countryList[$country[0]] = $country[1];
            $total += $country[1];
        }        

        arsort($countryList);
        $countryValues = array_values($countryList);
        $c1 = ($countryValues[0] / $total) * 100;
        $c2 = ($countryValues[1] / $total) * 100;
        $c3 = ($countryValues[2] / $total) * 100;
        $countryKeys = array_keys($countryList);
        $countryName1 = Locale::getDisplayRegion('_' . $countryKeys[0], 'en-GB');
        $countryName2 = Locale::getDisplayRegion('_' . $countryKeys[1], 'en-GB');
        $countryName3 = Locale::getDisplayRegion('_' . $countryKeys[2], 'en-GB');

        $ageLabel1 = str_replace('age', '', array_keys($ageSplit)[0]);
        $ageLabel2 = str_replace('age', '', array_keys($ageSplit)[1]);
        $ageLabel3 = str_replace('age', '', array_keys($ageSplit)[2]);

        $htmlDoc = str_replace('[%statistic_country%]', pie_chart_svg([$c1, $c2, $c3]), $htmlDoc);
        $htmlDoc = str_replace('[%statistic_gender%]', pie_chart_svg($malePercent), $htmlDoc);
        $htmlDoc = str_replace('[%statistic_age%]', pie_chart_svg([$ageValues[0], $ageValues[1], $ageValues[2]]), $htmlDoc);

        $htmlDoc = str_replace('[%statistic_country_1%]', $countryName1, $htmlDoc);
        $htmlDoc = str_replace('[%statistic_country_2%]', $countryName2, $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_country_3%]', $countryName3, $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_age_1%]', $ageLabel1, $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_age_2%]', $ageLabel2, $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_age_3%]', $ageLabel3, $htmlDoc);        
    } else {        
        $htmlDoc = str_replace('[%statistic_country%]', pie_chart_svg([0, 0, 0]), $htmlDoc);
        $htmlDoc = str_replace('[%statistic_gender%]', pie_chart_svg([0, 0, 0]), $htmlDoc);
        $htmlDoc = str_replace('[%statistic_age%]', pie_chart_svg([0, 0, 0]), $htmlDoc);        

        $htmlDoc = str_replace('[%statistic_country_1%]', '', $htmlDoc);
        $htmlDoc = str_replace('[%statistic_country_2%]', '', $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_country_3%]', '', $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_age_1%]', '', $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_age_2%]', '', $htmlDoc);        
        $htmlDoc = str_replace('[%statistic_age_3%]', '', $htmlDoc);        
    }

    $htmlDoc = str_replace('[%videos%]', createVideosHtml($json), $htmlDoc);

    file_put_contents(__DIR__ . '/../www/mediakit/' . $mediaKitFile . '.html', $htmlDoc);
}