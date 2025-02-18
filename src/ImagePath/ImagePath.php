<?php

namespace ImagePath;

use ImagePath\tasks\AsyncTask;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use SOFe\AwaitGenerator\Await;

class ImagePath
{
    use SingletonTrait;

    protected ?Plugin $plugin = null;

    public function __construct()
    {
        self::setInstance($this);
    }

    public function getImagePath(string $response): array
    {
        var_dump("caca");
        $decodedResponse = json_decode($response, true);

        $clean_text = preg_replace('/^```json\s*|\s*```$/', '', trim($decodedResponse['candidates'][0]['content']['parts'][0]['text']));
        $clean_text = str_replace("\\/", "/", $clean_text);
        $clean_text = str_replace('\/', '/', $clean_text);

        var_dump($clean_text);

        $data = json_decode($clean_text, true);

        var_dump($data);
        $result = [];

        if ($data !== null)
        {
            var_dump("pas nulle");
            foreach ($data as $key => $value)
            {
                if (is_array($value) && count($value) === 1)
                {
                    $result[$key] = $value[0];
                } else
                {
                    $result[$key] = $value;
                }
            }
        } else
        {
            var_dump("c nul");
            eval('$result = ' . $clean_text . ';');

            var_dump($clean_text . ';');
        }

        var_dump($result);

        return $result;
    }

    public function askGemini(array $items): \Generator
    {
        return Await::promise(function ($resolve, $reject) use ($items) {
            $dataFolder = $this->plugin->getDataFolder();
            $identifiers = [];

            foreach ($items as $item) {
                $identifiers[] = $this->formatItemName($item);
            }

            $async = new AsyncTask(function (AsyncTask $resolveTask) use ($identifiers, $dataFolder) {
                $apiKey = 'AIzaSyCnyVWP5vy2ck8rkqwp9wPxF7eHURGAt_A';
                $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

                $terrainFile = new Config($dataFolder . "terrain_texture.json", Config::JSON);
                $itemFile = new Config($dataFolder . "item_texture.json", Config::JSON);

                $terrainContent = $terrainFile->getAll();
                $itemContent = $itemFile->getAll();

                $escapedTerrain = json_encode($terrainContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $escapedItem = json_encode($itemContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $promptWithJson = "Here's some data:\n```json\n" . $escapedTerrain . "\n```\nAnd here's more data:\n```json\n" . $escapedItem . "\n```\nIn these two files, find the most coherent path for the items/blocks: [" . implode(", ", $identifiers) . "] send me the result in a array like this : {name i given : path, name i given : path} Each item/block should have only one associated path, and please do not exceed the number of items/blocks I provided and don't add note only the array.";

                $requestData = ['contents' => [['parts' => [['text' => $promptWithJson]]]]];
                $jsonData = json_encode($requestData);

                $ch = curl_init($apiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POST => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => $jsonData,
                ]);

                $response = curl_exec($ch);
                curl_close($ch);
                $resolveTask->setResult($response);
            }, function (AsyncTask $resolveTask) use ($resolve, $reject) {
                $resolve($resolveTask->getResult());
            });

            Server::getInstance()->getAsyncPool()->submitTask($async);
        });
    }

    public function formatItemName(Item $item): string
    {
        if ($item instanceof ItemBlock)
        {
            return GlobalBlockStateHandlers::getSerializer()->serialize($item->getBlock()->getStateId())->getName();
        } else return StringToItemParser::getInstance()->lookupAliases($item)[0];
    }

    public static function register(Plugin $plugin): void
    {
        if(!is_null(($pl = self::getInstance()->plugin)))
        {
            throw new \Error("Already registered with {$pl->getName()}");
        }

        self::getInstance()->plugin = $plugin;
    }

    public static function isRegistered(): bool
    {
        return !is_null(self::getInstance()->plugin);
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }
}