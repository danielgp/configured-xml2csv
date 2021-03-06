<?php

/*
 * The MIT License
 *
 * Copyright 2017 Daniel Popiniuc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\configured_xml2csv;

trait ConfiguredXmlToCsv
{

    protected $csvEolString;
    protected $csvFieldSeparator;

    private function cleanStringElement($initialString, $configuredFeatures)
    {
        $cleanedString = $initialString;
        if (array_key_exists('transformation', $configuredFeatures)) {
            $cleanedString = $this->cleanStringElementSpecific($initialString, $configuredFeatures);
        }
        return $cleanedString;
    }

    private function cleanStringElementSpecific($initialString, $configuredFeatures)
    {
        $knownCleaningTechniques = $this->knownCleaningTechniques();
        $cleanedString           = $initialString;
        foreach ($configuredFeatures['transformation'] as $crtCleaningTechnique) {
            if (is_array($knownCleaningTechniques[$crtCleaningTechnique])) {
                $cleanedString = call_user_func_array($knownCleaningTechniques[$crtCleaningTechnique][0], [
                    $knownCleaningTechniques[$crtCleaningTechnique][1],
                    $knownCleaningTechniques[$crtCleaningTechnique][2],
                    $cleanedString,
                ]);
            } else {
                $cleanedString = call_user_func($knownCleaningTechniques[$crtCleaningTechnique], $cleanedString);
            }
        }
        return $cleanedString;
    }

    private function evaluateMultipleInstanceOfSameElement($desiredEvaluationType, $arrayValues)
    {
        $knownEvaluationTechniques = $this->knownEvaluationTechniques();
        return call_user_func($knownEvaluationTechniques[$desiredEvaluationType], $arrayValues);
    }

    private function knownCleaningTechniques()
    {
        return [
            'html_entity_decode'         => 'html_entity_decode',
            'htmlspecialchars'           => 'htmlspecialchars',
            'str_replace__double_space'  => ['str_replace', '  ', ' '],
            'str_replace__nbsp__space'   => ['str_replace', '&nbsp;', ' '],
            'str_replace__tripple_space' => ['str_replace', '   ', ' '],
            'strip_tags'                 => 'strip_tags',
            'trim'                       => 'trim',
        ];
    }

    private function knownEvaluationTechniques()
    {
        return [
            'minimum' => 'min',
            'maximum' => 'max',
        ];
    }

    private function outputToCsvOneLine($lineCunter, $outputCsvArray)
    {
        $sReturn = [];
        if ($lineCunter == 0) {
            $sReturn[] = implode($this->csvFieldSeparator, array_keys($outputCsvArray[$lineCunter]));
        }
        $sReturn[] = implode($this->csvFieldSeparator, array_values($outputCsvArray[$lineCunter]));
        return implode($this->csvEolString, $sReturn);
    }

    private function processMultipleInstancesOfSameElement($config, $arr, $name)
    {
        $crncy     = $config['features'][$name]['multiple']['currency'];
        $optnl     = $config['features'][$name]['multiple']['discounter'];
        $mnyValues = [];
        foreach ($arr as $key => $val) {
            $attribs = $val->attributes();
            if ($key == $name) {
                $mnyValues[] = $attribs[$crncy] - ($attribs[$crncy] * $attribs[$optnl]) / 100;
            }
        }
        $evalType = $config['features'][$name]['multiple']['evaluation type'];
        return $this->evaluateMultipleInstanceOfSameElement($evalType, $mnyValues);
    }

    protected function readConfiguration($filePath, $fileBaseName)
    {
        $jSonContent = $this->readFileContent($filePath, $fileBaseName);
        return json_decode($jSonContent, true);
    }

    protected function readFileContent($filePath, $fileBaseName)
    {
        $fName    = $filePath . DIRECTORY_SEPARATOR . $fileBaseName;
        $fFile    = fopen($fName, 'r');
        $fContent = fread($fFile, filesize($fName));
        fclose($fFile);
        return $fContent;
    }

    private function transformKnownElements($config, $xmlIterator, $name, $data)
    {
        $cleanedData = $data;
        switch ($config['features'][$name]['type']) {
            case 'integer':
                $cleanedData = (int) $data;
                break;
            case 'multiple':
                $arr         = $xmlIterator->current();
                $cleanedData = $this->processMultipleInstancesOfSameElement($config, $arr, $name);
                break;
            case 'string':
                $cleanedData = $this->cleanStringElement($data, $config['features'][$name]);
                break;
        }
        return $cleanedData;
    }

    public function xmlToCSV($text)
    {
        $cnfg           = $this->readConfiguration(__DIR__, 'configuration.json');
        $xmlItrtr       = new \SimpleXMLIterator($text);
        $outputCsvArray = [];
        $lineCunter     = 0;
        $csvLine        = [];
        for ($xmlItrtr->rewind(); $xmlItrtr->valid(); $xmlItrtr->next()) {
            foreach ($xmlItrtr->getChildren() as $name => $data) {
                if (array_key_exists($name, $cnfg['features'])) {
                    $hdr                               = $cnfg['features'][$name]['header'];
                    $cleanedData                       = $this->transformKnownElements($cnfg, $xmlItrtr, $name, $data);
                    $outputCsvArray[$lineCunter][$hdr] = $cleanedData;
                }
            }
            $csvLine[] = $this->outputToCsvOneLine($lineCunter, $outputCsvArray);
            $lineCunter++;
        }
        return implode($this->csvEolString, $csvLine);
    }
}
