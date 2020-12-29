<?php


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
     * @param $param
     * @return false
     */
    private function getParam($param)
    {
        if (!isset($param)) return false;
        foreach ($this->settings as $setting) {
            preg_match('/^\S+/', $setting, $settingName);
            if ($param == $settingName[0]) {
                preg_match('/\s([0-9]?(\S[0-9])|[0-9])/', $setting, $result);
                return  $result[1];
            }
        }
        return false;
    }

    public function execute($input = [], $output = [])
    {
        if (empty($input)) return false;
        $inputParametersCount = count($input);
        $hiddenNeuronsCount = $this->getParam('HIDDEN_NEURONS');

        $synapse = 0;
        $hiddenNeurons = [];
        for ($i = 0; $i <= $inputParametersCount - 1; $i++) {
            for ($b = 0; $b <= $hiddenNeuronsCount - 1; $b++) {
                $weight = $this->getParam('WEIGHT_' . $synapse);
                $synapse++;
                $hiddenNeurons[$b] += $input[$i] * $weight;
            }
        }

        $test = '';
    }
}

$test = new NeuralNetwork();
$test->execute([1,1], []);
