<?php
require_once(__DIR__ . '/../include/functions.php');

$url = "https://$IMPACT_SID:$IMPACT_TOKEN@api.impact.com/Mediapartners/$IMPACT_SID/Ads?Type=TEXT_LINK";
$response = curlCall($url, 'GET', ['Accept: application/json'], "");

$json = json_decode($response[1]);


foreach ($json->Ads as $ad) {
    $startTime = null;
    if (!empty($ad->StartDate)) {
        $startTime = strtotime($ad->StartDate);
    }
    $endTime = null;
    if (!empty($ad->EndDate)) {
        $endTime = strtotime($ad->EndDate);
    }
    $text = strip_tags($ad->Code);

    $stmt = $mysqli->prepare('SELECT id FROM ads WHERE portalId = ?');
    $stmt->bind_param("s", $ad->Id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        $stmt = $mysqli->prepare(
            'INSERT INTO ads (' . 
                'portalId, advertiserName, name, startTime, endTime, snippet, trackingLink, landingPage' .
                ') VALUES (?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?, ?, ?)'
        );
        $stmt->bind_param("sssiisss",
            $ad->Id,
            $ad->AdvertiserName,
            $ad->Name,
            $startTime,
            $endTime,
            $text,
            $ad->TrackingLink,
            $ad->LandingPageUrl
        );
        $stmt->execute();
    } else {
        $adsData = $res->fetch_assoc();
        $stmt = $mysqli->prepare(
            'UPDATE ads SET advertiserName = ?, name = ?, startTime = FROM_UNIXTIME(?), endTime = FROM_UNIXTIME(?), snippet = ?, trackingLink = ?, landingPage = ? WHERE id = ?'
        );
        $stmt->bind_param("ssiisssi", 
            $ad->AdvertiserName,
            $ad->Name,
            $startTime,
            $endTime,
            $text,
            $ad->TrackingLink,
            $ad->LandingPageUrl,
            $adsData['id']
        );
        $stmt->execute();        
    }
}