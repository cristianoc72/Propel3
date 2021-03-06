<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\InputOutputException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class YamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     * @return array
     *
     * @throws \InvalidArgumentException                           if configuration file not found
     * @throws \Symfony\Component\Yaml\Exception\ParseException     if something goes wrong in parsing file
     * @throws \Propel\Common\Config\Exception\InputOutputException if configuration file is not readable
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access configuration file $file.");
        }

        $content = Yaml::parse(file_get_contents($path));

        //config file is empty
        if (null === $content) {
            $content = [];
        }

        //Invalid yaml content (e.g. text only) return a string
        if (!is_array($content)) {
            throw new ParseException('The content is not valid yaml.');
        }

        $content = $this->resolveParams($content); //Resolve parameter placeholders (%name%)

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     * Both 'yml' and 'yaml' extensions are accepted.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        $info = pathinfo($resource);
        $extension = $info['extension'];

        if ('dist' === $extension) {
            $extension = pathinfo($info['filename'], PATHINFO_EXTENSION);
        }

        return is_string($resource) && ('yml' === $extension || 'yaml' === $extension);
    }
}
