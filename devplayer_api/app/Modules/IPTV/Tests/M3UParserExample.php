<?php

namespace App\Modules\IPTV\Tests;

use App\Modules\IPTV\Helpers\M3UParser;

/**
 * Example usage of M3U Parser
 *
 * This demonstrates how the parser categorizes channels based on group-title
 */
class M3UParserExample
{
    public static function example(): void
    {
        // Example M3U content
        $m3uContent = <<<'EOT'
#EXTM3U
#EXTINF:-1 tvg-name="Motel Bates S04E10" tvg-logo="http://lgfp.one/EZU3" group-title="Series | Globoplay",Motel Bates S04E10
http://example.com/motel-bates.m3u8
#EXTINF:-1 tvg-name="Breaking Bad" tvg-logo="http://example.com/breaking-bad.jpg" group-title="Séries | Netflix",Breaking Bad
http://example.com/breaking-bad.m3u8
#EXTINF:-1 tvg-name="Homem Aranha" tvg-logo="http://example.com/homem-aranha.jpg" group-title="Filmes | Ação",Homem Aranha
http://example.com/homem-aranha.m3u8
#EXTINF:-1 tvg-name="Globo News" tvg-logo="http://example.com/globo-news.jpg" group-title="Canais | News",Globo News
http://example.com/globo-news.m3u8
#EXTINF:-1 tvg-name="HBO" tvg-logo="http://example.com/hbo.jpg" group-title="Canais | Premium",HBO
http://example.com/hbo.m3u8
EOT;

        // Parse M3U
        $parsed = M3UParser::parseM3UContent($m3uContent);

        echo "Parsed M3U Structure:\n";
        echo "=====================\n\n";

        // Display results
        foreach ($parsed as $type => $categories) {
            echo ucfirst($type) . " Streams:\n";
            echo str_repeat("-", 40) . "\n";

            foreach ($categories as $categoryName => $channels) {
                echo "  Category: {$categoryName}\n";
                foreach ($channels as $channel) {
                    echo "    - {$channel['name']}\n";
                    echo "      Logo: {$channel['logo_url']}\n";
                    echo "      URL: {$channel['url']}\n";
                }
                echo "\n";
            }

            echo "\n";
        }

        echo "Summary:\n";
        echo "========\n";
        foreach ($parsed as $type => $categories) {
            $totalChannels = array_sum(array_map('count', $categories));
            echo ucfirst($type) . ": " . count($categories) . " categories, " . $totalChannels . " channels\n";
        }
    }
}

// Run example if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
    M3UParserExample::example();
}
