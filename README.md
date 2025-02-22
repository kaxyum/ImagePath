# Image Path
Image Path is a library that provides the path to item's image using AI.

# Usage
Install [await-generator](https://github.com/SOF3/await-generator) in the src of your plugin

Register ImagePath
```php
use ImagePath\ImagePath;

if(!ImagePath::isRegistered())
{
    ImagePath::register($this);
}
```

Add [item_texture.json](https://github.com/Mojang/bedrock-samples/blob/main/resource_pack/textures/item_texture.json) & [terrain_texture.json](https://github.com/Mojang/bedrock-samples/blob/main/resource_pack/textures/terrain_texture.json) in the resources of your plugin 
to add custom items, simply add them to these files

Don't forget to save these files
```php
$this->saveResource("item_textures.json");
$this->saveResource("terrain_textures.json");
```

# How to use ?
To obtain the paths to item images:
```php
use ImagePath\ImagePath;
use SOFe\AwaitGenerator\Await;

Await::f2c(function () use ($sender, $form){
    try {
        $response = yield ImagePath::getInstance()->askGemini([$sender->getInventory()->getItemInHand()]); // array of items
        $paths = ImagePath::getInstance()->getImagePath($response);

        var_dump($paths[ImagePath::getInstance()->formatItemName($sender->getInventory()->getItemInHand())]); // return the path of your item's image e.g. textures/items/diamond_sword
    } catch (Exception $e) 
    {
        var_dump($e->getMessage());
    }
});
```

There is a delay of 2-3 seconds, so I advise you to save each time you retrieve the paths of the item images and then next time delete from the request those already retrieved like that:
```php
$allPaths = [];

array_merge($paths, $allPaths) // save paths

array_diff_key($arrayToCheckWithItemName, $allPaths) // remove paths already saved
```
