<?php

namespace Movie;

class YoutubeVideoInformation
{
    protected $videoId;

    public function __construct($videoId) {
        $this->videoId = $videoId;
    }

    public function getVideoStatistic(\Google_Service_YouTube $googleServiceYoutube) {
        $queryParams = [
            'id' => $this->videoId
        ];

        $response = $googleServiceYoutube->videos->listVideos('statistics', $queryParams);

        return $response->items[0]->statistics;
    }

    public function getCommentThread($googleServiceYoutube, $pageToken) {
        $queryParams = [
            'videoId' => $this->videoId,
            'maxResults' => 100,
            'textFormat' => 'plaintext',
            'pageToken' => $pageToken
        ];

        return $googleServiceYoutube->commentThreads->listCommentThreads('snippet', $queryParams);
    }

    public function loadCommentsAndReplies($response) {
        $commentsAndReplies = [];

        foreach ($response->items as $index => $item) {
            $comment = $item->snippet->topLevelComment->snippet->textOriginal;

            $commentsAndReplies[$index] = $comment;
//
//            if (array_key_exists("replies", $item)) {
//                foreach ($item->replies->comments as $replyIndex => $replyComment) {
//                    $commentsAndReplies[$index] = $replyComment->snippet->textOriginal;
//                }
//            }
        }

        return $commentsAndReplies;
    }
}
