<?php namespace Buchin\GoogleImageGrabber;

use Exception;
use PHPHtmlParser\Dom;
use __;

/**
 *
 */
class GoogleImageGrabber
{


    public static function grab($keyword, $limit = 10000000, $proxy = null)
    {
        $blacklist_domain = [
            'cdn.brilio.net',
            'lookaside.fbsbx.com',
            'cdn14.1cak.com'
        ];

        $url = "https://www.google.com/search?q=" . urlencode($keyword) . "&source=lnms&tbm=isch&tbs=";

        $ua = \Campo\UserAgent::random([
            'os_type' => ['Windows', 'OS X'],
            'device_type' => 'Desktop'
        ]);

        if($proxy){
            $p = explode(':', $proxy);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 60000);

            curl_setopt($ch, CURLOPT_PROXY, $p[0]);

            curl_setopt($ch, CURLOPT_PROXYPORT, $p[1]);

            $response = curl_exec($ch);

            if(curl_errno($ch)){
                throw new Exception(curl_error($ch),69);
            }

        }else{
            $options = [
                'http' => [
                    'method' => "GET",
                    'user_agent' => $ua,
                ],
                'ssl' => [
                    "verify_peer" => FALSE,
                    "verify_peer_name" => FALSE,
                ],
            ];

            if ($proxy) {
                $options['http']['proxy'] = $proxy;
            }

            $context = stream_context_create($options);

            $response = file_get_contents($url, FALSE, $context);
        }



        $htmldom = new Dom;
        $htmldom->loadStr($response, []);

        $results = [];

        $x = 0;
        foreach ($htmldom->find('.rg_di > .rg_meta') as $n => $dataset) {
            if ($x > $limit - 1) break;


            $jsondata = $dataset->text;
            $data = json_decode($jsondata);

            $domain = parse_url($data->ou, PHP_URL_HOST);

            if (!in_array($domain, $blacklist_domain)) {
                $results[$x]['keyword'] = $keyword;
                $results[$x]['slug'] = __::slug($keyword);
                $results[$x]['title'] = ucwords(__::slug($data->pt, ['delimiter' => ' ']));
                $results[$x]['alt'] = property_exists($data, 's') ? __::slug($data->s, ['delimiter' => ' ']) : '';
                $results[$x]['url'] = $data->ou;
                $results[$x]['filetype'] = $data->ity;
                $results[$x]['width'] = $data->ow;
                $results[$x]['height'] = $data->oh;
                $results[$x]['source'] = $data->ru;
                $x++;
            }

        }

        return $results;
    }
}
