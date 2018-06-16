<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/8/31
 * Time: 下午6:16
 */

namespace BlueWhale\WTask\Mods\WPhar;

use BlueWhale\WTask\Config;
use BlueWhale\WTask\Mods\ModBase;
use pocketmine\utils\TextFormat;

class WPhar extends ModBase
{
    /** @var WPhar obj */
    public static $obj;
    /** @var string path */
    public $path;

    public $config;
    private $commands;
    const CONFIG_VERSION = 1;
    const NAME = "WPhar";
    const VERSION = "1.0.0";

    public function onEnable() {
        $this->path = $this->getWTask()->getDataFolder() . "Mods/WPhar/";
        @mkdir($this->path);
        $this->createConfig();
        $this->registerCommands();
    }

    private function createConfig() {
        $this->config = new Config($this->path . "config.yml", Config::YAML, array(
            "Config-Version" => self::CONFIG_VERSION
        ));
        $this->commands = new Config($this->path . "commands.yml", Config::YAML, array(
            "PackCommand" => array(
                "command" => "pack",
                "permission" => "wtask.command.op",
                "description" => "用phar打包文件"
            ),
            "UnpackCommand" => array(
                "command" => "unpack",
                "permission" => "wtask.command.op",
                "description" => "解包phar文件"
            )
        ));
    }

    private function registerCommands() {
        foreach ($this->getCommands()->getAll() as $name => $cmd) {
            $class = "BlueWhale\\WTask\\Mods\\WPhar\\Commands\\" . $name;
            $this->getServer()->getCommandMap()->register("WTask", new $class($this));
        }
    }

    /**
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     * @return Config
     */
    public function getCommands(): Config {
        return $this->commands;
    }

    public function unpackPhar(string $name, string $target) {
        if ($target == "&&root") {
            $target = $this->getServer()->getDataPath();
        }
        $name = $name . (strripos($name, ".phar") === false ? ".phar" : "");
        $fileName = $this->path . $name;
        if (!file_exists($fileName)) {
            $this->getServer()->getLogger()->warning(TextFormat::RED . "对不起，您要解压的phar文件不存在！");
            return false;
        }
        if (!file_exists($target)) {
            @mkdir($target);
        }
        $pharPath = "phar://" . $fileName;
        $this->getServer()->getLogger()->notice(TextFormat::GOLD . "正在解压 $fileName ...");
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pharPath)) as $fInfo) {
            $path = $fInfo->getPathName();
            @mkdir(dirname($target . str_replace($pharPath, "", $path)), 0755, true);
            file_put_contents($target . str_replace($pharPath, "", $path), file_get_contents($path));
        }
        $this->getServer()->getLogger()->info(TextFormat::GREEN . "成功解压 $name 到文件夹 $target ！");
        return true;
    }

    public function packDir(string $dir, string $name) {
        $origin = $dir;
        if ($dir == "&&root") {
            $dir = $this->getServer()->getDataPath();
        }
        $metadata = [
            "name" => $name,
            "directory" => $dir,
            "creationDate" => time()
        ];
        $fileName = $this->path . $name . ".phar";
        if (file_exists($fileName)) {
            $this->getServer()->getLogger()->notice("Phar file already exists, overwriting...");
            @\Phar::unlinkArchive($fileName);
        }
        $phar = new \Phar($fileName);
        $phar->setMetadata($metadata);
        $phar->setStub('<?php if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $phar->startBuffering();
        $filePath = rtrim(str_replace("\\", "/", $dir), "/") . "/";
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath)) as $file) {
            if ($origin == "&&root") {
                $path = ltrim(str_replace(["\\", $filePath], ["/", ""], $file), "/");
            } else {
                $path = ltrim($file, "/");
            }
            if ($path{0} === "." or strpos($path, "/.") !== false) {
                continue;
            }
            $phar->addFile($file, $path);
            $this->getServer()->getLogger()->info("[WPhar] 正在添加文件 $path");
        }
        foreach ($phar as $file => $fInfo) {
            /** @var \PharFileInfo $fInfo */
            if ($fInfo->getSize() > (1024 * 512)) {
                $fInfo->compress(\Phar::GZ);
            }
        }
        $phar->compressFiles(\Phar::GZ);
        $phar->stopBuffering();
        $this->getServer()->getLogger()->notice("成功创建phar文件！");
        return true;
    }
}