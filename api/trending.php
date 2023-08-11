<?php

if (isset($_GET['apikey']) && isset($_GET['region'])) {
    $apiKey = $_GET['apikey'];
    $regionCode = $_GET['region'];

    // Jika ada parameter 'maxResults', ambil nilainya (default: 20)
    $maxResults = isset($_GET['maxResults']) ? $_GET['maxResults'] : 20;

    // URL API untuk mendapatkan video trending
    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics,contentDetails&chart=mostPopular&regionCode={$regionCode}&maxResults={$maxResults}&key={$apiKey}";

    // Jika ada parameter 'pageToken', tambahkan ke URL API
    if (isset($_GET['pageToken'])) {
        $pageToken = $_GET['pageToken'];
        $apiUrl .= "&pageToken={$pageToken}";
    }

    // Mengambil data dari API
    $response = @file_get_contents($apiUrl);

    if ($response === false) {
        $result = array('result' => 'error', 'message' => 'Tidak dapat mengambil data dari API.');
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        $data = json_decode($response, true);

        // Mengambil informasi yang dibutuhkan dan menyimpannya dalam array
        $videos = array();

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $publishedAt = strtotime($item['snippet']['publishedAt']);
                $currentTime = time();
                $timeDiff = $currentTime - $publishedAt;
                $timeAgo = getTimeAgo($timeDiff);

                $duration = $item['contentDetails']['duration'];

                $video = array(
                    'id' => $item['id'],
                    'title' => $item['snippet']['title'],
                    'publishedAt' => $timeAgo,
                    'duration' => formatDuration($duration),
                    'video_thumbnail' => $item['snippet']['thumbnails']['high']['url'],
                    'channel_title' => $item['snippet']['channelTitle'],
                    'views' => $item['statistics']['viewCount'],
                    'channel_id' => $item['snippet']['channelId']
                );

                // Mendapatkan informasi thumbnail saluran menggunakan API saluran
                $channelApiUrl = "https://www.googleapis.com/youtube/v3/channels?part=snippet&id={$video['channel_id']}&key={$apiKey}";
                $channelResponse = @file_get_contents($channelApiUrl);

                if ($channelResponse !== false) {
                    $channelData = json_decode($channelResponse, true);
                    if (isset($channelData['items'][0]['snippet']['thumbnails']['default']['url'])) {
                        $video['channel_thumbnail'] = $channelData['items'][0]['snippet']['thumbnails']['default']['url'];
                    }
                }

                array_push($videos, $video);
            }
        }

        // Mengembalikan data dalam format JSON beserta nextPageToken jika ada
        $responseArray = array('items' => $videos);
        if (isset($data['nextPageToken'])) {
            $responseArray['nextPageToken'] = $data['nextPageToken'];
        }

        header('Content-Type: application/json');
        echo json_encode($responseArray, JSON_PRETTY_PRINT);
    }
} else {
    $result = array('result' => 'error', 'message' => 'Harap berikan parameter "apikey" dan "region" dalam URL.');
    header('Content-Type: application/json');
    echo json_encode($result);
}

function getTimeAgo($timeDiff) {
    if ($timeDiff < 60) {
        return $timeDiff . ' detik yang lalu';
    } elseif ($timeDiff < 3600) {
        return floor($timeDiff / 60) . ' menit yang lalu';
    } elseif ($timeDiff < 86400) {
        return floor($timeDiff / 3600) . ' jam yang lalu';
    } elseif ($timeDiff < 2592000) {
        return floor($timeDiff / 86400) . ' hari yang lalu';
    } elseif ($timeDiff < 31536000) {
        return floor($timeDiff / 2592000) . ' bulan yang lalu';
    } else {
        return floor($timeDiff / 31536000) . ' tahun yang lalu';
    }
}

function formatDuration($duration) {
    preg_match_all('/(\d+)/', $duration, $matches);
    $parts = array_map('intval', $matches[0]);

    $formattedDuration = '';

    if ($parts[0] > 0) {
        $formattedDuration .= $parts[0] . ':';
    }
    if ($parts[1] > 0) {
        $formattedDuration .= sprintf("%02d", $parts[1]);
    }

    return $formattedDuration;
}


?>