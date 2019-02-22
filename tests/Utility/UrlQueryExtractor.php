<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 14:37
 */

namespace HashedUriTest\Utility;

use HashedUri\HashConfiguration;

class UrlQueryExtractor
{

    /**
     * @param HashConfiguration $config
     * @param string            $hashedUrl
     *
     * @return int
     * @throws ExtractionFailed
     */
    public static function extractTimeoutFormUrl(HashConfiguration $config, string $hashedUrl)
    {
        $pattern = sprintf('~\S%s=([0-9]+)~', $config->getTimeoutUrlKey());
        preg_match($pattern, $hashedUrl, $matches);
        if (empty($matches[1])) {
            throw new ExtractionFailed(sprintf('Timeout extraction failed: Url "%s" should match the pattern "%s", but failed.', $hashedUrl, $pattern));
        }

        return (int)$matches[1];
    }

    /**
     * @param HashConfiguration $config
     * @param string            $hashedUrl
     *
     * @return string
     * @throws ExtractionFailed
     */
    public static function extractSignatureFormUrl(HashConfiguration $config, string $hashedUrl)
    {
        $pattern = sprintf('~\S%s=([A-Fa-f0-9]{64})~',$config->getSignatureUrlKey());
        preg_match($pattern, $hashedUrl, $matches);
        if (empty($matches[1])) {
            throw new ExtractionFailed(sprintf('Signature extraction failed: Url "%s" should match the pattern "%s", but failed.', $hashedUrl, $pattern));
        }

        return (string)$matches[1];
    }
}