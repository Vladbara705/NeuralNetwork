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
    private function setParameter($parameter)
    {
        file_put_contents($this->rootDir . '/config/settings.txt', $parameter  . PHP_EOL, FILE_APPEND);
        return true;
    }

    /**
     * @param $parameter
     * @return false
     */
    public function getParameter($parameter)
    {
        if (!isset($parameter)) return false;
        $result = [];

        foreach ($this->settings as $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($parameter == $settingName[0]) {
                preg_match('/\s([0-9]?(\S[0-9])|[0-9])/', $setting, $result);
            }
        }

        if (empty($result) || empty($result[1])) {
            $value = $this->getRandomValue();
            $this->setParameter($parameter . ' = ' . $value);
            return $value;
        }

        return $result[1];
    }
}