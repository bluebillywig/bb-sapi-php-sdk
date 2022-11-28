<?php

namespace BlueBillywig\Tests\Support;

use Symfony\Component\Yaml\Yaml;

class Coverage
{
    const COVERAGE_CONFIG_FILE_PATH = __DIR__ . '/../../coverage.yaml';
    const COVERAGE_TEXT_FILE_PATH = __DIR__ . '/../_output/coverage.txt';

    private array $coverageConfig;

    public function __construct()
    {
        $coverageConfigFile = new \SplFileInfo(self::COVERAGE_CONFIG_FILE_PATH);
        if ($coverageConfigFile->isFile()) {
            $this->coverageConfig = Yaml::parse(file_get_contents($coverageConfigFile));
            foreach (['classes', 'methods', 'lines'] as $coverageCategory) {
                if (
                    empty($this->coverageConfig[$coverageCategory]) ||
                    empty($this->coverageConfig[$coverageCategory]['fail_lower_than'])
                ) {
                    $this->coverageConfig[$coverageCategory]['fail_lower_than'] = 0;
                }
            }
        }
    }

    public function check()
    {
        if (empty($this->coverageConfig)) {
            return;
        }
        $coverageTextFile = new \SplFileInfo(self::COVERAGE_TEXT_FILE_PATH);
        if (!$coverageTextFile->isFile()) {
            throw new CoverageException(
                'Could not find coverage results file at ' . self::COVERAGE_TEXT_FILE_PATH . '.'
            );
        }

        $file = $coverageTextFile->openFile();
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if ($line !== 'Summary:') {
                continue;
            }
            $re = '/[a-zA-Z]*:\s*(?\'percentage\'[0-9]*(?>\.[0-9]*)?)%\s*(?\'amount\'\([0-9]*\/[0-9]*\))/m';
            preg_match($re, trim($file->fgets()), $classMatches);
            preg_match($re, trim($file->fgets()), $methodMatches);
            preg_match($re, trim($file->fgets()), $lineMatches);
            foreach ([
                'classes' => $classMatches,
                'methods' => $methodMatches,
                'lines' => $lineMatches
            ] as $coverageCategory => $matches) {
                $catPerc = $matches['percentage'];
                $catCovNos = $matches['amount'];
                $catFailOn = $this->coverageConfig[$coverageCategory]['fail_lower_than'];
                if (floatval($catPerc) >= floatval($catFailOn)) {
                    continue;
                }
                throw new CoverageException(
                    "Coverage amount for $coverageCategory lower than expected minimum of $catFailOn%: $catPerc% $catCovNos"
                );
            }
            break;
        }
    }
}
