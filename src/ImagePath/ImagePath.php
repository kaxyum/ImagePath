<?php

namespace ImagePath;

use ImagePath\tasks\AsyncTask;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
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
        $decodedResponse = json_decode($response, true);

        $clean_text = preg_replace('/^```json\s*|\s*```$/', '', trim($decodedResponse['candidates'][0]['content']['parts'][0]['text']));
        $clean_text = str_replace("\\/", "/", $clean_text);
        $clean_text = str_replace('\/', '/', $clean_text);

        $data = json_decode($clean_text, true);

        $result = [];

        if ($data !== null)
        {
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
            eval('$result = ' . $clean_text . ';');
        }

        return $result;
    }

    public function askGemini(array $items): \Generator
    {
        return Await::promise(function ($resolve, $reject) use ($items) {
            $dataFolder = $this->plugin->getDataFolder();
            $identifiers = [];

            foreach ($items as $item) {
                $identifiers[] = $item->getVanillaName();
            }

            $async = new AsyncTask(function (AsyncTask $resolveTask) use ($identifiers, $dataFolder) {
                $apiKey = 'AIzaSyCnyVWP5vy2ck8rkqwp9wPxF7eHURGAt_A';
                $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

                $terrainFile = new Config($dataFolder . "terrain_texture.json", Config::JSON);
                $itemFile = new Config($dataFolder . "item_texture.json", Config::JSON);

                $terrainContent = $terrainFile->getAll();
                $itemContent = $itemFile->getAll();

                $escapedTerrain = addslashes(json_encode($terrainContent));
                $escapedItem = addslashes(json_encode($itemContent));

                $promptWithJson = "Here's some data:\n```json\n" . $escapedTerrain . "\n```\nAnd here's more data:\n```json\n" . $escapedItem . "\n```\nIn these two files, find the most coherent path for the items/blocks: [" . implode(", ", $identifiers) . "] send me the result in a array like this : [name i given => path, name i given => path] Each item/block should have only one associated path, and please do not exceed the number of items/blocks I provided.";

                $requestData = ['contents' => [['parts' => [['text' => $promptWithJson]]]]];
                $jsonData = json_encode($requestData);

                $ch = curl_init($apiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POST => true,
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