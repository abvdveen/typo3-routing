<?php
declare(strict_types = 1);

#namespace Netklaar\Ahk\XClass\Core\Routing;
namespace Netklaar\TYPO3\Routing\XClass;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

//use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use TYPO3\CMS\Core\Routing\Aspect\MappableProcessor;
use \TYPO3\CMS\Core\Routing\UrlGenerator as UrlGeneratorOriginal;

/**
 * @internal
 */
class UrlGenerator extends UrlGeneratorOriginal
{
    /**
     * @var MappableProcessor
     */
    protected $mappableProcessor;

    public function injectMappableProcessor(MappableProcessor $mappableProcessor): void
    {
        $this->mappableProcessor = $mappableProcessor;
    }

    /**
     * Processes aspect mapping on default values and delegates route generation to parent class.
     *
     * {@inheritdoc}
     */
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, array $requiredSchemes = [])
    {
        /** @var Route $route */
        $route = $this->routes->get($name);
        //debug($route,'route - urlgen');
        // _appliedDefaults contains internal(!) values (mapped default values are not generated yet)
        // (keys used are deflated and need to be inflated later using VariableProcessor)
        $relevantDefaults = array_intersect_key($defaults, array_flip($route->compile()->getPathVariables()));
        $route->setOption('_appliedDefaults', array_diff_key($relevantDefaults, $parameters));
        // map default values for URL generation (e.g. '1' becomes 'one' if defined in aspect)
        $mappableProcessor = $this->mappableProcessor ?? new MappableProcessor();
        $mappableProcessor->generate($route, $defaults);

        //Albert
        $tmpUrl = parent::doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
        $tmpUrlVoor = $tmpUrl;
        $tmpUrl = $this->user_encodeSpURL_postProc($tmpUrl);
        $date = new \DateTime();
        $date = $date->format("y:m:d h:i:s");
        if (strlen($tmpUrlVoor) != strlen($tmpUrl)){
            error_log(PHP_EOL.$date.' url voor - '.$tmpUrlVoor, 3, '/home/ahkalbert/var/log/url_log.log');
            error_log(PHP_EOL.$date.' url na   - '.$tmpUrl, 3, '/home/ahkalbert/var/log/url_log.log');

            error_log(PHP_EOL.$date.' url voor - '.$tmpUrlVoor, 3, '/home/ahkalbert/var/log/url_log_changed.log');
            error_log(PHP_EOL.$date.' url na   - '.$tmpUrl, 3, '/home/ahkalbert/var/log/url_log_changed.log');
        }
        else error_log(PHP_EOL.$date.' test url ongewijzigd - '.$tmpUrl, 3, '/home/ahkalbert/var/log/url_log.log');

        return($tmpUrl);

        return parent::doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }

    function user_encodeSpURL_postProc($url)
    {

        $encodeArraySearch = array(
            '#(lichting|festival|graduates|alumni)/student/(\d{4}+)/#',
            '#(lichting|festival|graduates|alumni)/(projecten|projects)/(\d{4}+)/#',

            '#actueel/agenda/event/cal////location/([0-9]+)///tx_cal_location//([0-9]+)/#',

            '#nieuws/artikel/([0-9]{4}+)/([0-9]+)/#',
            '#news/article/([0-9]{4}+)/([0-9]+)/#',

            '#(agenda|calendar)/event/([0-9]+)/([0-9]+)/([0-9]+)/#',

            '#(.*)/(cursussen/cursus|courses/course)/c/(.+)/#',
        );
        $encodeArrayReplace = array(
            '$1/$2/student/',
            '$1/$3/$2/',

            'actueel/agenda/location/$1/$2/',

            'nieuws/$1/$2/',
            'news/$1/$2/',

            '$1/$2/$3/$4/',

            '$1/$2/$3/',
        );
        $url = preg_replace($encodeArraySearch, $encodeArrayReplace, $url);
        return $url;
    }
}