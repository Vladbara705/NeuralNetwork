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
     * @return float|int
     */
    private static function getRandomValue()
    {
        return rand(1, 9) / 10;
    }

    /**
     * @param $parameter
     * @return bool
     */
    private static function addParameter($parameter)
    {
        file_put_contents(dirname(__DIR__) . '/config/settings.txt', $parameter  . PHP_EOL, FILE_APPEND);
        return true;
    }

    /**
     * @param $parameter
     * @param $value
     * @return bool
     */
    public static function setParameter($parameter, $value)
    {
        $settings = file(dirname(__DIR__) . '/config/settings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $settingExist = false;

        foreach ($settings as $key => $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($parameter == $settingName[0]) {
                $settingExist = true;
                unset($settings[$key]);
                $settings[$key] = $parameter . ' = ' . $value . PHP_EOL;
            } else {
                $settings[$key] = $setting . PHP_EOL;
            }
        }

        if ($settingExist) {
            file_put_contents(dirname(__DIR__) . '/config/settings.txt', $settings);
            return true;
        }

        self::addParameter($parameter . ' = ' . $value);
        return true;
    }

    /**
     * @param $parameter
     * @param bool $withCreated
     * @return false
     */
    public static function getParameter($parameter, $withCreated = true)
    {
        if (!isset($parameter)) return false;

        $result = [];
        $settings = file(dirname(__DIR__) . '/config/settings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($settings as $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($parameter == $settingName[0]) {
                preg_match('/=\s(.*)/', $setting, $result);
            }
        }

        if (!empty($withCreated) && (empty($result) || empty($result[1]))) {
            $value = self::getRandomValue();
            self::addParameter($parameter . ' = ' . $value);
            return $value;
        }

        return (!empty($result) && isset($result[1])) ? $result[1] : null;
    }
}
