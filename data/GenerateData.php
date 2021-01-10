<?php

/**
 * Class GenerateData
 */
class GenerateData
{

    public function generateDataset()
    {
        $i = 0;
        $fp = fopen('dataset/dataset.csv', 'w');
        fputcsv($fp, [
            'winnerKf',
            'loserKf',
            'winnerRank',
            'loserRank',
            'court',
            'surface',
            'win',
        ]);

        $csvFiles = glob("*.csv");
        foreach($csvFiles as $csvFile) {
            if (($handle = fopen($csvFile, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (!($i & 1)) {
                        fputcsv($fp, [
                            $data[28],
                            $data[29],
                            $data[11],
                            $data[12],
                            $data[5],
                            $data[6],
                            "[1, 0]"
                        ]);
                    } else {
                        fputcsv($fp, [
                            $data[29],
                            $data[28],
                            $data[12],
                            $data[11],
                            $data[5],
                            $data[6],
                            "[0, 1]"
                        ]);
                    }
                    $i++;
                }
                fclose($handle);
            }
        }
    }
}

$data = new GenerateData();
$data->generateDataset();
