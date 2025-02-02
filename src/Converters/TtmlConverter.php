<?php

namespace MindEdge\Subtitles\Converters;

class TtmlConverter implements ConverterContract
{
    public function fileContentToInternalFormat($file_content)
    {
        preg_match_all('/<p.+begin="(?<start>[^"]+).*end="(?<end>[^"]+)[^>]*>(?<text>(?!<\/p>).+)<\/p>/', $file_content, $matches, PREG_SET_ORDER);

        $internal_format = [];
        foreach ($matches as $block) {
            $internal_format[] = [
                'start' => static::ttmlTimeToInternal($block['start']),
                'end' => static::ttmlTimeToInternal($block['end']),
                'lines' => explode('<br />', $block['text']),
            ];
        }

        return $internal_format;
    }

    public function internalFormatToFileContent(array $internal_format)
    {
        $file_content = '<?xml version="1.0" encoding="utf-8"?>
<tt xmlns="http://www.w3.org/ns/ttml" xmlns:ttp="http://www.w3.org/ns/ttml#parameter" ttp:timeBase="media" xmlns:tts="http://www.w3.org/ns/ttml#style" xml:lang="en" xmlns:ttm="http://www.w3.org/ns/ttml#metadata">
  <head>
    <metadata>
      <ttm:title></ttm:title>
    </metadata>
    <styling>
      <style id="s0" tts:backgroundColor="black" tts:fontStyle="normal" tts:fontSize="16" tts:fontFamily="sansSerif" tts:color="white" />
    </styling>
  </head>
  <body style="s0">
    <div>
';

        foreach ($internal_format as $k => $block) {
            $start = static::internalTimeToTtml($block['start']);
            $end = static::internalTimeToTtml($block['end']);
            $lines = implode("<br />", $block['lines']);

            $file_content .= "      <p begin=\"{$start}s\" id=\"p{$k}\" end=\"{$end}s\">{$lines}</p>\n";
        }

        $file_content .= '    </div>
  </body>
</tt>';

        $file_content = str_replace("\r", "", $file_content);
        $file_content = str_replace("\n", "\r\n", $file_content);

        return $file_content;
    }

    // ---------------------------------- private ----------------------------------------------------------------------

    protected static function internalTimeToTtml($internal_time)
    {
        return number_format($internal_time, 1, '.', '');
    }

    protected static function ttmlTimeToInternal($ttml_time)
    {
        // Check to see if the file uses clock-time or offset-time format
        // Eg `hours:minutes:seconds.fraction` or `seconds.fraction`
        // When using offset-time we only support `s` endings even though the spec supports h,m,s since h & m are uncommon
        preg_match('/(?<hours>[0-9]{1,2}):(?<minutes>[0-9]{2}):(?<seconds>[0-9]{2})\.(?<fraction>[0-9]{1,2})/', $ttml_time, $clock_time);

        // Convert clock time to offset time
        if (!empty($clock_time)) {
            $seconds = 0;
            $seconds += intval($clock_time['hours']) * 3600;
            $seconds += intval($clock_time['minutes']) * 60;
            $seconds += intval($clock_time['seconds']);

            $ttml_time = "{$seconds}." . $clock_time['fraction'] . "s";
        }
        return rtrim($ttml_time, 's');
    }
}
