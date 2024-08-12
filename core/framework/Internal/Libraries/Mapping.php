<?php
namespace RightNow\Internal\Libraries;
use RightNow\Utils\Text;

final class Mapping
{
    private $pathIn;
    private $pathOut;
    private $parameterMapping = array();
    private $parameterOut;

    /**
     * Converts all mapped parameters to their new values
     *
     * @param string $paramsIn The parameter string of the old request
     * @param string|\Closure $overrideFunc A function that takes the url
     *        parameter key as its only argument and returns true
     *        if that key/value should be mapped.
     */
    public function createParamOut($paramsIn, $overrideFunc = null)
    {
        $paramsIn = htmlspecialchars_decode($paramsIn);
        $parameterMapping = $this->parameterMapping;
        if (!is_array($parameterMapping)) {
            return;
        }

        $paramsIn = explode('&', $paramsIn);
        $paramsInKeys = array();

        foreach ($paramsIn as $pairs) {
            list($key, $value) = explode('=', $pairs, 2);
            if ($key !== '' && $value !== null && $value !== '') {
                $paramsInKeys[$key] = $value;
            }
        }
        foreach ($paramsInKeys as $key => $value) {
            if ($key === 'redirect')
                $value = rawurlencode(rawurlencode($value));
            $value = str_replace('/', '%2F', $value);

            if (array_key_exists($key, $parameterMapping)) {
                if (is_array($complicatedMapping = $parameterMapping[$key])) {
                    $matchFound = false;
                    foreach ($complicatedMapping as $possibleParameterKey => $mappingLogic) {
                        if (!is_array($mappingLogic) || count($mappingLogic) !== 2)
                            continue;
                        if (array_key_exists($mappingLogic[0], $paramsInKeys) && $paramsInKeys[$mappingLogic[0]] === $mappingLogic[1]) {
                            $matchFound = true;
                            $parameterKey = $possibleParameterKey;
                            break;
                        }
                    }
                    if (!$matchFound)
                        continue;
                }
                else {
                    $parameterKey = $parameterMapping[$key];
                }
            }
            else if (is_callable($overrideFunc) && $overrideFunc($key)) {
                $parameterKey = $key;
            }
            else {
                continue;
            }

            if ($parameterKey === 'kw') {
                $addSearchKey = true;
            }
            else if (in_array($parameterKey, array('p', 'c'))) {
                $addSearchKey = true;
                // in a shining beacon of consistency, WAP uses colons instead of commas to separate prod/cat levels
                if (count($prodCatValues = explode(':', $value)) > 1)
                    $value = end($prodCatValues);
            }

            $this->parameterOut .= "/$parameterKey/$value";
        }

        // do not add the "/search/1" parameter if it is already there in the parameters
        // or the set page path
        if ($addSearchKey && !Text::stringContains($this->parameterOut, "/search/1") && !Text::stringContains($this->pathOut, "/search/1"))
            $this->parameterOut .= "/search/1";
    }

    /**
     * Returns the finally mapped path, including parameters
     * @return string The fully mapped path
     */
    public function getPath()
    {
        return $this->pathOut . $this->parameterOut;
    }

    /**
     * Parse the given path and prepend if necessary
     *
     * @param string $path The given path to redirect to
     */
    public function createPathOut($path)
    {
        $path = ltrim($path, '/');
        if(Text::beginsWith($path, 'app/') ||
            Text::beginsWith($path, 'ci/') ||
            Text::beginsWith($path, 'cc/'))
        {
            $this->pathOut = "/$path";
        }
        else
        {
            $this->pathOut = "/app/$path";
        }
    }

    /**
     * Sets the parameter mapping information
     *
     * @param array|null $globalMapping An array of global parameter mappings
     * @param array|null $mappingOverrides An array of per page parameter mappings
     */
    public function createParameterMapping($globalMapping, $mappingOverrides)
    {
        if(is_array($globalMapping))
            $this->parameterMapping = $globalMapping;
        if(is_array($mappingOverrides))
            $this->parameterMapping = array_merge($this->parameterMapping, $mappingOverrides);
    }
}
