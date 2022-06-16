<?php
function microTimeToTime(int $microTime): int
{
    return (int) round($microTime / 1000);
}

/**
 *  [val1, val2, val3....] to "'val1', 'val2', 'val3'..."
 * @param array $array
 * @return string
 */
function arrayToString(array $array): string
{
    return implode("','", $array);
}
