<?php

require __DIR__ . '/vendor/autoload.php';

use exceptions\ParameterNotFoundException;

/**
 * Class NeuralNetwork
 * @author vlad <vladbara705@gmail.com>
 * @package network
 */
class NeuralNetwork
{
    /**
     * @var array|false
     */
    private $settings;

    /**
     * NeuralNetwork constructor.
     */
    public function __construct()
    {
        $this->settings = file('settings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * @param $parameter
     * @return false
     */
    private function getParameter($parameter)
    {
        if (!isset($parameter)) return false;
        foreach ($this->settings as $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($parameter == $settingName[0]) {
                preg_match('/\s([0-9]?(\S[0-9])|[0-9])/', $setting, $result);
                return $result[1];
            }
        }
        return false;
    }

    /**
     * @param array $input
     * @param array $output
     * @return false
     */
    public function execute($input = [], $output = [])
    {
        if (empty($input)) return false;
        $inputParametersCount = count($input);
        $hiddenNeuronsCount = $this->getParameter('HIDDEN_NEURONS');
        if (empty($hiddenNeuronsCount)) throw new ParameterNotFoundException();

        $synapse = 0;
        $hiddenNeurons = [];
        for ($i = 0; $i <= $inputParametersCount - 1; $i++) {
            for ($b = 0; $b <= $hiddenNeuronsCount - 1; $b++) {
                $weight = $this->getParameter('WEIGHT_' . $synapse);
                if (empty($weight)) throw new ParameterNotFoundException();
                $synapse++;
                $hiddenNeurons[$b] += $input[$i] * $weight;
            }
        }

        $test = '';
    }
}

$test = new NeuralNetwork();
$test->execute([1,1], []);
