<?php

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
}

require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName('movie-dataset');
$client->setDeveloperKey('AIzaSyCBeyYRlIY1mSRndQo_N7Kqh3yETatlaxM');

$service = new Google_Service_YouTube($client);

// List of 30 best movie trailers in 2018
$imdbBestMovies2018Json = file_get_contents('./imdb-best-movies-2018.json');
$imdbBestMovies2018 = json_decode($imdbBestMovies2018Json,true);

$movieDataset = [];

$sentiment = new \Movie\PHPInsight\Sentiment();

foreach ($imdbBestMovies2018['movies'] as $movieIndex =>  $movie) {
    $movieDataset[$movieIndex]['name'] = $movie['name'];
    $movieDataset[$movieIndex]['genre_weight_frequency'] = $movie['genre_weight_frequency'];
    $movieDataset[$movieIndex]['sequel_movie'] = $movie['sequel_movie'];
    $movieDataset[$movieIndex]['director_follower_count_on_twitter'] = $movie['director_follower_count_on_twitter'];
    $movieDataset[$movieIndex]['actor_follower_count_on_twitter'] = $movie['actor_follower_count_on_twitter'];
    $movieDataset[$movieIndex]['actress_follower_count_on_twitter'] = $movie['actress_follower_count_on_twitter'];
    $movieDataset[$movieIndex]['movie_rating_on_imdb'] = $movie['movie_rating_on_imdb'];
    $movieDataset[$movieIndex]['movie_rating_on_rotten_tomatoes'] = $movie['movie_rating_on_rotten_tomatoes'];
    $movieDataset[$movieIndex]['gross'] = $movie['gross'];

    $sentimentScore = [];
    $sentimentClass = [];

    foreach ($movie['trailer_video_ids'] as $trailerVideoIndex => $trailerVideoId) {
        $youtubeVideoInformation = new \Movie\YoutubeVideoInformation($trailerVideoId);

        $trailerStatistic = $youtubeVideoInformation->getVideoStatistic($service);

        $movieDataset[$movieIndex]['official_trailer_view_count_on_youtube'] += $trailerStatistic['viewCount'];
        $movieDataset[$movieIndex]['official_trailer_comment_count_on_youtube'] += $trailerStatistic['commentCount'];
        $movieDataset[$movieIndex]['official_trailer_like_count_on_youtube'] += $trailerStatistic['likeCount'];
        $movieDataset[$movieIndex]['official_trailer_dislike_count_on_youtube'] += $trailerStatistic['dislikeCount'];

        // Sentiment Analysis Part
        $response = $youtubeVideoInformation->getCommentThread($service, '');
        $nextPageToken = $response->nextPageToken;

        foreach ($comments = $youtubeVideoInformation->loadCommentsAndReplies($response) as $comment) {
            array_push($sentimentClass, $sentiment->categorise($comment));
            array_push($sentimentScore, $sentiment->score($comment));
        }

        while ($nextPageToken) {
            $response = $youtubeVideoInformation->getCommentThread($service, $nextPageToken);

            $nextPageToken = $response->nextPageToken;
            $youtubeVideoInformation->loadCommentsAndReplies($response);

            foreach ($comments = $youtubeVideoInformation->loadCommentsAndReplies($response) as $comment) {
                array_push($sentimentClass, $sentiment->categorise($comment));
                array_push($sentimentScore, $sentiment->score($comment));
            }
        }

        $sentimentClass += $sentimentClass;
    }

    // Get the most sentimental value.
    $movieDataset[$movieIndex]['sentiment_analysis'] = getTheMostDuplicatedValueInArray($sentimentClass);
}

function getTheMostDuplicatedValueInArray(array $array) {
    $result = array_count_values($array);
    asort($result);
    end($result);

    return key($result);
}
