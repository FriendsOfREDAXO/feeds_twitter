<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Abraham\TwitterOAuth\TwitterOAuth;


class rex_feeds_stream_twitter_user_timeline extends rex_feeds_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_twitter_user_timeline');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_twitter_screen_name'),
                'name' => 'screen_name',
                'type' => 'string',
            ],
            [
                'label' => rex_i18n::msg('feeds_twitter_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
            [
                'label' => rex_i18n::msg('feeds_twitter_exclude_replies'),
                'name' => 'exclude_replies',
                'type' => 'select',
                'options' => ['1' => rex_i18n::msg('yes'), '0' => rex_i18n::msg('no')],
                'default' => 1,
            ],
        ];
    }

    public function fetch()
    {


        // Authenticate with Twitter
        $consumerKey = rex_config::get('feeds_twitter', 'twitter_consumer_key');
        $consumerSecret = rex_config::get('feeds_twitter', 'twitter_consumer_secret');
        $accessToken = rex_config::get('feeds_twitter', 'twitter_oauth_token');
        $accessTokenSecret = rex_config::get('feeds_twitter', 'twitter_oauth_token_secret');
        $twitter = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

        // Retrieve the user's timeline posts
        $user_timeline = $twitter->get('statuses/user_timeline', array('screen_name' => $this->typeParams['screen_name'], 'count' => '20'));

        foreach ($user_timeline as $post) {
            $item = new rex_feeds_item($this->streamId, $post->id);
            $item->setContentRaw($post->text);
            $item->setContent(strip_tags($post->text));
            $item->setDate(new DateTime($post->created_at));
            $item->setUrl('https://twitter.com/' . $post->user->screen_name . '/status/' . $post->id_str);
            $item->setAuthor($post->user->name);
            $item->setUsername($post->user->screen_name);
            $item->setLanguage($post->lang);
            $item->setRaw($post);
            if (isset($post->entities->media[0]) && $media = $post->entities->media[0]->media_url) {
                $item->setMedia($media);
            }
            $this->updateCount($item);
            $item->save();
        }
        self::registerExtensionPoint($this->streamId);
    }
}
