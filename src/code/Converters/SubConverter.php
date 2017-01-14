<?php namespace Done\Subtitles;

class SubConverter implements ConverterContract {

    /**
     * Converts file's content (.srt) to library's "internal format" (array)
     *
     * @param string $file_content      Content of file that will be converted
     * @return array                    Internal format
     */
    public function fileContentToInternalFormat($file_content)
    {
        $internal_format = []; // array - where file content will be stored

        $blocks = explode("\n\n", trim($file_content)); // each block contains: start and end times + text
        foreach ($blocks as $block) {
            $lines = explode("\n", $block); // separate all block lines
            $times = explode(',', $lines[0]); // one the second line there is start and end times

            $internal_format[] = [
                'start' => self::srtTimeToInternal($times[0]),
                'end' => self::srtTimeToInternal($times[1]),
                'lines' => explode('[br]', $lines[1]), // get all the remaining lines from block (if multiple lines of text)
            ];
        }

        return $internal_format;
    }

    /**
     * Convert library's "internal format" (array) to file's content
     *
     * @param array $internal_format    Internal format
     * @return string                   Converted file content
     */
    public function internalFormatToFileContent(array $internal_format)
    {
        $file_content = '';

        foreach ($internal_format as $k => $block) {
            $start = self::internalTimeToSrt($block['start']);
            $end = self::internalTimeToSrt($block['end']);
            $lines = implode("[br]", $block['lines']);

            $file_content .= $start . ',' . $end . "\n";
            $file_content .= $lines . "\n";
            $file_content .= "\n";
        }

        $file_content = trim($file_content);

        return $file_content;
    }

    // ------------------------------ private --------------------------------------------------------------------------

    /**
     * Convert .srt file format to internal time format (float in seconds)
     * Example: 00:02:17,440 -> 137.44
     *
     * @param $srt_time
     *
     * @return float
     */
    private static function srtTimeToInternal($srt_time)
    {
        $parts = explode('.', $srt_time);

        $only_seconds = strtotime("1970-01-01 {$parts[0]} UTC");
        $milliseconds = (float)('0.' . $parts[1]);

        $time = $only_seconds + $milliseconds;

        return $time;
    }

    /**
     * Convert internal time format (float in seconds) to .srt time format
     * Example: 137.44 -> 00:02:17,440
     *
     * @param float $internal_time
     *
     * @return string
     */
    private static function internalTimeToSrt($internal_time)
    {
        $parts = explode('.', $internal_time); // 1.23
        $whole = $parts[0]; // 1
        $decimal = isset($parts[1]) ? $parts[1] : 0; // 23

        $srt_time = gmdate("00:i:s", floor($whole)) . '.' . str_pad($decimal, 2, '0', STR_PAD_RIGHT);

        return $srt_time;
    }
}