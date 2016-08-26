<?php

class Jp7_YouTube
{
    const URL_PREFIX = 'https://www.youtube.com/v/';
    const SHORT_URL_PREFIX = 'http://youtu.be/';
    const API_KEY = 'AIzaSyACr-Ib2wc9mxT1AbGQHhzJ71GAeJoDrj4';

    /**
     * Gets the link for embedding from the YouTube URL.
     *
     * @param string $youTubeVideoUrl
     *
     * @return string
     */
    public static function getEmbedLink($youTubeVideoUrl)
    {
        if ($id = self::getId($youTubeVideoUrl)) {
            return self::URL_PREFIX.$id;
        } else {
            return $youTubeVideoUrl;
        }
    }

    /**
     * Gets the thumbnail address from the YouTube URL.
     *
     * @param string $youTubeVideoUrl
     * @param int    $size            [optional] Default is 0.
     *
     * @return string
     */
    public static function getThumbnail($youTubeVideoUrl, $size = 0)
    {
        if ($id = self::getId($youTubeVideoUrl)) {
            return 'https://img.youtube.com/vi/'.$id.'/'.$size.'.jpg';
        }
    }

    public static function matchUrl($url)
    {
        return preg_match('/^http(s)?:\/\/www.youtube.com/', $url);
    }

    public static function getTitle($youTubeVideoUrl)
    {
        if ($id = self::getId($youTubeVideoUrl)) {
            $restUrl = 'https://www.googleapis.com/youtube/v3/videos?id='.$id.'&part=snippet&key='.self::API_KEY;

            if ($data = json_decode(file_get_contents($restUrl))) {
                if ($title = $data->items[0]->snippet->title) {
                    return $title;
                }
            }
        }
    }

    public static function getDuration($youTubeVideoUrl)
    {
        if ($id = self::getId($youTubeVideoUrl)) {
            $restUrl = 'https://www.googleapis.com/youtube/v3/videos?id='.$id.'&part=contentDetails&key='.self::API_KEY;

            if ($data = json_decode(file_get_contents($restUrl))) {
                if ($duration = $data->items[0]->contentDetails->duration) {
                    $date = new DateTime('00:00');
                    $date->add(new DateInterval($duration));

                    return preg_replace('/^00:/', '', $date->format('H:i:s'));
                }
            }
        }
    }

    /**
     * Gets the HTML for embedding a Youtube Video.
     *
     * @param string $youTubeVideoUrl
     * @param int    $width           [optional] Default is 310.
     * @param int    $height          [optional] Default is 230.
     *
     * @return string
     */
    public static function getHtml($youTubeVideoUrl, $width = 310, $height = 230)
    {
        if (starts_with($youTubeVideoUrl, self::SHORT_URL_PREFIX)) {
            $youTubeVideoUrl = str_replace(self::SHORT_URL_PREFIX, self::URL_PREFIX, $youTubeVideoUrl);
        }

        $youTubeVideoUrl = self::getEmbedLink($youTubeVideoUrl);
        $youTubeVideoUrl .= (str_contains($youTubeVideoUrl, '?') ? '&' : '?') . 'hl=pt-br&fs=1&rel=0&vq=hd720';

        return '
            <object width="'.$width.'" height="'.$height.'">
                <param name="movie" value="'.$youTubeVideoUrl.'"></param>
                <param name="wmode" value="transparent"></param>
                <param name="allowFullScreen" value="true"></param>
                <param name="allowscriptaccess" value="always"></param>
                <param name="allowFullScreen" value="true"></param>
                <embed src="'.$youTubeVideoUrl.'" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="transparent" width="'.$width.'" height="'.$height.'"></embed>
            </object>';
    }

    /**
     * Gets the ID from the YouTube URL.
     *
     * @param string $youTubeVideoUrl
     *
     * @return string
     */
    public static function getId($youTubeVideoUrl)
    {
        if (preg_match('/^http(s)?:\/\/www.youtube.com\/user\//', $youTubeVideoUrl)) {
            // Channels
            return preg_replace('~(.*)/u/([0-9]*)/(.*)~', '\3', $youTubeVideoUrl);
        } else {
            // Normal link
            return preg_replace('/.*(\?v=|v\/|&v=)([^&]*).*/', '\2', $youTubeVideoUrl);
        }
    }
}
