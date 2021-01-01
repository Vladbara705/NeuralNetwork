<?php

namespace helpers;

/**
 * Class Config
 * @author vlad <vladbara705@gmail.com>
 * @package helpers
 */
class Config
{
    /**
     * @var array|false
     */
    private $settings;
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->rootDir = dirname(__DIR__);
        $this->settings = file($this->rootDir . '/config/settings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * @return float|int
     */
    private function getRandomValue()
    {
        return rand(1, 9) / 10;
    }

    /**
     * @param $parameter
     * @return bool
     */
    private function addParameter($parameter)
    {
        file_put_contents($this->rootDir . '/config/settings.txt', $parameter  . PHP_EOL, FILE_APPEND);
        return true;
    }

    /**
     * @param $parameter
     * @param $value
     * @return bool
     */
    public function setParameter($parameter, $value)
    {
        $this->settings = file($this->rootDir . '/config/settings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $settingExist = false;
        foreach ($this->settings as $key => $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($parameter == $settingName[0]) {
                $settingExist = true;
                unset($this->settings[$key]);
                $this->settings[$key] = $parameter . ' = ' . $value . PHP_EOL;
            } else {
                $this->settings[$key] = $setting . PHP_EOL;
            }
        }

        if ($settingExist) {
            file_put_contents($this->rootDir . '/config/settings.txt', $this->settings);
            return true;
        }

        $this->addParameter($parameter . ' = ' . $value);
        return true;
    }

    /**
     * @param $parameter
     * @param bool $withCreated
     * @return false
     */
    public function getParameter($parameter, $withCreated = true)
    {
        if (!isset($parameter)) return false;
        $result = [];

        foreach ($this->settings as $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($parameter == $settingName[0]) {
                preg_match('/=\s(.*)/', $setting, $result);
            }
        }

        if (!empty($withCreated) && (empty($result) || empty($result[1]))) {
            $value = $this->getRandomValue();
            $this->addParameter($parameter . ' = ' . $value);
            return $value;
        }

        return $result[1];
    }
}
